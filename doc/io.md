# Core IO Functions

This document describes the **core IO primitives** of BADHAT.

They form a **minimal execution lattice** that maps an HTTP request to one or more filesystem execution points, executes them deterministically, and optionally terminates the request.

BADHAT does **not** impose MVC, controllers, or response objects.
It provides **tools**, not decisions.



---

## Constants

```php
const IO_NEST = 1;    // Flexible routing: try file + file/file
const IO_DEEP = 2;    // Deep-first seek (N → 1 segments)
const IO_ROOT = 4;    // Root-first seek (1 → N segments)

const IO_CHAIN  = 8;  // Chain previous loot as args for next execution

const IO_RETURN = 16; // Loot slot: included file return value
const IO_BUFFER = 32; // Loot slot: output buffer content
const IO_INVOKE = 64; // Behavior: call return value with args
const IO_ABSORB = 128 | IO_BUFFER | IO_INVOKE;
// Behavior: call return value with args + output buffer
```

### Notes

* Flags are **orthogonal** and **composable**
* `IO_ABSORB` is a *semantic modifier*, not a shortcut
* No flag implies output, HTTP, or rendering decisions

---

## Functions

### `io_in(string $raw, string $accept = 'html', string $default = 'index'): array`

Normalizes and parses an inbound URI.

* Extracts the path component
* Rejects null bytes explicitly
* Normalizes duplicate slashes
* Extracts a trailing extension if present
* Applies a default route name if empty

**Returns:**

```php
[$path, $accept]
```

This function is **pure**: it does not read globals and does not emit output.

---

### `io_map(string $base_dir, string $uri_path, string $file_ext = 'php', int $behave = 0): ?array`

Resolves a URI path to one or more executable filesystem paths.

Resolution happens in **two stages**:

1. **Direct lookup** via `io_look()`
2. **Segment-based seek** via `io_seek()` (if enabled)

**Behavior flags:**

* `IO_NEST` – enables `file/file.ext` fallback
* `IO_DEEP` – deep-first segment walk
* `IO_ROOT` – root-first segment walk

**Returns one of:**

* `[string $filepath]`
* `[string $filepath, array $remaining_segments]`
* `null`

No normalization is performed on the return shape.
Consumers **must branch consciously**.

---

### `io_run(array $file_paths, array $io_args, int $behave = 0): array`

Executes one or more resolved filesystem paths.

For each file:

* Includes it
* Optionally captures output
* Optionally invokes the returned value
* Optionally chains the result into the next execution

**Behavior flags:**

* `IO_BUFFER` – capture output buffer
* `IO_INVOKE` – call return value as callable
* `IO_ABSORB` – pass buffer into callable
* `IO_CHAIN` – propagate previous loot as args

**Invocation semantics:**

* `IO_INVOKE` → `fn(array $args)`
* `IO_ABSORB` → `fn(array $args_with_buffer)`

**Returns:**

The **last execution loot only**, containing at least:

```php
[
  IO_RETURN => mixed,
  IO_BUFFER => string (if enabled)
]
```

Earlier executions are intentionally discarded unless chained.

---

### `io_die(int $status, string $body, array $headers = []): void`

Terminates execution and emits an HTTP response.

* Sets HTTP status code
* Emits headers (CRLF-safe)
* Outputs body
* Calls `exit`

This function is **terminal**.

If `io_die()` is called anywhere — route, render, index —
**no further BADHAT logic should run**.

---

### `io_look(string $base_dir, string $candidate, string $file_ext, int $behave = 0): ?string`

Resolves a single candidate path to a filesystem file.

Tries, in order:

1. `$base_dir/$candidate.$file_ext`
2. `$base_dir/$candidate/basename($candidate).$file_ext` (if `IO_NEST`)

**Returns:**

* Full filepath if found
* `null` otherwise

No segment logic occurs here.

---

### `io_seek(string $base_dir, string $uri_path, string $file_ext, int $behave = 0): ?array`

Performs **segment-based path resolution**.

* Splits URI into segments
* Walks segments either:

  * deep → shallow (`IO_DEEP`)
  * shallow → deep (`IO_ROOT`)
* Uses `io_look()` at each step

**Returns:**

```php
[$filepath, $remaining_segments]
```

or `null` if no match is found.

The remaining segments are **positional arguments**, not parameters.

---

## Usage Patterns

### Mirroring (default)

```php
[$path, $accept] = io_in($_SERVER['REQUEST_URI']);

$route = io_map('/app/route', $path, 'php');

$loot = $route
    ? io_run($route, [])
    : io_die(404, 'Not Found');
```

---

### Deep-first routing (API style)

```php
$route = io_map(
    '/app/api',
    'users/42/profile',
    'php',
    IO_DEEP
);

// Possible resolution:
// users/42/profile.php
// users/42.php
// users.php
// index.php
```

Returned arguments are positional and untyped.

---

### Output-driven rendering

```php
$loot = io_run(
    [$route],
    [],
    IO_BUFFER | IO_INVOKE
);

echo $loot[IO_BUFFER];
```

No implicit rendering occurs.
If output exists, it is because a file produced it.

---

## Design Notes

* BADHAT does **not** normalize return shapes
* BADHAT does **not** enforce architectural roles
* BADHAT does **not** hide execution order
* BADHAT exposes sharp tools deliberately

If something hurts, it is because **control is explicit**.
