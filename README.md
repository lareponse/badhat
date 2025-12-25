# BADHAT

```
Bits
Are
Decisive
HTTP
As
Terminal
```

> **A request is just a path that becomes a file that becomes execution.**

**BADHAT** is a minimalist PHP **execution engine** that treats HTTP like a terminal:
files decide, files execute, files emit output.

No controllers.
No routers.
No middleware stacks.
No framework lock-in.

Just ~200 lines of PHP that add **almost no overhead beyond PHP itself**, suitable for a large class of **direct, file-driven web applications**.

---

## What BADHAT Is (and Is Not)

BADHAT is **not** a framework.

It does not abstract HTTP away — it **embraces it**.

BADHAT provides:

* deterministic mapping from request → file
* explicit execution and exit points
* optional output capture and callable invocation
* zero dependencies

BADHAT deliberately avoids:

* routing tables
* dependency injection containers
* ORMs
* lifecycle hooks
* hidden control flow

There is no DSL.
There are no annotations.
PHP *is* the abstraction.

---

## Request Lifecycle (12 lines)

1. **Apache receives the request**
   Unsafe methods, dot-files, traversal, null bytes are rejected *before PHP*.

2. **`index.php` is the single entry point**
   Every request becomes code, or it dies here.

3. **The raw URI is read**
   No routing table. No controller resolution. Just a string.

4. **The path is normalized**
   Decoded, cleaned, null-byte–free.

5. **The suffix / accept is resolved**
   `html`, `json`, or fallback. No content-negotiation theatre.

6. **Candidate file paths are generated**
   Nesting, depth, and root rules decide where to look.

7. **Files are tried in order**
   First existing file wins. Deterministic and inspectable.

8. **The file is included**
   Output may be buffered. A return value may exist.

9. **Optional invocation happens**
   If the file returns a callable, it may be executed.

10. **Results are captured**
    Return value, output buffer, invocation result.

11. **Headers are emitted explicitly**
    One place. No late mutation. No CRLF tolerated.

12. **The response ends**
    Execution stops. Nothing else runs.

---

## Core Principle (Truthful)

BADHAT does **not** define layers, phases, or architecture.

It defines **execution mechanics**.

From those mechanics, you may choose to structure your application however you want:

* logic and rendering separated
* logic and rendering combined
* early exits
* callable pipelines
* single-file pages
* multi-step flows

If you want phases, BADHAT supports them.
If you don’t, BADHAT stays out of the way.

> **BADHAT doesn’t tell you how to structure your app.
> It only makes execution predictable.**

---

## Installation (30 seconds)

```bash
git clone https://github.com/lareponse/BADHAT.git add/badhat
mkdir -p app/io/{route,render}
```

## File Structure

```
project/
├── add/              # BADHAT core
├── app/
│   └── io/
│       ├── route/    # Optional logic grouping
│       └── render/   # Optional rendering grouping
└── public/
    └── index.php     # Entry point
```

---

## Entry Point (`public/index.php`) — correct

```php
<?php
require 'add/badhat/io.php';

[$path, $accept] = io_in($_SERVER['REQUEST_URI']);

$base = 'app/io';

// --- one possible wiring (not required) ---

// Resolve route
$route = io_map("$base/route", $path, 'php', IO_DEEP | IO_NEST);

// Execute route (may invoke callable)
$data = $route
    ? io_run([$route[0]], $route[1] ?? [], IO_INVOKE)
    : [];

// Resolve render
$render = io_map("$base/render", $path, $accept, IO_DEEP | IO_NEST);

// Execute render, capture output
$html = $render
    ? io_run([$render[0]], $data[IO_RETURN] ?? [], IO_ABSORB)
    : [];

// Final output
io_die(200, $html[IO_RETURN] ?? '');
```

> This wiring is **an example**, not a model.

---

## Core Functions (Actual Contracts)

### `io_in(string $raw, string $accept = 'html', string $default = 'index'): array`

Parses and normalizes the request URI.

Returns:

```php
[$path, $accept]
```

---

### `io_map(string $base_dir, string $uri_path, string $file_ext, int $behave = 0): ?array`

Resolves execution paths.

Returns:

* `null` if nothing matches
* `[path]`
* `[path, args]`

---

### `io_run(array $file_paths, array $args, int $behave = 0): array`

Executes one or more files.

Depending on flags:

* includes files
* captures output
* invokes returned callables
* chains results

Return shape:

```php
[
  IO_RETURN => mixed,
  IO_BUFFER => string (if buffered)
]
```

---

### `io_die(int $status, string $body, array $headers = []): void`

Emits headers, outputs body, terminates execution.

This is the **only hard exit primitive**.

---

## Common Execution Patterns (Not Required)

### 1. View-Only

```php
// app/io/render/about.php
<h1>About</h1>
```

Included directly. Output is sent as-is.

---

### 2. Logic → Render (Common, Optional)

```php
// app/io/route/users.php
return function (array $args) {
    return [
        'users' => qp("SELECT id, name FROM users")->fetchAll()
    ];
};
```

```php
// app/io/render/users.php
return function (array $args) {
    extract($args, EXTR_SKIP);
    ?>
    <ul>
        <?php foreach ($users as $u): ?>
            <li><?= htmlspecialchars($u['name']) ?></li>
        <?php endforeach ?>
    </ul>
    <?php
};
```

---

### 3. API Fast-Path

```php
// app/io/route/api/users.php
return function (array $args) {
    io_die(
        200,
        json_encode(
            qp("SELECT id, name FROM users")->fetchAll()
        ),
        ['Content-Type' => 'application/json']
    );
};
```

No render phase. Explicit exit.

---

## Access Guards (Explicit)

```php
// app/io/route/admin/users.php
return function (array $args) {
    auth() || io_die(401, 'Unauthorized');

    return [
        'users' => qp("SELECT * FROM users")->fetchAll()
    ];
};
```

This is **not middleware**.
It is just a file that decides whether execution continues.

---

## Routing Examples

```
/                  → app/io/route/index.php
/about             → app/io/route/about.php

/users/edit/42     → app/io/route/users/edit.php   (args: ['42'])
/api/posts/123/tag → app/io/route/api/posts.php    (args: ['123','tag'])

/deep/missing/path → tries:
  • deep/missing/path.php
  • deep/missing/path/path.php
  • deep/missing.php        (args: ['path'])
  • deep.php                (args: ['missing','path'])
  • index.php               (args: ['deep','missing','path'])
```

Filesystem-native.
Deterministic.
Debuggable.

---

## Philosophy

* **Explicitness** — nothing happens implicitly
* **Minimalism** — fewer concepts, fewer bugs
* **Control** — headers, exit, and flow are yours
* **Honesty** — PHP is the abstraction

BADHAT assumes discipline.
It does not protect you from yourself.

---

## When BADHAT Fits Naturally

This is **not a recommendation matrix** — it describes alignment.

**Fits well**

* APIs
* admin tools
* internal apps
* prototypes
* small to mid teams
* direct SQL

**May fit**

* apps up to ~50k LOC
* read-heavy systems
* simple domains

**Probably not**

* large compliance-driven orgs
* massive teams (100+)
* heavy vendor ecosystems
* static marketing sites


---

## License

MIT.
No warranty.
Use at your own risk.
