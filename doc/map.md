# bad\map

A request hits your server.
It brings a URL-shaped mess: maybe a full URL, maybe an origin-form path, maybe query/fragment noise.

`bad\map` does two things, and stops there:

1. **hook()** — extract a clean, rootless path you can trust
2. **look()/seek()** — map that path to a file under a base directory

No controllers, no registries.
Just a map from intent → file.

---

## 1) hook(): turn a URL into a path

`hook()` takes a raw URL (or `REQUEST_URI`) and returns a **rootless** path:

- strips scheme + authority
- strips query (`?`) and fragment (`#`)
- trims leading/trailing `/`
- validates percent-escapes **syntactically** (it does not decode)
- optionally rejects forbidden characters via `$reject`

```php
use function bad\map\hook;

$path = hook($_SERVER['REQUEST_URI'], "\0");
// "/users/42?page=3"        → "users/42"
// "https://ex.com/api/v1#x" → "api/v1"
// "/"                       → "" (empty string)
````

On invalid input (bad percent-encoding, forbidden chars), it returns an `\InvalidArgumentException`.
With `E_THROW`, it throws instead.

```php
use const bad\map\E_THROW;

$path = hook('/a/%2G');                 // InvalidArgumentException (bad hex)
$path = hook("/a/\0b", "\0");        // InvalidArgumentException (forbidden)

$path = hook('/a/%2G', '', E_THROW);    // throws
```

---

## 2) look(): strict mapping

A strict map says: *a file is a file*. If it’s not there, it’s not handled.

```php
use function bad\map\look;
use function bad\run\loop;
use function bad\http\out;

use const bad\run\INVOKE;
use const bad\http\QUIT;

$file = look($base, $path, '.php');

$file
    ? loop([$file], [], INVOKE)
    : out(QUIT | 404, 'Not Found');
```

`look()` returns an absolute path or `null`.
It also guards the boundary: returned files are inside `$base`.

---

## 3) seek(): progressive mapping (+ leftover intent)

A progressive map says: *find the tightest handler, hand the rest as args*.

```php
use function bad\map\seek;
use function bad\run\loop;
use function bad\http\out;

use const bad\run\INVOKE;
use const bad\http\QUIT;

$hit = seek($base, $path, '.php');
if ($hit === null) {
    out(QUIT | 404, 'Not Found');
}

[$file, $args] = $hit;
loop([$file], $args, INVOKE);
```

Default behavior is **tail-seeking** (deepest-first).
For `users/edit/42`, seek tries:

1. `users/edit/42.php`
2. `users/edit.php`  with args `['42']`
3. `users.php`       with args `['edit', '42']`

On success, you always get:

```php
[$file, $args]   // where $args is always an array (maybe empty)
```

Note: `seek()` expects `$base` to end with `/`.

---

## 4) Flags (only when you need the plot twist)

Flags are opt-in story changes. Combine them with `|`.

### ASCEND — start at the front door

Some apps want a gateway like `api.php` or `admin.php` to swallow what’s beneath.

```php
use const bad\map\ASCEND;

[$file, $args] = seek($base, $path, '.php', ASCEND);
```

Now `admin/users/edit` tries, in this order:

1. `admin.php`         with args `['users', 'edit']`
2. `admin/users.php`   with args `['edit']`
3. `admin/users/edit.php`

First match wins.

### REBASE — let a directory own itself

Some sections want the pattern `x/x.php` when `x.php` is missing.

```php
use const bad\map\REBASE;

$file = look($base, 'admin', '.php', REBASE);
// tries admin.php, else admin/admin.php
```

The same fallback applies inside `seek()` when `REBASE` is set.

---

## Reference

### Constants

|  Constant | Value | Meaning                                                        |
| --------: | ----: | -------------------------------------------------------------- |
|  `REBASE` |   `1` | allow `x/x.php` when `x.php` is missing                        |
|  `ASCEND` |   `2` | search from the front (gateway-first)                          |
| `E_THROW` | `256` | throw on invalid hook() input (instead of returning exception) |

### Functions

* `hook($url, $reject = '', int $behave = 0): string|\InvalidArgumentException`
* `look($base, $path, $shim = '', $behave = 0): ?string`
* `seek($base, $path, $shim = '', $behave = 0): ?array`
