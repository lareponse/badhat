# BADHAT PDO wrapper

Procedural PDO. Three functions.

## TL;DR

- Single cached connection; inject replaces
- All failures throw — no false returns
- Call `db($pdo)` before any `qp()` or `dbt()`
- Full PDO API available on returned instances

---

## Functions

### Connection

```php
db(?\PDO $pdo = null, int $behave = 0): \PDO
```

**Inject:**
- `db($pdo)` — cache and return PDO

**Retrieve:**
- `db()` — return cached PDO

**Reset:**
- `db(null, DB_DROP)` — clear cache, then throw (no connection)
- `db($pdo, DB_DROP)` — clear cache, set new connection

**Throws:** `BadFunctionCallException` if no cached connection

```php
// Bootstrap (once)
db(new PDO('mysql:host=localhost;dbname=shop', 'user', 'pass', [
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]));

// Use anywhere after
$users = db()->query("SELECT * FROM users")->fetchAll();

// Reset connection
db(null, DB_DROP);
db($newPdo);
```

---

### Querying
```php 
qp(string $query, ?array $params = null, array $prep_options = [], ?\PDO $pdo = null): \PDOStatement
```

**Param semantics:**
- `null` — `query()` immediate
- `[]` — prepare only, no execute
- `[...]` — prepare + execute with bindings

**Throws:** `RuntimeException` on query/prepare/execute failure

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

// Explicit connection
$data = qp("SELECT * FROM archive", null, [], $readReplica);
```

---

### dbt()

```php
function dbt(callable $transaction, ?\PDO $pdo = null)
```

**Flow:**
1. `beginTransaction()`
2. Call `$transaction($pdo)`
3. Success → `commit()` → return result
4. Exception → `rollBack()` → rethrow

**Throws:** `RuntimeException` on transactionnal failure (beginTransaction, commit, rollBack)

```php
$order_id = dbt(function($pdo) use ($data) {
    qp("INSERT INTO orders (total) VALUES (?)", [$data['total']]);
    $order_id = $pdo->lastInsertId();
    
    qp("INSERT INTO order_items (order_id, item) VALUES (?, ?)", 
        [$order_id, $data['item']]);
    
    return $order_id;
});

// Explicit connection
dbt($fn, $writeConnection);
```

---

## Patterns

```php
$users = qp("SELECT * FROM users WHERE active = 1")->fetchAll();
$count = qp("SELECT COUNT(*) FROM users")->fetchColumn();

qp("INSERT INTO users (name) VALUES (?)", [$name]);
$id = db()->lastInsertId();

qp("UPDATE users SET name = ? WHERE id = ?", [$name, $id]);
qp("DELETE FROM users WHERE id = ?", [$id]);
```

---

## Multiple Connections

Caller owns topology:

```php
// Bootstrap
$write = new PDO($dsn_primary, ...);
$read  = new PDO($dsn_replica, ...);

db($write);                          // default

// Usage
qp($select, [$id], [], $read);       // explicit read
qp($insert, [$data]);                // default write
dbt($fn, $write);                    // explicit write
```