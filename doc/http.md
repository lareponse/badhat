# badhat\http

HTTP in, HTTP out. One nonce, cached. Headers that accumulate safely and flush on demand.

> Bitmask-driven. Status codes in the low 10 bits, behavior flags above. Combine them freely.

---

## 1) Normalize the incoming URL

```php
use function bad\http\in;

$path = in($_SERVER['REQUEST_URI'] ?? '/');
```

Strips scheme and authority, returns the routing-relevant portion:

```php
in('/a/b?x=1');               // "/a/b?x=1"
in('https://ex.com/a/b?x=1'); // "/a/b?x=1"
in('//ex.com/a/b?x=1');       // "/a/b?x=1"
in('mailto:user@ex.com');     // "user@ex.com"
```

---

## 2) Accumulate headers

`headers()` validates, stages, and flushes. First argument combines behavior flags and optional status code.

```php
use function bad\http\headers;
use const bad\http\{SET, ADD, CSV, EMIT};

// Single value (replaces previous)
headers(SET, 'Content-Type', 'application/json');

// Multiple lines
headers(ADD, 'Set-Cookie', 'a=1; Path=/');
headers(ADD, 'Set-Cookie', 'b=2; Path=/');

// CSV accumulation (emits as one line)
headers(CSV, 'Vary', 'Accept');
headers(CSV, 'Vary', 'Accept-Encoding');
// → Vary: Accept, Accept-Encoding
```

Flush and clear:

```php
foreach (headers(EMIT) as $params)
    header(...$params);
```

---

## 3) Mode flags

Exactly one required per call (except EMIT):

| Flag | Effect |
|------|--------|
| `SET` | Single value, replaces previous |
| `ADD` | Append as separate header line |
| `CSV` | Accumulate, emit comma-separated |
| `EMIT` | Yield all staged headers, clear storage |

---

## 4) Modifier flags

Combine with mode via bitwise OR:

| Flag | Effect |
|------|--------|
| `READ_ONLY` | Lock header after this write |
| `KEEP_CASE` | Preserve field name casing (default: lowercase) |
| `KEEP_FIRST` | In CSV mode, keep first call's metadata |
| `NO_REPLACE` | Pass `false` to `header()` third arg |

```php
headers(SET | READ_ONLY, 'X-Frame-Options', 'DENY');
headers(SET, 'X-Frame-Options', 'SAMEORIGIN');  // throws LogicException
```

---

## 5) Status codes with headers

The low 10 bits carry an HTTP status code. When present, it's passed to `header()` on emit:

```php
headers(302 | SET, 'Location', '/dashboard');
```

The last non-zero code is tracked and available via `headers(EMIT)`.

---

## 6) Error handling flags

Control validation failure behavior:

| Flag | Effect |
|------|--------|
| `E_IGNORE` | Swallow validation errors |
| `E_WARNING` | `trigger_error(..., E_USER_WARNING)` then throw |
| `E_NOTICE` | `trigger_error(..., E_USER_NOTICE)` then throw |

Default: throw `InvalidArgumentException` immediately.

```php
headers(SET | E_WARNING, 'Bad Name!', 'value');  // warns, then throws
headers(SET | E_IGNORE, 'Bad Name!', 'value');   // silently ignored
```

---

## 7) Validation rules

RFC 9110 compliance:

- Field names: token characters only (`!#$%&'*+-.^_`|~` plus alphanumerics)
- Field values: VCHAR, obs-text, SP, HTAB allowed; CR/LF/NUL forbidden
- No leading/trailing whitespace in field names

`Set-Cookie` auto-converts to `ADD | NO_REPLACE` regardless of specified mode.

---

## 8) Emit a response

`out()` flushes headers, sets status, outputs body:

```php
use function bad\http\out;

headers(SET, 'Content-Type', 'text/plain');
out(404, 'Not found');
```

Sequence:
1. `headers(EMIT)` → flush all staged headers
2. If `$ignored_header` provided with `HEADER_RAW` flag, emit it raw
3. `http_response_code($code)` if non-zero
4. Echo body (suppressed for `<200`, `204`, `205`, `304`)

```php
use const bad\http\{HEADER_RAW, NO_REPLACE};

// Raw header injection (use sparingly)
out(200 | HEADER_RAW, $body, 'X-Custom: value');

// With NO_REPLACE
out(200 | HEADER_RAW | NO_REPLACE, $body, 'X-Multi: first');
```

---

## 9) CSP nonce

Per-request cached nonce:

```php
use function bad\http\csp_nonce;

$nonce = csp_nonce();
headers(SET, 'Content-Security-Policy', "script-src 'nonce-$nonce'");
```

---

## Reference

### Constants

**Modes** (bit 10+):

| Constant | Value | Purpose |
|----------|-------|---------|
| `SET` | `1 << 10` | Single value |
| `ADD` | `2 << 10` | Multi-line append |
| `CSV` | `4 << 10` | Comma-separated accumulation |
| `EMIT` | `1048576 << 10` | Flush and clear |

**Modifiers** (bit 10+):

| Constant | Value | Purpose |
|----------|-------|---------|
| `KEEP_CASE` | `8 << 10` | Preserve casing |
| `READ_ONLY` | `16 << 10` | Lock header |
| `KEEP_FIRST` | `32 << 10` | CSV: retain first call's metadata |
| `NO_REPLACE` | `128 << 10` | `header(..., false)` |
| `HEADER_RAW` | `2048 << 10` | Raw header in `out()` |

**Error handling** (bit 10+):

| Constant | Value | Purpose |
|----------|-------|---------|
| `E_IGNORE` | `256 << 10` | Swallow errors |
| `E_WARNING` | `512 << 10` | Warn then throw |
| `E_NOTICE` | `1024 << 10` | Notice then throw |

**Masks**:

| Constant | Purpose |
|----------|---------|
| `HTTP_CODE_MASK` | Extract status code (low 10 bits) |
| `MODE_MASK` | Extract mode flags |

### Functions

```php
in(?string $url = null): string
```
Normalize URL to origin-form request-target.

```php
headers(int $code_behave, ?string $field = null, ?string $token = null): iterable
```
Stage/emit headers. Returns staged map normally, yields `[header_line, replace, code]` tuples in EMIT mode.

```php
out(int $code_behave, $body = null, $ignored_header = null): void
```
Flush headers, set status, output body.

```php
csp_nonce(int $bytes = 16): string
```
Per-request hex nonce.

### Throws

| Exception | Condition |
|-----------|-----------|
| `InvalidArgumentException` | Empty field name |
| `InvalidArgumentException` | Non-token chars in field name |
| `InvalidArgumentException` | Control chars in field value |
| `InvalidArgumentException` | Invalid HTTP status code |
| `BadFunctionCallException` | Multiple modes combined |
| `LogicException` | Writing to locked header |