# BADDAD Database Layer

**Three functions. No classes. No abstraction.**

This *is* PDO. Procedural API. If you know `PDO` and `PDOStatement`, you know everything.


## Core Functions

* `db(mixed $arg = null, string $profile = ''): ?PDO` — Get or inject a PDO instance by profile ('' = default). Existing connections are pinged (`SELECT 1`) for liveness before reuse.
* `dbq(PDO $pdo, string $sql, array $bind = []): PDOStatement` — Execute a raw or prepared SQL query on the given PDO instance.
* `db_transaction(PDO $pdo, callable $transaction): mixed` — Run a transaction block safely on the given PDO instance; automatically commits or rolls back on exception.

---

## TL;DR

If you know PDO, you already know everything. No DSL, no wrapper. Full PDO and `PDOStatement` API at your disposal. BADDAD just removes the noise.

---

## Design Philosophy

* **Procedural** — No classes, no DI, no static methods
* **Explicit** — You choose the connection, the query, and the bindings
* **Scoped** — No global state, no shared memory
* **Profile-aware** — Named profiles for defaults and replicas, with liveness checks
* **Fail-fast** — Exceptions on error, rollback on failure


## Environment

Set environment variables per profile:

```bash
export DB_DSN_="mysql:host=localhost;dbname=shop"
export DB_USER_="shop_user"
export DB_PASS_="secret"

export DB_DSN_read="mysql:host=replica.local;dbname=shop"
export DB_USER_read="readonly"
export DB_PASS_read="readonlypass"
```

---

## 1. db()

```php
// Get default connection (auto-connect via ENV)
$pdo     = db();

// Get 'read' replica connection
$replica = db('read');

// Inject a custom PDO instance as default
$custom  = new PDO('sqlite::memory:');
$dbDefault = db($custom);         // sets default
$dbTest    = db($custom, 'test'); // sets profile 'test'
```

`db()` returns the cached PDO for the profile if alive (pinged), otherwise creates a new connection via ENV variables `DB_DSN_$PROFILE`, `DB_USER_$PROFILE`, `DB_PASS_$PROFILE`.

---

## 2. dbq(db(), )

```php
$pdo   = db();

// Simple query
$all   = dbq(db(), $pdo, "SELECT * FROM products")->fetchAll();

// With positional bindings
$user  = dbq(db(), $pdo, "SELECT * FROM users WHERE id = ?", [$id])->fetch();

// Named parameters
$rows  = dbq(db(), $pdo, "UPDATE products SET price = :price WHERE id = :id", ['price' => 19.99, 'id' => 42])->rowCount();
```

`dbq(db(), )` returns a `PDOStatement` and throws on failure. It delegates to `query()` if no bindings, otherwise `prepare()` + `execute()`.

---

## 3. db_transaction()

```php
$pdo = db();

$result = db_transaction($pdo, function() use ($pdo) {
    dbq(db(), $pdo, "INSERT INTO orders (customer_id) VALUES (?)", [5]);
    $orderId = $pdo->lastInsertId();

    dbq(db(), $pdo, "INSERT INTO order_items (order_id, product_id) VALUES (?, ?)", [$orderId, 7]);
    dbq(db(), $pdo, "INSERT INTO order_items (order_id, product_id) VALUES (?, ?)", [$orderId, 8]);

    return $orderId;
});
```

`db_transaction()` begins a transaction on the given PDO, commits on success or rolls back if an exception is thrown. Returns whatever the callback returns.

---

## Notes

* Connections are stored per profile; re-injecting a PDO for an existing profile throws `LogicException`.
* Use `db()` to select or inject a connection, then `dbq(db(), )`/`db_transaction()` to execute queries.
* `db()` returns `?PDO`, `dbq(db(), )` returns `PDOStatement`, `db_transaction()` returns mixed (callback result).
* Errors surface as exceptions (`PDOException`, `RuntimeException`, etc.).

---

## FAQs

### Why does `db()` ping existing connections?

To ensure the cached PDO is still alive before reuse, preventing stale or closed connections.

### How can I work with multiple databases?

Use named profiles:

```php
$main    = db();          // default
$replica = db('read');    // read-only replica
$logs    = db('analytics'); // analytics DB

// Then:
$users = dbq(db(), $main, "SELECT * FROM users");
```

### Can I still use environment injection?

Yes. `db()` auto-connects via ENV when no PDO is provided. To bypass ENV, inject a PDO manually.

### What about prepared statement re-use?

You can hold onto a `PDOStatement` returned by `dbq(db(), )` and call `execute()` on it again with new bindings.
### How do I handle errors?
Use try-catch blocks around `dbq(db(), )` and `db_transaction()` calls. They throw exceptions on errors, which you can catch and handle appropriately.

```php
try {
    $result = dbq(db(), $pdo, "SELECT * FROM non_existent_table")->fetchAll();
} catch (PDOException $e) {
    // Handle error
    echo "Database error: " . $e->getMessage();
}
```
### Can I use transactions ?
Yes, `db_transaction()` is specifically designed for transactions. It automatically begins a transaction, commits on success, and rolls back on any exceptions thrown within the callback.

```php
try {
    $orderId = db_transaction($pdo, function() use ($pdo) {
        dbq(db(), $pdo, "INSERT INTO orders (customer_id) VALUES (?)", [5]);
        $orderId = $pdo->lastInsertId();

        dbq(db(), $pdo, "INSERT INTO order_items (order_id, product_id) VALUES (?, ?)", [$orderId, 7]);
        dbq(db(), $pdo, "INSERT INTO order_items (order_id, product_id) VALUES (?, ?)", [$orderId, 8]);

        return $orderId;
    });
} catch (RuntimeException $e) {
    // Handle transaction error
    echo "Transaction failed: " . $e->getMessage();
}
```