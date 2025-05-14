# ADDBAD — A Procedural Micro-Framework for Serious Developers

**ADDBAD** is not a framework. It's a refusal.

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

---

## Core Principles

### Simplicity over abstraction

* **No classes, no namespaces, no autoloading**
  Structure logic with directories and filenames. If you need namespaces to avoid collisions, rename your functions.
  Use `require` directly—no Composer, no magic.

* **No config files**
  Define paths with `define()`. Let your server keep your secrets. Be explicit.

### Routing is convention, not configuration

* **Filesystem as router**
  The URL `/user/show/42` maps to a file under `app/route/user/show.php` (or `app/route/user.php` with arguments).
  No route registration, no middleware—if you want code to run first, put it first in the file.

### PHP as template engine

* **No Blade, Twig, JSX or template DSLs**
  PHP itself is your view layer. Use:

  * `render('viewname', $data, $layout)`
  * `slot($name, $value)` and `slot($name)` / `implode(slot($name), $sep)` for injection points

### SQL is a first-class citizen

* **No ORM, no SELECT builder, no DELETE helper**
  SQL is a language—respect it.
  Automate only what's repetitive:

  ```php
  $stmt = db_create('table', ['a' => 1, 'b' => 2]);
  $stmt = db_update('table', ['a' => 1], 'id = ?', [42]);
  ```

  Write your `SELECT` and hand-craft your destructive `DELETE`.

### No fake architecture

* **No DI containers or service layers**
  Include a file. Pass variables. Don't summon frameworks or factories.

* **No meta-framework**
  ADDBAD is not a foundation for something bigger—it *is* the final product.

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
    return [
        'status' => 200,
        'body' => render('users/edit', ['id' => $id], 'layout')
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
  return function() {
    if (!operator()) {
        trigger_error('403 Forbidden', E_USER_ERROR);
    }
  };
  ```

* Use `conclude.php` for logging or response mutation:
  ```php
  return function($response) {
    // Add logging, modify headers, etc.
    return $response;
  };
  ```

### Development Mode

When `DEV_MODE` is enabled, missing routes will trigger the `scaffold()` function, which shows a list of candidate route files with template code to create them.

```php
putenv('DEV_MODE=true');
```

---

## Views

* **Render a view**: `render('viewname', $data, $layout)`
* **Layout**: Specified as the third parameter to `render()`
* **Slots**:
  * `slot('head', '<meta>')` to push content to a slot
  * `slot('head')` to retrieve slot values as array
  * Use `implode(slot('head'), "\n")` to join slot values

Use slots for injecting `<meta>`, scripts, toolbars, footers, etc.

---

## Database Helpers

No ORM—use PDO directly. Helpers for common database operations:

```php
// Get a PDO instance
$pdo = db($dsn, $user, $pass);

// Execute a prepared statement
$stmt = db_state("SELECT * FROM users WHERE id = ?", [$id]);

// Insert data
$stmt = db_create('users', ['name' => $name, 'email' => $email]);

// Update data
$stmt = db_update('posts', ['title' => $t], 'id = ?', [$postId]);

// Run operations in a transaction
db_transaction(function() {
    db_create(...);
    db_update(...);
    return true; // commit if true, rollback if false
});
```

Write your `SELECT`s and never automate `DELETE`.

---

## Security

ADDBAD provides several security functions:

### Authentication

```php
// Basic auth check
if (!auth()) {
    // Will trigger 401 Unauthorized error
}

// HMAC-verified auth
$username = operator();
if (!$username) {
    // Invalid or missing auth
}
```

Authentication requires:
* `X-AUTH-USER` HTTP header
* `X-AUTH-SIG` HTTP header for HMAC verification
* `ADDBAD_AUTH_HMAC_SECRET` environment variable

### CSRF Protection

```php
// Generate a token
$token = csrf(); // Returns base64 string

// Validate a token
if (!csrf($_POST['csrf_token'])) {
    trigger_error('403 Forbidden: Invalid CSRF', E_USER_ERROR);
}
```

### Content Security Policy

```php
// Generate a nonce for scripts
$nonce = csp_nonce();
```

---

## Error Handling

ADDBAD uses custom error handling to convert errors to HTTP responses:

```php
// Trigger a specific HTTP error
trigger_error('404 Not Found', E_USER_ERROR);

// More detailed error
trigger_error('400 Bad Request: Invalid input', E_USER_ERROR);
```

Exception handling is also implemented, converting uncaught exceptions to 500 responses.

---

## File Layout

```
addbad-app/
├── add/
│   ├── bad/
│   │   ├── db.php
│   │   ├── security.php
│   │   └── ui.php
│   └── core.php
│
├── app/
│   ├── render/
│   └── route/
│
├── public/
│   ├── index.php
│   └── .htaccess
│
└── README.md
```

### Entry Point (index.php)

```php
<?php
putenv('DEV_MODE=true'); // Optional for development

require '../add/core.php';
require '../add/bad/ui.php';
require '../add/bad/security.php';

try {
    $response = handle(route(realpath(__DIR__ . '/../app/route')));
    respond($response);
} catch (Throwable $e) {
    respond([
        'status' => 500,
        'body' => 'Internal Server Error: ' . $e->getMessage()
    ]);
}
```

---

## License

Use it, fork it, ignore it—just don't automate `DELETE` and then blame the framework.

Made with precision and refusal by **La Reponse**.