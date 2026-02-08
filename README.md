# BADHAT

```

Bits As Decision.
HTTP As Terminal.

````

~100 lines of PHP to hook, map and run php.
**Maps**, not routes. **Loops**, not controllers. **Bitmasks**, not config files.

> requires POSIX and PHP >= 8.0

---

## What it does

```php
use function bad\map\{hook, seek};
use function bad\run\loop;
use function bad\http\out;

use const bad\run\{INVOKE, INC_RETURN};
use const bad\http\QUIT;

$base = realpath(__DIR__ . '/routes') . '/';
$path = hook($_SERVER['REQUEST_URI'], "\0");

[$file, $args] = seek($base, $path, '.php')
    ?? exit(out(QUIT | 404, 'Not Found'));

$loot = loop([$file], $args, INVOKE);

exit(out(QUIT | 200, (string)($loot[INC_RETURN] ?? '')));
````

Three moves:

1. **hook**: turn a URL into a rootless path you can trust
2. **seek**: map that path to an executable file (and leftover intent)
3. **loop**: include + (optionally) invoke what the file returns

---

## Kernel

BADHAT’s kernel is two files:

* `map.php` — URL → path → file (+ args)
* `run.php` — loop / loot / boot (include + invoke + buffer)

Everything else is plumbing.

---

## Core modules

| Module      | Purpose                          |
| ----------- | -------------------------------- |
| `map.php`   | URL → path → file (+ args)       |
| `run.php`   | loop / loot / boot               |
| `error.php` | handler installation             |
| `pdo.php`   | query helper (no ORM)            |
| `http.php`  | header staging + response output |
| `auth.php`  | session login                    |
| `csrf.php`  | token management                 |
| `rfc.php`   | small RFC-shaped validators      |

---

## Philosophy

**Maps resolve depth.** A single map can represent infinite routes with O(depth) resolution.

**Files produce.** A PHP file can return a value. Better: it can return a closure.

**Closures capture intent.** A file computes once, returns a callable that remembers.

**Bitmasks are the interface.** One `int` tells you what the engine will do.

**Failure is explicit.** No silent `false`. Faults become exceptions with context.

---

## Quick taste

A handler file can do the whole response (no framework ceremony required):

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
use function bad\run\loop;
use const bad\run\INVOKE;

loop([__DIR__ . '/routes/api/users.php'], [], INVOKE);
```

---

## Install

```bash
git fetch --depth=1 git@github.com:lareponse/BADHAT.git main
git subtree add --prefix=add/badhat FETCH_HEAD --squash
```

---

## License

MIT. No warranty. Ship it.
