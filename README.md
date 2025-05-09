# ADDBAD — A Procedural Micro-Framework for Serious Developers

**ADDBAD** is not a framework. It’s a refusal.

A refusal of boilerplate.  
A refusal of magic.  
A refusal of engineering theater.

That means: 

- No config
- No classes (or containers, annotations, attributes or autowiring)
- No sessions
- No routing tables
- No templating engines
- No dependency injection
- No layers of abstraction

None of them are needed.
What we need are **file systems**, **functions**, **arrays** and **conventions**.

addbad is ~80 lines of core code that give you everything required to build real applications -and nothing you don’t ask for.

addbad is built on the belief that clarity, control, and constraint lead to better code—and that modern PHP has been hijacked by people trying to turn it into Java.

---

## Core Principles

### Simplicity over abstraction

- **No classes**  
  Structure logic with directories and filenames. Not object hierarchies.

- **No namespaces**
  If you need namespaces to avoid name collisions, your function names suck.

- **No autoloading**  
  You use it, you include it. No composer. No magic. One line does the job.

---

### Routing is convention, not configuration

- **No route registration**  
  The URL `/user/show/42` maps to `user.php → show()`. That’s it.

- **No middleware stack**  
  You want code to run before something? Put it before it. Use the order of lines.

---

### No templating engines, ever

- **No Blade, Twig, or template DSLs**  
  PHP *is* your template engine. Use `render()`, `slot()`, `partial()`—nothing more.

---

### SQL is not the enemy

- **No ORM**  
  SQL is a language. Respect it. Don’t wrap it in toys.

- **No SELECT builder**  
  Writing a `SELECT` is not a problem. Stop pretending it is.

- **No DELETE helper**  
  Destructive queries must be written by hand. If you automate `DELETE`, you don’t deserve root access.

---

### No fake architecture

- **No DI containers**  
  Include a file. Pass a variable. Don’t summon a container to resolve a logger.

- **No service layers**  
  You don’t need to inject a `UserManagerFactoryInterface`. You need to write better functions.

---

### Configuration by the developer, not the framework

- **No config files**  
  Use `define()` for paths. Use `.env` for secrets. Be direct.

- **No meta-framework**  
  ADDBAD is not a foundation for something bigger. It *is* the final product.

---

## Is ADDBAD for you?

ADDBAD is for you if:

- You'd rather write 10 lines of clear code than configure a service container.
- You think HTML templates should be `.php`, not `.twig`, `.blade`, or `.jsx`.
- You don’t need 8000 stars on GitHub to feel good about a solution.
- You treat SQL as a language, not a leaky abstraction.
- You believe the filesystem is a perfectly good routing mechanism.
- You like reading code more than reading documentation.
- You understand what `require` does—and that it's enough.
- You want to know exactly what happens when a request hits your server.
- You believe control is more important than convention.

ADDBAD is **not** for you if:

- You need framework magic to feel productive.
- You prefer "clean architecture" over readable code.
- You reach for `composer require` before writing a function.
- You think DI containers are an achievement.
- You believe boilerplate is inevitable.
- You think auto-generating classes is programming.

---

## Processing routes

There are no controllers. There are only files.

Each route is a file. That file returns a closure. That closure receives the request and any arguments extracted from the URL. That’s it. The filesystem is the router.

A request to `/secure/users/edit/42` resolves to:

```
app/controller/secure/users/edit.php
```

That file returns:

```php
return function ($req, $id) {
    return render('users/edit', ['id' => $id]);
};
```

Closures returns an array: `['status' => int, 'headers' => [], 'body' => string]`

You may also include two optional lifecycle files:

* `prepare.php` runs before the route (used for authentication, setup)
* `conclude.php` runs after the route (used for logging, response mutation)

The execution order is:

1. Area-level `prepare.php` (e.g. `secure/prepare.php`)
2. Controller-level `prepare.php` (e.g. `secure/users/prepare.php`)
3. Route file (e.g. `secure/users/edit.php`)
4. Controller-level `secure/users/conclude.php`
5. Area-level `secure/conclude.php`

Each file returns a closure.
No class-based routing.
No annotations.
No dependency injection.

If you want something to happen, write it.
If you want something to stop, return early.
Everything else is noise.

---

Let me know if you want this dropped into the full README draft now.

---

## Views

- Use `render('viewname', $data)`
- Layout is defined in `layout.php`, uses `$content`
- Partial rendering: `partial('name', $data)` — looks for `_name.php`
- Slot system:
  - `slot($name, $value)` — adds a value
  - `slot($name)` — returns the last value
  - `slots($name, $sep)` — returns all values joined by a separator

Use slots for injecting:
- `<meta>` tags
- `<script>` or `<style>` blocks
- toolbars, footers, or sidebars

---

## Database Helpers

There is no ORM. You write SQL. You run it with PDO.

The only helpers provided:

```php
[$sql, $params] = qb_insert('table', ['a' => 1, 'b' => 2]);
[$sql, $params] = qb_update('table', ['a' => 1], 'id = ?', [42]);
````

Why?

* INSERT and UPDATE are repetitive
* SELECT is not
* DELETE is a crime

No builder for SELECT. Write your queries.
No builder for DELETE. Ever.

---

## Authentication & Access Control: Header-based

ADDBAD uses a **single HTTP header** for authentication and access control.

No login forms. No sessions. No cookie p# ADDBAD — A Procedural Micro-Framework for Serious Developers

**ADDBAD** is not a framework. It’s a refusal.

A refusal of boilerplate.  
A refusal of magic.  
A refusal of engineering theater.

That means: 

- No config
- No classes (or containers, annotations, attributes or autowiring)
- No sessions
- No routing tables
- No templating engines
- No dependency injection
- No layers of abstraction

None of them are needed.
What we need are **file systems**, **functions**, **arrays** and **conventions**.

addbad is ~80 lines of core code that give you everything required to build real applications -and nothing you don’t ask for.

addbad is built on the belief that clarity, control, and constraint lead to better code—and that modern PHP has been hijacked by people trying to turn it into Java.

---

## Core Principles

### Simplicity over abstraction

- **No classes**  
  Structure logic with directories and filenames. Not object hierarchies.

- **No namespaces**
  If you need namespaces to avoid name collisions, your function names suck.

- **No autoloading**  
  You use it, you include it. No composer. No magic. One line does the job.

---

### Routing is convention, not configuration

- **No route registration**  
  The URL `/user/show/42` maps to `user.php → show()`. That’s it.

- **No middleware stack**  
  You want code to run before something? Put it before it. Use the order of lines.

---

### No templating engines, ever

- **No Blade, Twig, or template DSLs**  
  PHP *is* your template engine. Use `render()`, `slot()`, `partial()`—nothing more.

---

### SQL is not the enemy

- **No ORM**  
  SQL is a language. Respect it. Don’t wrap it in toys.

- **No SELECT builder**  
  Writing a `SELECT` is not a problem. Stop pretending it is.

- **No DELETE helper**  
  Destructive queries must be written by hand. If you automate `DELETE`, you don’t deserve root access.

---

### No fake architecture

- **No DI containers**  
  Include a file. Pass a variable. Don’t summon a container to resolve a logger.

- **No service layers**  
  You don’t need to inject a `UserManagerFactoryInterface`. You need to write better functions.

---

### Configuration by the developer, not the framework

- **No config files**  
  Use `define()` for paths. Use `.env` for secrets. Be direct.

- **No meta-framework**  
  ADDBAD is not a foundation for something bigger. It *is* the final product.

---

## Is ADDBAD for you?

ADDBAD is for you if:

- You'd rather write 10 lines of clear code than configure a service container.
- You think HTML templates should be `.php`, not `.twig`, `.blade`, or `.jsx`.
- You don’t need 8000 stars on GitHub to feel good about a solution.
- You treat SQL as a language, not a leaky abstraction.
- You believe the filesystem is a perfectly good routing mechanism.
- You like reading code more than reading documentation.
- You understand what `require` does—and that it's enough.
- You want to know exactly what happens when a request hits your server.
- You believe control is more important than convention.

ADDBAD is **not** for you if:

- You need framework magic to feel productive.
- You prefer "clean architecture" over readable code.
- You reach for `composer require` before writing a function.
- You think DI containers are an achievement.
- You believe boilerplate is inevitable.
- You think auto-generating classes is programming.

---

## Controllers and Routing

There are no controllers. There are only files.

Each route is a file. That file returns a function. That function receives the request, and the arguments extracted from the URL. That’s it. The filesystem is your routing table.

A request to `/secure/users/edit/42` resolves to:

```
app/controller/secure/users/edit.php
```

That file returns:

```php
return function ($req, $id) {
    // your logic here
};
```
Function returns either:
  - HTML string  
  - or an array: `['status' => int, 'headers' => [], 'body' => string]`

Each route can be preceded by a `prepare.php` (in the same folder or parent folder), and followed by a `conclude.php`.

* `prepare.php` is executed before

---

## Views

- Use `render('viewname', $data)`
- Layout is defined in `layout.php`, uses `$content`
- Partial rendering: `partial('name', $data)` — looks for `_name.php`
- Slot system:
  - `slot($name, $value)` — adds a value
  - `slot($name)` — returns the last value
  - `slots($name, $sep)` — returns all values joined by a separator

Use slots for injecting:
- `<meta>` tags
- `<script>` or `<style>` blocks
- toolbars, footers, or sidebars

---

## Database Helpers

There is no ORM. You write SQL. You run it with PDO.

The only helpers provided:

```php
[$sql, $params] = qb_insert('table', ['a' => 1, 'b' => 2]);
[$sql, $params] = qb_update('table', ['a' => 1], 'id = ?', [42]);
````

Why?

* INSERT and UPDATE are repetitive
* SELECT is not
* DELETE is a crime

No builder for SELECT. Write your queries.
No builder for DELETE. Ever.

---

## Authentication & Access Control: Header-based

ADDBAD uses a **single HTTP header** for authentication and access control.

No login forms. No sessions. No cookie parsing. No database involved.  
Just a signed reverse proxy or upstream service setting `X-AUTH-USER`, because it's:
- Simple (one header, one identity)
- Stateless (no session or DB lookup)
- Flexible (can integrate with any SSO or auth provider)
- Enforced upstream (reverse proxy, gateway, or load balancer)

Authentication is upstream.  
ADDBAD trusts the header.
This keeps your app fast, stateless, and focused.

---

## Multiple Fronts: Public vs Secure

Every real application has two surfaces:

- **Public** — what everyone sees
- **Secure** — what only authorized users should access

ADDBAD doesn’t treat your secure interface like a plugin or sub-framework.  
It’s just another controller directory—with structure and protection you define.

---

### File layout
```
addbad-app/
├── app/
│   ├── controller/
│   │   ├── public/          # Public routes (open)
│   │   │   ├── home.php     # function home($req)
│   │   │   └── about.php
│   │   └── secure/          # Protected routes (auth checks inside)
│   │       ├── dashboard.php
│   │       └── users.php
│   ├── view/                # Layouts, views, and partials
│   │   ├── layout.php       # Default layout (uses $content)
│   │   ├── secure-layout.php
│   │   ├── home.php
│   │   ├── about.php
│   │   └── _footer.php      # Partial: render('footer') → looks for _footer.php
│   └── lib/                 # Helpers (auth.php, db.php, render.php, etc.)
│       ├── auth.php
│       ├── db.php
│       ├── render.php
│       ├── router.php
│       └── slot.php
├── db/
│   ├── users.sqlite         # Auth-only SQLite DB
│   └── logs.sqlite          # Optional write-only logs
├── log/
│   └── auth.log             # Flat file login activity (optional)
├── public/                 # Web root
│   ├── index.php            # Entry point
│   ├── css/
│   │   └── main.css
│   └── js/
│       └── app.js
├── .env                     # Secrets and config
├── .gitignore
└── README.md
```

---

### Protect the secure routes

In any `controller/secure/*.php`:

```php
if (!is_authenticated()) {
    return ['status' => 403, 'body' => 'Forbidden'];
}
```

There is no middleware.
No `@SecureOnly` decorator.
No access config.
If a route must be protected, **you protect it.**

---

### Secure layout

If your secure interface has different structure, use:

```php
return render('secure/dashboard', ['user' => $user], layout: 'secure-layout');
```

No layout inheritance. No template engine. Just call the right file.

---

### TL;DR

| Path                   | Controller File             | Notes              |
| ---------------------- | --------------------------- | ------------------ |
| `/about`               | `public/about.php`          | Open to all        |
| `/secure/dashboard`    | `secure/dashboard.php`      | Must check auth    |
| `/secure/users/edit/5` | `secure/users.php → edit()` | Enforce protection |

This is ADDBAD's multi-surface architecture:
**routing by structure, not by configuration.**

`public/` is open.
`secure/` is your responsibility.

--- 
## License

Use it, fork it, ignore it.
Just don’t automate `DELETE` and then blame the framework.

---

Made with precision and refusal by **La Reponse**.
arsing. No database involved.  
Just a signed reverse proxy or upstream service setting `X-AUTH-USER`, because it's:
- Simple (one header, one identity)
- Stateless (no session or DB lookup)
- Flexible (can integrate with any SSO or auth provider)
- Enforced upstream (reverse proxy, gateway, or load balancer)

Authentication is upstream.  
ADDBAD trusts the header.
This keeps your app fast, stateless, and focused.

---

## Multiple Fronts: Public vs Secure

Every real application has two surfaces:

- **Public** — what everyone sees
- **Secure** — what only authorized users should access

ADDBAD doesn’t treat your secure interface like a plugin or sub-framework.  
It’s just another controller directory—with structure and protection you define.

---

### File layout
```
addbad-app/
├── app/
│   ├── controller/
│   │   ├── public/          # Public routes (open)
│   │   │   ├── home.php     # function home($req)
│   │   │   └── about.php
│   │   └── secure/          # Protected routes (auth checks inside)
│   │       ├── dashboard.php
│   │       └── users.php
│   ├── view/                # Layouts, views, and partials
│   │   ├── layout.php       # Default layout (uses $content)
│   │   ├── secure-layout.php
│   │   ├── home.php
│   │   ├── about.php
│   │   └── _footer.php      # Partial: render('footer') → looks for _footer.php
│   └── lib/                 # Helpers (auth.php, db.php, render.php, etc.)
│       ├── auth.php
│       ├── db.php
│       ├── render.php
│       ├── router.php
│       └── slot.php
├── db/
│   ├── users.sqlite         # Auth-only SQLite DB
│   └── logs.sqlite          # Optional write-only logs
├── log/
│   └── auth.log             # Flat file login activity (optional)
├── public/                 # Web root
│   ├── index.php            # Entry point
│   ├── css/
│   │   └── main.css
│   └── js/
│       └── app.js
├── .env                     # Secrets and config
├── .gitignore
└── README.md
```

---

### Protect the secure routes

In any `controller/secure/*.php`:

```php
if (!is_authenticated()) {
    return ['status' => 403, 'body' => 'Forbidden'];
}
```

There is no middleware.
No `@SecureOnly` decorator.
No access config.
If a route must be protected, **you protect it.**

---

### Secure layout

If your secure interface has different structure, use:

```php
return render('secure/dashboard', ['user' => $user], layout: 'secure-layout');
```

No layout inheritance. No template engine. Just call the right file.

---

### TL;DR

| Path                   | Controller File             | Notes              |
| ---------------------- | --------------------------- | ------------------ |
| `/about`               | `public/about.php`          | Open to all        |
| `/secure/dashboard`    | `secure/dashboard.php`      | Must check auth    |
| `/secure/users/edit/5` | `secure/users.php → edit()` | Enforce protection |

This is ADDBAD's multi-surface architecture:
**routing by structure, not by configuration.**

`public/` is open.
`secure/` is your responsibility.

--- 
## License

Use it, fork it, ignore it.
Just don’t automate `DELETE` and then blame the framework.

---

Made with precision and refusal by **La Reponse**.
