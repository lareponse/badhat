# BADHAT Database Layer

**Four functions. No classes. No abstraction.**

This *is* PDO. Procedural API. If you know `PDO` and `PDOStatement`, you know everything.

## Core Functions

* `db(?PDO $pdo = null, string $suffix = ''): PDO` — Get or override a PDO instance for the given suffix ('' = default). Returns a cached PDO if available; otherwise connects via ENV variables `DB_DSN_$suffix`, `DB_USER_$suffix`, `DB_PASS_$suffix`.  
* `dbq(PDO $pdo, string $sql, array $bind = []): ?PDOStatement` — Execute a raw or prepared SQL query on the given PDO instance. Returns a `PDOStatement` on success or `null` on failure (exceptions are caught and swallowed).  
* `db_transaction(PDO $pdo, callable $transaction): mixed` — Run a transaction block safely on the given PDO instance; automatically begins a transaction, commits on success, rolls back on exception; requires `PDO::ERRMODE_EXCEPTION`.  
* `db_pool(string $profile = '', ?PDO $pdo = null, int $set_ttl = 0): ?PDO` — Manage a pool of PDO instances per profile, with optional time-to-live for each pooled entry.

---

## TL;DR

If you know PDO, you already know everything. No DSL, no wrapper. BADHAT just removes the noise, now with optional connection pooling.

## Design Philosophy

* **Procedural** — No classes, no DI, no static methods beyond simple functions  
* **Explicit** — You choose the connection, the query, and the bindings  
* **Scoped** — No global state, no hidden abstractions  
* **Profile-aware** — Named suffixes for defaults, replicas, and pools  
* **Fail-fast** — Exceptions surface from PDO where appropriate; pooled getters return `null` on failure

## Environment

Set environment variables per suffix (profile):

```bash
export DB_DSN_="mysql:host=localhost;dbname=shop"
export DB_USER_="shop_user"
export DB_PASS_="secret"

export DB_DSN_read="mysql:host=replica.local;dbname=shop"
export DB_USER_read="readonly"
export DB_PASS_read="readonlypass"
````

---

## 1. db()

```php
// Get default connection (auto-connect via ENV)
$pdo = db();

// Get 'read' replica connection
$replica = db(null, 'read');

// Override default connection with a custom PDO
$custom = new PDO('sqlite::memory:');
db($custom);          // sets default profile
db($custom, 'test');  // sets 'test' profile
```

**Signature:**

```php
function db(?PDO $pdo = null, string $suffix = ''): PDO
```

* **Returns:** A `PDO` instance for the requested profile.
* **Behavior:**

  * If a `PDO` is provided, it becomes the cached instance for that suffix and is returned.
  * If no `PDO` is provided and a cached instance exists, that instance is returned immediately (no liveness check).
  * Otherwise, a new `PDO` is created using `DB_DSN_$suffix`, `DB_USER_$suffix`, `DB_PASS_$suffix`, with `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, and `ATTR_EMULATE_PREPARES = false`.


## 2. dbq()

```php
$pdo = db();

// Simple query (no bindings)
$all = dbq($pdo, "SELECT * FROM products");

// With positional bindings
$user = dbq($pdo, "SELECT * FROM users WHERE id = ?", [$id]);

// Named parameters
$rows = dbq($pdo, "UPDATE products SET price = :price WHERE id = :id", ['price'=>19.99, 'id'=>42]);

if ($rows === null) {
    // Handle error or retry
}
```

**Signature:**

```php
function dbq(PDO $pdo, string $sql, array $bind = []): ?PDOStatement
```

* **Returns:** A `PDOStatement` on success, or `null` on failure.
* **Behavior:**

  * If `$bind` is empty, calls `$pdo->query($sql)`.
  * Otherwise, calls `$pdo->prepare($sql)` and then `->execute($bind)`.
  * Catches any `PDOException` internally, swallowing the exception and returning `null` on error.


## 3. db\_transaction()

```php
$pdo = db();

try {
    $result = db_transaction($pdo, function() use ($pdo) {
        dbq($pdo, "INSERT INTO logs (event) VALUES (?)", ['created']);
        dbq($pdo, "INSERT INTO users (name) VALUES (?)", ['Alice']);
        return dbq($pdo, "SELECT * FROM users WHERE name = ?", ['Alice'])->fetchAll();
    });
} catch (Throwable $e) {
    echo "Transaction failed: " . $e->getMessage();
}
```

**Signature:**

```php
function db_transaction(PDO $pdo, callable $transaction): mixed
```

* **Returns:** Whatever the `$transaction` callback returns.
* **Behavior:**

  * Requires `ERRMODE_EXCEPTION` on the given `PDO`.
  * Begins a transaction, invokes the callback, commits on success, or rolls back and rethrows on exception.


## 4. db\_pool()

```php
// Setter: store a PDO in the 'read' pool for 3600 seconds
db_pool('read', db(null,'read'), 3600);

// Getter: retrieve from pool (if not expired or still alive), or null
$pooled = db_pool('read');
if ($pooled === null) {
    // Re-establish connection or fallback
}
```

**Signature:**

```php
function db_pool(string $profile = '', ?PDO $pdo = null, int $set_ttl = 0): ?PDO
```

* **Parameters:**

  * `$profile` *(string)* — Profile name ('' = default).
  * `$pdo` *(PDO|null)* — If provided, acts as a setter; if `null`, acts as a getter.
  * `$set_ttl` *(int)* — Time-to-live in seconds for the pooled entry when setting; zero means no TTL.
* **Returns:** A `PDO` instance on getter calls if a valid (non-expired and/or alive) pooled connection exists; otherwise `null`.
* **Behavior:**

  * **Setter (`$pdo !== null`):**

    * Stores the given PDO in the pool under `$profile`.
    * If TTL is set, records an expiry timestamp.
    * Throws `LogicException` if overriding an existing entry.
  * **Getter (`$pdo === null`):**

    * If a pool entry exists and has no TTL or has not yet expired, returns it immediately.
    * If an entry has expired, attempts a `SELECT 1` ping; on success, resets TTL and returns it; on failure, removes it and returns `null`.
    * If no entry exists, returns `null`.

---

## Notes

* Use **`db()`** for simple, one-off connections when you don’t need health checks.
* Use **`db_pool()`** for safe reuse across requests or long-running scripts, with optional TTL and auto-ping.
* **`dbq()`** returns `null` on failure—be sure to check it.
* All functions wrap PDO procedurally; you still have full access to the underlying PDO and `PDOStatement` APIs.

---

## FAQs

### Why doesn’t `db()` ping existing connections?

`db()` is optimized for simplicity and speed: it returns any cached PDO immediately without a liveness check. For health-checked pooling, use `db_pool()`, which handles `SELECT 1` pings automatically on expired entries.

### What should I use for high-availability connection reuse?

Use `db_pool()` with a reasonable TTL (e.g., one hour) to cache connections, have them automatically validated when stale, and avoid reconnection overhead.

### How do I handle query errors?

Since `dbq()` swallows exceptions and returns `null` on error, wrap calls in checks:

```php
$stmt = dbq($pdo, $sql, $bind);
if (!$stmt) {
    throw new RuntimeException("Query failed: $sql");
}
```

### Can I inject environment-less connections?

Yes: call `db($pdo)` or `db(null, 'profile')` to override or set profiles manually, bypassing environment-based auto-connection.
