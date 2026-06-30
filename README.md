# BADHAT

```
Bits As Decision.
HTTP As Terminal.
```

Emergent programming for PHP.

Architecture that emerges from the language and the filesystem, not from a framework's ontology.
The request is reality. Structure arises from execution, not doctrine.
No registry, no lifecycle, no hive — just path → file → chain → bytes.

> BADHAT provides conditions. What grows is yours.

---

## What it is

BADHAT is not a framework. It is a climate.

It doesn't tell you what the application should be. It ensures that whatever you build remains in contact with reality: every transformation can be traced, every responsibility has an address, every effect has a cause you can point to.

~100 lines of PHP to hook, map and run php.
**Maps**, not routes. **Loops**, not controllers. **Bitmasks**, not config files.

> requires POSIX and PHP >= 8.0

---

## The physics

An organism doesn't start with a blueprint. It starts with a physics:

- **Boundaries** — request in, response out
- **Medium** — filesystem, PHP execution
- **Metabolism** — map → run → emit
- **Constraints** — flags, failure semantics, header rules
- **Feedback** — loot, buffers, codified faults

Nothing in BADHAT exists to *replace* something PHP already does. Everything exists to make what PHP already does *safer to rely on*.

The filesystem is the router. Output buffering is the template engine. Bitmasks are the configuration language. PHP is the config file. `if` is the validation framework.

---

## What it does

```php
use function bad\map\{hook, seek};
use function bad\run\loot;
use function bad\http\out;

use const bad\run\{INVOKE, RESULT};
use const bad\http\QUIT;

$base = realpath(__DIR__ . '/routes') . '/';
$path = hook($_SERVER['REQUEST_URI'], "\0");

[$file, $args] = seek($base, $path, '.php')
    ?? exit(out(QUIT | 404, 'Not Found'));

$loot = loot([$file], $args, INVOKE);

exit(out(QUIT | 200, (string)($loot[RESULT] ?? '')));
```

Three moves:

1. **hook** — turn a URL into a rootless path you can trust
2. **seek** — map that path to an executable file (and leftover intent)
3. **loot** — include + (optionally) invoke what the file returns

---

## Kernel

Two files:

- `map.php` — URL → path → file (+ args)
- `run.php` — include + invoke + buffer

Everything else is plumbing.

---

## Core modules

| Module      | Purpose                          |
| ----------- | -------------------------------- |
| `map.php`   | URL → path → file (+ args)       |
| `run.php`   | include + invoke + buffer        |
| `trap.php`  | handler installation             |
| `pdo.php`   | query helper (no ORM)            |
| `http.php`  | header staging + response output |
| `auth.php`  | session login                    |
| `csrf.php`  | token management                 |
| `rfc.php`   | small RFC-shaped validators      |

---

## Philosophy

**Emergent, not imposed.** Frameworks define a world and require you to inhabit it. BADHAT observes what PHP and HTTP already do and formalizes the minimum needed to work with that reality reliably.

**Maps resolve depth.** A single map can represent infinite routes with O(depth) resolution.

**Files produce.** A PHP file can return a value. Better: it can return a closure.

**Closures capture intent.** A file computes once, returns a callable that remembers.

**Bitmasks are the interface.** One `int` tells you what the engine will do.

**Failure is explicit.** No silent `false`. Faults become exceptions with context.

---

## Quick taste

```php
// routes/api/users.php
use function bad\pdo\qp;
use function bad\http\{headers, out};
use const bad\http\{ONE, QUIT};

return function(array $bag) {
    headers(ONE, 'Content-Type', 'application/json; charset=utf-8');

    $rows = qp('SELECT id, name FROM users')->fetchAll();
    out(QUIT | 200, json_encode($rows));
};
```

Call-site:

```php
use function bad\run\loot;
use const bad\run\INVOKE;

loot([__DIR__ . '/routes/api/users.php'], [], INVOKE);
```

---

## What BADHAT will never implement

Six concerns that belong to infrastructure, external tools, or standalone libraries — not to a request lifecycle toolkit:

1. **Infrastructure** — compression, TLS, rate limiting, static file serving, process management, log rotation. Your web server and OS handle this.
2. **Data abstraction** — ORM, cache wrappers, storage engine facades. Use PDO, APCu, phpredis directly.
3. **External I/O** — HTTP client, mail, queues, sockets. Use cURL, PHPMailer, AMQP directly.
4. **Rendering** — template engines, Markdown, PDF, image processing. Use PHP itself, league/commonmark, dompdf, GD directly.
5. **Tooling** — testing, linting, asset compilation, CLI scaffolding. Use PHPUnit, PHPStan, php-cs-fixer directly.
6. **Policy orchestration** — DI containers, event buses, middleware stacks, config DSLs, validation frameworks, global state stores. Call the constructor. Call the function. Write the `if`.

The rule: if a mature, standalone tool already does it, BADHAT will not re-skin it behind a `bad\` namespace.

## Install

```bash
git fetch --depth=1 git@github.com:lareponse/BADHAT.git main
git subtree add --prefix=add/badhat FETCH_HEAD --squash
```

---
    
## License

BADHAT LICENSE. No warranty. Ship it.