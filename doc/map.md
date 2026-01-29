# bad\map

A request hits your server.

It's messy: query strings, fragments, maybe a full URL from somewhere unexpected. What you actually want is simple:

> a stable **routing key** you can trust, and a safe way to turn that key into an **executable file inside your app**.

---

## 1) First, validate your base and clean the path

You start with raw input. You want one thing back: a path you can trust.

```php
$base = realpath(__DIR__ . '/routes') . '/';
$path = bad\map\hook($base, $_SERVER['REQUEST_URI']);
```

That single line quietly does what you used to have to ask for:

* drops `?query` and `#fragment`
* validates the base is real and slash-terminated
* rejects anything containing forbidden characters

The base validation matters: it prevents `/var/www` from matching `/var/www-evil`. The trailing slash isn't pedantry—it's a security boundary.

**Default story:**
"Give me a clean path, and prove the base is trustworthy first."

---

## 2) Then, decide what kind of router you're building

Two common philosophies. BADHAT supports both.

### A) "A route *is* a file" (strict routing)

You already know the handler file you want to exist. No guessing.

```php
$file = bad\map\look($base, $path, '.php');

$file
  ? run([$file], [], INVOKE)
  : bad\http\out(404, 'Not Found');
```

**Story:**
"I only run exactly what exists. If it's not there, it's not a route."

That's the default posture of `look()`—direct, boring, predictable.

---

### B) "A route is a controller + remaining segments" (parameterized routing)

Sometimes you want `/users/edit/42` to land on `users.php`, with `['edit','42']` handed to it.

That's what `seek()` is for.

```php
[$file, $args] = bad\map\seek($base, $path, '.php')
  ?? out(404, 'Not Found');

run([$file], $args, INVOKE);
```

Key default:

> `seek()` assumes **tail-seeking** (deepest-first).

Because that matches how people usually think about intent:

* "Try the most specific handler first"
* then gracefully fall back to the broader controller

`/users/edit/42` tries:
1. `users/edit/42.php`
2. `users/edit.php` → args: `['42']`
3. `users.php` → args: `['edit', '42']`

**Story:**
"Find the tightest matching handler. Hand the leftover intent to something that can."

---

## 3) Only when you need it, you opt in

Flags read like plot twists: you use them when the story changes.

### `ASCEND`: you want a gateway at the top

Sometimes your app has entry points like `api.php` or `admin.php` that intentionally swallow everything underneath.

```php
[$file, $args] = bad\map\seek($base, $path, '.php', bad\map\ASCEND);
```

Now `/admin/users/edit` tries:
1. `admin.php` → args: `['users', 'edit']`
2. `admin/users.php` → args: `['edit']`
3. `admin/users/edit.php`

First match wins.

**Story:**
"Start at the front door. Let the gateway decide what the rest means."

---

### `REBASE`: folders get their own "index handler"

When `admin.php` doesn't exist, you might want `admin/admin.php` to be the real entry point.

```php
$file = bad\map\look($base, 'admin', '.php', bad\map\REBASE);
```

Because `seek()` calls `look()` internally, `REBASE` works there too.

**Story:**
"If a section is a directory, let it own itself."

---

## Reference

### Constants

| Constant | Value | Effect |
|----------|-------|--------|
| `REBASE` | 1 | Fallback: `base/x.shim` missing → try `base/x/x.shim` |
| `ASCEND` | 2 | Forward search: shallowest match first, not deepest |

### Functions

#### `hook(string $base, string $url, string $forbidden = ''): string`

Validates base directory and sanitizes URL path. Returns the cleaned path (query/fragment stripped).

**Parameters:**
- `$base` — Must equal `realpath($base) . '/'`
- `$url` — Raw URL to sanitize
- `$forbidden` — Optional string of characters to reject in path

**Throws:**
- `RuntimeException` — Non-POSIX environment (requires `/` as directory separator)
- `InvalidArgumentException` — `'request has explicitly forbidden chars'`
- `InvalidArgumentException` — `'invalid base or path'`

#### `look(string $base, string $path, string $shim = '', int $behave = 0): ?string`

Direct file lookup. Returns canonical path if file exists within base, `null` otherwise.

**Parameters:**
- `$base` — Directory to search within
- `$path` — Relative path to look up
- `$shim` — File extension or suffix to append
- `$behave` — Behavior flags (`REBASE`)

**Behavior:**
1. Forms candidate: `$base . $path . $shim`
2. If `REBASE` and candidate not a file: tries `$base . $path . '/' . basename($path) . $shim`
3. Validates file exists, resolves via `realpath()`, confirms result starts with `$base`

#### `seek(string $base, string $path, string $shim = '', int $behave = 0): ?array`

Progressive segment search. Returns `[$file, $args]` on match, `null` otherwise.

**Parameters:**
- `$base` — Directory to search within
- `$path` — Relative path to search
- `$shim` — File extension or suffix to append
- `$behave` — Behavior flags (`REBASE`, `ASCEND`)

**Behavior:**
- Default (no `ASCEND`): reverse scan from end, deepest match first
- With `ASCEND`: forward scan from start, shallowest match first
- Calls `look()` for each segment candidate
- Remaining path segments become `$args` array