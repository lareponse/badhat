# BADHAT HTTP — Response Output


HTTP does one thing: emit responses.

- Validates headers
- Sets status code
- Outputs body (when appropriate)
- Exits

Path resolution belongs to `io_map()`. Execution belongs to `run()`.


---

## Constants

```php
const HTTP_HDR_SOFT   = 64;   // reserved
const HTTP_HDR_STRICT = 128;  // reserved

const ASCII_CTL = "\x00\x01...\x1F\x7F";  // control characters
const HTTP_PATH_UNSAFE = ' ' . ASCII_CTL;
const HTTP_TCHAR = '!#$%&\'*+-.^_`|~0-9A-Za-z';  // valid header name chars
```

---

## Functions

### http_headers

```php
function http_headers(string $name, string $value, bool $replace = true): ?array
```

Accumulates headers with validation. Returns header state or `null` on invalid input.

```php
// Set header (replaces existing)
http_headers('Content-Type', 'application/json');

// Append header (for Set-Cookie, etc.)
http_headers('Set-Cookie', 'a=1', false);
http_headers('Set-Cookie', 'b=2', false);

// Invalid name (contains non-tchar)
http_headers('Bad Header', 'value');  // returns null

// Invalid value (contains control char)
http_headers('X-Data', "has\x00null");  // returns null
```

**Validation:**
- Header names must contain only `HTTP_TCHAR` characters
- Header values must not contain `ASCII_CTL` characters

---

### http_out

```php
function http_out(int $code, ?string $body = null, array $headers = []): array
```

Emits HTTP response and exits. Does not return.

```php
// Simple response
http_out(200, 'Hello World');

// With headers
http_out(200, json_encode($data), [
    'Content-Type' => ['application/json'],
    'Cache-Control' => ['no-store']
]);

// Multiple values for same header
http_out(200, $body, [
    'Set-Cookie' => ['a=1; Path=/', 'b=2; Path=/']
]);

// No body (204, 205, 304 or <200)
http_out(204, null);
http_out(301, null, ['Location' => ['/new-path']]);
```

**Behavior:**
- Sets `http_response_code($code)`
- Emits all headers via `header()` with `replace=false`
- Echoes body unless: `$code < 200`, `$code === 204`, `$code === 205`, or `$code === 304`
- Calls `exit`

---

## Patterns

### JSON API

```php
$data = run($route, $args, RUN_INVOKE)[RUN_RETURN];

http_out(200, json_encode($data), [
    'Content-Type' => ['application/json; charset=utf-8']
]);
```

### Redirect

```php
http_out(302, null, ['Location' => ['/dashboard']]);
```

### Error Response

```php
http_out(404, 'Not Found');
http_out(500, 'Internal Server Error');
```

### Download

```php
http_out(200, $file_contents, [
    'Content-Type' => ['application/octet-stream'],
    'Content-Disposition' => ['attachment; filename="export.csv"']
]);
```

### CORS

```php
http_out(200, $body, [
    'Access-Control-Allow-Origin' => ['https://example.com'],
    'Access-Control-Allow-Methods' => ['GET, POST, OPTIONS']
]);
```

---

## Header Format

Headers passed to `http_out()` use array values:

```php
$headers = [
    'Header-Name' => ['value1', 'value2'],  // multiple values
    'Single' => ['only-value'],              // single value
];
```

Each value emits a separate `header()` call with `replace=false`.

