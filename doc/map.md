# bad\map

A request hits your server.

It’s messy: query strings, fragments, maybe a full URL from somewhere unexpected.  
What you actually want is simple:

> a stable **routing key** you can trust, and a safe way to turn that key into an  
> **executable file inside your app**.

That’s the job of `bad\map`.

---

## 1) First, decide what you consider a “path”

`bad\map` does **not** parse URLs, `bad\http` does.
It assumes you already decided what part of the request represents intent.

A typical call-site looks like this:

```php
$base = __DIR__ . '/routes';   // trailing slash optional

$path = trim(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
    '/'
);

// Decide what "/" means in *your* app
$path = ($path === '') ? 'index' : $path;
````

**Default story:**
“Give me a relative path that represents intent. I’ll take it from there.”

What you can rely on:

* `$base` may be passed **with or without** a trailing `/`
* `$path` must be **relative** (no leading `/`)
* If nothing matches, you get `null` — not an exception

---

## 2) Then, decide what kind of router you’re building

Two common philosophies. BADHAT supports both.

---

### A) “A route *is* a file” (strict routing)

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
“I only run exactly what exists. If it’s not there, it’s not a route.”

That’s the posture of `look()` — direct, boring, predictable.

---

### B) “A route is a controller + remaining intent” (parameterized routing)

Sometimes `/users/edit/42` should land on `users.php`,
with `['edit', '42']` handed to it.

That’s what `seek()` is for.

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

* “Try the most specific handler first”
* then gracefully fall back to something broader

`users/edit/42` tries, in order:

1. `users/edit/42.php`
2. `users/edit.php` → `['42']`
3. `users.php` → `['edit', '42']`

**Story:**
“Find the tightest matching handler. Hand the leftover meaning to something that can.”

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
“Start at the front door. Let the gateway decide what the rest means.”

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
* `admin/admin.php` if the flat file doesn’t exist

The same idea applies when `REBASE` is used with `seek()`.

**Story:**
“If a section is a directory, let it own itself.”

---

## 4) Practical guardrails

What `bad\map` guarantees:

* Returned files are **inside** `$base`
* Missing routes return `null`
* No guessing, no side effects

What you decide at the call-site:

* how to normalize the request path
* what `/` means
* when a route is “not found”

---

## Reference

### Constants

| Constant | Value | Meaning                                 |
| -------: | ----: | --------------------------------------- |
| `REBASE` |   `1` | Allow `x/x.php` when `x.php` is missing |
| `ASCEND` |   `2` | Search from the front, not the tail     |

---

### Functions

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
