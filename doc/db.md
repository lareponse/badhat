# BADHAT Database

Procedural PDO. Three functions.

---

## Environment

```bash
# Default profile (empty suffix):
export DB_DSN_="mysql:host=localhost;dbname=shop"
export DB_USER_="shop_user"
export DB_PASS_="secret"

# Named profile "read":
export DB_DSN_read="sqlite:/path/to/read.db"
```

Values read from `$_SERVER` first, then `getenv()`.

---

## Functions

### db

```php
function db($param = null, array $options = [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]): PDO
```

**Getter:**
- `db()` — return cached PDO (auto-creates from default profile if none)

**Setter:**
- `db('suffix')` — create, cache, return PDO using ENV with suffix
- `db($pdo)` — cache and return provided PDO

**Throws:** `LogicException` if no cache and invalid param, `DomainException` if DSN missing

```php
// Default profile
$users = db()->query("SELECT * FROM users")->fetchAll();

// Named profile
db('read');
$data = db()->query("SELECT * FROM logs")->fetchAll();

// Inject existing PDO
db(new PDO('sqlite::memory:'));
```

---

### qp

```php
function qp(string $query, ?array $params = null, array $prepare_options = [], ?string $suffix = null): PDOStatement|false
```

**Param semantics:**
- `null` — `db()->query($query)` (immediate)
- `[]` — prepare only, no execute
- `[...]` — prepare + execute with bindings

```php
// Immediate query
$users = qp("SELECT * FROM users")->fetchAll();

// Prepared with params
$user = qp("SELECT * FROM users WHERE id = ?", [$id])->fetch();

// Named params
$user = qp("SELECT * FROM users WHERE id = :id", ['id' => $id])->fetch();

// Prepare only (reusable)
$stmt = qp("INSERT INTO logs (msg) VALUES (?)", []);
$stmt->execute(['login']);
$stmt->execute(['logout']);

// Specific profile
$data = qp("SELECT * FROM archive", null, [], 'read');
```

---

### dbt

```php
function dbt(callable $transaction, ?string $suffix = null)
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

**Error checking:** throws `RuntimeException` if `errorCode() !== '00000'` after commit.

---

## Patterns

```php
// Select
$users = qp("SELECT * FROM users WHERE active = 1")->fetchAll();

// Count
$count = qp("SELECT COUNT(*) FROM users")->fetchColumn();

// Insert + ID
qp("INSERT INTO users (name) VALUES (?)", [$name]);
$id = db()->lastInsertId();

// Update
qp("UPDATE users SET name = ? WHERE id = ?", [$name, $id]);

// Delete
qp("DELETE FROM users WHERE id = ?", [$id]);
```

---

## Notes

- Single cached connection; setter replaces
- `qp()` returns `false` on prepare failure
- Exceptions bubble from PDO
- Full PDO API available on returned instances