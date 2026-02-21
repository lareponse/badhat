# bad\run

You mapped a request to files.
Now you need to **run them** without turning every call-site into a ceremony of `include`, output buffers, callable plumbing and try-catches.


`bad\run` is wrapper around include, in a single verb:

No lifecycle.
No context object.
Just PHP + a bitmask.

---

## loot(): the only call-site

```php
use function bad\run\loot;
use const bad\run\{RESULT, BUFFER, INVOKE};

$loot = loot('/path/to/handler.php', $args, INVOKE);
$loot = loot(['/path/a.php', '/path/b.php'], $args, BUFFER);
```

### Signature

```php
loot(array|string $paths, mixed $args = [], int $behave = 0): array
```

Pass a single path string or an array of paths. They run in order.

### Return value

`loot()` returns the **loot array** from the last step.

Three reserved slots may be present:

| Slot     | Index | Contains |
|----------|------:|----------|
| `RESULT` |   `0` | Return value of include (or invoke, if `INVOKE`) |
| `BUFFER` |   `1` | Captured output string (when `BUFFER` is set) |
| `FAULTS` |   `2` | Array of collected `\Throwable`s (when `FAULTS` is set) |

---

## Flags

### BUFFER

Capture output per file.

- without `BUFFER`: the file prints normally
- with `BUFFER`: output is captured into `$loot[BUFFER]`

```php
use const bad\run\{BUFFER};

$loot = loot('/views/hello.php', ['name' => 'World'], BUFFER);
echo $loot[BUFFER] ?? '';
```

### SILENT

Suppress output. Like `BUFFER` but discards instead of capturing.

- `ob_start()` before include, `ob_end_clean()` after
- nothing stored in loot
- also suppresses invoke output when combined with `INVOKE`

```php
use const bad\run\SILENT;

loot('/jobs/cleanup.php', [], SILENT);
```

### INVOKE

If the file returned a callable, invoke it.

The callable always receives two arguments:

1. `$loot` — the current loot array (including `RESULT`, `BUFFER` if captured, `FAULTS` if enabled)
2. `$args` — the original seed

The invoked result overwrites `$loot[RESULT]`.

```php
use const bad\run\{INVOKE, RESULT};

$loot = loot('/routes/user.php', ['id' => '42'], INVOKE);
echo $loot[RESULT];
```

`routes/user.php`:

```php
return function(array $loot, array $args) {
    return 'user:' . ($args['id'] ?? '');
};
```

### SPREAD

Controls how `$args` reaches the callable.

- without `SPREAD`: `$callable($loot, $args)`
- with `SPREAD`: `$callable($loot, ...$args)`

```php
use const bad\run\{INVOKE, SPREAD, RESULT};

$loot = loot('/routes/user.php', ['42'], INVOKE | SPREAD);
```

`routes/user.php`:

```php
return function(array $loot, string $id) {
    return "user:$id";
};
```

### FAULTS

Collect exceptions instead of throwing.

- without `FAULTS`: first `\Throwable` from include, invoke, or OB guard stops execution
- with `FAULTS`: exceptions are appended to `$loot[FAULTS]` and execution continues through the current step

```php
use const bad\run\{INVOKE, FAULTS, RESULT};

$loot = loot('/routes/risky.php', [], INVOKE | FAULTS);

if (!empty($loot[FAULTS])) {
    // inspect, log, recover
}
```

Faults from include, invoke, and OB guards all land in the same array.

### RELOOT

Retry-on-fault. Once.

When a step faults (requires `FAULTS`), the same path is re-included one more time.
The retry sees the previous faults in `$loot[FAULTS]` — the file can inspect what went wrong and adapt.

If the retry also faults, execution moves to the next path. No infinite loop.

```php
use const bad\run\{INVOKE, FAULTS, RELOOT, RESULT};

$loot = loot('/routes/fragile.php', [], INVOKE | FAULTS | RELOOT);
```

`routes/fragile.php`:

```php
if (!empty($loot[FAULTS] ?? [])) {
    // we're on the retry pass — previous faults are visible
    return ['fallback' => true];
}

// normal path that might throw
return function(array $loot, $args) {
    // ...
};
```

### OB_TRIM / OB_SAME

Buffer discipline flags. Checked after each step.

- `OB_TRIM`: if the file leaked extra output buffers, clean them silently
- `OB_SAME`: assert that `ob_get_level()` is unchanged after the step

When the guard fails:

- without `FAULTS`: throws `\RuntimeException`
- with `FAULTS`: appends to `$loot[FAULTS]`

```php
use const bad\run\{INVOKE, OB_TRIM, OB_SAME};

$loot = loot($files, $args, INVOKE | OB_TRIM | OB_SAME);
```

---

## Callable contract

Every invoked callable receives exactly two arguments:

```php
function(array $loot, mixed $args): mixed
```

With `SPREAD`:

```php
function(array $loot, ...$args): mixed
```

`$loot` is always first. It contains whatever the include phase produced (`RESULT`, `BUFFER`, `FAULTS`).
`$args` is always the original seed passed to `loot()`.

---

## Layout pattern (BUFFER + INVOKE)

One file prints the body and returns a wrapper callable.
The callable reads the captured buffer from `$loot[BUFFER]`.

```php
use const bad\run\{BUFFER, INVOKE, RESULT};

$loot = loot('/views/page.php', ['title' => 'Hello'], BUFFER | INVOKE);
echo $loot[RESULT] ?? '';
```

`views/page.php`:

```php
?><h1><?= htmlspecialchars($args['title'] ?? '') ?></h1><?php

return function(array $loot, $args) {
    $body = $loot[BUFFER] ?? '';
    return "<!doctype html><html><body>$body</body></html>";
};
```

---

## Fault handling

Default: fail-fast. First `\Throwable` propagates.

```php
try {
    $loot = loot($files, $args, INVOKE);
} catch (\Throwable $t) {
    // your policy
}
```

With `FAULTS`: exceptions are collected, execution continues.
With `FAULTS | RELOOT`: faulted step retries once, carrying previous faults.

---

## Reference

### Constants

| Constant  | Value | Purpose |
|-----------|------:|---------|
| `RESULT`  |   `0` | Loot slot: return value |
| `BUFFER`  |   `1` | Loot slot + flag: capture output |
| `FAULTS`  |   `2` | Loot slot + flag: collect exceptions |
| `SILENT`  |   `4` | Suppress output (no capture) |
| `INVOKE`  |   `8` | Invoke callable return values |
| `SPREAD`  |  `16` | Spread `$args` into callable |
| `RELOOT`  |  `32` | Retry faulted step once |
| `OB_TRIM` | `128` | Clean leaked output buffers |
| `OB_SAME` | `256` | Assert buffer level unchanged |

### Function

```php
loot(array|string $paths, mixed $args = [], int $behave = 0): array
```

### Fault shape

All faults are the original `\Throwable` — no wrapping, no synthetic codes.
OB guard faults are `\RuntimeException` with message `loot:ob:{$path}`.

### OB guard

```php
ob(int $behave = 0): \Closure
```

Returns a closure that checks `ob_get_level()` against the level at creation time.
Called internally by `loot()` when `OB_TRIM` or `OB_SAME` is set.