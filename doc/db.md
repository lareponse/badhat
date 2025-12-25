# BADHAT Database Layer

Procedural PDO: three functions, no classes.

---

## Environment

```bash
# Default profile (trailing underscore):
export DB_DSN_="mysql:host=localhost;dbname=shop"
export DB_USER_="shop_user"
export DB_PASS_="secret"

# Named profile "read":
export DB_DSN_read="sqlite:/path/to/read.db"
# (no DB_USER_read/DB_PASS_read for SQLite)
```

Values read from `$_SERVER` first, then `getenv()`.

---

## 1. Connection

```php
function db($param = null, array $options = [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]): PDO
```

**Getter:**
- `db()` — return cached PDO (auto-creates from default profile if none)

**Setter:**
- `db('suffix')` — create, cache, return PDO using ENV with suffix
- `db($pdo)` — cache and return provided PDO

**Throws:** `LogicException` if no cache and invalid param, `DomainException` if DSN missing

**Defaults:** `FETCH_ASSOC` (override via `$options`)

Single connection cached; each setter replaces it.

```php
// Use default profile
$users = db()->query("SELECT * FROM users")->fetchAll();

// Use named profile
db('read');
$data = db()->query("SELECT * FROM logs")->fetchAll();

// Inject existing PDO
db(new PDO('sqlite::memory:'));
```

---

## 2. Execution

```php
function qp(string $query, ?array $params = null, array $prepare_options = [], ?string $suffix = null): PDOStatement|false
```

**Param semantics:**
- `null` → `db()->query($query)` (immediate, no prepare)
- `[]` → prepare only, no execute
- `[...]` → prepare + execute with bindings

**Returns:** `PDOStatement` or `false` on prepare failure.

```php
// Immediate query (no params)
$users = qp("SELECT * FROM users")->fetchAll();

// Prepared with params
$user = qp("SELECT * FROM users WHERE id = ?", [$id])->fetch();

// Named params
$user = qp("SELECT * FROM users WHERE id = :id", ['id' => $id])->fetch();

// Prepare only (reusable)
$stmt = qp("INSERT INTO logs (msg) VALUES (?)", []);
$stmt->execute(['login']);
$stmt->execute(['logout']);

// Use specific profile
$data = qp("SELECT * FROM archive", null, [], 'read');
```

---

## 3. Transactions

```php
function dbt(callable $transaction, ?string $suffix = null): mixed
```

**Flow:**
1. `beginTransaction()`
2. Call `$transaction($pdo)`
3. Success → `commit()` → return result
4. Exception → `rollBack()` → rethrow

```php
$order_id = dbt(function($pdo) use ($data) {
    qp("INSERT INTO orders (total) VALUES (?)", [$data['total']]);
    $order_id = $pdo->lastInsertId();
    
    qp("INSERT INTO order_items (order_id, item) VALUES (?, ?)", 
        [$order_id, $data['item']]);
    
    return $order_id;
});
```

**Error checking:** Throws `RuntimeException` if `errorCode() !== '00000'` after commit.

---

## Examples

```php
// Simple select
$users = qp("SELECT * FROM users WHERE active = 1")->fetchAll();

// Count
$count = qp("SELECT COUNT(*) FROM users")->fetchColumn();

// Insert and get ID
qp("INSERT INTO users (name) VALUES (?)", [$name]);
$id = db()->lastInsertId();

// Update
qp("UPDATE users SET name = ? WHERE id = ?", [$name, $id]);

// Delete
qp("DELETE FROM users WHERE id = ?", [$id]);

// Multiple profiles
db('write');  // Primary
qp("INSERT INTO logs (msg) VALUES (?)", [$msg]);

db('read');   // Replica
$logs = qp("SELECT * FROM logs")->fetchAll();
```

---

## Notes

- Single cached connection; setter replaces
- `qp()` returns `false` on prepare failure
- Exceptions bubble from PDO (wrap in try/catch if needed)
- Full PDO API available on returned instances
- ENV: `$_SERVER` takes precedence over `getenv()`