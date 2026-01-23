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
* returns whatever remains (path + optional query/fragment), or `''` if nothing remains

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
use const bad\http\{H_SET, H_ADD, H_CSV, H_OUT, H_LOCK};

// Single value (replaces any previous)
headers(H_SET, 'Content-Type', 'application/json');

// Multiple values as separate header lines
headers(H_ADD, 'Set-Cookie', 'a=1; Path=/');
headers(H_ADD, 'Set-Cookie', 'b=2; Path=/');

// Multiple values as CSV (combined into one line on flush)
headers(H_CSV, 'Vary', 'Accept');
headers(H_CSV, 'Vary', 'Accept-Encoding');
// emits: Vary: Accept, Accept-Encoding
```

Headers accumulate in a static map until you flush:

```php
headers(H_OUT);  // emits all accumulated headers, clears the map, returns what was staged
```

**Default story:**
"Accumulate headers safely. Flush when ready."

---

## 3) Header modes

| Flag     | Value | Effect                                      |
| -------- | ----- | ------------------------------------------- |
| `H_SET`  | 1     | Single value, replaces previous             |
| `H_ADD`  | 2     | Append as separate header line              |
| `H_CSV`  | 4     | Append value, emit as comma-separated line  |
| `H_OUT`  | 8     | Flush all headers, clear map                |
| `H_LOCK` | 16    | Prevent further changes to this header      |

### Lock a header

```php
headers(H_SET | H_LOCK, 'X-Frame-Options', 'DENY');
headers(H_SET, 'X-Frame-Options', 'SAMEORIGIN');  // throws BadFunctionCallException
```

### Set-Cookie requires H_ADD

```php
headers(H_SET, 'Set-Cookie', 'x=1');  // throws InvalidArgumentException
headers(H_ADD, 'Set-Cookie', 'x=1');  // correct
```

`Set-Cookie` is restricted to `H_ADD` and optionally `H_LOCK` (no `H_SET`, no `H_CSV`).

---

## 4) Validation

`headers()` throws on invalid input:

| Exception                  | Condition                                                                   |
| -------------------------- | --------------------------------------------------------------------------- |
| `InvalidArgumentException` | Empty or missing name                                                       |
| `InvalidArgumentException` | Name contains non-token characters                                          |
| `InvalidArgumentException` | Value contains ASCII control characters                                     |
| `InvalidArgumentException` | `Set-Cookie` used without `H_ADD` (or combined with other disallowed flags) |
| `BadFunctionCallException` | Header is locked                                                            |
| `BadFunctionCallException` | `H_SET` and `H_ADD` both set                                                |

Token characters (`HTTP_TCHAR`): ``!#$%&'*+-.^_`|~`` plus alphanumerics.

Additional behavior:

* Header names are normalized to lowercase internally (and emitted in lowercase on flush).

---

## 5) Finally, you emit a response

`out()` sets the status code, flushes accumulated headers, optionally emits one extra raw header line, and outputs the body.

```php
use function bad\http\out;

headers(H_SET, 'Content-Type', 'text/plain; charset=utf-8');
exit(out(404, 'Not found'));
```

What it does:

1. `http_response_code($code)`
2. `headers(H_OUT)` — flushes accumulated headers
3. if `$header` is provided, calls `header($header)` (unvalidated, emitted after the flush)
4. echoes `$body` only when appropriate (no body for `<200`, `204`, `205`, `304`)
5. returns an exit status derived from the HTTP code

Exit status mapping:

| HTTP code | Exit |
| --------- | ---- |
| `< 400`   | `0`  |
| `400–499` | `4`  |
| `500–599` | `5`  |
| other     | `1`  |

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

| Constant     | Description                                   |
| ------------ | --------------------------------------------- |
| `H_SET`      | Single value mode                             |
| `H_ADD`      | Append as separate lines                      |
| `H_CSV`      | Append value, emit as comma-separated line    |
| `H_OUT`      | Flush and clear                               |
| `H_LOCK`     | Prevent changes                               |
| `CTRL_ASCII` | All ASCII control chars (forbidden in values) |
| `HTTP_TCHAR` | Allowed header name characters                |

### Functions

| Function    | Signature                                                     | Purpose                            |
| ----------- | ------------------------------------------------------------- | ---------------------------------- |
| `in`        | `($url = null): string`                                       | Strip scheme/authority for routing |
| `headers`   | `(int $behave, ?string $name = null, $value = null): array`   | Accumulate/flush headers           |
| `out`       | `($code, $body = null, $header = null): int`                  | Emit response, return exit status  |
| `csp_nonce` | `($bytes = 16): string`                                       | Per-request CSP nonce              |

### Throws

`headers()` throws `InvalidArgumentException` for validation failures and `BadFunctionCallException` for usage violations. `out()` does not validate the optional `$header` string — use `headers()` for validated accumulation.