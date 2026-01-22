# badhat\db

Your app needs one database, use it everywhere without passing it anywhere.
Not an ORM or a query builder, just a way to run queries without repeating the same PDO boilerplate in every file.

> One PDO connection, cached. App-wide access without DI or globals. 
> Two helpers that throw on failure. That's it.

---

## 1) First, you connect once

Somewhere in your bootstrap—index.php, init.php, wherever your app starts breathing—you hand badhat a PDO:

```php
use function bad\db\{db, qp, trans};

db(new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]));
```

That's the setup. One line. The connection is now cached.

Every subsequent call to `db()` returns that same connection. No containers. No service providers. No "resolving dependencies." You made a PDO, you stored it, you're done.

---

## 2) Then, you query

Most database work is one of three things:

* Run a query, get results
* Run a query with parameters
* Prepare a statement for repeated use

`qp()` handles all three, depending on what you pass for `$params`:

```php
// null params → raw query (no prepare)
$users = qp("SELECT * FROM users")->fetchAll();

// array params → prepare + execute
$user = qp("SELECT * FROM users WHERE id = ?", [$id])->fetch();

// empty array → prepare only, execute later
$insert = qp("INSERT INTO logs(msg) VALUES(?)", []);
$insert->execute(['first']);
$insert->execute(['second']);
```

The difference is the second argument:

| `$params` | What happens |
|-----------|--------------|
| `null` | `$pdo->query()` |
| `[]` | `$pdo->prepare()` only |
| `[...]` | `$pdo->prepare()` + `execute($params)` |

No booleans to check. No `false` returns. If something fails, it throws. Your code either gets a statement or an exception.

**Default story:**
"I want to write SQL. Handle the ceremony for me."

---

## 3) Transactions, when you need them

Sometimes you need multiple queries to succeed together or fail together.

```php
$orderId = trans(function(\PDO $pdo) use ($cart, $userId) {
    qp("INSERT INTO orders(user_id) VALUES(?)", [$userId], [], $pdo);
    $id = $pdo->lastInsertId();
    
    foreach ($cart as $item) {
        qp("INSERT INTO order_items(order_id, sku, qty) VALUES(?, ?, ?)",
           [$id, $item['sku'], $item['qty']], [], $pdo);
    }
    
    return $id;
});
```

`trans()` wraps the dance:

1. `beginTransaction()`
2. Run your callable
3. `commit()` if it returns cleanly
4. `rollBack()` if anything throws

The callable receives `$pdo` so you can pass it explicitly to `qp()` calls inside. You return whatever you want—`trans()` passes it through.

**Note:** Nested transactions throw `LogicException`. PDO doesn't support real nested transactions.

**Default story:**
"I need atomicity. Don't make me remember the try/catch/rollback pattern."

---

## 4) Only when you need it, you escape the cache

The cached connection is the default. But sometimes you have a second database. A read replica. A different connection for a specific job.

Every function accepts an explicit `$pdo` as the last argument:

```php
$archive = new PDO($archiveDsn, $user, $pass);

// Use specific connection, ignore cache
$old = qp("SELECT * FROM logs WHERE year < 2020", null, [], $archive)->fetchAll();

// Transaction on specific connection
trans(function($pdo) {
    qp("DELETE FROM temp", null, [], $pdo);
}, $archive);
```

**Story:**
"Usually I want the default. Sometimes I don't. Let me choose per-call."

---

## 5) Replacing the connection

To swap the cached connection, just call `db()` with a new PDO:

```php
db($newPdo);  // replaces the cached connection
```

There is no explicit "clear cache" operation. The cache persists for the request lifetime.

---

## Reference

### Functions

| Function | Signature | Purpose |
|----------|-----------|---------|
| `db` | `(?\PDO $pdo = null, int $behave = 0): \PDO` | Get/set cached connection |
| `qp` | `(string $query, ?array $params = null, array $prep_options = [], ?\PDO $pdo = null): \PDOStatement` | Query/prepare/execute |
| `trans` | `(callable $transaction, ?\PDO $pdo = null): mixed` | Wrapped transaction |

### db()

```php
db($pdo);   // cache this connection
db();       // retrieve cached connection (throws if not set)
```

### qp()

| `$params` | Behavior |
|-----------|----------|
| `null` | `$pdo->query($sql)` — direct execution |
| `[]` | `$pdo->prepare($sql)` — returns unexecuted statement |
| `[...]` | `$pdo->prepare($sql)` + `$stmt->execute($params)` |

### trans()

Executes callable inside `beginTransaction()` / `commit()`. Rolls back on any exception. Returns whatever the callable returns.

### Throws

| Exception | Condition |
|-----------|-----------|
| `BadFunctionCallException` | `db()` called before connection cached |
| `LogicException` | `trans()` called while already in transaction |
| `RuntimeException` | PDO operation failed (message includes `[STATE=..., CODE=...]`) |

Error info from PDO is embedded in the message:

```
[STATE=23000, CODE=1062] PDO::execute() failed (Duplicate entry '5' for key 'PRIMARY')
```

No silent failures. No checking return values. It works or it explodes.