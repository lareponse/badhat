# BADHAT PDO wrapper

A tiny procedural PDO helper: **1 cached PDO + 2 helpers**.
If you want internals: it’s ~50 lines — just read the source.

## TL;DR

* Call `db($pdo)` once during bootstrap
* Use `qp()` for query/prepare/execute
* Use `trans()` for transactions
* **Everything throws** on failure (no `false` returns)
* You can always pass an explicit `$pdo` if you don’t want the cache

---

## Bootstrap

```php
use function bad\db\{db, qp, trans};

db(new PDO($dsn, $user, $pass, [
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]));
```

---

## API

### `db()`

```php
db(?\PDO $pdo = null, int $behave = 0): \PDO
```

* `db($pdo)` → set/replace cached connection
* `db()` → get cached connection (or throws)
* `db(null, VOID_CACHE)` → clear cache (then throws)

```php
db(null, bad\db\VOID_CACHE);
```

---

### `qp()`

```php
qp(string $query, ?array $params = null, array $prep_options = [], ?\PDO $pdo = null): \PDOStatement
```

`$params` meaning:

* `null` → `$pdo->query($query)`
* `[]` → prepare only (no execute)
* non-empty array → prepare + execute

```php
$rows = qp("SELECT * FROM users")->fetchAll();
$row  = qp("SELECT * FROM users WHERE id = ?", [$id])->fetch();

$stmt = qp("INSERT INTO logs(msg) VALUES(?)", []);
$stmt->execute(['hello']);
```

Pass a specific connection:

```php
$rows = qp("SELECT * FROM archive", null, [], $readPdo)->fetchAll();
```

---

### `trans()`

```php
trans(callable $transaction, ?\PDO $pdo = null)
```

Runs:

* `beginTransaction()`
* `$transaction($pdo)`
* `commit()` or `rollBack()` on error

```php
$id = trans(function(\PDO $pdo){
  qp("INSERT INTO t(x) VALUES(?)", [123], [], $pdo);
  return $pdo->lastInsertId();
});
```