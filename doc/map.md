# bad\map

A request hits your server.

It's messy: query strings, fragments, maybe a full URL from somewhere unexpected.  
What you actually want is simple:

> a stable **routing key** you can trust, and a safe way to turn that key into an  
> **executable file inside your app**.

That's the job of `bad\map`.

---

## 1) First, extract a path from the request

`hook()` takes a raw URL (or `REQUEST_URI`) and returns a rootless path.

It strips scheme, authority, query string, and fragment. It validates percent-encoding.
Optionally rejects forbidden characters via `$reject`.

```php
$path = bad\map\hook($_SERVER['REQUEST_URI'], "\0");
// "/users/42?page=3" → "users/42"
// "https://example.com/api/v1#top" → "api/v1"
```

What you can rely on:

* scheme, authority, query, and fragment are stripped
* percent-escapes are validated syntactically (not decoded)
* result is trimmed of leading/trailing `/`
* result can be empty string (root request)

On invalid input (bad percent-encoding, forbidden chars), returns an `\InvalidArgumentException` instance. With `E_THROW`, it throws instead.

```php
$path = bad\map\hook('/a/%2G');       // InvalidArgumentException (bad hex)
$path = bad\map\hook("/a/\0b", "\0"); // InvalidArgumentException (forbidden)
```

**Story:**
"Give me the raw request. I'll give you a clean, rootless path."

---

## 2) Then, decide what kind of router you're building

Two common philosophies. BADHAT supports both.

---

### A) "A route *is* a file" (strict routing)

You already know what file must exist. No guessing.

```php
$file = bad\map\look($base, $path, '.php');

$file
  ? run([$file], [], INVOKE)
  : bad\http\out(404, 'Not Found');
```

If the file exists under `$base`, you get its absolute path.
If not, you get `null`.

**Story:**
"I only run exactly what exists. If it's not there, it's not a route."

That's the posture of `look()` — direct, boring, predictable.

---

### B) "A route is a controller + remaining intent" (parameterized routing)

Sometimes `/users/edit/42` should land on `users.php`,
with `['edit', '42']` handed to it.

That's what `seek()` is for.

```php
$hit = bad\map\seek($base, $path, '.php');
if ($hit === null) {
    bad\http\out(404, 'Not Found');
    return;
}

[$file, $args] = $hit;
run([$file], $args, INVOKE);
```

Default behavior:

> `seek()` assumes **tail-seeking** (deepest-first).

Because that matches how people usually think about intent:

* "Try the most specific handler first"
* then gracefully fall back to something broader

`users/edit/42` tries, in order:

1. `users/edit/42.php`
2. `users/edit.php` → `['42']`
3. `users.php` → `['edit', '42']`

**Story:**
"Find the tightest matching handler. Hand the leftover meaning to something that can."

On success:

* you always get `[$file, $args]`
* `$args` is always an array (possibly empty)

---

## 3) Only when you need it, you opt in

Flags read like plot twists.
You use them when the story changes.

Combine them with `|`.

---

### `ASCEND` — you want a gateway at the top

Some apps have intentional entry points like `api.php` or `admin.php`
that are meant to swallow everything underneath.

```php
[$file, $args] = bad\map\seek(
    $base,
    $path,
    '.php',
    bad\map\ASCEND
);
```

Now `admin/users/edit` tries:

1. `admin.php` → `['users', 'edit']`
2. `admin/users.php` → `['edit']`
3. `admin/users/edit.php`

First match wins.

**Story:**
"Start at the front door. Let the gateway decide what the rest means."

---

### `REBASE` — a section owns itself

Sometimes a section is a directory with its own entry file.

```php
$file = bad\map\look(
    $base,
    'admin',
    '.php',
    bad\map\REBASE
);
```

This allows:

* `admin.php`, or
* `admin/admin.php` if the flat file doesn't exist

The same idea applies when `REBASE` is used with `seek()`.

**Story:**
"If a section is a directory, let it own itself."

---

## 4) Practical guardrails

What `bad\map` guarantees:

* Returned files are **inside** `$base`
* Missing routes return `null`
* No guessing, no side effects

What you decide at the call-site:

* what `/` means
* when a route is "not found"

---

## Reference

### Constants

| Constant  | Value | Meaning                                          |
| --------: | ----: | ------------------------------------------------ |
| `REBASE`  |   `1` | Allow `x/x.php` when `x.php` is missing          |
| `ASCEND`  |   `2` | Search from the front, not the tail              |
| `E_THROW` | `256` | Throw on invalid input instead of returning error |

---

### Functions

#### `hook($url, $reject = '', int $behave = 0): string|InvalidArgumentException`

Extracts a rootless path from a URL or `REQUEST_URI`.

Strips scheme, authority, query, fragment. Validates percent-encoding.
Rejects characters present in `$reject`.

Returns the path string if valid, otherwise an `\InvalidArgumentException`
(or throws with `E_THROW`).

---

#### `look($base, $path, $shim = '', $behave = 0): ?string`

Strict lookup.

Returns the absolute path of a matching file under `$base`,
or `null` if nothing matches.

---

#### `seek($base, $path, $shim = '', $behave = 0): ?array`

Progressive lookup.

Returns:

```php
[$file, $args]
```

or `null` if nothing matches.

`$args` contains the remaining path segments.

Note: `$base` must have a trailing `/` for `seek()` to operate.
`look()` appends one if missing.