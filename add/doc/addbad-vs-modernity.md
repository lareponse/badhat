# BADGE vs Modern Frameworks

This document provides code comparisons between BADGE and popular modern PHP frameworks, showcasing the simplicity of BADGE's design philosophy.

## Code Comparison: BADGE vs. Modern Frameworks

### Entity/Data Model Definition

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

**BADGE:**

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

### Routing Definition

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

**BADGE:**

```php
// app/route/users/show.php
<?php
return function ($id) {
    if (!auth()) {
        trigger_error('401 Unauthorized', E_USER_ERROR);
    }

    $user = pdo("SELECT * FROM users WHERE id = ?", [$id])->fetch();

    return [
        'status' => 200,
        'body' => render('users/show', ['user' => $user])
    ];
};
```

**More complex routes in modern frameworks:**

```php
// Modern frameworks
Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'admin']], function () {
    Route::resource('users', AdminUserController::class);
    Route::post('users/{id}/disable', [AdminUserController::class, 'disable']);
    Route::post('users/{id}/verify', [AdminUserController::class, 'verify']);
});
```

**BADGE's simple folder structure:**

```
app/route/admin/users/disable.php
app/route/admin/users/verify.php
app/route/admin/prepare.php  // Contains auth checks for all admin routes
```

### Templating Systems

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

**BADGE:**

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

### Database Operations

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

**BADGE:**

```php
$user = pdo("SELECT * FROM users WHERE email = ?", [$email])->fetch();

$update_data = [
    'last_login' => date('Y-m-d H:i:s'),
    'login_count' => $user['login_count'] + 1
];
$stmt = pdo(...qb_update('users', $update_data$, 'id = ?', [$user['id']]));
```

### Dependency Injection

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

**BADGE:**

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

### Database Connection

**Modern frameworks:**

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

**BADGE:**

```php
// Initialize once in your entry point
pdo('mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME'),
   getenv('DB_USER'),
   getenv('DB_PASS'));

// Use anywhere without importing/injecting
function get_user($id) {
    return pdo("SELECT * FROM users WHERE id = ?", [$id])->fetch();
}
```

## Real-World Example: Multi-tenant CMS

**Modern framework approach:** 3,000+ files, 50+ database tables, complex migrations, 100+ service classes

**BADGE approach:**

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
    $sites = pdo("SELECT COUNT(*) FROM sites")->fetchColumn();
    $users = pdo("SELECT COUNT(*) FROM users")->fetchColumn();
    $pages = pdo("SELECT COUNT(*) FROM pages")->fetchColumn();

    // Get recent activity
    $activity = pdo(
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

    $user = pdo("SELECT * FROM users WHERE username = ?", [$username])->fetch();
    if (!$user || $user['role'] !== 'admin') {
        trigger_error('403 Forbidden: Admin access required', E_USER_ERROR);
    }
};
```

## Benchmarks: BADGE vs. Popular Frameworks

| Framework  | Request Time | Memory Usage | Files Loaded |
| ---------- | ------------ | ------------ | ------------ |
| Laravel 10 | 120-180ms    | 20-32MB      | 800-1200     |
| Symfony 6  | 90-120ms     | 15-22MB      | 600-900      |
| BADGE      | 5-8ms        | 1-2MB        | 4-15         |

_Note: Benchmarks performed on a simple "show user profile" page with database access._
