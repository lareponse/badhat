# bad\pdo

Tiny PDO helpers with explicit failure semantics.

- `db()` caches one shared `\PDO` (request-scoped).
- `qp()` gives you a `\PDOStatement` in three call-site modes.
- `trans()` runs a callable atomically and returns whatever it returns.

---

## 1) Connect once

Bootstrap your app by caching a `\PDO`:

```php
use function bad\pdo\{db, qp, trans};

$pdo = new \PDO($dsn, $user, $pass, [
    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
]);

db($pdo);
````

Later, anywhere in your app:

```php
$pdo = db();
```

If you call `db()` before caching a connection, it throws `\BadFunctionCallException`.

---

## 2) Query with `qp()`

`qp()` always returns a `\PDOStatement`. The difference is whether it's already executed.

### A) No parameters: execute immediately

```php
$users = qp("SELECT * FROM users ORDER BY id")
    ->fetchAll();
```

### B) Parameters: execute immediately

```php
$user = qp("SELECT * FROM users WHERE id = ?", [42])
    ->fetch();
```

### C) Empty array: prepare only (execute later)

Use this when you want to reuse one prepared statement:

```php
$insert = qp("INSERT INTO logs(msg) VALUES(?)", []);

$insert->execute(['first']);
$insert->execute(['second']);
```

### Quick rule of thumb

| What you type                  | What you get back                 |
| ------------------------------ | --------------------------------- |
| `qp($sql)` or `qp($sql, null)` | executed statement                |
| `qp($sql, [])`                 | prepared statement (not executed) |
| `qp($sql, [...])`              | executed statement                |

### Optional: prepare options

If you need driver-specific prepare options, pass them as the 3rd argument:

```php
$stmt = qp(
    "SELECT * FROM users WHERE email = ?",
    ['a@b.test'],
    [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
);
```

---

## 3) Transactions with `trans()`

Use `trans()` for "all-or-nothing" multi-step writes. Your callable receives the `\PDO` and can return a value.

```php
$orderId = trans(function(\PDO $pdo) use ($userId, $cart) {
    qp("INSERT INTO orders(user_id) VALUES(?)", [$userId], [], $pdo);
    $id = (int)$pdo->lastInsertId();

    $add = qp(
        "INSERT INTO order_items(order_id, sku, qty) VALUES(?, ?, ?)",
        [],
        [],
        $pdo
    );

    foreach ($cart as $item) {
        $add->execute([$id, $item['sku'], $item['qty']]);
    }

    return $id;
});
```

### Nested transactions

If you call `trans()` while already inside a transaction, it throws `\LogicException`.

---

## 4) Using an explicit connection

All helpers accept an optional `$pdo` argument (4th for `qp()`, 2nd for `trans()`).

```php
$archive = new \PDO($archiveDsn, $user, $pass, [
    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
]);

$old = qp(
    "SELECT * FROM logs WHERE year < 2020",
    null,
    [],
    $archive
)->fetchAll();

trans(function(\PDO $pdo) {
    qp("DELETE FROM temp", null, [], $pdo);
}, $archive);
```

---

## Errors

These helpers do not return `false`.

* `db()` throws `\BadFunctionCallException` if no cached connection exists.
* `qp()` throws `\RuntimeException` on database failure.
* `trans()` throws:

  * `\LogicException` if called while already in a transaction
  * `\RuntimeException` on database failure
  * `\RuntimeException` with code **`0xBADC0DE`** if **your callable throws**

Exception messages are **sanitized**: they include the action and SQLSTATE but omit driver-specific detail. The full diagnostic (including driver messages and chained throwable info) is built internally but not emitted — if you need it logged, configure `PDO::ERRMODE_EXCEPTION` and catch `\PDOException` directly, or add your own error handler.

```php
try {
    trans(function(\PDO $pdo) {
        throw new \DomainException("bad input");
    });
} catch (\RuntimeException $e) {
    if ($e->getCode() === 0xBADC0DE) {
        // Your callable threw — $e->getMessage() is sanitized.
    }
}
```

> Note: if you configure PDO to throw (`ERRMODE_EXCEPTION`), you may see `\PDOException` directly.

---

## Reference

```php
db(?\PDO $pdo = null): \PDO

qp(
    string $query,
    ?array $params = null,
    array $prep_options = [],
    ?\PDO $pdo = null
): \PDOStatement

trans(callable $transaction, ?\PDO $pdo = null)