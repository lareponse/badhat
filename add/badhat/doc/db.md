# BADHAT Database Layer

Procedural PDO helper: three functions, no classes, no abstractions.

## Environment

Set env vars per profile suffix (empty = default):

```bash
# Default (suffix = empty):
export DB_DSN_="mysql:host=localhost;dbname=shop"
export DB_USER_="shop_user"
export DB_PASS_="secret"

# "read" profile (SQLite):
export DB_DSN_read="sqlite:/path/to/read.db"
# (no DB_USER_read/DB_PASS_read needed for SQLite)

Notes:
- Values are read from `$_SERVER[...]` first, then `getenv(...)`.
- The default connection uses a trailing underscore keys like `DB_DSN_`.

Notes:
- Values are read from `$_SERVER[...]` first, then `getenv(...)`.
- The default connection uses a trailing underscore keys like `DB_DSN_`.
```

---

## 1. Connection

```php
function db($param = null, array $param_options = []): PDO
```

**Getter**
* `db()`: return cached PDO. Calls `db('')` if none.

**Setter**
* `db('suffix')`: create, cache and return new PDO using ENV with suffix
* `db($pdo)`: cache & return the PDO

**Throws**: `LogicException` if no cache and invalid param

Defaults when creating a PDO (unless overridden via `$param_options`):
- `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`
- `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`
- `PDO::ATTR_EMULATE_PREPARES => false`

Details:
- A single connection is cached; each setter call replaces it.
- Falsy `$param` values are treated as the empty suffix `''`.

---

## 2. Execution

```php
function qp(string $query, ?array $params = null, array $prepareOptions = []): PDOStatement|false
```

dbq uses the cached `db()` connection internally.

Semantics of `$params`:
- `null`: run via `db()->query($query)` (immediate execute, no prepare)
- `[]` (empty array): prepare only, no execute yet
- non-empty array: prepare and execute with bindings

Flow:
1. If `$params === null`: return `db()->query($query)`
2. Else: `$stmt = db()->prepare($query, $prepareOptions)`
3. If `$params` is non-empty: `$stmt->execute($params)`
4. Return `$stmt` (or `false` on prepare failure)

Exceptions bubble up from PDO.

---

## 3. Transactions

```php
function db_transaction(PDO $pdo, callable $transaction): mixed
```

**Precondition**: PDO must use `ERRMODE_EXCEPTION` or throws `DomainException`

**Flow**:
1. `beginTransaction()`
2. call `$transaction($pdo)`
3. on success → `commit()` → return result
4. on exception → `rollBack()` → rethrow

---

## Examples

```php
// Query without params (immediate execution)
$users = qp("SELECT * FROM users")->fetchAll();

// Query with params
$user = qp("SELECT * FROM users WHERE id = ?", [$id])->fetch();

// Prepare only (no execute yet)
$stmt = qp("INSERT INTO logs (msg) VALUES (?)", []);
$stmt->execute(['login']);
$stmt->execute(['logout']);

// Transaction
$result = db_transaction(db(), function($pdo) use ($data) {
    qp("INSERT INTO orders (total) VALUES (?)", [$data['total']]);
    $order_id = $pdo->lastInsertId();
    qp("INSERT INTO order_items (order_id, item) VALUES (?, ?)", 
        [$order_id, $data['item']]);
    return $order_id;
});
```

---

## Notes

* Single cached connection; each setter call replaces it
* `dbq` returns `false` on prepare failure, `PDOStatement` on success
* Wrap calls in `try/catch` to handle `PDOException`
* Full PDO API remains available on returned instances
* ENV is read from `$_SERVER` first, then `getenv`
