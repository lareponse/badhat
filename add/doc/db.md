# BADGE Database Layer

**Three functions. No classes. No abstraction.**
---

This *is* PDO. Just procedural.
If you know `PDO` and `PDOStatement`, you already know how it works.


## Core Functions

* `db()`    get/set a PDO connection (default or by profile)
* `dbq()`   execute raw or prepared queries
* `dbt()`   run transaction blocks in try/catch

Nothing hidden. Nothing wrapped. You get the raw `PDO` and `PDOStatement` back.

## TL;DR
If you know PDO, you already know everything. 

No DSL, no wrapper. Full PDO and PDOStatement API at your disposal.

BADGE just removes the noise.

## Design Philosophy

* **Procedural** — No classes, no DI, no static methods
* **Explicit** — You choose the query, the connection, and the bindings
* **Scoped** — No global state, no shared memory
* **Profile-aware** — Send reads to replicas, writes to main, logs to analytics
* **Fail-fast** — Exceptions on error, rollback on failure


## Environment, for default and read replica connections

```bash
export DB_DSN_="mysql:host=localhost;dbname=shop"
export DB_USER_="shop_user"
export DB_PASS_="secret"

export DB_DSN_read="mysql:host=replica.local;dbname=shop"
export DB_USER_read="readonly"
export DB_PASS_read="readonlypass"
```

# 1. dbq()

```php
// No bindings, uses PDO::query()
dbq("SELECT * FROM products")->fetchAll();

// With bindings → uses PDO::prepare() + ->execute()
dbq("SELECT * FROM products WHERE slug = ?", ['slug-piccolo'])->fetch();

// Read replica (profile 'read')
dbq("SELECT * FROM products WHERE id = ?", [42], 'read')->fetch();

// Named parameters
dbq("UPDATE products SET price = :price WHERE id = :id", ['price' => 19.99,'id' => 42])->rowCount();


// Prepared statement re-execution 
$results = [];

$first_category = array_shift($categories);

$stmt = dbq("SELECT * array_shiftM products WHERE category = ?", [$first_category]);
$results[$first_category] = $stmt->fetchAll();

foreach($categories as $category) {
    $stmt->execute([$category]);
    $results[$category] = $stmt->fetchAll();
}
array_unshift($categories, $first_category); // restore original order
```




```php
// Main connection
$product = dbq("SELECT * FROM products WHERE id = ?", [$id])->fetch();

// Read replica
$count = dbq("SELECT COUNT(*) FROM orders", [], 'read')->fetchColumn();
```

---

## Manual Injection

```php
$pdo = new PDO('sqlite::memory:');
db($pdo);              // default
db($pdo, 'test');      // profile "test"

$ok = dbq("SELECT 1", [], 'test')->fetchColumn();
```

---

## Transactions

```php
$order_id = dbt(function () {
    dbq("INSERT INTO orders (customer_id) VALUES (?)", [5]);
    $id = db()->lastInsertId();

    dbq("INSERT INTO order_items (order_id, product_id) VALUES (?, ?)", [$id, 7]);
    dbq("INSERT INTO order_items (order_id, product_id) VALUES (?, ?)", [$id, 8]);

    return $id;
});
```

* `dbt()` runs a closure in a transaction
* Rolls back automatically on exception
* Returns whatever your callback returns

---

## Notes

* Connections are stored per profile. Re-injecting overwrites.
* `db()` returns the default connection if no profile is specified.
* `dbq()` and `dbt()` use the default connection unless a profile is specified.
* All `dbq()` calls return native `PDOStatement` or throw an exception.
* Only you know what `dbt` returns



# FAQs


---

### **What is ENV? Why does it matter?**

ENV means **environment variables** — configuration stored *outside* your code. Example:

```bash
export DB_DSN="mysql:host=localhost;dbname=shop"
export DB_USER="root"
export DB_PASS="secret"
```

They're used to keep **secrets out of your codebase**, so:

* You don't commit credentials to Git
* You can switch environments without changing code
* You deploy the same code to dev, staging, and production safely

If these ENV variables exist, `db()` will auto-connect using them.

---

### **I want `db($dsn, $user, $pass)`**

`db()` accepts **either** a `PDO` instance (injection) or a profile name (lookup). 
Mixing modes makes everything fragile and error-prone.

Want to connect manually? Do it once — explicitly:

```php
$pdo = new PDO($dsn, $user, $pass, $options);
db($pdo);              // default
db($pdo, 'read');      // profile 'read'
```

After that, call `db()` with just the profile name. No magic. No guessing.

---

### **Is this a wrapper?**

No. It's a loader. It **returns native `PDO` and `PDOStatement` objects**. If you know how to use them, you already know 100% of this API.

No classes. No decorators. No base model.

---

### **Where is the query builder?**

Busy reading this sentence. SQL is readable, portable, and expressive.
Don't outsource the one part of your backend that actually talks to the database.

---

### **Isn't this unsafe? Where's the ORM?**

ORMs are the clutches you reach for when you don't understand SQL or don't trust your data layer.

Its not that this code is unsafe, it's that you might not know how to use it safely.
This is not a guided tour through SQL. It's a tool for those who already know how to write queries.

No builder can protect you from not understanding SQL.
What they do is restrict the surface area of mistakes, at the cost of performance, verbosity, and transparency.

Don’t outsource safety. Understand what your query does. Then write it yourself.

---

### **How do I handle errors?**

Exceptions. Always.
This code sets `PDO::ERRMODE_EXCEPTION` by default. If something goes wrong, it throws. That's the contract. Catch it or crash.

---

### **What if I need multiple databases?**

Use named profiles:

```php
db($pdo1);                     // default
db($pdo2, 'read');             // replica
db($pdo3, 'analytics');        // analytics

dbq("SELECT * FROM users", [], 'read');
dbq("INSERT INTO logs (...) VALUES (...)", [...], 'analytics');
```

---

### **Can I use named parameters in `dbq()`?**

Yes. `dbq()` just delegates to `prepare()` and `execute()`. Use `?` or `:name` — your choice.

```php
dbq("SELECT * FROM users WHERE id = :id", ['id' => 42]);
```

---




### **Don't want to use ENV yet? Inject manually.**

```php
$pdo = new PDO(
    'mysql:host=localhost;dbname=shop',
    'root',
    'secret',
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
);

// Inject the connection (default profile)
db($pdo);

// Use it
$user = dbq("SELECT * FROM users WHERE id = ?", [42])->fetch();
```

Same connection. Same queries. Just bypassing ENV entirely.
Useful for scripts, tests, or early development.

If you're wondering what the `$profile` argument is for — wait until you have ENV skills.
For now: ignore it. Stick with the default. It works.

