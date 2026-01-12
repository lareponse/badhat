# BADHAT HTTP — Response Output

`bad\http` emits responses.

- `http_headers()` validates + accumulates headers (optional helper)
- `http_out()` sets status, emits headers/body, then `exit`s
- `csp_nonce()` gives you a per-request CSP nonce

Path resolution lives in `bad\io\path()` / `bad\io\look()` / `bad\io\seek()`.
Execution lives in `bad\run\run()`.

---

## Constants

```php
const ASCII_CTL = "\x00...\x1F\x7F"; // all ASCII control chars
const HTTP_PATH_UNSAFE = ' ' . ASCII_CTL; // space + all control chars
const HTTP_TCHAR = "!#$%&'*+-.^_`|~0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
```

> `HTTP_PATH_UNSAFE` is a good *forbidden set* to pass to `bad\io\path(...)`.

---

## Functions

### http_headers

```php
function http_headers(string $name, string $value, bool $replace = true): ?array
```

Validates and stores headers in a static map. Returns the full map on success, or `null` on invalid input.

Rules (as implemented):
- header name must be non-empty and contain only `HTTP_TCHAR`
- header value must not contain any `ASCII_CTL`

```php
http_headers('Content-Type', 'application/json');
http_headers('Set-Cookie', 'a=1; Path=/', false);
http_headers('Set-Cookie', 'b=2; Path=/', false);
```

---

### http_out

```php
function http_out(int $code, ?string $body = null, array $headers = []): void
```

Emits an HTTP response and exits.

- calls `http_response_code($code)`
- emits each header value via `header("$name: $v", false)`
- echoes `$body` only when `$code >= 200` and not `204/205/304`
- calls `exit`

Header values may be a string or an array of strings:

```php
http_out(200, 'ok', [
    'Content-Type' => 'text/plain; charset=utf-8',
]);

http_out(200, 'ok', [
    'Set-Cookie' => ['a=1; Path=/', 'b=2; Path=/'],
]);
```

> `http_out()` does **not** validate header names/values. If you want validation, build them via `http_headers()` (or validate yourself) first.

---

### csp_nonce

```php
function csp_nonce(): string
```

Returns `bin2hex(random_bytes(16))`, cached for the rest of the request.

```php
$nonce = csp_nonce();

http_out(200, $html, [
    'Content-Security-Policy' => "script-src 'nonce-$nonce'",
    'Content-Type'           => 'text/html; charset=utf-8',
]);
```