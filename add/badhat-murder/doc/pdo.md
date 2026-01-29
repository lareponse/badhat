# badhat\pdo

One PDO connection, cached. App-wide access without DI or globals.

> Two helpers that throw on failure. That's it.

---

## 1) First, you connect once

Bootstrap hands badhat a PDO:

```php
use function bad\pdo\{db, qp, trans};

db(new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]));
```

Connection cached. Every subsequent `db()` returns it.

---

## 2) Then, you query

`qp()` behavior depends on `$params`:

```php
// null → raw query (no prepare)
$users = qp("SELECT * FROM users")->fetchAll();

// array → prepare + execute
$user = qp("SELECT * FROM users WHERE id = ?", [$id])->fetch();

// empty array → prepare only
$insert = qp("INSERT INTO logs(msg) VALUES(?)", []);
$insert->execute(['first']);
$insert->execute(['second']);
```

| `$params` | Behavior |
|-----------|----------|
| `null` | `$pdo->query()` |
| `[]` | `$pdo->prepare()` only |
| `[...]` | `$pdo->prepare()` + `execute($params)` |

Throws on failure. No booleans to check.

---

## 3) Transactions

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

`trans()` wraps `beginTransaction()` / `commit()` / `rollBack()`. Callable receives `$pdo`. Return value passes through.

Nested transactions throw `LogicException`.

---

## 4) Explicit connection

Every function accepts `$pdo` as last argument:

```php
$archive = new PDO($archiveDsn, $user, $pass);

$old = qp("SELECT * FROM logs WHERE year < 2020", null, [], $archive)->fetchAll();

trans(function($pdo) {
    qp("DELETE FROM temp", null, [], $pdo);
}, $archive);
```

---

## Reference

### Functions

```php
db(?\PDO $pdo = null): \PDO
```
Get/set cached connection. Throws `BadFunctionCallException` if not set.

```php
qp(string $query, ?array $params = null, array $prep_options = [], ?\PDO $pdo = null): \PDOStatement
```
Query/prepare/execute. Returns statement or throws.

```php
trans(callable $transaction, ?\PDO $pdo = null): mixed
```
Atomic transaction wrapper. Returns callable result.

### Throws

| Exception | Condition |
|-----------|-----------|
| `BadFunctionCallException` | `db()` called before connection cached |
| `LogicException` | `trans()` called while already in transaction |
| `RuntimeException` | PDO operation failed |

Error info embedded in message:

```
[STATE=23000, CODE=1062] PDO::execute() failed (Duplicate entry '5' for key 'PRIMARY')
```