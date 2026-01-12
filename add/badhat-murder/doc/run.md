# BADHAT Run — File Execution


Run does one thing: execute files and capture results.

- Includes files
- Can invoke returned callables
- Can buffer output
- Can chain execution
- Wraps errors

Path resolution belongs to `io_map()`. HTTP output belongs to `http_out()`.

---

## Constants

```php
// Behavior flags
const RUN_BUFFER = 1;                           // capture output
const RUN_INVOKE = 2;                           // call returned callable
const RUN_ABSORB = 4 | RUN_BUFFER | RUN_INVOKE; // pass buffer to callable
const RUN_RESCUE = 8;                           // invoke even if include threw
const RUN_ONWARD = 16;                          // suppress throws, continue

const RUN_CHAIN  = 256;                         // pipe loot to next file

// Loot keys
const RUN_RETURN = -1;                          // include/invoke return value
const RUN_OUTPUT = -2;                          // captured output buffer

```

---

## Function

```php
function run(array $file_paths, array $io_args, int $behave = 0): array
```

Executes one or more files. Returns loot array with results.

**Parameters:**
- `$file_paths` — files to execute in order
- `$io_args` — arguments passed to callables
- `$behave` — bitmask of RUN_* flags

**Returns:** loot array containing `RUN_RETURN` and optionally `RUN_OUTPUT`

---

## Basic Execution

```php
// Just include (no buffering, no invoke)
$loot = run(['/app/route/home.php'], []);
// $loot[RUN_RETURN] = whatever the file returned (or 1)

// With args available as $args in file
$loot = run(['/app/route/users.php'], ['edit', '42']);
```

---

## Invoke Pattern

Files return callables. Run calls them.

```php
// users.php
return function(array $args) {
    [$action, $id] = $args;
    return qp("SELECT * FROM users WHERE id = ?", [$id])->fetch();
};
```

```php
$loot = run(['/app/route/users.php'], ['view', '42'], RUN_INVOKE);
// $loot[RUN_RETURN] = user row from database
```

---

## Buffer Pattern

Capture output instead of echoing.

```php
// template.php
<h1>Hello <?= $args['name'] ?></h1>
```

```php
$loot = run(['/app/render/template.php'], ['name' => 'World'], RUN_BUFFER);
echo $loot[RUN_OUTPUT]; // <h1>Hello World</h1>
```

---

## Absorb Pattern

Output becomes input to returned callable.

```php
// page.php
<article><?= $args['content'] ?></article>

<?php return function(array $args) {
    $body = $args[count($args) - 1]; // buffer is last
    return "<!DOCTYPE html><html><body>$body</body></html>";
};
```

```php
$loot = run(['/app/render/page.php'], ['content' => 'Hello'], RUN_ABSORB);
echo $loot[RUN_RETURN]; // full HTML document
```

---

## Chain Pattern

Loot flows between files.

```php
// auth.php
return fn($args) => ['user' => checkin()] + $args;

// users.php  
return fn($args) => ['users' => get_users($args['user'])] + $args;

// render.php
return fn($args) => render_template($args);
```

```php
$loot = run([
    '/app/mw/auth.php',
    '/app/route/users.php', 
    '/app/render/users.php'
], [], RUN_INVOKE | RUN_CHAIN);
```

Each file receives the previous file's return value.

---

## Error Handling

### Default: Throw on Error

```php
$loot = run(['/app/route/broken.php'], []);
// throws RuntimeException("include:/app/route/broken.php", 0xC0D, $original)
```

### RUN_RESCUE: Invoke Despite Include Error

```php
$loot = run(['/app/route/noisy.php'], [], RUN_INVOKE | RUN_RESCUE);
// if include throws but returned a callable before throwing,
// still attempts to invoke it
```

### RUN_ONWARD: Continue to Next File

```php
$loot = run(['/app/a.php', '/app/b.php'], [], RUN_ONWARD);
// if a.php throws, continues to b.php
// final throw suppressed
```

---

## Loot Structure

```php
$loot = run([$file], $args, RUN_ABSORB);

$loot[RUN_RETURN]; // -1: callable's return value
$loot[RUN_OUTPUT]; // -2: captured output (if RUN_BUFFER)
$loot['key'];      // any other keys from $args preserved
```

---

## Complete Example

```php
// index.php
require 'badhat/io.php';
require 'badhat/run.php';
require 'badhat/http.php';

$path = path($_SERVER['REQUEST_URI'], "\0", IO_URL);

// Route phase
$route = io_map('/app/route/', $path, '.php');
$loot = $route ? run($route, [], RUN_INVOKE) : [];

// Render phase
$render = io_map('/app/render/', $path, '.php', IO_NEST);
$loot = $render ? run($render, $loot, RUN_ABSORB) : $loot;

// Output
isset($loot[RUN_RETURN]) && is_string($loot[RUN_RETURN])
    ? http_out(200, $loot[RUN_RETURN], ['Content-Type' => ['text/html']])
    : http_out(404, 'Not Found');
```

