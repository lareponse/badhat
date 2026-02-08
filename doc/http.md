# bad\http

HTTP is the last mile.

You’ve already mapped a request to a file and run it. Now you need to do two boring-but-critical things reliably:

1. **Stage** headers (and a status) while your code is still thinking.
2. **Emit once**, when you’re ready.

`bad\http` gives you a tiny staging buffer for headers + status, a single `out()` convenience for response bodies, and a cached `csp_nonce()` for CSP.

No policy. No validation. No opinions about your app.
Just a controlled exit ramp to the network.

---

## The two moves

### 1) Stage and mutate: `headers()`

You can stage headers in any order, overwrite scalars, append to list headers, lock entries, drop entries, reset the whole stage.

Nothing is sent to the client until you **EMIT**.

### 2) Respond: `out()`

`out()` is the “final move” helper:

* computes and stages `Content-Length`
* emits staged headers + status
* echoes the body (only when it makes sense)
* optionally exits

---

## Quick taste

```php
use function bad\http\{headers, out, csp_nonce};
use const bad\http\{ONE, ADD, CSV, SSV, CSP, COOKIE, LOCK, EMIT, EXIT};

// content type (scalar)
headers(ONE, 'Content-Type', 'text/html; charset=utf-8');

// cookies must be list-mode
headers(COOKIE, 'Set-Cookie', 'a=1; Path=/; HttpOnly');
headers(COOKIE, 'Set-Cookie', 'b=2; Path=/; HttpOnly');

// CSP is commonly a directive list (semicolon-separated)
$nonce = csp_nonce();
headers(CSP, 'Content-Security-Policy', "script-src 'nonce-$nonce' 'strict-dynamic'");

// lock something you never want overwritten later
headers(LOCK, 'X-Frame-Options', 'DENY');

// done thinking → emit + body + exit
out(EXIT | 200, "<h1>Hello</h1>");
```

---

## Status + flags live in one int

Every call uses a single integer that packs:

* **low 10 bits**: status code (0..1023)
* **high bits**: behavior flags

So these are the same idea:

```php
headers(200);           // stage status only
headers(EMIT | 200);    // stage status, then emit
```

Status `0` means “don’t change”.

---

## headers()

### Signature

```php
headers(int $status_behave = 0, string $field = '', mixed $value = ''): int
```

### Return value

* `0` = staging/mutation happened (or no-op)
* `200..599` = status emitted (when you EMIT)
* **negative** = refused (a “why” code)

Think of negative values as: “you asked for something that violates the staging rules”.

### 1) Stage a scalar header (overwrite)

```php
headers(ONE, 'Content-Type', 'application/json');
headers(ONE, 'Content-Type', 'text/plain'); // overwrites
```

### 2) Stage a list header (append)

Use list-mode flags on the *first* call. That chooses the “shape” for that header.

* `ADD` → multiple header lines
* `CSV` → one line, values joined with `, `
* `SSV` → one line, values joined with `; `

```php
headers(ADD, 'X-Tag', 'a');
headers(ADD, 'X-Tag', 'b');   // emits as two header lines on EMIT
```

```php
headers(CSV, 'Vary', 'Accept');
headers(CSV, 'Vary', 'Origin'); // emits: "Vary: Accept, Origin"
```

```php
headers(SSV, 'Cache-Control', 'no-store');
headers(SSV, 'Cache-Control', 'private');  // emits: "Cache-Control: no-store; private"
```

### 3) `Set-Cookie` is special (and strict)

`Set-Cookie` **must** be list-mode, and specifically **ADD-mode** (multi header lines).

That means: always use `COOKIE` (alias of `ADD`) for it.

```php
headers(COOKIE, 'Set-Cookie', 'a=1');
headers(COOKIE, 'Set-Cookie', 'b=2');
```

Trying to set `Set-Cookie` as a scalar or CSV/SSV list is refused.

### 4) Lock an entry

`LOCK` freezes the staged entry against later mutation.

```php
headers(LOCK, 'X-Frame-Options', 'DENY'); // set + lock
headers(ONE,  'X-Frame-Options', 'SAMEORIGIN'); // refused (locked)
```

### 5) Drop one staged header

```php
headers(DROP, 'X-Tag');
```

If headers haven’t been emitted yet, dropping also removes it from PHP’s pending header list.
If the entry is locked, drop is refused.

### 6) Stage a status (without emitting)

```php
headers(404);   // “remember 404”
```

The last non-zero staged status wins, until you reset it.

### 7) Reset the stage

`RESET` clears staged headers and the staged status.
You can reset without emitting, or use it as a “clear after sending” pattern.

```php
headers(RESET);
```

### 8) Emit

This is the only moment PHP headers are actually sent.

```php
headers(EMIT | 200);
```

If `headers_sent()` is already true, EMIT is refused (negative return).

### 9) Remove previously set headers on emit

Sometimes something elsewhere already called `header('X: old')` and you want to neutralize it.
`REMOVE` calls `header_remove($name)` for each staged name *just before* sending the staged values.

```php
headers(ONE, 'X-Legacy', 'clean');
headers(REMOVE | EMIT | 200);
```

---

## out()

### Signature

```php
out(int $behave, mixed $body = null): int
```

What it does, in order:

1. extracts status from `$behave`
2. decides whether the response is allowed to have a body
3. stages `Content-Length`
4. `headers(EMIT | status)`
5. echoes body (only if length > 0)
6. if `EXIT` is set, exits with code `0` on success, `1` on failure

### Example: simplest response

```php
use function bad\http\out;
use const bad\http\EXIT;

out(EXIT | 200, "ok");
```

### Body suppression rules

A body is considered “no payload” when:

* body is `null` or `''`, **or**
* status is informational (1xx), **or**
* status is `204`, `205`, or `304`

In these cases `Content-Length` becomes `0` and nothing is echoed.

---

## csp_nonce()

### Signature

```php
csp_nonce(int $bytes = 16): string
```

* first call generates a random nonce and caches it for the request
* later calls return the same nonce (even if you pass a different size)
* passing a **negative** value resets the cache and regenerates using `abs($bytes)`

```php
$nonce = csp_nonce();     // default: 16 bytes → 32 hex chars
$nonce = csp_nonce(24);   // same cached value (still the first one)

$nonce = csp_nonce(-24);  // reset + new 24-byte nonce (48 hex chars)
```

---

## Sharp edges (by design)

* **No validation.** If you need “RFC-shaped bytes”, validate at the call-site (e.g. via `bad\rfc`) before staging. 
* **First call sets the header shape.** Start a header as scalar vs list, and that shape sticks. Trying to “change shape” later is refused. 
* **`Set-Cookie` must be `COOKIE`/`ADD`.** Always. 
* **EMIT can fail late.** If something already sent output and PHP committed headers, `headers(EMIT …)` is refused. 

---

## Reference

### Common flags

* `ONE` — scalar mode placeholder (read: “normal set/overwrite”)
* `ADD` — list mode (append, emits multiple header lines)
* `CSV` — list mode (emit as `v1, v2`)
* `SSV` — list mode (emit as `v1; v2`)
* `LOCK` — freeze a staged entry
* `DROP` — remove a staged entry by name
* `RESET` — clear stage + staged status
* `REMOVE` — on EMIT, `header_remove(name)` for staged names first
* `EMIT` — send staged headers + staged status
* `EXIT` — `out()` only: emit then exit

### Aliases

* `COOKIE` — alias for `ADD` (required shape for `Set-Cookie`)
* `CSP` — alias for `ADD | SSV` (handy for CSP directive lists)