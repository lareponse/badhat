# BADHAT

```
Bits As Decision
HTTP As Terminal
```

A minimalist, two-phase PHP engine that treats HTTP like a terminal: route files decide, render files display. No magic, no ORM, no framework lock-in. Just \~500 lines of PHP.

---

## Philosophy

* **Separation of Concerns:** Data prep (route) vs. presentation (render).
* **Minimalism:** Direct file execution, zero dependencies.
* **Flexibility:** Swap renderers (HTML, JSON, PDF) without touching logic.
* **Explicitness:** Every file is a PHP script—no hidden layers.
* **Performance:** Millisecond routes, minimal overhead.

---

## Installation (30s)

```bash
git clone https://github.com/lareponse/BADHAT.git
cd BADHAT
mkdir -p app/io/{route,render}
```

---

## Entry Point (`public/index.php`)

```php
<?php
require 'add/io.php';

$io      = realpath(__DIR__ . '/../io');
$request = http_in();

// Phase 1: Logic
$route   = io_route("$io/route", $request, 'index');
$data    = io_fetch($route, [], IO_INVOKE);

// Phase 2: Presentation
$render  = io_route("$io/render", $request, 'index');
$html    = io_fetch($render, $data[IO_INVOKE], IO_ABSORB);

http_out(200, $html[IO_ABSORB]);
```

---

## Core Functions

* **`http_in()`**
  Parse & validate URI, query params, POST data, headers.

* **`http_out($status, $body, $headers = [])`**
  Send HTTP response and exit.

* **`io_route($dir, $request, $default)`**
  Map URI to a PHP file (supports dynamic args & fallbacks).

* **`io_fetch($file, $vars, $flag)`**
  Invoke or absorb a script:

  * **`IO_INVOKE`**: call returned closure with args → returns data array.
  * **`IO_ABSORB`**: buffer output, include file, execute returned closure (if any), collect HTML.

---

## Execution Modes

### 1. Default View-Only

No flags → include render template directly; echo output is sent immediately. Ideal for static pages.

### 2. Two-Act Play

* **Act I (IO_INVOKE):** route returns an array of data.
* **Act II (IO_ABSORB):** render buffers output, invokes closures, collects HTML, then sends it.

### 3. API-Only Performance

Skip render. In your route (invoked with IO_INVOKE), gather data, `json_encode()` it, call `http_out()`, and exit.

---

## Optional Phasing

* **Static pages or pages with mostly rendering logic:** skip the route phase—just drop in a render template.
* **SSR Partials for AJAX:** route can return pre-rendered HTML or closures; render integrates or you can `http_out()` directly in the route.

---

## Routing Examples

```
/                  → app/io/route/index.php
/about             → app/io/route/about.php

/users/edit/42     → app/io/route/users/edit.php (args: ['42'])
/api/posts/123/tag → app/io/route/api/posts.php (args: ['123','tag'])

/deep/missing/path → tries:
  • app/io/route/deep/missing/path.php
  • app/io/route/deep/missing/path/path.php
  • app/io/route/deep/missing.php           (args: ['path'])
  • app/io/route/deep/missing/missing.php   (args: ['path'])
  • app/io/route/deep.php                   (args: ['missing','path'])
  • app/io/route/deep/deep.php              (args: ['missing','path'])
  • app/io/route/index.php                  (args: ['deep','missing','path'])
```

---

## Real-World Patterns

### API Endpoint

```php
// app/io/route/api/users.php
return fn($args) => header('Content-Type: application/json')
    && exit(json_encode(
        dbq(db(), "SELECT id,name FROM users LIMIT 10")->fetchAll()
    ));
```

### Form Processing

```php
// app/io/route/contact.php
return function($args) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'] ?: throw new Exception('Name required');
        dbq(db(), "INSERT INTO contacts (name,email) VALUES (?,?)",
            [$name, $_POST['email']]);
        header('Location:/contact/thanks');
        exit;
    }
    return ['csrf_token' => csrf_token()];
};
```

### Admin Middleware

```php
// app/io/route/admin/users.php
return function($args) {
    auth() ?: http_out(401, 'Login required');
    return ['users' => dbq(db(), "SELECT * FROM users")->fetchAll()];
};
```

---

## Why BADHAT?

* **Simplicity:** \~500 lines of core, zero bloat.
* **Performance:** Direct file execution, minimal overhead.
* **Flexibility:** Swap or reuse renderers.
* **Control:** Full-stack ownership, no hidden magic.
* **No Dependencies:** Pure PHP.
* **No Learning Curve:** If you know PHP, you know BADHAT.

---

## When to Use

* **Go:** High-performance APIs, admin UIs, prototypes, small teams (1–10 devs), direct SQL.
* **Maybe:** Apps up to 50k LOC, read-heavy, simple domains.
* **Avoid:** Enterprise compliance, massive teams (100+), heavy third-party integrations, static marketing sites.

[Sample projects](readme-sample-projects.md)

---

## File Structure

```
project/
├── add/                    # BADHAT core
├── app/
│   └── io/
│       ├── route/         # Logic handlers
│       └── render/        # Presentation templates
└── public/
    └── index.php          # Entry point
```

---

## License

MIT. No warranty. Use at your own risk.
