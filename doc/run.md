# BADHAT Run — File Execution

`bad\run` does one thing: execute files and capture results.

- Includes files
- Can invoke returned callables
- Can buffer output
- Can *pipe* the previous step into the next invoke
- Wraps include/invoke failures as `RuntimeException("include:..."|"invoke:...", 0xC0D, $previous)`

Path resolution belongs to `bad\io\look()` / `bad\io\seek()`.
HTTP output belongs to `bad\http\http_out()`.

---

## Constants

```php
// Behavior flags
const RUN_BUFFER = 1;                           // capture output
const RUN_INVOKE = 2;                           // call returned callable
const RUN_ABSORB = 4 | RUN_BUFFER | RUN_INVOKE; // append buffer to callable args
const RUN_RESCUE = 8;                           // attempt invoke even if include threw
const RUN_ONWARD = 16;                          // suppress throws, continue

const RUN_CHAIN  = 256;                         // pass loot bag to next invoke

// Loot keys
const RUN_RETURN = -1;                          // include/invoke return value
const RUN_OUTPUT = -2;                          // captured output buffer
```

---

## Function

```php
function run(array $file_paths, array $args = [], int $behave = 0): array
```

Executes one or more files (in order) and returns a **loot array**.

- Included files run in the current scope (so `$args` is visible inside them).
- `RUN_RETURN` and (optionally) `RUN_OUTPUT` are written into the loot array.

---

## Basic execution

```php
// Just include
$loot = run(['/app/route/home.php']);

// Include with args (visible to the file as $args)
$loot = run(['/app/route/users.php'], ['edit', '42']);
```

---

## Invoke pattern

If a file returns a callable, `RUN_INVOKE` calls it.

```php
// users.php
return function(array $args) {
    [$action, $id] = $args;
    return qp("SELECT * FROM users WHERE id = ?", [$id])->fetch();
};
```

```php
$loot = run(['/app/route/users.php'], ['view', '42'], RUN_INVOKE);
// $loot[RUN_RETURN] is the callable return value
```

---

## Buffer pattern

Capture output instead of streaming it.

```php
$loot = run(['/app/render/template.php'], ['name' => 'World'], RUN_BUFFER);

echo $loot[RUN_OUTPUT];
```

---

## Absorb pattern

With `RUN_ABSORB`, the current output buffer is appended to the invoked callable's argument list.

```php
// page.php
?><article><?= $args['content'] ?></article><?php

return function(array $args) {
    $body = end($args); // appended buffer
    return "<!doctype html><html><body>$body</body></html>";
};
```

```php
$loot = run(['/app/render/page.php'], ['content' => 'Hello'], RUN_ABSORB);

echo $loot[RUN_RETURN];
```

---

## Chain pattern

`RUN_CHAIN` changes what arguments are passed to invoked callables:

- **without** `RUN_CHAIN`: each callable receives the original `$args`
- **with** `RUN_CHAIN`: each callable receives the current **loot bag**

In chain mode, the previous step's return value is available as `$args[RUN_RETURN]`.

```php
// auth.php
return fn(array $args) => checkin();

// users.php
use const bad\run\RUN_RETURN;
return fn(array $args) => get_users($args[RUN_RETURN]);

// render.php
use const bad\run\RUN_RETURN;
return fn(array $args) => render_template(['users' => $args[RUN_RETURN]]);
```

```php
$loot = run([
    '/app/mw/auth.php',
    '/app/route/users.php',
    '/app/render/users.php',
], [], RUN_INVOKE | RUN_CHAIN);

echo $loot[RUN_RETURN];
```

---

## Error handling

### Default: throw on error

```php
$loot = run(['/app/route/broken.php']);
// throws RuntimeException("include:/app/route/broken.php", 0xC0D, $original)
```

### RUN_RESCUE: try invoke even if include threw

If `include` throws but `RUN_RESCUE` is enabled, `run()` will still attempt to invoke **if** there is a callable in `RUN_RETURN`.

```php
$loot = run(['/app/route/noisy.php'], [], RUN_INVOKE | RUN_RESCUE);
```

### RUN_ONWARD: continue to next file

```php
$loot = run(['/app/a.php', '/app/b.php'], [], RUN_ONWARD);
```

---

## Complete example

```php
require 'badhat/io.php';
require 'badhat/run.php';
require 'badhat/http.php';

use function bad\io\path;
use function bad\io\seek;
use function bad\run\run;
use function bad\http\http_out;

use const bad\run\RUN_BUFFER;
use const bad\run\RUN_INVOKE;
use const bad\run\RUN_OUTPUT;

$routes = __DIR__ . '/app/route/';

[$file, $segments] = seek($routes, path($_SERVER['REQUEST_URI'], "\0"), '.php')
    ?? http_out(404, 'Not Found');

$loot = run([$file], $segments, RUN_BUFFER | RUN_INVOKE);

http_out(200, $loot[RUN_OUTPUT], [
    'Content-Type' => 'text/html; charset=utf-8',
]);
```