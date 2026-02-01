# BADHAT

```
Bits As Decision
HTTP As Terminal
```

~200 lines of PHP. Files are routes. Bitmasks are config. That's it.

---

## What it does

```php
[$file, $args] = seek($base, '/users/42', '.php');  // find handler
$loot = run([$file], $args, INVOKE);                 // execute it
out(200, $loot[INC_RETURN]);                         // respond
```

Three lines. Request to response.

---

## Core modules

| Module | Purpose |
|--------|---------|
| `map.php` | URL → file resolution |
| `run.php` | Include + invoke + buffer |
| `http.php` | Headers + output |
| `pdo.php` | Query helper, no ORM |
| `auth.php` | Session login |
| `csrf.php` | Token management |
| `error.php` | Handler installation |

Each under 150 lines. No inheritance. No interfaces. No magic.

---

## Philosophy

**Files decide.** `/admin/users` looks for `admin/users.php` or walks back to `admin.php` with `['users']`.

**Bitmasks configure.** `run([$file], $args, BUFFER | INVOKE)` — behavior is explicit, composable, fits in one int.

**Failures explode.** No silent `false` returns. Exceptions with context.

---

## Quick taste

```php
// app/io/route/api/users.php
use function bad\pdo\qp;
use function bad\http\{headers, out};
use const bad\http\SET;

return function($args) {
    headers(SET, 'Content-Type', 'application/json');
    exit(out(200, json_encode(
        qp("SELECT id, name FROM users")->fetchAll()
    )));
};
```

---

## Install

```bash
git fetch --depth=1 git@github.com:lareponse/BADHAT.git main
git subtree add --prefix=add/badhat FETCH_HEAD --squash
```

See `doc/setup/quickstart.md` for full bootstrap.

---

## You'll like this if

- You miss when `index.php` *was* the architecture diagram
- You think RAM is for your app, not your framework
- Loading 400 files to serve one request feels excessive
- You think flags should fit in an int, not a YAML file
- You understand the chain: fewer cycles, fewer servers, fewer cooling fans
- You'd rather pay for users than for framework overhead

## You won't like this if

- Abstractions are comfort, not overhead
- Your team needs a framework to enforce what meetings couldn't
- `$user->posts()->where()->with()->get()` sparks joy
- Autoloading 400 files feels like progress
- You find comfort in not knowing what happens
- "Explicit" sounds like a content warning

---

## License

MIT. No warranty. Ship it.