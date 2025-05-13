# ADDBAD — A Procedural Micro-Framework for Serious Developers

**ADDBAD** is not a framework. It’s a refusal.

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
ADDBAD is \~80 lines of core code that give you everything required to build real applications—and nothing you don’t explicitly ask for.

---

## Core Principles

### Simplicity over abstraction

* **No classes, no namespaces, no autoloading**
  Structure logic with directories and filenames. If you need namespaces to avoid collisions, rename your functions.
  Use `require` directly—no Composer, no magic.

* **No config files**
  Define paths with `define()`. Manage secrets in `.env`. Be explicit.

### Routing is convention, not configuration

* **Filesystem as router**
  The URL `/user/show/42` maps to a file under `app/controller/user/show.php` (or `app/controller/user.php` with arguments).
  No route registration, no middleware—if you want code to run first, put it first in the file.

### PHP as template engine

* **No Blade, Twig, JSX or template DSLs**
  PHP itself is your view layer. Use:

  * `render('viewname', $data)`
  * `partial('name', $data)` to load `_name.php`
  * `slot($name, $value)` and `slot($name)` / `slots($name, $sep)` for injection points

### SQL is a first-class citizen

* **No ORM, no SELECT builder, no DELETE helper**
  SQL is a language—respect it.
  Automate only what’s repetitive:

  ```php
  [$sql, $params] = qb_insert('table', ['a' => 1, 'b' => 2]);
  [$sql, $params] = qb_update('table', ['a' => 1], 'id = ?', [42]);
  ```

  Write your `SELECT` and hand-craft your destructive `DELETE`.

### No fake architecture

* **No DI containers or service layers**
  Include a file. Pass variables. Don’t summon frameworks or factories.

* **No meta-framework**
  ADDBAD is not a foundation for something bigger—it *is* the final product.

---

## Is ADDBAD for you?

**Yes**, if:

* You’d rather write 10 lines of clear code than configure a container.
* You treat SQL as a language, not a leaky abstraction.
* You believe the filesystem is a perfectly good routing mechanism.
* You understand what `require` does—and that it's enough.
* You want to know exactly what happens when a request hits your server.

**No**, if:

* You need framework magic or auto-generated classes.
* You prefer “clean architecture” over readable code.
* You reach for `composer require` before writing a function.

---

## Controllers & Routing

There are no controllers—only files. Each route is a PHP file that **returns a closure**. That closure is passed the request and any URL-extracted arguments, and returns either:

* An HTML string, or
* An array: `['status' => int, 'headers' => [], 'body' => string]`

### Route resolution

A request to `/secure/users/edit/42` resolves by walking the directory:

1. `app/controller/secure/users/edit.php`
2. Or `app/controller/secure/users.php` with `edit`, `42` as args
3. Or `app/controller/secure.php` with `users`, `edit`, `42`

That file looks like:

```php
<?php
return function ($req, $id) {
    return render('users/edit', ['id' => $id]);
};
```

### Lifecycles: prepare & conclude

You may add optional `prepare.php` and `conclude.php` files at any directory level:

1. `secure/prepare.php`
2. `secure/users/prepare.php`
3. `secure/users/edit.php` (the route)
4. `secure/users/conclude.php`
5. `secure/conclude.php`

Each returns a closure. Use `prepare.php` for auth or setup, and `conclude.php` for logging or response mutation.

---

## Views

* **Render a view**: `render('viewname', $data)`
* **Layout**: `layout.php` wraps your `$content`; swap in `secure-layout.php` if needed.
* **Partials**: `partial('name', $data)` loads `_name.php`.
* **Slots**:

  * `slot('head', '<meta>')`
  * `slot('head')` to retrieve last, or `slots('head', "\n")` to join all.

Use slots for injecting `<meta>`, scripts, toolbars, footers, etc.

---

## Database Helpers

No ORM—use PDO directly. Helpers only for repetitive `INSERT`/`UPDATE`:

```php
[$sql, $params] = qb_insert('users', ['name' => $name, 'email' => $email]);
[$sql, $params] = qb_update('posts', ['title' => $t], 'id = ?', [$postId]);
```

Write your `SELECT`s and never automate `DELETE`.

---

## Authentication & Access Control

ADDBAD uses a **single HTTP header** for auth:

* A reverse proxy or upstream sets `X-AUTH-USER`.
* Your code trusts that header—no sessions, no cookies, no DB lookups in-app.
* In `controller/secure/*.php`:

  ```php
  if (!is_authenticated()) {
      return ['status' => 403, 'body' => 'Forbidden'];
  }
  ```
* Upstream enforcement makes your app fast, stateless, and focused.

---

## Multiple Fronts: Public vs Secure

Every real app has two surfaces:

* **Public**: open to all
* **Secure**: only authorized users

ADDBAD doesn’t treat secure as a plugin. It’s just another controller directory—protect routes by explicit code.

### File layout

```
addbad-app/
├── add/
│   ├── bad/
│   │   ├── db.php
│   │   └── ui.php
│   └── core.php
│
├── app/
│   ├── render/
│   └── route/
│
├── log/
│   ├── access.log
│   └── error.log
│
├── public/
│   ├── doc/
│   ├── index.php
│   ├── .htaccess
│   └── js/app.js
├── .gitignore
└── README.md
```

### Protecting secure routes

In any `app/controller/secure/*.php`:

```php
if (!is_authenticated()) {
    return ['status' => 403, 'body' => 'Forbidden'];
}
```

No annotations, no decorators—protect each route where needed.

### Secure layout

```php
return render(
    'secure/dashboard',
    ['user' => $user],
    layout: 'secure-layout'
);
```

No template inheritance—just call the layout you need.

---

## TL;DR

| Path                   | Controller File           | Notes              |
| ---------------------- | ------------------------- | ------------------ |
| `/about`               | `public/about.php`        | Open to all        |
| `/user/show/42`        | `public/user/show.php`    | Filesystem routing |
| `/secure/dashboard`    | `secure/dashboard.php`    | Enforce auth       |
| `/secure/users/edit/5` | `secure/users.php` → edit | Explicit args      |

Routing by structure, not configuration.
`public/` is open; `secure/` is your responsibility.

---

## License

Use it, fork it, ignore it—just don’t automate `DELETE` and then blame the framework.

Made with precision and refusal by **La Reponse**.
