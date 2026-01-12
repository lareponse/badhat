# badhat\io

A request hits your server.

It's messy: it might include a query string, a fragment, maybe even a full URL if it came from somewhere you didn't expect. But what you actually want is simple:

> a stable **routing key** you can trust, and a safe way to turn that key into an **executable file inside your app**.


---

## 1) First, turn “whatever came in” into a routing key

You start with raw input. You want one thing back: `a/b/c`.

```php
$key = bad\io\path($_SERVER['REQUEST_URI'], "\0");
```

That single line quietly does what you used to have to ask for:

* it drops `?query` and `#fragment`
* it trims leading `/`
* it returns a **rootless** key (`users/edit/42`), always

So your app stops arguing with slashes and delimiters. Every route begins the same way.

**Default story:**
“Give me the meaningful path, in the shape my router expects.”

---

## 2) Then, decide what kind of router you're building

There are two common philosophies. BADHAT supports both.

### A) “A route *is* a file” (strict routing)

You already know the handler file you want to exist. No guessing.

```php
$file = bad\io\look('/app/route/', $key, '.php');

$file
  ? run([$file], [], RUN_INVOKE)
  : http_out(404, 'Not Found');
```

**Story:**
“I only run exactly what exists. If it's not there, it's not a route.”

That's the default posture of `look()`—direct, boring, predictable.

---

### B) “A route is a controller + remaining segments” (parameterized routing)

Sometimes you want `/users/edit/42` to land on `users.php`, with `['edit','42']` handed to it.

That's what `seek()` is for.

```php
[$file, $args] = bad\io\seek('/app/route/', $key, '.php')
  ?? http_out(404, 'Not Found');

run([$file], $args, RUN_INVOKE);
```

And here's the key default:

> `seek()` assumes **tail-seeking** (deepest-first).

Because that matches how people usually think about intent:

* “Try the most specific handler first”
* then gracefully fall back to the broader controller

**Story:**
“I'll try to find the tightest matching handler. If I can't, I'll hand the leftover intent to something that can.”

---

## 3) Only when you need it, you opt in

The flags now read like plot twists: you use them when the story changes.

### `IO_HEAD`: you want a gateway at the top

Sometimes your app has entry points like `api.php` or `admin.php` that intentionally swallow everything underneath.

```php
[$file, $args] = bad\io\seek('/app/route/', $key, '.php', bad\io\IO_HEAD);
```

**Story:**
“Start at the front door. Let the gateway decide what the rest means.”

---

### `IO_NEST`: folders get their own “index handler”

When `admin.php` doesn't exist, you might want `admin/admin.php` to be the real entry point.

```php
$file = bad\io\look('/app/route/', 'admin', '.php', bad\io\IO_NEST);
```

And because `seek()` calls `look()` internally, `IO_NEST` works there too.

**Story:**
“If a section is a directory, let it own itself.”

---

### `IO_URL`: the input is a full URL, not just a path

Most of the time you already have `REQUEST_URI` and you're fine.

But if your input might be:

* `https://example.com/users/edit/42?x=1#y`
* `//example.com/users`

…then you opt in:

```php
$key = bad\io\path($raw, "\0", bad\io\IO_URL);
```
