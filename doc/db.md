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

---

## 2. Execution

```php
function qp(PDO $pdo, string $query, ?array $params, array $prepareOptions = []): PDOStatement|false
```

**With null `$params`**:
* `qp($pdo, 'SQL', null)`: return `$pdo->prepare('SQL')` (prepare only, no execute)

**With non-null `$params`**:
* `qp($pdo, 'SQL', [])`: prepare and execute with empty bindings
* `qp($pdo, 'SQL', $bindings)`: prepare and execute with parameter bindings

**Flow**:
1. `$stmt = $pdo->prepare($query, $prepareOptions)`
2. If `$params !== null`: `$stmt->execute($params)`
3. Return `$stmt` or `false` on prepare failure

**Exceptions** bubble up from PDO.

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
// Connect
$pdo = db();

// Query without params
$stmt = qp($pdo, "SELECT * FROM users");
$users = $stmt->fetchAll();

// Query with params
$stmt = qp($pdo, "SELECT * FROM users WHERE id = ?", [$id]);
$user = $stmt->fetch();

// Prepare only (no execute)
$stmt = qp($pdo, "INSERT INTO logs (msg) VALUES (?)", null);
$stmt->execute(['login']);
$stmt->execute(['logout']);

// Transaction
$result = db_transaction($pdo, function($pdo) use ($data) {
    qp($pdo, "INSERT INTO orders (total) VALUES (?)", [$data['total']]);
    $order_id = $pdo->lastInsertId();
    qp($pdo, "INSERT INTO order_items (order_id, item) VALUES (?, ?)", 
       [$order_id, $data['item']]);
    return $order_id;
});
```

---

## Notes

* Single cached connection; each setter call replaces it
* `qp` returns `false` on prepare failure, `PDOStatement` on success
* Wrap calls in `try/catch` to handle `PDOException`
* Full PDO API remains available on returned instances