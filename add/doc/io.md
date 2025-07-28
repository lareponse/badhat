# io.md
**Requires:** glibc 2.26+
---

`http_in(max_length, max_decode): string`

>Validates and decodes request URI path.
>
>- CSRF validation (if `csrf_validate()` exists)
>- Prevents path traversal and stream wrappers
>- Limits decode iterations and path length
>- Returns normalized path

**Throws:** `DomainException` on validation failure
**Returns:** normalized URI path

`http_out(int $status, string $body, array $headers = []): void`

> Sends HTTP response and exits.

**Exits**

`io_route(start, guarded_uri, default): array`

>File-based routing with directory traversal protection.
> 
>Uses http_in() to guard and parse the URI path 
>- Searches for `{segment}.php` files from deepest to shallowest path
>- Falls back to `{segment}/{segment}.php` pattern
>- Uses `$default` when no segments match

**Returns:** `[IO_PATH => $filepath, IO_ARGS => $remaining_segments]`


`io_fetch(io_route, include_vars, behave): array`

>Executes route with configurable behavior.
>
>- Includes file with extracted variables
>- Captures return value and output buffer
>- Optional callable invocation via `IO_INVOKE|IO_ABSORB` flags

**Returns:** Route array + `IO_RETURN` + `IO_OB_GET` + optional invoke results

`ob_ret_get(path, include_vars): array`

>Includes file with output buffering.

**Returns:** `[return_value, buffer_content]`

# Usage Pattern

```php
$uri = http_in();
$route = io_route('/app/controllers', $uri, 'index');
$result = io_fetch($route, ['db' => $pdo], IO_INVOKE);
```
