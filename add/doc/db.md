# BADHAT Database Layer

Procedural PDO helper: three simple functions, no classes, no abstractions.


## Environment

Set env vars per profile suffix (empty = default):

```apache
# Default (suffix = empty):
export DB_DSN_="mysql:host=localhost;dbname=shop"
export DB_USER_="shop_user"
export DB_PASS_="secret"

# “read” profile (SQLite):
export DB_DSN_read="sqlite:/path/to/read.db"
# (no DB_USER_read/DB_PASS_read needed for SQLite)
````

## 1. Connection
```php 
function db($param = null): PDO ?! LogicException
```
**Getter** 
* `db()`: return cached PDO. Calls `db('')` if none.

**Setter**
* `db('suffix')`: create, cache and return new PDO, using ENV with suffix
* `db($pdo)`: cache & return the PDO

---

## 2. Execution
```php
function dbq(PDO $pdo, string $sql, ?array $bind = null): ?PDOStatement { … }
```
* `dbq('SQL')`: return `PDO::query('SQL')`
* `dbq('SQL', [non empty bindings])`: prepare and execute 'SQL' `PDO::prepare('SQL')->execute($bind)`
* **With non-empty `$bind`**:

  1. `$stmt = $pdo->prepare($sql)`
  2. `$stmt->execute($bind)`
  3. return `$stmt`
* **With null/empty `$bind`**:

  * return `$pdo->query($sql)`
* **Exceptions** bubble up.


---

## 3. `db_transaction(PDO $pdo, callable $transaction): mixed`

* **Precondition**: PDO must use `ERRMODE_EXCEPTION` or throws `DomainException`.
* **Flow**:

  1. `beginTransaction()`
  2. call `$transaction($pdo)`
  3. on success → `commit()` → return result
  4. on exception → `rollBack()` → rethrow

```php
function db_transaction(PDO $pdo, callable $transaction): mixed { … }
```

---

## Notes

* Single cached connection; each setter call replaces it.
* Wrap calls in `try/catch` to handle `PDOException` or transaction errors.
* Full PDO/API access remains available on returned instances.

```php
// Example:
try {
    $pdo = db(); // getter or default connect
    $stmt = dbq($pdo, "SELECT * FROM users WHERE id = ?", [$id]);
    $user = $stmt->fetch();
} catch (Exception $e) {
    // handle error
}
```

