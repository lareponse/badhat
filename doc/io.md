# Core IO Functions

Minimal execution lattice: URI → filesystem → execution → response.

No MVC. No controllers. Just tools.

---

## Constants

```php
const IO_RETURN = 0;    // Loot slot: included file return value
const IO_BUFFER = 1;    // Loot slot: output buffer content

const IO_INVOKE = 2;    // Call fn(args), store in IO_RETURN
const IO_ABSORB = 4 | IO_BUFFER | IO_INVOKE;  // Call fn(args + buffer)

const IO_NEST   = 8;    // Try file + file/file patterns
const IO_DEEP   = 16;   // Deep-first seek (N → 1 segments)
const IO_ROOT   = 32;   // Root-first seek (1 → N segments)

const IO_CHAIN  = 64;   // Propagate loot as args to next file
```

**Flags are orthogonal and composable.**

---

## Functions

### `io_in(string $raw, string $accept = 'html', string $default = 'index'): array`

Normalize inbound URI.

```php
[$path, $ext] = io_in($_SERVER['REQUEST_URI']);
```

- Extracts path component
- Rejects null bytes (400)
- Normalizes `//` → `/`
- Extracts trailing extension if present
- Falls back to `$default` if empty

**Returns:** `[$path, $accept_or_extension]`

**Pure function:** no globals read, no output.

---

### `io_map(string $base_dir, string $uri_path, string $file_ext = 'php', int $behave = 0): ?array`

Resolve URI to executable path.

**Resolution stages:**
1. Direct lookup via `io_look()`
2. Segment-based seek via `io_seek()` (if `IO_DEEP|IO_ROOT`)

**Returns:**
- `[string $filepath]` — exact match
- `[string $filepath, array $remaining_segments]` — seek match
- `null` — no match

```php
// Direct match
$route = io_map('/app/route', 'users/edit', 'php');
// → ['/app/route/users/edit.php']

// Deep-first with args
$route = io_map('/app/route', 'api/users/42', 'php', IO_DEEP);
// → ['/app/route/api/users.php', ['42']]
```

---

### `io_run(array $file_paths, array $io_args, int $behave = 0): array`

Execute resolved paths.

**For each file:**
1. Optionally capture output buffer (`IO_BUFFER`)
2. Include file
3. Optionally invoke returned callable (`IO_INVOKE`)
4. Optionally pass buffer to callable (`IO_ABSORB`)
5. Optionally chain result to next file (`IO_CHAIN`)

**Invocation signatures:**
- `IO_INVOKE` → `fn(array $args)`
- `IO_ABSORB` → `fn(array $args_with_buffer_appended)`

**Returns:** Last execution loot:
```php
[
    IO_RETURN => mixed,  // return value or callable result
    IO_BUFFER => string  // if IO_BUFFER enabled
]
```

```php
$loot = io_run(['/app/route/users.php'], ['id' => 42], IO_INVOKE);
$data = $loot[IO_RETURN];

$loot = io_run(['/app/render/users.php'], $data, IO_ABSORB);
$html = $loot[IO_RETURN];  // callable result
```

---

### `io_die(int $status, string $body, array $headers = []): void`

Terminate with HTTP response.

```php
io_die(404, 'Not Found');
io_die(200, $json, ['Content-Type' => 'application/json']);
```

- Sets status code
- Emits headers (validates no CRLF injection)
- Outputs body
- **Calls `exit`**

---

### `io_look(string $base_dir, string $candidate, string $file_ext, int $behave = 0): ?string`

Direct file resolution.

Tries in order:
1. `$base_dir/$candidate.$file_ext`
2. `$base_dir/$candidate/basename($candidate).$file_ext` (if `IO_NEST`)

**Security:** validates `realpath()` stays within `$base_dir`.

**Returns:** full filepath or `null`

---

### `io_seek(string $base_dir, string $uri_path, string $file_ext, int $behave = 0): ?array`

Segment-based resolution.

- `IO_DEEP`: N → 1 segments (deepest first)
- `IO_ROOT`: 1 → N segments (shallowest first)

Uses `io_look()` at each depth.

**Returns:** `[$filepath, $remaining_segments]` or `null`

---

## Usage Patterns

### Mirroring (default)

```php
[$path, $accept] = io_in($_SERVER['REQUEST_URI']);
$route = io_map('/app/route', $path);

$route 
    ? io_run($route, [])
    : io_die(404, 'Not Found');
```

### Deep-first API routing

```php
// /api/users/42/profile
$route = io_map('/app/api', 'users/42/profile', 'php', IO_DEEP);

// Tries: users/42/profile.php → users/42.php → users.php → index.php
// Returns: ['/app/api/users.php', ['42', 'profile']]
```

### Two-phase execution

```php
// Phase 1: Route (data)
$route = io_map('/app/route', $path, 'php', IO_DEEP);
$loot = io_run($route, [], IO_INVOKE);

// Phase 2: Render (output)
$render = io_map('/app/render', $path, 'php', IO_DEEP | IO_NEST);
$loot = io_run($render, $loot, IO_ABSORB);

io_die(200, $loot[IO_RETURN], ['Content-Type' => 'text/html']);
```

### Chained execution

```php
$files = ['/app/middleware/auth.php', '/app/route/dashboard.php'];
$loot = io_run($files, [], IO_INVOKE | IO_CHAIN);
// Each file receives previous file's return as args
```

---

## Resolution Examples

```
URI                     Flag        Result
────────────────────────────────────────────────────
/users                  default     route/users.php
/users/edit             default     route/users/edit.php
/users/edit             IO_NEST     route/users/edit/edit.php (fallback)
/api/users/42           IO_DEEP     route/api/users.php + ['42']
/api/users/42           IO_ROOT     route/api.php + ['users','42']
/deep/missing/path      IO_DEEP     tries: deep/missing/path.php
                                         → deep/missing.php + ['path']
                                         → deep.php + ['missing','path']
```

---

## Design Notes

- No normalized return shapes
- No architectural role enforcement
- No hidden execution order
- Sharp tools, explicit control