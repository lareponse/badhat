# BADGE — A Procedural Micro-Framework for Serious Developers

**BADGE** is not a php framework. It's a refusal.

A refusal of boilerplate.
A refusal of magic.
A refusal of engineering theater.

That means:

* No config files or metadata
* No classes, containers, annotations or autowiring
* No namespaces or autoloading
* No dependency injection or service layers
* No sessions or cookie parsing
* No routing tables or middleware stacks
* No templating engines or DSLs
* No layers of abstraction

All you need are **file systems**, **functions**, **arrays** and **conventions**.
BADGE is about 150 lines of core code that give you everything required to build real applications—and nothing you don't explicitly ask for.
BADGE adds another 150 lines or so of helpers for database access, security, templating and routing.

This is not retro. This is not hip. This is the future that was stolen. We're taking it back.

---

## Is BADGE for you?

**Yes**, if:

* You'd rather write 10 lines of clear code than configure a container.
* You treat SQL as a language, not a leaky abstraction.
* You believe the filesystem is a perfectly good routing mechanism.
* You understand what `require` does—and that it's enough.
* You want to know exactly what happens when a request hits your server.

**No**, if:

* You need framework magic or auto-generated classes.
* You prefer "clean architecture" over readable code.
* You reach for `composer require` before writing a function.

---

## Setup & Installation

### 1. Basic Directory Structure
```
myapp/
├── add/                    # BADGE framework core
├── app/
│   ├── io/
│   │   ├── route/         # Route handlers
│   │   └── views/         # View templates
│   ├── mapper/            # Database functions
│   ├── morph/             # Data transformation
│   ├── data/              # Configuration & credentials
│   └── public/            # Web-accessible files
│       ├── index.php
│       ├── .htaccess
│       └── assets/
└── README.md
```

### 2. Entry Point (app/public/index.php)
```php
<?php
require '../../add/core.php';
require '../../add/bad/db.php';
require '../../add/bad/ui.php';
require '../../add/bad/security.php';
require '../../add/bad/error.php';

// Database setup
list($dsn, $user, $pass) = require '../data/credentials.php';
pdo($dsn, $user, $pass);

// Route and respond
$route = route(__DIR__ . '/../io/route');
$response = handle($route);
respond($response);
```

### 3. Environment Variables
```bash
# Required for auth
export BADGE_AUTH_HMAC_SECRET="your-secret-key"

# Optional for development
export DEV_MODE=true
```

### 4. Web Server Configuration (app/public/.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [QSA,L]

<IfModule mod_env.c>
    SetEnv DEV_MODE true
</IfModule>
```

---

## Core Principles: The How & Why

### Simplicity over abstraction

* **No [classes](./public/doc/overhead-oop.md), no [namespaces](./public/doc/overhead-namespace.md), no [autoloading](./public/doc/overhead-autoload.md)**
  Structure logic with directories and filenames. If you need namespaces to avoid collisions, rename your functions.
  Use `require` directly—no Composer, no magic.

  ```php
  // This is all you need to include code:
  require 'users/auth.php';  // Simple, direct, obvious
  ```

* **No config files**
  Environment variables are all you need. Let your server keep your secrets. Be explicit.

  ```php
  // No constants or define() calls
  // Access environment variables directly
  $host = getenv('DB_HOST');
  $assets_path = __DIR__ . '/public/assets';
  ```

### Routing is convention, not configuration

* **Filesystem as router**
  The URL `/user/show/42` maps to a file under `app/route/user/show.php` (or `app/route/user.php` with arguments).
  No route registration, no middleware—if you want code to run first, put it first in the file.

  **BADGE simply uses folders:**
  ```
  app/io/route/admin/users/disable.php
  app/io/route/admin/users/verify.php
  app/io/route/admin/prepare.php  // Contains auth checks for all admin routes
  ```

### PHP as template engine

* **No Blade, Twig, JSX or template DSLs**
  PHP itself is your view layer. Use:

  * `render($data, $route_file, $layout)`
  * `slot($name, $value)` and `slot($name)` / `implode(slot($name), $sep)` for injection points
  
  ```php
  // Add meta tags or scripts from anywhere:
  slot('head', '<meta name="description" content="User profile">');
  slot('scripts', '<script src="/js/profile.js"></script>');
  
  // In layout.php:
  <!DOCTYPE html>
  <html>
  <head>
      <title><?= $title ?? 'BADGE App' ?></title>
      <?= implode("\n    ", slot('head')) ?>
  </head>
  <body>
      <?= implode("\n    ", slot('main')) ?>
      <?= implode("\n    ", slot('scripts')) ?>
  </body>
  </html>
  ```

### SQL is a first-class citizen

* **No ORM, no SELECT builder, no DELETE helper**
  SQL is a language—respect it.
  Automate only what's repetitive:

  ```php
  // BADGE query-builders for repetitive operations:
  [$sql, $params] = qb_create('users', ['name' => 'John', 'email' => 'john@example.com']);
  $stmt = pdo($sql, $params);
  $user_id = pdo()->lastInsertId();
  
  [$sql, $params] = qb_update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [42]);
  $stmt = pdo($sql, $params);
  
  // But for SELECTs, write what you mean:
  $products = pdo(
      "SELECT p.*, c.name as category_name 
       FROM products p
       JOIN categories c ON p.category_id = c.id
       WHERE p.price > ? AND c.active = 1
       ORDER BY p.created_at DESC
       LIMIT 10",
      [$min_price]
  )->fetchAll();
  ```

  **Write your `SELECT` and hand-craft your destructive `DELETE`.**
  
  ```php
  // This is better than ORM abstractions:
  pdo("DELETE FROM sessions WHERE last_active < ? AND user_id = ?", 
      [date('Y-m-d H:i:s', time() - 86400), $user_id]);
  ```

### No fake architecture

* **No DI containers or service layers**
  Include a file. Pass variables. Don't summon frameworks or factories.

  **Why global functions eliminate DI needs:**
  
  1. **Simplicity**: No container setup, no binding interfaces to implementations
  2. **Direct dependencies**: Your code explicitly shows what it needs
  3. **Zero overhead**: Function calls are orders of magnitude faster than container resolution
  4. **Natural layering**: Keep related functions in well-named files that map to your domain
  5. **Pure functions**: Encourage functional programming and stateless design

* **No meta-framework**
  BADGE is not a foundation for something bigger—it *is* the final product.

---

## Routing & Request Handling

There are no controllers—only files. Each route is a PHP file that **returns a closure**. That closure is passed the URL-extracted arguments, and returns an array:

```php
['status' => int, 'body' => string, 'headers' => []]
```

### Route resolution

A request to `/secure/users/edit/42` resolves by walking the directory:

1. `app/io/route/secure/users/edit.php`
2. Or `app/io/route/secure/users.php` with `edit`, `42` as args
3. Or `app/io/route/secure.php` with `users`, `edit`, `42` as args

That file looks like:

```php
<?php
return function ($id) {
    // $id will be 42
    $user = pdo("SELECT * FROM users WHERE id = ?", [$id])->fetch();
    
    if (!$user) {
        trigger_error('404 Not Found: User not found', E_USER_ERROR);
    }
    
    return [
        'status' => 200,
        'body' => render(['user' => $user])
    ];
};
```

### Lifecycles: prepare & conclude

You may add optional `prepare.php` and `conclude.php` files at any directory level:

1. `secure/prepare.php`
2. `secure/users/prepare.php`
3. `secure/users/edit.php` (the route)
4. `secure/users/conclude.php`
5. `secure/conclude.php`

Each returns a closure. The `prepare.php` files run before the handler in top-down order, and the `conclude.php` files run after the handler in bottom-up order.

* Use `prepare.php` for auth or setup:
  ```php
  <?php
  return function() {
    if (!operator()) {
        trigger_error('403 Forbidden', E_USER_ERROR);
    }
    
    // Use function return values instead of globals
    user_set_current(pdo("SELECT * FROM users WHERE username = ?", 
                           [operator()])->fetch());
  };
  ```

* Use `conclude.php` for logging or response mutation:
  ```php
  <?php
  return function($response) {
    // Log this request
    [$sql, $params] = qb_create('access_log', [
        'url' => $_SERVER['REQUEST_URI'],
        'user_id' => user_current()['id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'status' => $response['status'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    pdo($sql, $params);
    
    // Add security headers
    $response['headers']['Content-Security-Policy'] = "default-src 'self'";
    
    return $response;
  };
  ```

### Development Mode

When `DEV_MODE` is enabled, missing routes will trigger the `scaffold()` function, which shows a list of candidate route files with template code to create them.

```php
putenv('DEV_MODE=true');
```

**What you'll see:**
```
Route not found: /products/categories/list

Create one of:
- app/io/route/products/categories/list.php
- app/io/route/products/categories.php (with 'list' as arg)
- app/io/route/products.php (with 'categories', 'list' as args)

Suggested template:
<?php
return function () {
    return [
        'status' => 200,
        'body' => render(['title' => 'Products Categories List'])
    ];
};
```

---

## UI/View Functions

BADGE provides a minimal but powerful approach to views and templating:

### Slot-based Composition

* **Push a value onto a named slot**: `slot('name', 'value')`
* **Retrieve slot values**: `slot('name')` returns array of values
* **Render slots**: `implode("\n", slot('scripts'))` to join values with newlines

Slots allow any part of your application to contribute content to designated areas in your layout, without complex passing of variables or global state.

```php
// In a view or partial file:
slot('head', '<meta name="description" content="User profile">');
slot('scripts', '<script src="/js/profile.js"></script>');

// In layout.php:
<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?? 'BADGE App' ?></title>
    <?= implode("\n    ", slot('head')) ?>
</head>
<body>
    <?= implode("\n    ", slot('main')) ?>
    <?= implode("\n    ", slot('scripts')) ?>
</body>
</html>
```

### View Rendering

The `render()` function processes PHP templates with extracted variables:

```php
// Renders view and returns HTML string
$html = render([
    'name' => $user['name'],
    'posts' => $posts
]);
```

Key details:
* Views are rendered into the `main` slot
* The layout is responsible for echoing the main slot
* Variables passed in the first argument are extracted into the view scope
* The route file path determines the corresponding view file

### HTML Element Helper

The `html()` function provides a clean way to generate HTML elements:

```php
// Simple element
echo html('div', 'Content');

// Element with attributes
echo html('a', 'Click me', ['href' => 'https://example.com', 'class' => 'btn']);

// Self-closing element
echo html('img', null, ['src' => 'logo.png', 'alt' => 'Logo']);

// Custom escaping/formatting
echo html('pre', $code, [], function($v) { return htmlentities($v); });
```

This helper handles attribute escaping and formatting while keeping your templates clean and readable.

### Combining These Features

A typical route handler using these view functions:

```php
<?php
return function ($id) {
    $user = pdo("SELECT * FROM users WHERE id = ?", [$id])->fetch();
    
    // Add page-specific metadata
    slot('head', '<meta name="author" content="' . htmlspecialchars($user['name']) . '">');
    slot('scripts', '<script src="/js/profile.js"></script>');
    
    // Render the view inside the layout
    $html = render([
        'user' => $user,
        'title' => 'Profile: ' . $user['name']
    ]);
    
    return [
        'status' => 200,
        'body' => $html
    ];
};
```

This approach keeps your views simple, your controllers focused on business logic, and your layouts in control of the overall page structure.

---

## Database Helpers

No ORM—use PDO directly. Helpers for common database operations:

### The Magic of Global Functions vs. DI Containers

BADGE's `pdo()` function demonstrates why global functions are superior to complex dependency injection.

**How it works:**

1. **First call**: `pdo($dsn, $user, $pass)` initializes the PDO connection and stores it in a static variable
2. **Subsequent calls**: `pdo()` without parameters returns the existing connection
3. **No service locator**: Unlike DI containers where you ask for dependencies, the function just works

**Basic database operations:**

```php
// Execute a prepared statement
$stmt = pdo("SELECT * FROM users WHERE id = ?", [$id]);
$user = $stmt->fetch();

// Insert data
[$sql, $params] = qb_create('users', [
    'name' => $name, 
    'email' => $email,
    'created_at' => date('Y-m-d H:i:s')
]);
$stmt = pdo($sql, $params);
$userId = pdo()->lastInsertId();

// Update data
[$sql, $params] = qb_update('posts', 
    ['title' => $title, 'content' => $content], 
    'id = ? AND user_id = ?', 
    [$postId, $userId]);
$stmt = pdo($sql, $params);

// Run operations in a transaction
pdo(function() use ($userData, $settingsData) {
    [$sql, $params] = qb_create('users', $userData);
    $stmt = pdo($sql, $params);
    $userId = $stmt->rowCount() ? pdo()->lastInsertId() : null;
    
    if (!$userId) {
        return false; // Will trigger rollback
    }
    
    [$sql, $params] = qb_create('user_settings', ['user_id' => $userId] + $settingsData);
    pdo($sql, $params);
    return true; // Will commit
});
```

Write your `SELECT`s and never automate `DELETE`.

### Mapper Pattern for Database Operations

BADGE applications typically organize database functions using the mapper pattern:

```php
// app/mapper/user.php
function user_create($data) {
    [$sql, $params] = qb_create('users', $data);
    $stmt = pdo($sql, $params);
    return pdo()->lastInsertId();
}

function user_get_by_id($id) {
    return pdo("SELECT * FROM users WHERE id = ?", [$id])->fetch();
}

function user_get_by_username($username) {
    return pdo("SELECT * FROM users WHERE username = ?", [$username])->fetch();
}

// In routes, include the mapper:
require_once __DIR__ . '/../../mapper/user.php';

$user = user_get_by_id($id);
```

---

## Authentication

BADGE provides two authentication backends and security functions:

### Authentication Backends

Choose one based on your deployment architecture:

#### HTTP Header Auth (auth_http.php)
For apps behind reverse proxies or SSO systems:
```php
require 'add/bad/auth_http.php';
// Uses $_SERVER['HTTP_X_AUTH_USER']
```

#### Database Auth (auth_sql.php)  
For traditional username/password with sessions:
```php
require 'add/bad/auth_sql.php';
// Requires users and tokens tables
```

### Basic Authentication

```php
// Basic auth check
if (!auth()) {
    trigger_error('401 Unauthorized', E_USER_ERROR);
}

// HMAC-verified auth
$username = operator();
if (!$username) {
    // Invalid or missing auth
}

// Auth middleware example (in prepare.php)
return function() {
    if (!operator()) {
        if (isset($_COOKIE['session_token'])) {
            // Try to restore from session
            $session = pdo(
                "SELECT u.username FROM sessions s
                 JOIN users u ON s.user_id = u.id
                 WHERE s.token = ? AND s.expires_at > NOW()",
                [$_COOKIE['session_token']]
            )->fetch();
            
            if ($session) {
                // Set auth headers for this request
                $_SERVER['HTTP_X_AUTH_USER'] = $session['username'];
                $_SERVER['HTTP_X_AUTH_SIG'] = /* calculate HMAC */;
            } else {
                header('Location: /login?return=' . urlencode($_SERVER['REQUEST_URI']));
                exit;
            }
        } else {
            header('Location: /login?return=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }
};
```

Authentication requires:
* `X-AUTH-USER` HTTP header
* `X-AUTH-SIG` HTTP header for HMAC verification
* `BADGE_AUTH_HMAC_SECRET` environment variable

### CSRF Protection

```php
// In forms.php
function render_form($action, $fields, $method = 'POST') {
    $html = '<form method="' . $method . '" action="' . htmlspecialchars($action) . '">';
    $html .= '<input type="hidden" name="csrf_token" value="' . csrf() . '">';
    
    // Render fields...
    
    return $html;
}

// In route that handles form submission:
if (!csrf($_POST['csrf_token'])) {
    trigger_error('403 Forbidden: Invalid CSRF', E_USER_ERROR);
}

// Now process the form...
```

### Content Security Policy

```php
// Generate a nonce for scripts
$nonce = csp_nonce();

// In layout:
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}'");

// When including scripts:
echo '<script nonce="' . $nonce . '" src="/js/app.js"></script>';
```

---

## Error Handling

BADGE uses custom error handling to convert errors to HTTP responses:

```php
// Trigger a specific HTTP error
trigger_error('404 Not Found', E_USER_ERROR);

// More detailed error
trigger_error('400 Bad Request: Invalid input', E_USER_ERROR);

// With validation messages
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    trigger_error('400 Bad Request: Invalid email address', E_USER_ERROR);
}

if (strlen($password) < 8) {
    trigger_error('400 Bad Request: Password too short (min 8 chars)', E_USER_ERROR);
}
```

Exception handling is also implemented, converting uncaught exceptions to 500 responses.

Include error handling in your entry point:
```php
require 'add/bad/error.php';
```

---

## Testing

BADGE includes a minimal testing framework:

```php
require 'add/test.php';

test('user creation works', function() {
    $id = user_create(['username' => 'test']);
    assert($id > 0);
});

test('handles errors correctly', function() {
    assert_throws(function() {
        user_create(['username' => 'duplicate']);
    }, 'PDOException', 'UNIQUE constraint');
});

run_tests();
```

See `add/doc/test.md` for complete testing guide.

---

## File Layout

```
BADGE-app/
├── add/
│   ├── bad/
│   │   ├── auth_http.php
│   │   ├── auth_sql.php
│   │   ├── db.php
│   │   ├── dev.php
│   │   ├── error.php
│   │   ├── qb.php
│   │   ├── scaffold.php
│   │   ├── security.php
│   │   └── ui.php
│   ├── core.php
│   ├── test.php
│   └── doc/
│
├── app/
│   ├── io/
│   │   ├── route/
│   │   │   ├── admin/
│   │   │   │   ├── prepare.php  // Auth for all admin routes
│   │   │   │   ├── articles/
│   │   │   │   │   ├── alter.php
│   │   │   │   │   └── articles.php
│   │   │   │   ├── events/
│   │   │   │   │   ├── alter.php
│   │   │   │   │   └── events.php
│   │   │   │   ├── resources/
│   │   │   │   └── users/
│   │   │   │       └── users.php
│   │   │   ├── blog/
│   │   │   │   ├── article.php
│   │   │   │   └── blog.php
│   │   │   ├── events/
│   │   │   │   └── event.php
│   │   │   ├── resources/
│   │   │   │   └── download.php
│   │   │   ├── contact.php
│   │   │   ├── home.php
│   │   │   ├── login.php
│   │   │   ├── logout.php
│   │   │   ├── register.php
│   │   │   └── search.php
│   │   │
│   │   └── views/
│   │       ├── layout.php
│   │       ├── admin/
│   │       │   ├── layout.php
│   │       │   ├── admin.php
│   │       │   ├── articles/
│   │       │   ├── events/
│   │       │   ├── resources/
│   │       │   └── users/
│   │       ├── blog/
│   │       ├── events/
│   │       ├── resources/
│   │       ├── contact.php
│   │       ├── login.php
│   │       └── register.php
│   │
│   ├── mapper/              // Database functions
│   │   ├── article.php
│   │   ├── category.php
│   │   ├── event.php
│   │   ├── resource.php
│   │   └── user.php
│   │
│   ├── morph/               // Data transformation
│   │   └── slugify.php
│   │
│   ├── data/                // Configuration
│   │   ├── credentials.php
│   │   └── 000-schema.sql
│   │
│   └── public/              // Web-accessible files
│       ├── index.php
│       ├── .htaccess
│       └── assets/
│           ├── css/
│           └── images/
│
└── README.md
```