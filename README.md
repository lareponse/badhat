# ADDBAD — A Procedural Micro-Framework for Serious Developers

**ADDBAD** is not a php framework. It's a refusal.

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
ADDBAD is \~200 lines of core code that give you everything required to build real applications—and nothing you don't explicitly ask for.

This is not retro. This is not hip. This is the future that was stolen. We're taking it back.

---

## Is ADDBAD for you?

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


## Core Principles: The How & Why

### Simplicity over abstraction

**Modern frameworks:**
```php
// First, install 87 dependencies
composer require framework/core framework/router framework/orm framework/validation

// Create a new entity
namespace App\Entity;

use Framework\ORM\Annotations\Entity;
use Framework\ORM\Annotations\Column;
use Framework\ORM\Annotations\GeneratedValue;
use Framework\ORM\Annotations\Id;

/**
 * @Entity
 * @Table(name="users")
 */
class User implements JsonSerializable
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Column(type="string", length=255, nullable=false)
     */
    private $name;

    // Plus another 50 lines of getters/setters
}
```

**ADDBAD:**
```php
// A simple contact form submission
function save_contact($data) {
    $stmt = db_create('contacts', [
        'name' => $data['name'],
        'email' => $data['email'],
        'message' => $data['message'],
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    return $stmt->rowCount() > 0;
}
```

**Why it matters:** If you need 2 million lines of dependencies to build a contact form, you're doing it wrong. Modern frameworks have abstracted away essential knowledge, leaving developers dependent on magical layers they don't understand.

* **No classes, no namespaces, no autoloading**
  Structure logic with directories and filenames. If you need namespaces to avoid collisions, rename your functions.
  Use `require` directly—no Composer, no magic.

  ```php
  // This is all you need to include code:
  require 'users/auth.php';  // Simple, direct, obvious
  ```

* **No config files**
  Define paths with `define()`. Let your server keep your secrets. Be explicit.

  ```php
  // Modern frameworks:
  // config/database.yml
  // config/routes.rb
  // config/environments/production.rb
  // ...50 more config files

  // ADDBAD:
  define('DB_HOST', getenv('DB_HOST'));
  define('ASSETS_PATH', __DIR__ . '/public/assets');
  ```

### Routing is convention, not configuration

**Modern frameworks:**
```php
// routes.php or routes.yaml
$router->get('/users/{id}', [UserController::class, 'show'])
    ->middleware(AuthMiddleware::class)
    ->name('users.show');

// UserController.php
namespace App\Controllers;

class UserController extends BaseController
{
    public function show($id)
    {
        // Layers of abstraction...
    }
}
```

**ADDBAD:**
```php
// app/route/users/show.php
<?php
return function ($id) {
    if (!auth()) {
        trigger_error('401 Unauthorized', E_USER_ERROR);
    }
    
    $user = db_exec("SELECT * FROM users WHERE id = ?", [$id])->fetch();
    
    return [
        'status' => 200,
        'body' => render('users/show', ['user' => $user])
    ];
};
```

* **Filesystem as router**
  The URL `/user/show/42` maps to a file under `app/route/user/show.php` (or `app/route/user.php` with arguments).
  No route registration, no middleware—if you want code to run first, put it first in the file.

  **Look at what happens when routes get complex in modern frameworks:**
  ```php
  // Modern frameworks
  Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'admin']], function () {
      Route::resource('users', AdminUserController::class);
      Route::post('users/{id}/disable', [AdminUserController::class, 'disable']);
      Route::post('users/{id}/verify', [AdminUserController::class, 'verify']);
  });
  ```

  **ADDBAD simply uses folders:**
  ```
  app/route/admin/users/disable.php
  app/route/admin/users/verify.php
  app/route/admin/prepare.php  // Contains auth checks for all admin routes
  ```

### PHP as template engine

**Modern frameworks (e.g., Blade):**
```php
@extends('layouts.app')

@section('content')
    <h1>Welcome, {{ $user->name }}</h1>
    
    @foreach($posts as $post)
        <div class="post">
            <h2>{{ $post->title }}</h2>
            {!! $post->content !!}
        </div>
    @endforeach
@endsection
```

**ADDBAD:**
```php
<!-- views/users/profile.php -->
<h1>Welcome, <?= htmlspecialchars($name) ?></h1>

<?php foreach ($posts as $post): ?>
    <div class="post">
        <h2><?= htmlspecialchars($post['title']) ?></h2>
        <?= $post['content'] ?>
    </div>
<?php endforeach; ?>

<!-- In your route: -->
<?php
return function ($username) {
    $user = get_user($username);
    $posts = get_user_posts($username);
    
    return [
        'status' => 200,
        'body' => render('users/profile', [
            'name' => $user['name'],
            'posts' => $posts
        ], 'layout')
    ];
};
```

* **No Blade, Twig, JSX or template DSLs**
  PHP itself is your view layer. Use:

  * `render('viewname', $data, $layout)`
  * `slot($name, $value)` and `slot($name)` / `implode(slot($name), $sep)` for injection points
  
  ```php
  // Add meta tags or scripts from anywhere:
  slot('head', '<meta name="description" content="User profile">');
  slot('scripts', '<script src="/js/profile.js"></script>');
  
  // In layout.php:
  <!DOCTYPE html>
  <html>
  <head>
      <title><?= $title ?? 'ADDBAD App' ?></title>
      <?= implode("\n    ", slot('head')) ?>
  </head>
  <body>
      <?= $content ?? '' ?>
      <?= implode("\n    ", slot('scripts')) ?>
  </body>
  </html>
  ```

### SQL is a first-class citizen

**Modern frameworks (with ORM):**
```php
// First, define a 100-line entity class
// Then, define a 50-line repository class
// Finally:

$user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
$user->setLastLogin(new \DateTime());
$user->incrementLoginCount();
$entityManager->persist($user);
$entityManager->flush();
```

**ADDBAD:**
```php
$user = db_exec("SELECT * FROM users WHERE email = ?", [$email])->fetch();
db_update('users', [
    'last_login' => date('Y-m-d H:i:s'),
    'login_count' => $user['login_count'] + 1
], 'id = ?', [$user['id']]);
```

* **No ORM, no SELECT builder, no DELETE helper**
  SQL is a language—respect it.
  Automate only what's repetitive:

  ```php
  // ADDBAD helpers for common patterns:
  $stmt = db_create('table', ['a' => 1, 'b' => 2]);
  $stmt = db_update('table', ['a' => 1], 'id = ?', [42]);
  
  // But for SELECTs, write what you mean:
  $products = db_exec(
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
  db_exec("DELETE FROM sessions WHERE last_active < ? AND user_id = ?", 
         [date('Y-m-d H:i:s', time() - 86400), $user_id]);
  ```

### No fake architecture

**Modern frameworks:**
```php
// ServiceProvider.php
namespace App\Providers;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(PaymentGateway::class, function ($app) {
            return new StripePaymentGateway(
                $app->make(ApiClient::class),
                $app->make(Logger::class),
                config('services.stripe.key')
            );
        });
    }
}

// Controller
public function __construct(
    private PaymentGateway $paymentGateway,
    private OrderRepository $orderRepository
) {}

// Using the service
public function processOrder(Request $request)
{
    $this->paymentGateway->charge(
        $this->orderRepository->find($request->order_id),
        $request->token
    );
}
```

**ADDBAD:**
```php
// payment.php
function process_payment($order_id, $amount, $card) {
    require_once 'gateways/stripe.php';  // Include what you need
    
    $api_key = getenv('STRIPE_API_KEY');
    $result = stripe_charge($api_key, $amount, $card);
    
    return $result;
}

// In route:
$result = process_payment($order_id, $amount, $card_data);
```

* **No DI containers or service layers**
  Include a file. Pass variables. Don't summon frameworks or factories.

  **Why global functions eliminate DI needs:**
  
  1. **Simplicity**: No container setup, no binding interfaces to implementations
  2. **Direct dependencies**: Your code explicitly shows what it needs
  3. **Zero overhead**: Function calls are orders of magnitude faster than container resolution
  4. **Natural layering**: Keep related functions in well-named files that map to your domain
  5. **Pure functions**: Encourage functional programming and stateless design

* **No meta-framework**
  ADDBAD is not a foundation for something bigger—it *is* the final product.

---

## Real-World Example: Multi-tenant CMS

**Modern framework approach:** 3,000+ files, 50+ database tables, complex migrations, 100+ service classes

**ADDBAD approach:**

```
app/
├── route/
│   ├── admin/
│   │   ├── prepare.php             # Auth check for all admin routes
│   │   ├── dashboard.php           # Admin dashboard
│   │   ├── sites/
│   │   │   ├── create.php          # Site creation form + handler
│   │   │   ├── edit.php            # Site edit form + handler
│   │   │   └── delete.php          # Site deletion
│   │   └── users/
│   │       ├── create.php
│   │       ├── edit.php
│   │       └── permissions.php
│   ├── site/
│   │   ├── prepare.php             # Site resolver based on hostname
│   │   ├── home.php                # Dynamic homepage
│   │   └── page.php                # Dynamic page renderer
│   ├── login.php                   # Login form + handler
│   └── logout.php                  # Logout handler
├── views/
│   ├── layout.php                  # Main layout
│   ├── admin/
│   │   ├── dashboard.php
│   │   ├── sites/
│   │   │   ├── form.php            # Reused for create/edit 
│   │   │   └── list.php
│   │   └── users/
│   │       ├── form.php
│   │       └── list.php
│   └── site/
│       ├── home.php
│       └── page.php
└── functions/
    ├── auth.php                    # Authentication functions
    ├── sites.php                   # Site management functions
    └── pages.php                   # Page rendering logic
```

**Admin dashboard route (admin/dashboard.php):**
```php
<?php
return function () {
    // Get site stats
    $sites = db_exec("SELECT COUNT(*) FROM sites")->fetchColumn();
    $users = db_exec("SELECT COUNT(*) FROM users")->fetchColumn();
    $pages = db_exec("SELECT COUNT(*) FROM pages")->fetchColumn();
    
    // Get recent activity
    $activity = db_exec(
        "SELECT u.username, a.action, a.entity_type, a.created_at
         FROM activity_log a
         JOIN users u ON a.user_id = u.id
         ORDER BY a.created_at DESC LIMIT 10"
    )->fetchAll();
    
    return [
        'status' => 200,
        'body' => render('admin/dashboard', [
            'stats' => [
                'sites' => $sites,
                'users' => $users,
                'pages' => $pages
            ],
            'activity' => $activity
        ], 'admin-layout')
    ];
};
```

**Authentication (admin/prepare.php):**
```php
<?php
return function () {
    $username = operator();
    if (!$username) {
        header('Location: /login?return=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    
    $user = db_exec("SELECT * FROM users WHERE username = ?", [$username])->fetch();
    if (!$user || $user['role'] !== 'admin') {
        trigger_error('403 Forbidden: Admin access required', E_USER_ERROR);
    }
};
```

---

## Benchmarks: ADDBAD vs. Popular Frameworks

| Framework     | Request Time | Memory Usage | Files Loaded |
|---------------|--------------|--------------|--------------|
| Laravel 10    | 120-180ms    | 20-32MB      | 800-1200     |
| Symfony 6     | 90-120ms     | 15-22MB      | 600-900      |
| ADDBAD        | 5-8ms        | 1-2MB        | 4-15         |

*Note: Benchmarks performed on a simple "show user profile" page with database access.*

---

## Routing & Request Handling

There are no controllers—only files. Each route is a PHP file that **returns a closure**. That closure is passed the URL-extracted arguments, and returns an array:

```php
['status' => int, 'body' => string, 'headers' => []]
```

### Route resolution

A request to `/secure/users/edit/42` resolves by walking the directory:

1. `app/route/secure/users/edit.php`
2. Or `app/route/secure/users.php` with `edit`, `42` as args
3. Or `app/route/secure.php` with `users`, `edit`, `42` as args

That file looks like:

```php
<?php
return function ($id) {
    // $id will be 42
    $user = db_exec("SELECT * FROM users WHERE id = ?", [$id])->fetch();
    
    if (!$user) {
        trigger_error('404 Not Found: User not found', E_USER_ERROR);
    }
    
    return [
        'status' => 200,
        'body' => render('users/edit', ['user' => $user], 'layout')
    ];
};
```

**Compare with Laravel:**
```php
// routes/web.php
Route::get('/secure/users/{id}/edit', [UserController::class, 'edit'])
    ->middleware(['auth', 'can:edit-users']);

// app/Http/Controllers/UserController.php
public function edit($id)
{
    $user = User::findOrFail($id);
    return view('users.edit', compact('user'));
}
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
    
    // Set a global for the rest of the chain
    $GLOBALS['current_user'] = db_exec("SELECT * FROM users WHERE username = ?", 
                                     [operator()])->fetch();
  };
  ```

* Use `conclude.php` for logging or response mutation:
  ```php
  <?php
  return function($response) {
    // Log this request
    db_create('access_log', [
        'url' => $_SERVER['REQUEST_URI'],
        'user_id' => $GLOBALS['current_user']['id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'status' => $response['status'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
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
- app/route/products/categories/list.php
- app/route/products/categories.php (with 'list' as arg)
- app/route/products.php (with 'categories', 'list' as args)

Suggested template:
<?php
return function () {
    return [
        'status' => 200,
        'body' => render('products/categories/list', [])
    ];
};
```

---

## Views

* **Render a view**: `render('viewname', $data, $layout)`
* **Layout**: Specified as the third parameter to `render()`
* **Slots**:
  * `slot('head', '<meta>')` to push content to a slot
  * `slot('head')` to retrieve slot values as array
  * Use `implode(slot('head'), "\n")` to join slot values

**Example layout.php:**
```php
<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?? 'My App' ?></title>
    <?= implode("\n    ", slot('head')) ?>
</head>
<body>
    <header>
        <?= implode("\n", slot('header')) ?>
    </header>
    
    <main>
        <?= $content ?? '' ?>
    </main>
    
    <footer>
        <?= implode("\n", slot('footer')) ?>
    </footer>
    
    <?= implode("\n    ", slot('scripts')) ?>
</body>
</html>
```

**Using slots from anywhere:**
```php
// In a route file:
slot('head', '<meta name="description" content="User profile page">');
slot('scripts', '<script src="/js/profile.js"></script>');

// In a view file:
slot('header', '<h1>Welcome back!</h1>');
```

---

## Database Helpers

No ORM—use PDO directly. Helpers for common database operations:

### The Magic of Global Functions vs. DI Containers

ADDBAD's `db()` function demonstrates why global functions are superior to complex dependency injection:

```php
// In add/bad/db.php
function db($dsn = null, $user = null, $pass = null) {
    static $pdo = null;
    
    // First call initializes the connection
    if ($dsn && $pdo === null) {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    }
    
    // Subsequent calls just return the existing connection
    if ($pdo === null) {
        trigger_error("Database not initialized", E_USER_ERROR);
    }
    
    return $pdo;
}

// Helper functions that use the global db() function
function db_exec($query, $params = []) {
    $stmt = db()->prepare($query);
    $stmt->execute($params);
    return $stmt;
}

function db_create($table, $data) {
    $fields = array_keys($data);
    $placeholders = array_fill(0, count($fields), '?');
    
    $sql = sprintf(
        "INSERT INTO %s (%s) VALUES (%s)",
        $table,
        implode(', ', $fields),
        implode(', ', $placeholders)
    );
    
    return db_exec($sql, array_values($data));
}
```

**How it works:**

1. **First call**: `db($dsn, $user, $pass)` initializes the PDO connection and stores it in a static variable
2. **Subsequent calls**: `db()` without parameters returns the existing connection
3. **No service locator**: Unlike DI containers where you ask for dependencies, the function just works

**Modern frameworks vs. ADDBAD:**

Modern frameworks:
```php
// Configuration (config/database.php)
return [
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            // ...more config
        ],
    ],
];

// Service provider registration
$this->app->singleton('db', function ($app) {
    return new DatabaseManager($app, $app['db.factory']);
});

// Dependency injection in controllers
class UserController extends Controller
{
    private $db;
    
    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }
    
    public function show($id)
    {
        $user = $this->db->table('users')->find($id);
        // ...
    }
}
```

ADDBAD:
```php
// Initialize once in your entry point
db('mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME'), 
   getenv('DB_USER'), 
   getenv('DB_PASS'));

// Use anywhere without importing/injecting
function get_user($id) {
    return db_exec("SELECT * FROM users WHERE id = ?", [$id])->fetch();
}
```

**Why global functions are better than DI:**

1. **Explicit, not implicit**: There's no magic happening behind the scenes
2. **No configuration hell**: No XML, YAML, or PHP config files defining services
3. **No autowiring complexity**: No need for attributes/annotations to guide the container
4. **No performance overhead**: No container lookups, no proxy objects, no reflection
5. **Testable**: Can be mocked just as easily as DI with function overrides
6. **Discoverable**: Use your IDE to find all calls to `db()` - try doing that with injected dependencies!

**Basic database operations:**

```php
// Execute a prepared statement
$stmt = db_exec("SELECT * FROM users WHERE id = ?", [$id]);
$user = $stmt->fetch();

// Insert data
$stmt = db_create('users', [
    'name' => $name, 
    'email' => $email,
    'created_at' => date('Y-m-d H:i:s')
]);
$userId = db()->lastInsertId();

// Update data
$stmt = db_update('posts', 
    ['title' => $title, 'content' => $content], 
    'id = ? AND user_id = ?', 
    [$postId, $userId]
);

// Run operations in a transaction
db_transaction(function() use ($userData, $settingsData) {
    $userId = db_create('users', $userData)->rowCount() ? db()->lastInsertId() : null;
    
    if (!$userId) {
        return false; // Will trigger rollback
    }
    
    db_create('user_settings', ['user_id' => $userId] + $settingsData);
    return true; // Will commit
});
```

**Why write SQL directly?**

```php
// With ORM:
$users = User::with(['posts' => function($query) {
    $query->where('active', true)->orderBy('created_at', 'desc');
}])->whereHas('role', function($query) {
    $query->where('name', 'editor');
})->get();

// With ADDBAD:
$users = db_exec(
    "SELECT u.*, GROUP_CONCAT(p.id) as post_ids
     FROM users u
     JOIN roles r ON u.role_id = r.id
     LEFT JOIN posts p ON p.user_id = u.id AND p.active = 1
     WHERE r.name = ?
     GROUP BY u.id
     ORDER BY u.name",
    ['editor']
)->fetchAll();

// Fetch any posts if needed
if ($users) {
    $postIds = [];
    foreach ($users as $user) {
        if ($user['post_ids']) {
            $postIds = array_merge($postIds, explode(',', $user['post_ids']));
        }
    }
    
    if ($postIds) {
        $posts = db_exec(
            "SELECT * FROM posts WHERE id IN (" . implode(',', array_fill(0, count($postIds), '?')) . ")
             ORDER BY created_at DESC",
            $postIds
        )->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
        
        // Attach posts to users
        foreach ($users as &$user) {
            $user['posts'] = $posts[$user['id']] ?? [];
        }
    }
}
```

Write your `SELECT`s and never automate `DELETE`.

---

## Security

ADDBAD provides several security functions:

### Authentication

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
            $session = db_exec(
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
* `ADDBAD_AUTH_HMAC_SECRET` environment variable

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

ADDBAD uses custom error handling to convert errors to HTTP responses:

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

---

## File Layout

```
addbad-app/
├── add/
│   ├── bad/
│   │   ├── db.php
│   │   ├── dev.php
│   │   ├── security.php
│   │   └── ui.php
│   └── core.php
│
├── app/
│   ├── route/
│   │   ├── admin/
│   │   │   ├── prepare.php  // Auth for all admin routes
│   │   │   └── users/
|   │   │       ├── create.php
|   │   │       ├── read.php
|   │   │       ├── update.php
│   │   │       └── disable.php
│   │   ├── users/
│   │   │   ├── account.php
│   │   │   ├── bills.php
│   │   │   └── profile.php
│   │   ├── contact.php
│   │   ├── home.php
│   │   └── catalog.php
│   │
│   ├── prepare.php  // Global setup for all routes
│   ├── conclude.php // Global cleanup for all routes
│   └── views/
│       ├── layout.php
│       ├── admin/
│       │   └── users/
│       │       ├── form.php
│       │       └── list.php
│       ├── users/
│       │   ├── account.php
│       │   └── profile.php
│       ├── contact.php
│       └── home.php
├── public/
│   ├── index.php
│   ├── assets/
│   └── .htaccess
│
└── README.md
