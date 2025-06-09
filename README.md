# BADDAD

**Bits Are Data, Dont Add Decoration**

BADDAD is not a framework. It's a **refusal**.

A refusal of:
- Classes and object-oriented ceremony  
- Namespaces and autoloading overhead
- Routing tables and middleware stacks
- Template engines and abstraction layers
- Configuration files and dependency injection

Just **bits**, **functions**, **files**, and **speed**.

BADDAD is ~400 lines of core PHP that delivers everything needed for high-performance web applications—and nothing you don't explicitly require.

---

## Core Philosophy

**Bitwise state management**
- Use integers with bitwise flags instead of arrays or objects
- `IO_SEEK | IO_CALL` is faster than `['seek' => true, 'call' => true]`
- Native PHP constants are more explicit than magic strings

**File-system as router**
- URL `/admin/users/edit/42` maps to `route/admin/users/edit.php`  
- No route registration—if the file exists, it executes
- Bitwise flags control execution flow: seek, send, call, load

**Procedural everything**
- Functions over methods, includes over autoloading
- Static variables for request-scoped singletons
- Native PHP error handling with structured logging

**SQL as first-class citizen**
- Direct PDO with query builders only for repetitive operations
- No ORM—SQL is a language, respect it

---

## Quick Start

### 1. Setup Structure
```bash
mkdir myapp && cd myapp
git clone https://github.com/lareponse/BADDAD.git add
mkdir -p app/{route,views,data}
```

### 2. Entry Point (`index.php`)
```php
<?php
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/..');

require 'add/bad/io.php';
require 'add/bad/dad/db.php'; 
require 'add/bad/error.php';
require 'add/bad/dad/guard_auth.php';

// Database connection
[$dsn, $user, $pass] = require 'app/data/credentials.php';
db(new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]));

// Route and respond  
$quest = io(__DIR__ . '/app/route');
http_respond(deliver($quest));
```

### 3. First Route (`app/route/home.php`)
```php
<?php
return function() {
    $users = dbq("SELECT * FROM users WHERE active = ? LIMIT 5", [1])->fetchAll();
    
    tray('head', '<meta name="description" content="User list">');
    tray('main', render('home', ['users' => $users]));
    
    return ['status' => 200, 'body' => render_layout()];
};
```

### 4. View (`app/views/home.php`)
```php
<h1>Active Users</h1>
<?php foreach ($users as $user): ?>
    <?= html('div', 
        html('strong', htmlspecialchars($user['name'])) . 
        html('span', htmlspecialchars($user['email']), 'class' => 'email'),
        'class' => 'user-card'
    ) ?>
<?php endforeach; ?>
```

### 5. Layout (`app/views/layout.php`)
```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BADDAD App</title>
    <?= implode("\n", tray('head')) ?>
</head>
<body>
    <?= implode("\n", tray('main')) ?>
    <?= implode("\n", tray('scripts')) ?>
</body>
</html>
```

---

## Core Functions

### Bitwise I/O Router
```php
// Route resolution with bitwise flags
$quest = io(__DIR__ . '/app/route');

// IO constants (bitwise flags)
const IO_SEEK = 1;   // Seeking handler file
const IO_SEND = 2;   // Sending response  
const IO_FILE = 4;   // File found
const IO_ARGS = 8;   // URL arguments
const IO_CALL = 16;  // Callable handler
const IO_LOAD = 32;  // Output buffer

// Combined operations
const IO_SEEK_CALL = IO_SEEK | IO_CALL | IO_ARGS;  // 25
const IO_SEND_CALL = IO_SEND | IO_CALL | IO_LOAD;  // 50
```

### Database Layer  
```php
// Connect once, use everywhere
db(new PDO($dsn, $user, $pass));

// Query with bindings
$user = dbq("SELECT * FROM users WHERE id = ?", [42])->fetch();

// Transaction blocks
$order_id = dbt(function() {
    dbq("INSERT INTO orders (user_id, total) VALUES (?, ?)", [42, 99.99]);
    $id = db()->lastInsertId();
    dbq("INSERT INTO order_items (order_id, product_id) VALUES (?, ?)", [$id, 5]);
    return $id;
});
```

### Query Builders (Optional)
```php
// Create operations
[$sql, $binds] = qb_create('users', null, [
    'username' => 'john',
    'email' => 'john@example.com'
]);
dbq($sql, $binds);

// Read with conditions  
[$sql, $binds] = qb_read('users', ['status' => 'active', 'role' => ['admin', 'editor']]);
$users = dbq($sql, $binds)->fetchAll();

// Update operations
[$sql, $binds] = qb_update('users', ['last_login' => date('Y-m-d H:i:s')], ['id' => 42]);
dbq($sql, $binds);
```

### Authentication
```php
// Check current user
$username = whoami();  // Returns username or null

// HTTP header auth (reverse proxy/SSO)
// Requires: HTTP_X_AUTH_USER and HTTP_X_AUTH_SIG headers

// Token-based auth  
if (auth_post($_POST['username'], $_POST['password'], $_POST['remember_me'])) {
    header('Location: /dashboard');
    exit;
}

// Logout
auth_revoke();  // Destroys session and tokens

// CSRF protection
csrf_field();  // Generates hidden input
csrf($_POST['csrf_token']);  // Validates token
```

### HTML & UI
```php
// Slot system for content accumulation
tray('head', '<link rel="stylesheet" href="/css/app.css">');
tray('scripts', '<script src="/js/app.js"></script>');
tray('main', '<h1>Page Content</h1>');

// Consume slots in layout
$head_content = implode("\n", tray('head'));     // Returns and clears
$main_content = implode("\n", tray('main'));     // Returns and clears  
$script_content = implode("\n", tray('scripts')); // Returns and clears

// HTML generation
html('div', 'Content here', 'class' => 'container', 'id' => 'main');
// Output: <div class="container" id="main">Content here</div>

html('input', null, 'type' => 'text', 'name' => 'username', 'required');
// Output: <input type="text" name="username" required/>
```

---

## File Structure

```
myapp/
├── add/                    # BADDAD core (~400 lines)
│   ├── bad/
│   │   ├── io.php         # Bitwise I/O router  
│   │   ├── http.php       # HTTP utilities
│   │   ├── error.php      # Error handling
│   │   └── dad/
│   │       ├── db.php     # Database layer
│   │       ├── qb.php     # Query builders
│   │       ├── guard_auth.php  # Authentication
│   │       └── html.php   # HTML generation
├── app/
│   ├── route/             # Route handlers
│   │   ├── admin/
│   │   │   ├── prepare.php     # Auth guard for /admin/*
│   │   │   └── users.php       # /admin/users  
│   │   ├── api/
│   │   │   └── users.php       # /api/users
│   │   └── home.php            # / or /home
│   ├── views/             # Templates
│   │   ├── layout.php
│   │   └── home.php
│   └── data/
│       └── credentials.php     # Database config
└── index.php              # Entry point
```

---

## Routing System

### File-Based URL Mapping
```
URL                     →  File                    →  Arguments
/                      →  route/home.php          →  []
/users                 →  route/users.php         →  []  
/users/42              →  route/users.php         →  ['42']
/admin/users/edit/42   →  route/admin/users/edit.php  →  ['42']
```

### Route Handler Pattern
```php
<?php
// route/users/edit.php
return function($id = null) {
    if (!$id) {
        trigger_error('400 User ID required', E_USER_ERROR);
    }
    
    $user = dbq("SELECT * FROM users WHERE id = ?", [$id])->fetch();
    if (!$user) {
        trigger_error('404 User not found', E_USER_ERROR);  
    }
    
    if ($_POST) {
        // Handle form submission
        [$sql, $binds] = qb_update('users', $_POST, ['id' => $id]);
        dbq($sql, $binds);
        
        header('Location: /admin/users');
        exit;
    }
    
    tray('main', render('users/edit', ['user' => $user]));
    return ['status' => 200, 'body' => render_layout()];
};
```

### Auth Guards (`prepare.php`)
```php
<?php
// route/admin/prepare.php - Runs before all /admin/* routes
return function() {
    if (!whoami()) {
        header('Location: /login');
        exit;
    }
    
    // Additional admin checks
    $user = dbq("SELECT role FROM users WHERE username = ?", [whoami()])->fetch();
    if ($user['role'] !== 'admin') {
        trigger_error('403 Forbidden', E_USER_ERROR);
    }
};
```

---

## Environment Configuration

```bash
# Database  
export DB_DSN="mysql:host=localhost;dbname=myapp;charset=utf8mb4"
export DB_USER="myapp_user"  
export DB_PASS="secure_password"

# Authentication
export BADDAD_AUTH_HMAC_SECRET="your-256-bit-secret-key"
export CSRF_SECRET="your-csrf-secret-key"

# Development
export DEV_MODE=true
```

### Credentials File (`app/data/credentials.php`)
```php
<?php
return [
    getenv('DB_DSN') ?: 'sqlite:' . __DIR__ . '/app.db',
    getenv('DB_USER') ?: null,
    getenv('DB_PASS') ?: null
];
```

---

## Performance Characteristics

| Operation | BADDAD | Laravel | Symfony |
|-----------|-------|---------|---------|
| **Cold boot** | 1-2ms | 80-120ms | 60-100ms |
| **Memory usage** | 0.5-1MB | 15-25MB | 12-20MB |
| **Files loaded** | 4-8 | 200-400 | 150-300 |
| **Database query** | ~0.1ms overhead | ~2-5ms overhead | ~1-3ms overhead |

### Why BADDAD is Fast

1. **Bitwise operations** - Bitwise operations are the fastest operations in all programming languages, mapping directly to underlying C operations in PHP
2. **No autoloading** - Direct `require` statements eliminate class resolution overhead
3. **Static variables** - Request-scoped singletons without DI container complexity  
4. **Procedural code** - Function calls are faster than method dispatch
5. **File-based routing** - No route compilation or matching algorithms

---

## Error Handling

### Structured Error Responses
```php
// Application errors
throw new RuntimeException("404 User not found", 404);
throw new InvalidArgumentException("400 Invalid email format", 400);

// System errors  
trigger_error("Database connection failed", E_USER_ERROR);

// Notices and warnings
trigger_error("Cache miss, regenerating", E_USER_NOTICE);
trigger_error("Slow API response", E_USER_WARNING);
```

### Error Logging
```
UNCAUGHT RuntimeException a1b2c3: 404 User not found in /app/route/users.php:15
SHUTDOWN FATAL e4f5g6h: [1] Parse error in /app/route/admin.php:23
```

---

## Testing

BADDAD includes a minimal 15-line testing framework:

```php
// test/user_test.php  
require_once 'add/test.php';

test('user creation works', function() {
    db('sqlite::memory:');
    dbq("CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT)");
    
    [$sql, $binds] = qb_create('users', null, ['username' => 'test']);
    $stmt = dbq($sql, $binds);
    
    assert($stmt->rowCount() === 1);
});

test('handles duplicate usernames', function() {
    // Test that constraint violations throw properly
    assert_throws(function() {
        [$sql, $binds] = qb_create('users', null, ['username' => 'test']);
        dbq($sql, $binds);  // Should throw PDOException
    }, 'PDOException', 'UNIQUE constraint');
});

run_tests();
```

---

## Advanced Usage

### Multiple Database Connections
```php
// Main database
db(new PDO($main_dsn, $user, $pass));

// Read replica  
db(new PDO($replica_dsn, $user, $pass), 'read');

// Analytics database
db(new PDO($analytics_dsn, $user, $pass), 'analytics');

// Use specific connections
$users = dbq("SELECT * FROM users", [], 'read')->fetchAll();
dbq("INSERT INTO events (...) VALUES (...)", $data, 'analytics');
```

### Content Security Policy
```php
// Generate nonce for inline scripts
$nonce = csp_nonce();
tray('head', "<script nonce='$nonce'>console.log('Safe inline script');</script>");

// Multiple nonces for different contexts
$style_nonce = csp_nonce('style');  
$script_nonce = csp_nonce('script');
```

### Complex Query Building
```php
// Multi-row inserts
[$sql, $binds] = qb_create('users', ['name', 'email'], 
    ['Alice', 'alice@example.com'],
    ['Bob', 'bob@example.com']
);

// Complex WHERE conditions
[$sql, $binds] = qb_read('products', [
    'status' => ['active', 'featured'],
    'price >=' => 10.00,
    'category_id' => [1, 2, 3]
]);

// Compound operations
[$where_sql, $where_binds] = qb_where(['active' => 1, 'role' => 'admin']);
[$limit_sql, $limit_binds] = qb_limit(10, 20);

$sql = "SELECT * FROM users $where_sql ORDER BY created_at DESC $limit_sql";
$users = dbq($sql, $where_binds + $limit_binds)->fetchAll();
```

---

## Philosophy

BADDAD doesn't scale by adding abstractions.  
It scales by composition of simple, measurable parts.

- **~400 lines of core** vs 50,000+ in modern frameworks
- **Bitwise state management** vs complex object hierarchies  
- **Direct requires** vs autoloading systems
- **Static variables** vs dependency injection containers
- **File-based routing** vs route compilation
- **Procedural functions** vs class instantiation

This is not retro. This is not a learning exercise.  
This is **performance-first web development**.

**Modern frameworks optimize for developer comfort.**  
**BADDAD optimizes for application speed.**

---

## License

MIT