# badhat\io

A request hits your server.

It's messy: query strings, fragments, maybe a full URL from somewhere unexpected. What you actually want is simple:

> a stable **routing key** you can trust, and a safe way to turn that key into an **executable file inside your app**.


---

## 1) First, validate your base and clean the path

You start with raw input. You want one thing back: a path you can trust.

```php
$base = realpath(__DIR__ . '/routes') . '/';
$path = bad\io\hook($base, $_SERVER['REQUEST_URI']);
```

That single line quietly does what you used to have to ask for:

* it drops `?query` and `#fragment`
* it validates the base is real and slash-terminated
* it rejects anything containing forbidden characters

The base validation matters: it prevents `/var/www` from matching `/var/www-evil`. The trailing slash isn't pedantry—it's a security boundary.

**Default story:**
"Give me a clean path, and prove the base is trustworthy first."

---

## 2) Then, decide what kind of router you're building

There are two common philosophies. BADHAT supports both.

### A) "A route *is* a file" (strict routing)

You already know the handler file you want to exist. No guessing.

```php
$file = bad\io\look($base, $path, '.php');

$file
  ? run([$file], [], RUN_INVOKE)
  : http_out(404, 'Not Found');
```

**Story:**
"I only run exactly what exists. If it's not there, it's not a route."

That's the default posture of `look()`—direct, boring, predictable.

---

### B) "A route is a controller + remaining segments" (parameterized routing)

Sometimes you want `/users/edit/42` to land on `users.php`, with `['edit','42']` handed to it.

That's what `seek()` is for.

```php
[$file, $args] = bad\io\seek($base, $path, '.php')
  ?? http_out(404, 'Not Found');

run([$file], $args, RUN_INVOKE);
```

And here's the key default:

> `seek()` assumes **tail-seeking** (deepest-first).

Because that matches how people usually think about intent:

* "Try the most specific handler first"
* then gracefully fall back to the broader controller

`/users/edit/42` tries:
1. `users/edit/42.php`
2. `users/edit.php` → args: `['42']`
3. `users.php` → args: `['edit', '42']`

**Story:**
"I'll try to find the tightest matching handler. If I can't, I'll hand the leftover intent to something that can."

---

## 3) Only when you need it, you opt in

The flags read like plot twists: you use them when the story changes.

### `IO_GROW`: you want a gateway at the top

Sometimes your app has entry points like `api.php` or `admin.php` that intentionally swallow everything underneath.

```php
[$file, $args] = bad\io\seek($base, $path, '.php', bad\io\IO_GROW);
```

Now `/admin/users/edit` tries:
1. `admin.php` → args: `['users', 'edit']`
2. `admin/users.php` → args: `['edit']`
3. `admin/users/edit.php`

First match wins.

**Story:**
"Start at the front door. Let the gateway decide what the rest means."

---

### `IO_NEST`: folders get their own "index handler"

When `admin.php` doesn't exist, you might want `admin/admin.php` to be the real entry point.

```php
$file = bad\io\look($base, 'admin', '.php', bad\io\IO_NEST);
```

And because `seek()` calls `look()` internally, `IO_NEST` works there too.

**Story:**
"If a section is a directory, let it own itself."

---

## Reference

### Constants

| Constant | Value | Effect |
|----------|-------|--------|
| `IO_NEST` | 1 | Fallback: `base/x.shim` missing → try `base/x/x.shim` |
| `IO_GROW` | 2 | Reverse search: shallowest match first, not deepest |

### Functions

#### `hook(string $base, string $url, string $forbidden = '', int $behave = 0): string`

Validates base directory and sanitizes URL path. Throws on security violations.

**Throws `\InvalidArgumentException` (code 400):**
- `'base has no trailing separator...'` — Base must end with `/`
- `'base is not real...'` — Base must equal its own `realpath()`
- `'request has explicitly forbidden chars...'` — Path contains forbidden characters

#### `look(string $base, string $path, string $shim = '', int $behave = 0): ?string`

Direct file lookup. Returns canonical path if file exists within base, `null` otherwise.

#### `seek(string $base, string $path, string $shim = '', int $behave = 0): ?array`

Progressive segment search. Returns `[file, args]` on match, `null` otherwise.