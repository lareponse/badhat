# badhat\run

You've resolved a path to a file. Now what?

PHP's `include` is powerful but raw. You get the file's return value, but output streams immediately. Errors are messy. Chaining files means writing the same try/catch/buffer dance every time.

> `run()` executes files and collects what they produce—return values, output, or both.

---

## 1) First, you include

At its simplest, `run()` is just `include` with structure:

```php
use function bad\run\run;
use const bad\run\RUN_RETURN;

$loot = run(['/app/boot.php']);
// $loot[RUN_RETURN] = whatever boot.php returned
```

Pass arguments, and they're visible inside the file as `$args`:

```php
// users.php
$id = $args[0];  // '42'
return load_user($id);
```

```php
$loot = run(['/app/route/users.php'], ['42']);
```

**Default story:**
"Include a file. Get back what it returned."

---

## 2) Then, you invoke

Most handlers don't just return data—they return a function that *processes* data. The file defines behavior; execution happens when you're ready.

```php
// users.php
return function(array $args) {
    [$action, $id] = $args;
    return qp("SELECT * FROM users WHERE id = ?", [$id])->fetch();
};
```

Add `INVOKE`, and badhat calls the returned callable:

```php
use const bad\run\INVOKE;

$loot = run(['/app/route/users.php'], ['view', '42'], INVOKE);
// $loot[RUN_RETURN] = the user row, not the function
```

**Story:**
"Files define handlers. `INVOKE` runs them."

---

## 3) Buffer when you need the output

PHP files can echo. Sometimes you want that output captured, not streamed.

```php
// template.php
<h1>Hello, <?= htmlspecialchars($args['name']) ?></h1>
```

```php
use const bad\run\BUFFER;
use const bad\run\RUN_OUTPUT;

$loot = run(['/app/template.php'], ['name' => 'World'], BUFFER);

echo $loot[RUN_OUTPUT];  // "<h1>Hello, World</h1>"
```

Without `BUFFER`, output goes straight to the browser. With it, output lands in `$loot[RUN_OUTPUT]`.

**Story:**
"Sometimes you want to capture, not emit."

---

## 4) Absorb: when output feeds into the callable

Here's where it gets interesting. What if your file outputs HTML *and* returns a wrapper function?

```php
// page.php
<article><?= $args['content'] ?></article>
<?php
return function(array $args) {
    $body = end($args);  // the buffered output, appended
    return "<!doctype html><html><body>$body</body></html>";
};
```

`ABSORB` captures the output, then passes it as the last argument to the invoked callable:

```php
use const bad\run\ABSORB;

$loot = run(['/app/page.php'], ['content' => 'Hello'], ABSORB);

echo $loot[RUN_RETURN];
// <!doctype html><html><body><article>Hello</article></body></html>
```

`ABSORB` implies both `BUFFER` and `INVOKE`—it's the full pipeline.

**Story:**
"Template outputs markup. Wrapper receives it. One file, two phases."

---

## 5) Chain: pipelines across files

One file is simple. But what about auth → handler → renderer?

Without `RELOOT`, each callable gets the original `$args`.

With `RELOOT`, each callable gets the **loot bag**—including whatever the previous step returned:

```php
// auth.php
return fn($args) => get_current_user();  // returns user or null

// handler.php
use const bad\run\RUN_RETURN;
return fn($args) => [
    'user'  => $args[RUN_RETURN],        // from auth.php
    'posts' => load_posts(),
];

// render.php
use const bad\run\RUN_RETURN;
return fn($args) => render('home', $args[RUN_RETURN]);
```

```php
use const bad\run\INVOKE;
use const bad\run\RELOOT;

$loot = run([
    '/app/mw/auth.php',
    '/app/route/home.php',
    '/app/render/home.php',
], [], INVOKE | RELOOT);

echo $loot[RUN_RETURN];
```

Each step sees what came before. No globals. No shared state objects. Just the loot bag flowing forward.

**Story:**
"Middleware isn't magic. It's files that return values to the next file."

---

## 6) When things break

By default, `run()` throws on failure. The exception wraps the original:

```php
$loot = run(['/app/broken.php']);
// RuntimeException("include:/app/broken.php", 0xC0D, $originalException)
```

### RESCUE_CALL: invoke anyway

If the include throws but somehow left a callable in `RUN_RETURN`, try to invoke it anyway:

```php
$loot = run(['/app/noisy.php'], [], INVOKE | RESCUE_CALL);
```

Rare. But sometimes a file does setup that throws, yet still defines a handler.

### PIPE_ONWARD: keep going

Multiple files, and you want to continue even if one fails:

```php
$loot = run(['/app/a.php', '/app/b.php', '/app/c.php'], [], PIPE_ONWARD);
```

Failures are swallowed. The last file's results end up in `$loot`.

**Story:**
"Usually, fail fast. Sometimes, fail forward."

---

## Putting it together

```php
use function bad\io\{hook, seek};
use function bad\run\run;
use function bad\http\http_out;
use const bad\run\{BUFFER, INVOKE, RUN_OUTPUT};

$base = realpath(__DIR__ . '/routes') . '/';
$path = hook($base, $_SERVER['REQUEST_URI']);

[$file, $args] = seek($base, $path, '.php')
    ?? http_out(404, 'Not Found');

$loot = run([$file], $args, BUFFER | INVOKE);

http_out(200, $loot[RUN_OUTPUT]);
```

Five lines. Request to response.

---

## Reference

### Constants (behavior)

| Constant | Value | Effect |
|----------|-------|--------|
| `BUFFER` | 1 | Capture output to `RUN_OUTPUT` |
| `INVOKE` | 2 | Call returned callable |
| `ABSORB` | 7 | Buffer + Invoke + append buffer to callable args |
| `RESCUE_CALL` | 8 | Try invoke even if include threw |
| `PIPE_ONWARD` | 16 | Suppress throws, continue to next file |
| `RELOOT` | 256 | Pass loot bag (not original args) to each callable |

### Constants (loot keys)

| Constant | Value | Contains |
|----------|-------|----------|
| `RUN_RETURN` | -1 | Return value from include or invoke |
| `RUN_OUTPUT` | -2 | Captured output buffer |

### Throws

Failures wrap as `RuntimeException` with code `0xC0D`:

```
RuntimeException("include:/path/to/file.php", 0xC0D, $original)
RuntimeException("invoke:/path/to/file.php", 0xC0D, $original)
```

The message tells you which phase failed. The previous exception tells you why.