# BADGE

**BADGE** is not a PHP framework. It's a refusal.

A refusal of boilerplate, magic, and engineering theater.

* No config files or metadata
* No classes, containers, or dependency injection  
* No namespaces or autoloading
* No routing tables or middleware stacks
* No templating engines or abstractions

Just **files**, **functions**, **arrays**, and **conventions**.

BADGE is ~300 lines of core code that give you everything needed to build real applications—and nothing you don't explicitly ask for.

---

## Core Principles

**Simplicity over abstraction**
- Use `require` directly—no Composer magic, no autoloading
- Structure logic with directories and filenames
- Environment variables for configuration

**Filesystem as router**  
- URL `/admin/users/edit/42` maps to `app/route/admin/users/edit.php`
- No route registration—if you want code to run first, put it first

**PHP as template engine**
- No Blade, Twig, or template DSLs
- Use `render()` with slots for composition

**SQL as first-class citizen**
- No ORM—SQL is a language, respect it
- Query builders only for repetitive operations

---

## Quick Start

### 1. Setup Structure
```bash
mkdir myapp && cd myapp
git clone https://github.com/lareponse/BADGE.git add
mkdir -p app/{io/{route,views},data,public}
```

### 2. Entry Point (`app/public/index.php`)
```php
<?php
require '../../add/core.php';
require '../../add/bad/db.php';
require '../../add/bad/ui.php';
require '../../add/bad/error.php';

// Database setup
list($dsn, $user, $pass) = require '../data/credentials.php';
db(new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]));

// Route and respond
$route = route(__DIR__ . '/../io/route');
http_respond(handle($route));
```

### 3. First Route (`app/io/route/home.php`)
```php
<?php
return function() {
    $users = dbq("SELECT * FROM users LIMIT 5")->fetchAll();
    
    return [
        'status' => 200,
        'body' => render(['users' => $users])
    ];
};
```

### 4. View (`app/io/views/home.php`)
```php
<h1>Users</h1>
<?php foreach ($users as $user): ?>
    <p><?= htmlspecialchars($user['name']) ?></p>
<?php endforeach; ?>
```

### 5. Layout (`app/io/views/layout.php`)
```php
<!DOCTYPE html>
<html>
<head>
    <title>BADGE App</title>
    <?= implode("\n", slot('head')) ?>
</head>
<body>
    <?= implode("\n", slot('main')) ?>
</body>
</html>
```

---

## Core Functions

### Database
```php
// Connect
db(new PDO($dsn, $user, $pass));

// Query
$users = dbq("SELECT * FROM users WHERE active = ?", [1])->fetchAll();

// Transaction
$result = dbt(function() {
    dbq("INSERT INTO orders (user_id) VALUES (?)", [42]);
    dbq("INSERT INTO order_items (order_id, product_id) VALUES (?, ?)", [1, 5]);
    return true;
});
```

### Routing
Routes are files that return closures:
```php
<?php
// app/io/route/users/edit.php
return function($id) {
    $user = dbq("SELECT * FROM users WHERE id = ?", [$id])->fetch();
    
    if (!$user) {
        trigger_error('404 Not Found', E_USER_ERROR);
    }
    
    return ['status' => 200, 'body' => render(['user' => $user])];
};
```

### Authentication
```php
// Protect routes with prepare.php
// app/io/route/admin/prepare.php
return function() {
    if (!operator()) {
        trigger_error('401 Unauthorized', E_USER_ERROR);
    }
};
```

### Views & Slots
```php
// Add content to layout slots
slot('head', '<meta name="description" content="User profile">');
slot('scripts', '<script src="/js/app.js"></script>');

// Render view
return ['status' => 200, 'body' => render(['user' => $user])];
```

---

## File Structure

```
myapp/
├── add/                    # BADGE framework
├── app/
│   ├── io/                # Parent folder (route + views only)
│   │   ├── route/         # Route handlers
│   │   │   ├── admin/
│   │   │   │   ├── prepare.php    # Auth for all /admin/*
│   │   │   │   └── users.php      # /admin/users
│   │   │   └── home.php           # /home or /
│   │   └── views/         # Templates
│   │       ├── layout.php
│   │       └── home.php
│   ├── data/              # Config & credentials
│   │   └── credentials.php
│   └── public/            # Web root
│       ├── index.php
│       └── .htaccess
└── README.md
```

---

## Environment Setup

```bash
# Database
export DB_DSN="mysql:host=localhost;dbname=myapp"
export DB_USER="user"
export DB_PASS="password"

# Auth (optional)
export BADGE_AUTH_HMAC_SECRET="your-secret-key"

# Development (optional)
export DEV_MODE=true
```

---

## Philosophy

BADGE doesn't scale by adding abstractions.  
It scales by composition of simple, understandable parts.

- **~300 lines** vs 10,000+ in typical frameworks
- **Direct requires** vs complex autoloading
- **Explicit control** vs magic and conventions
- **Files and functions** vs classes and containers

This is not retro. This is not hip. This is the future that was stolen.

---

## Documentation

- [Setup & Installation](add/doc/setup/quickstart.md)
- [Database Layer](add/doc/db.md)
- [Authentication](add/doc/auth.md)
- [Testing](add/doc/test.md)
- [Error Handling](add/doc/errors.md)
- [Performance Analysis](add/doc/addbad-vs-modernity.md)

## License

MIT