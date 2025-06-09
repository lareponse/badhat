# BADDAD Developer Guide

**Complete technical reference for implementing with BADDAD**

---

## Table of Contents

1. [Design Principles](#design-principles)
2. [Core Functions](#core-functions)
3. [Database Layer](#database-layer)
4. [Routing System](#routing-system)
5. [Authentication](#authentication)
6. [HTML & Templates](#html--templates)
7. [Advanced Patterns](#advanced-patterns)
8. [Testing](#testing)
9. [Production Deployment](#production-deployment)
10. [Troubleshooting](#troubleshooting)

---

## Design Principles

BADDAD follows a systematic approach from data primitives to application architecture:

### 1. Bit
Use boolean variables for binary or ternary decisions (`true`/`false`/`null`).

```php
$active = true;           // Not: $status = 'active'
$verified = false;        // Not: $verification = 0  
$pending = null;          // Not: $state = 'pending'
```

### 2. Sequence  
Use integers as bitmasks instead of arrays or strings.

```php
// Bitwise flags (fast)
const USER_READ = 1;      // 001
const USER_WRITE = 2;     // 010  
const USER_DELETE = 4;    // 100
const USER_ADMIN = USER_READ | USER_WRITE | USER_DELETE; // 111

$permissions = USER_READ | USER_WRITE;  // 011
if ($permissions & USER_DELETE) { /* check */ }

// NOT arrays of booleans (slow)
$permissions = ['read' => true, 'write' => true, 'delete' => false];
```

**Why bitwise is faster:** PHP constants are more explicit than strings. Bitwise operations map directly to underlying C operations, making them among the fastest operations in PHP.

### 3. Blocks

**Control Flow**
- No `switch`, no `match`, minimal `if`/`elseif`/`else` chains
- Loops are faster than array functions—use `while`, `foreach`

```php
// Fast iteration
foreach ($users as $user) {
    // process
}

// NOT array functions
$users = array_filter($users, fn($u) => $u['active']);
```

**Functions**  
- Config is function signature—set values where needed, use constants for shared values
- No convenience wrapping:

```php
// This is lazy and opinionated - DON'T DO THIS
function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
```

- Bundling acceptable only with 2+ native functions:

```php
// Acceptable bundling
function dbq($sql, $binds = []) {
    return db()->prepare($sql)->execute($binds);  // 2+ operations
}
```

### 4. Files
File-based routing system where URLs map directly to PHP files.

### 5. Folders  
Directory structure as application organization and namespace replacement.

### 6. Quest
Don't test ahead—expect success, use error handling for failure response.

```php
// Expect success
$user = dbq("SELECT * FROM users WHERE id = ?", [$id])->fetch();
if (!$user) {
    trigger_error('404 User not found', E_USER_ERROR);
}

// NOT defensive programming
if (isset($id) && is_numeric($id) && $id > 0) {
    // paranoid checks
}
```

### 7. Session
User state management through minimal session handling.

### 8. Journey  
Multi-request application flow without complex state machines.

---

## Core Functions

### Database Connection

```php
// Initialize once in entry point
db(new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]));

// Use anywhere without importing/injecting
function get_user($id) {
    return dbq("SELECT * FROM users WHERE id = ?", [$id])->fetch();
}

// Multiple connections
db(new PDO($replica_dsn, $user, $pass), 'read');
db(new PDO($analytics_dsn, $user, $pass), 'analytics');

// Use specific connections
$users = dbq("SELECT * FROM users", [], 'read')->fetchAll();
dbq("INSERT INTO events (...) VALUES (...)", $data, 'analytics');
```

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

---

## Database Layer

### Basic Queries

```php
// Simple query
$users = dbq("SELECT * FROM users WHERE active = 1")->fetchAll();

// With bindings
$user = dbq("SELECT * FROM users WHERE id = ?", [42])->fetch();

// Multiple bindings
$products = dbq(
    "SELECT * FROM products WHERE category = ? AND price BETWEEN ? AND ?",
    ['electronics', 100, 500]
)->fetchAll();

// Named bindings
$orders = dbq(
    "SELECT * FROM orders WHERE user_id = :user_id AND status = :status",
    ['user_id' => 42, 'status' => 'completed']
)->fetchAll();
```

### Transactions

```php
// Transaction blocks
$order_id = dbt(function() {
    dbq("INSERT INTO orders (user_id, total) VALUES (?, ?)", [42, 99.99]);
    $id = db()->lastInsertId();
    dbq("INSERT INTO order_items (order_id, product_id) VALUES (?, ?)", [$id, 5]);
    return $id;
});

// Manual transaction control
db()->beginTransaction();
try {
    dbq("UPDATE inventory SET qty = qty - ? WHERE product_id = ?", [1, 5]);
    dbq("INSERT INTO sales (product_id, qty) VALUES (?, ?)", [5, 1]);
    db()->commit();
} catch (Exception $e) {
    db()->rollback();
    throw $e;
}
```

### Query Builders (Optional)

```php
// Create operations
[$sql, $binds] = qb_create('users', null, [
    'username' => 'john',
    'email' => 'john@example.com',
    'created_at' => date('Y-m-d H:i:s')
]);
dbq($sql, $binds);

// Multi-row inserts
[$sql, $binds] = qb_create('users', ['name', 'email'], 
    ['Alice', 'alice@example.com'],
    ['Bob', 'bob@example.com']
);
dbq($sql, $binds);

// Read with conditions  
[$sql, $binds] = qb_read('users', [
    'status' => 'active', 
    'role' => ['admin', 'editor'],
    'created_at >=' => '2024-01-01'
]);
$users = dbq($sql, $binds)->fetchAll();

// Complex WHERE conditions
[$sql, $binds] = qb_read('products', [
    'status' => ['active', 'featured'],
    'price >=' => 10.00,
    'category_id' => [1, 2, 3]
]);

// Update operations
[$sql, $binds] = qb_update('users', 
    ['last_login' => date('Y-m-d H:i:s')], 
    ['id' => 42]
);
dbq($sql, $binds);

// Delete operations
[$sql, $binds] = qb_delete('sessions', ['expires_at <' => date('Y-m-d H:i:s')]);
dbq($sql, $binds);
```

### Advanced Query Building

```php
// Compound operations
[$where_sql, $where_binds] = qb_where(['active' => 1, 'role' => 'admin']);
[$limit_sql, $limit_binds] = qb_limit(10, 20);

$sql = "SELECT * FROM users $where_sql ORDER BY created_at DESC $limit_sql";
$users = dbq($sql, array_merge($where_binds, $limit_binds))->fetchAll();

// Custom WHERE clauses
[$sql, $binds] = qb_read('orders', [
    'created_at >=' => '2024-01-01',
    'total BETWEEN ? AND ?' => [100, 1000],
    'status IN' => ['pending', 'processing']
]);
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
/api/v1/products       →  route/api/v1/products.php  →  []
```

### Route Handler Patterns

**Basic Route**
```php
<?php
// route/users.php
return function($id = null) {
    if ($id) {
        // Show single user
        $user = dbq("SELECT * FROM users WHERE id = ?", [$id])->fetch();
        if (!$user) {
            trigger_error('404 User not found', E_USER_ERROR);
        }
        
        tray('main', render('users/show', ['user' => $user]));
    } else {
        // List users
        $users = dbq("SELECT * FROM users ORDER BY name")->fetchAll();
        tray('main', render('users/list', ['users' => $users]));
    }
    
    return ['status' => 200, 'body' => render_layout()];
};
```

**Form Handling**
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
        // Validate and update
        $errors = [];
        if (empty($_POST['name'])) $errors[] = 'Name required';
        if (empty($_POST['email'])) $errors[] = 'Email required';
        
        if (!$errors) {
            [$sql, $binds] = qb_update('users', [
                'name' => $_POST['name'],
                'email' => $_POST['email']
            ], ['id' => $id]);
            dbq($sql, $binds);
            
            header('Location: /users/' . $id);
            exit;
        }
        
        tray('main', render('users/edit', ['user' => $user, 'errors' => $errors]));
    } else {
        tray('main', render('users/edit', ['user' => $user]));
    }
    
    return ['status' => 200, 'body' => render_layout()];
};
```

**API Endpoint**
```php
<?php
// route/api/users.php
return function($id = null) {
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($id) {
            $user = dbq("SELECT id, name, email FROM users WHERE id = ?", [$id])->fetch();
            return ['status' => $user ? 200 : 404, 'body' => json_encode($user ?: ['error' => 'Not found'])];
        } else {
            $users = dbq("SELECT id, name, email FROM users")->fetchAll();
            return ['status' => 200, 'body' => json_encode($users)];
        }
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        [$sql, $binds] = qb_create('users', null, $data);
        dbq($sql, $binds);
        $new_id = db()->lastInsertId();
        
        return ['status' => 201, 'body' => json_encode(['id' => $new_id])];
    }
    
    return ['status' => 405, 'body' => json_encode(['error' => 'Method not allowed'])];
};
```

### Auth Guards (`prepare.php`)

```php
<?php
// route/admin/prepare.php - Runs before all /admin/* routes
return function() {
    if (!whoami()) {
        header('Location: /login?return=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    
    // Additional admin checks
    $user = dbq("SELECT role FROM users WHERE username = ?", [whoami()])->fetch();
    if ($user['role'] !== 'admin') {
        trigger_error('403 Forbidden', E_USER_ERROR);
    }
    
    // Add admin-specific content to all admin pages
    tray('head', '<link rel="stylesheet" href="/css/admin.css">');
    tray('scripts', '<script src="/js/admin.js"></script>');
};
```

**Multiple Guard Levels**
```php
<?php
// route/api/prepare.php - API authentication
return function() {
    $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!validate_api_token($token)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
};

// route/api/admin/prepare.php - Admin API access
return function() {
    $user = get_current_api_user();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit;
    }
};
```

---

## Authentication

### Basic Authentication

```php
// Check current user
$username = whoami();  // Returns username or null

// Login form handler
if ($_POST && isset($_POST['username'], $_POST['password'])) {
    if (auth_post($_POST['username'], $_POST['password'], $_POST['remember_me'] ?? false)) {
        $return_url = $_GET['return'] ?? '/dashboard';
        header('Location: ' . $return_url);
        exit;
    } else {
        $error = 'Invalid credentials';
    }
}

// Logout
auth_revoke();  // Destroys session and tokens
header('Location: /');
exit;
```

### HTTP Header Authentication

For reverse proxy/SSO integration:

```php
// Requires HTTP_X_AUTH_USER and HTTP_X_AUTH_SIG headers
$user = auth_header();
if (!$user) {
    trigger_error('401 Unauthorized', E_USER_ERROR);
}
```

### CSRF Protection

```php
// Generate token in forms
echo csrf_field();  // Outputs: <input type="hidden" name="csrf_token" value="...">

// Validate token in handlers
if ($_POST) {
    csrf($_POST['csrf_token']);  // Throws error if invalid
    // Process form...
}
```

### Token-Based Authentication

```php
// Generate API token
$token = auth_generate_token($user_id, $expires_at);

// Validate token
$user_id = auth_validate_token($_SERVER['HTTP_AUTHORIZATION']);
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}
```

---

## HTML & Templates

### Slot System

```php
// Accumulate content in slots
tray('head', '<meta name="description" content="User profile">');
tray('head', '<link rel="stylesheet" href="/css/profile.css">');
tray('main', '<h1>User Profile</h1>');
tray('main', '<div class="profile-content">...</div>');
tray('scripts', '<script src="/js/profile.js"></script>');

// Consume slots in layout (returns and clears)
$head_content = implode("\n", tray('head'));
$main_content = implode("\n", tray('main'));
$script_content = implode("\n", tray('scripts'));
```

### HTML Generation

```php
// Basic elements
html('div', 'Content here', 'class' => 'container', 'id' => 'main');
// Output: <div class="container" id="main">Content here</div>

// Self-closing elements
html('input', null, 'type' => 'text', 'name' => 'username', 'required');
// Output: <input type="text" name="username" required/>

// Complex structures
$user_card = html('div',
    html('h3', htmlspecialchars($user['name'])) .
    html('p', htmlspecialchars($user['email']), 'class' => 'email') .
    html('a', 'Edit', 'href' => '/users/edit/' . $user['id'], 'class' => 'btn'),
    'class' => 'user-card'
);
```

### Template Rendering

```php
// Basic template
function render($template, $data = [], $layout = 'layout') {
    extract($data);
    ob_start();
    include "views/$template.php";
    $content = ob_get_clean();
    
    if ($layout) {
        ob_start();
        include "views/$layout.php";
        return ob_get_clean();
    }
    
    return $content;
}

// Usage
$html = render('users/profile', ['user' => $user, 'posts' => $posts]);
tray('main', $html);
```

### Content Security Policy

```php
// Generate nonce for inline scripts
$nonce = csp_nonce();
tray('head', "<script nonce='$nonce'>console.log('Safe inline script');</script>");

// Context-specific nonces
$style_nonce = csp_nonce('style');  
$script_nonce = csp_nonce('script');

tray('head', "<style nonce='$style_nonce'>.highlight { background: yellow; }</style>");
tray('scripts', "<script nonce='$script_nonce'>initApp();</script>");
```

---

## Advanced Patterns

### Error Handling

```php
// Application errors with HTTP status
throw new RuntimeException("404 User not found", 404);
throw new InvalidArgumentException("400 Invalid email format", 400);

// System errors  
trigger_error("Database connection failed", E_USER_ERROR);

// Notices and warnings for debugging
trigger_error("Cache miss, regenerating", E_USER_NOTICE);
trigger_error("Slow API response: " . $duration . "ms", E_USER_WARNING);

// Custom error handler
set_error_handler(function($severity, $message, $file, $line) {
    if ($severity === E_USER_ERROR) {
        // Extract HTTP status from message
        if (preg_match('/^(\d{3})\s/', $message, $matches)) {
            http_response_code((int)$matches[1]);
            echo json_encode(['error' => substr($message, 4)]);
            exit;
        }
    }
    
    error_log("$severity: $message in $file:$line");
});
```

### Middleware Pattern

```php
// Simple middleware implementation
function apply_middleware($quest, $middlewares) {
    foreach ($middlewares as $middleware) {
        $result = $middleware($quest);
        if ($result) return $result; // Early termination
    }
    return null;
}

// Usage in routes
$auth_middleware = function($quest) {
    if (!whoami()) {
        return ['status' => 401, 'body' => 'Unauthorized'];
    }
    return null;
};

$result = apply_middleware($quest, [$auth_middleware]);
if ($result) return $result;
```

### Caching Patterns

```php
// Simple file-based cache
function cache_get($key) {
    $file = "cache/" . md5($key) . ".cache";
    if (!file_exists($file) || filemtime($file) < time() - 3600) {
        return null;
    }
    return unserialize(file_get_contents($file));
}

function cache_set($key, $value) {
    $file = "cache/" . md5($key) . ".cache";
    file_put_contents($file, serialize($value));
}

// Usage
$cache_key = "user_posts_" . $user_id;
$posts = cache_get($cache_key);
if (!$posts) {
    $posts = dbq("SELECT * FROM posts WHERE user_id = ?", [$user_id])->fetchAll();
    cache_set($cache_key, $posts);
}
```

### Background Tasks

```php
// Simple task queue using database
function queue_task($task_name, $data) {
    [$sql, $binds] = qb_create('task_queue', null, [
        'task_name' => $task_name,
        'data' => json_encode($data),
        'created_at' => date('Y-m-d H:i:s'),
        'status' => 'pending'
    ]);
    dbq($sql, $binds);
}

// Worker script
function process_tasks() {
    $tasks = dbq("SELECT * FROM task_queue WHERE status = 'pending' LIMIT 10")->fetchAll();
    
    foreach ($tasks as $task) {
        try {
            // Mark as processing
            dbq("UPDATE task_queue SET status = 'processing' WHERE id = ?", [$task['id']]);
            
            // Process task
            $data = json_decode($task['data'], true);
            call_user_func($task['task_name'], $data);
            
            // Mark as completed
            dbq("UPDATE task_queue SET status = 'completed' WHERE id = ?", [$task['id']]);
        } catch (Exception $e) {
            dbq("UPDATE task_queue SET status = 'failed', error = ? WHERE id = ?", 
                [$e->getMessage(), $task['id']]);
        }
    }
}
```

---

## Testing

BADDAD includes a minimal testing framework:

```php
// test/user_test.php  
require_once 'add/test.php';

test('user creation works', function() {
    // Setup in-memory database
    db(new PDO('sqlite::memory:'));
    dbq("CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT UNIQUE)");
    
    // Test creation
    [$sql, $binds] = qb_create('users', null, ['username' => 'testuser']);
    $stmt = dbq($sql, $binds);
    
    assert($stmt->rowCount() === 1);
    
    // Verify data
    $user = dbq("SELECT * FROM users WHERE username = 'testuser'")->fetch();
    assert($user['username'] === 'testuser');
});

test('handles duplicate usernames', function() {
    db(new PDO('sqlite::memory:'));
    dbq("CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT UNIQUE)");
    
    // First insert should work
    [$sql, $binds] = qb_create('users', null, ['username' => 'duplicate']);
    dbq($sql, $binds);
    
    // Second should throw
    assert_throws(function() {
        [$sql, $binds] = qb_create('users', null, ['username' => 'duplicate']);
        dbq($sql, $binds);
    }, 'PDOException');
});

test('route handler returns correct format', function() {
    require 'app/route/users.php';
    $handler = include 'app/route/users.php';
    
    $result = $handler(42);
    assert(is_array($result));
    assert(isset($result['status']));
    assert(isset($result['body']));
});

// Run all tests
run_tests();
```

### Testing Utilities

```php
// Custom assertions
function assert_contains($needle, $haystack, $message = '') {
    assert(strpos($haystack, $needle) !== false, $message ?: "Expected '$needle' in '$haystack'");
}

function assert_http_status($expected, $callable) {
    ob_start();
    $callable();
    ob_end_clean();
    
    assert(http_response_code() === $expected);
}

// Test database setup
function setup_test_db() {
    db(new PDO('sqlite::memory:'));
    
    // Load schema
    $schema = file_get_contents('schema.sql');
    db()->exec($schema);
    
    // Load test data
    $test_data = file_get_contents('test/fixtures.sql');
    db()->exec($test_data);
}
```

---

## Production Deployment

### Environment Configuration

```bash
# .env file
DB_DSN="mysql:host=db.example.com;dbname=myapp;charset=utf8mb4"
DB_USER="myapp_user"  
DB_PASS="secure_password"

# Authentication
BADDAD_AUTH_HMAC_SECRET="your-256-bit-secret-key"
CSRF_SECRET="your-csrf-secret-key"

# Feature flags
ENABLE_DEBUG=false
ENABLE_CACHE=true
```

### Performance Optimization

```php
// index.php - Production optimizations
<?php
// Enable opcache
ini_set('opcache.enable', 1);
ini_set('opcache.memory_consumption', 128);
ini_set('opcache.max_accelerated_files', 1000);

// Disable debug in production
if (getenv('ENABLE_DEBUG') !== 'true') {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Preload critical files (PHP 7.4+)
if (function_exists('opcache_compile_file')) {
    opcache_compile_file(__DIR__ . '/add/bad/io.php');
    opcache_compile_file(__DIR__ . '/add/bad/dad/db.php');
}

require 'add/bad/io.php';
require 'add/bad/dad/db.php';
require 'add/bad/error.php';

// Database connection with production settings
db(new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_PERSISTENT => true,  // Connection pooling
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false  // Memory optimization
]));

$quest = io(__DIR__ . '/app/route');
http_respond(deliver($quest));
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name myapp.com;
    root /var/www/myapp;
    index index.php;

    # Security headers
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";

    # Static files
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # PHP handling
    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Performance
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # Security - deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ /(add|app/data)/ {
        deny all;
    }
}
```

### Monitoring

```php
// Simple performance monitoring
function log_performance($start_time, $memory_start) {
    $duration = (microtime(true) - $start_time) * 1000; // ms
    $memory = memory_get_peak_usage() - $memory_start;
    
    if ($duration > 100 || $memory > 1024 * 1024) { // 100ms or 1MB
        error_log("PERF: {$duration}ms, {$memory}bytes - " . $_SERVER['REQUEST_URI']);
    }
}

// Usage in index.php
$start_time = microtime(true);
$memory_start = memory_get_usage();

// ... application code ...

register_shutdown_function('log_performance', $start_time, $memory_start);
```

---

## Troubleshooting

### Common Issues

**Route not found**
```bash
# Check file exists and is executable
ls -la app/route/your/path.php

# Check URL mapping
# /your/path/123 should map to app/route/your/path.php with args ['123']
```

**Database connection failed**
```php
// Test connection manually
try {
    $pdo = new PDO($dsn, $user, $pass);
    echo "Connection successful";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
```

**CSRF token mismatch**
```php
// Ensure forms include token
echo csrf_field();

// Check session is started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
```

**Memory leaks**
```php
// Monitor memory usage
echo "Memory: " . memory_get_usage() . " bytes\n";
echo "Peak: " . memory_get_peak_usage() . " bytes\n";

// Check for circular references in objects
// (BADDAD's procedural approach avoids this)
```

### Debugging Techniques

**Enable query logging**
```php
// Log all database queries
function dbq($sql, $binds = [], $connection = 'default') {
    error_log("SQL: $sql " . json_encode($binds));
    return db($connection)->prepare($sql)->execute($binds);
}
```

**Route debugging**
```php
// Debug route resolution
function io($route_path) {
    $quest = parse_url($_SERVER['REQUEST_URI']);
    error_log("Resolving: " . $quest['path']);
    
    // ... normal io() logic ...
    
    error_log("Resolved to: $file_path");
    return $quest;
}
```

**Performance profiling**
```php
// Simple profiler
$profiler = [];
function profile($label) {
    global $profiler;
    $profiler[$label] = microtime(true);
}

profile('start');
// ... code ...
profile('database');
// ... code ...
profile('end');

foreach ($profiler as $label => $time) {
    error_log("$label: " . ($time - $profiler['start']) . "s");
}