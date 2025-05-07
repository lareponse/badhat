# ADDBAD — A Procedural Micro-Framework for Serious Developers

**ADDBAD** is a micro-framework for PHP that refuses to pretend.

No classes.  
No config.  
No routing tables.  
No dependency injection.  
No layers of abstraction you don’t need.

It’s ~80 lines of core code that give you everything required to build real applications—and nothing you don’t ask for.

---

## ✨ Philosophy

**ADDBAD** is not a framework. It’s a refusal.

A refusal of boilerplate.  
A refusal of magic.  
A refusal of engineering theater.

It’s built on the belief that clarity, control, and constraint lead to better code—and that modern PHP has been hijacked by people trying to turn it into Java.

You don’t need classes. You don’t need containers. You don’t need annotations, attributes, or autowiring.  
You need **functions**, **arrays**, and **discipline**.

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

## Routing

- URL `/foo/bar/123` maps to:
  - `app/controller/public/foo.php`
  - `function bar($req, 123)`
- If the controller or function doesn’t exist, you get a 404.
- Default action: if missing, uses controller name.

---

## Controllers

- One file = one controller.
- One function = one action.
- Signature: `function action_name($req, ...$params)`
- Return either:
  - HTML string  
  - or an array: `['status' => int, 'headers' => [], 'body' => string]`

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

## Authentication & Access Control: SQLite

ADDBAD uses **SQLite exclusively for authentication and ACL**.

No ORM. No user model. No session.  
Just raw SQL, a small database file, and signed cookies.

---

### Why SQLite?

Because it's:

- Secure by design (no network surface)
- Durable (ACID-compliant, file-backed)
- Simple (one file, one schema)
- Not used for anything else

No business data. No CMS.  
Only auth. Only access control.

---

### How it works

- `users.sqlite` stores usernames and password hashes
- Authentication is read-only and stateless
- `auth_token()` creates an HMAC-signed cookie
- `validate_token()` parses and verifies it
- `current_user()` gives you the username
- ACL logic is yours to define (flat file, function, roles)

---

### No writes during login

Login reads from `users.sqlite`. That's it.

Log entries go elsewhere:

- `auth.log` (flat text file)
- or `logs.sqlite` (write-only, optional)

This avoids database locking and keeps your system responsive under concurrent access.

---

### TL;DR

| Purpose         | SQLite Used? |
|-----------------|---------------|
| Login auth      | Yes           |
| Password hashes | Yes           |
| Role/ACL checks | Yes (optional)|
| Login logging   | No            |
| App data        | No            |

SQLite is your identity layer.  
Nothing more. Nothing less.

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
