# badhat\db

Your app needs a database.

Not an ORM. Not a query builder. Not an abstraction that "protects you from SQL." Just a way to run queries without repeating the same PDO boilerplate in every file.

> One connection, cached. Two helpers that throw on failure. That's it.

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

**Default story:**
"I have one database. I want to use it everywhere without passing it everywhere."

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

## 5) And if you need to reset

Testing. Connection rotation. Whatever the reason:

```php
db(null, bad\db\VOID_CACHE);  // clears the cache (next db() throws)
db($newPdo);                   // set a new connection
```

---

## Reference

### Constants

| Constant | Value | Effect |
|----------|-------|--------|
| `VOID_CACHE` | 1 | Clears the cached PDO |

### Functions

| Function | Purpose |
|----------|---------|
| `db($pdo)` | Cache a connection |
| `db()` | Retrieve cached connection |
| `qp($sql, $params, $opts, $pdo)` | Query / prepare / execute |
| `trans($callable, $pdo)` | Wrapped transaction |

### Throws

Everything throws `\RuntimeException` or `\BadFunctionCallException` on failure. Error info from PDO is included in the message:

```
[STATE=23000, CODE=1062] PDO::execute failed (Duplicate entry '5' for key 'PRIMARY')
```

No silent failures. No checking return values. It works or it explodes.