# badhat\http

Your app needs to speak HTTP.

Just a few sharp helpers to normalize an incoming URL, collect headers safely, and emit a response without repeating the same `header()` / `http_response_code()` ceremony everywhere.

> One nonce, cached. One output function that returns an exit status.
> HTTP in, HTTP out and one header helper that refuses bad input and accumulates until flush. That's it.

---

## 1) First, you normalize the incoming URL

Whether you get a raw path, a full URL, or something in between, `in()` strips the parts you don't route on.

```php
use function bad\http\in;

$path = in($_SERVER['REQUEST_URI'] ?? '/');
```

What it does:

* removes a leading scheme when `:` appears before any `/`, `?`, or `#`
* removes an authority when the string starts with `//` (or becomes `//` after scheme removal)
* returns whatever remains (path + optional query/fragment)

```php
in('/a/b?x=1');               // "/a/b?x=1"
in('https://ex.com/a/b?x=1'); // "/a/b?x=1"
in('//ex.com/a/b?x=1');       // "/a/b?x=1"
in('mailto:user@ex.com');     // "user@ex.com"
```

**Default story:**
"I want one routing input, no matter what shape the URL came in."

---

## 2) Then, you collect headers

`headers()` validates, accumulates, and eventually flushes headers. Bitmask-driven.

```php
use function bad\http\headers;
use const bad\http\{H_SET, H_ADD, H_CSV, H_OUT, H_LOCK, H_FOLD};

// Single value (replaces any previous)
headers(H_SET, 'Content-Type', 'application/json');

// Multiple values as separate header lines
headers(H_ADD, 'Set-Cookie', 'a=1; Path=/');
headers(H_ADD, 'Set-Cookie', 'b=2; Path=/');

// Multiple values as CSV (combined into one line)
headers(H_CSV, 'Vary', 'Accept');
headers(H_CSV, 'Vary', 'Accept-Encoding');
// emits: Vary: Accept, Accept-Encoding
```

Headers accumulate in a static map until you flush:

```php
headers(H_OUT);  // emits all accumulated headers, clears the map
```

**Default story:**
"Accumulate headers safely. Flush when ready."

---

## 3) Header modes

| Flag | Value | Effect |
|------|-------|--------|
| `H_SET` | 1 | Single value, replaces previous |
| `H_ADD` | 2 | Append as separate header line |
| `H_CSV` | 6 | Append as comma-separated value |
| `H_OUT` | 8 | Flush all headers, clear map |
| `H_LOCK` | 16 | Prevent further changes to this header |
| `H_FOLD` | 32 | Promote existing H_SET value to H_ADD/H_CSV |

### Lock a header

```php
headers(H_SET | H_LOCK, 'X-Frame-Options', 'DENY');
headers(H_SET, 'X-Frame-Options', 'SAMEORIGIN');  // throws BadFunctionCallException
```

### Promote SET to ADD

When you started with `H_SET` but later need multiple values:

```php
headers(H_SET, 'Cache-Control', 'no-cache');
headers(H_ADD | H_FOLD, 'Cache-Control', 'no-store');
// emits both as separate lines
```

### Set-Cookie requires H_ADD

```php
headers(H_SET, 'Set-Cookie', 'x=1');  // throws BadFunctionCallException
headers(H_ADD, 'Set-Cookie', 'x=1');  // correct
```

---

## 4) Validation

`headers()` throws on invalid input:

| Exception | Condition |
|-----------|-----------|
| `InvalidArgumentException` | Empty or missing name |
| `InvalidArgumentException` | Name contains non-token characters |
| `InvalidArgumentException` | Value contains ASCII control characters |
| `BadFunctionCallException` | Set-Cookie without H_ADD |
| `BadFunctionCallException` | Header is locked |
| `BadFunctionCallException` | H_SET and H_ADD both set |

Token characters (`HTTP_TCHAR`): ``!#$%&'*+-.^_`|~`` plus alphanumerics.

---

## 5) Finally, you emit a response

`out()` sets the status code, flushes accumulated headers, outputs the body, and returns a process exit status.

```php
use function bad\http\out;

headers(H_SET, 'Content-Type', 'text/plain; charset=utf-8');
exit(out(404, 'Not found'));
```

What it does:

1. `http_response_code($code)`
2. `headers(H_OUT)` — flushes accumulated headers
3. echoes `$body` only when appropriate (no body for `1xx`, `204`, `205`, `304`)
4. returns an exit status derived from the HTTP code

Exit status mapping:

| HTTP code | Exit |
|-----------|------|
| `< 400` | `0` |
| `400–499` | `4` |
| `500–599` | `5` |
| other | `1` |

**Note:** The `$headers` parameter in `out()` is currently unused. Accumulate headers via `headers()` calls before calling `out()`.

**Default story:**
"Emit the response. Give me an exit code. Don't make me remember the rules."

---

## 6) CSP nonce, when you need it

`csp_nonce()` gives you a per-request nonce, cached after the first call.

```php
use function bad\http\csp_nonce;

$nonce = csp_nonce();

headers(H_SET, 'Content-Security-Policy', "script-src 'nonce-$nonce'");
headers(H_SET, 'Content-Type', 'text/html; charset=utf-8');
exit(out(200, $html));
```

**Default story:**
"I need a nonce once. Don't generate it twice."

---

## Reference

### Constants

| Constant | Description |
|----------|-------------|
| `H_SET` | Single value mode |
| `H_ADD` | Append as separate lines |
| `H_CSV` | Append as CSV |
| `H_OUT` | Flush and clear |
| `H_LOCK` | Prevent changes |
| `H_FOLD` | Promote SET to ADD/CSV |
| `CTRL_ASCII` | All ASCII control chars (forbidden in values) |
| `HTTP_TCHAR` | Allowed header name characters |

### Functions

| Function | Signature | Purpose |
|----------|-----------|---------|
| `in` | `(string $url): string` | Strip scheme/authority for routing |
| `headers` | `(int $behave, ?string $name = null, $value = null): array` | Accumulate/flush headers |
| `out` | `(int $code, $body = null, array $headers = []): int` | Emit response, return exit status |
| `csp_nonce` | `(): string` | Per-request CSP nonce |

### Throws

`headers()` throws `InvalidArgumentException` for validation failures and `BadFunctionCallException` for usage violations. `out()` does not validate — use `headers()` for that.