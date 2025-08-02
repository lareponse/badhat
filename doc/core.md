# Core IO Functions

**Constants:**
```php
const IO_DEEP = 1;     // Deep-first route lookup
const IO_ROOT = 2;     // Root-first route lookup  
const IO_FLEX = 4;     // Try file + file/file patterns

const IO_RETURN = 16;  // Loot: included file return value
const IO_BUFFER = 32;  // Loot: output buffer content
const IO_INVOKE = 64;  // Behavior: call return value with args
const IO_ABSORB = 128; // Behavior: call return value with buffer+args
```

---

## Functions

`http_in(int $max_decode = 9): string`

>Validates request URI path with decode loop protection.
>
>- CSRF validation (if `csrf_validate()` exists)
>- Prevents decode loops via counter
>- Normalizes multiple slashes
>- Returns clean path

**Throws:** `DomainException` on decode loop

`http_out(int $status, string $body, array $headers = []): void`

>Sends HTTP response and exits.

**Exits**

`io_map(string $base, string $guarded_uri, string $ext = 'php', int $behave = 0): array`

>Maps URI to filesystem path.
>
>- Default: mirroring mode (URI = filesystem path)
>- `IO_DEEP|IO_ROOT`: delegates to `io_seek()` for segment matching
>- `IO_FLEX`: tries both `file.ext` and `file/file.ext` patterns

**Returns:** `[filepath]` or `[filepath, args]` or `[]`

`io_run(string $io_path, array $io_args, int $behave = 0): array`

>Executes file with configurable capture and invocation.
>
>- `IO_BUFFER|IO_ABSORB`: captures output buffer via `ob_ret_get()`
>- `IO_INVOKE`: calls return value as `fn($io_args)`
>- `IO_ABSORB`: calls return value as `fn($buffer, $io_args)`

**Returns:** Loot array with `IO_RETURN`, `IO_BUFFER`, `IO_INVOKE`, `IO_ABSORB` keys

`io_look(string $base, string $candidate, string $ext, int $behave = 0): ?string`

>Resolves candidate to existing file path.
>
>- Tries `$base/$candidate.$ext`
>- `IO_FLEX`: also tries `$base/$candidate/basename($candidate).$ext`

**Returns:** Full filepath or `null`

`io_seek(string $base, string $guarded_uri, string $ext, int $behave = 0): array`

>Segment-based file matching with directional search.
>
>- `IO_ROOT`: searches shallow to deep (1→N segments)
>- `IO_DEEP`: searches deep to shallow (N→1 segments)
>- Uses `io_look()` for file resolution

**Returns:** `[filepath, remaining_args]` or `[]`

`ob_ret_get(string $path, array $include_vars = []): array`

>Includes file with output buffering and variable extraction.

**Returns:** `[return_value, buffer_content]`

---

## Usage Patterns

**Mirroring (default):**
```php
$route = io_map('/app/route', '/users/edit', 'php');
// → ['/app/route/users/edit.php'] or []
```

**Deep-first matching:**
```php
$route = io_map('/app/route', '/api/users/123', 'php', IO_DEEP);
// Tries: api/users/123.php → api/users.php → api.php → index.php
// → [filepath, remaining_segments]
```

**Flexible execution:**
```php
$loot = io_run($filepath, [], IO_BUFFER | IO_INVOKE);
// → [IO_RETURN => $return, IO_BUFFER => $output, IO_INVOKE => $called_result]
```