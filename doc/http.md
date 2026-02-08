# bad\http

HTTP is the last mile.

`bad\map` gives you a file.
`bad\run` executes it.
Now you need to do the boring part reliably:

1. stage headers while your code is still thinking
2. emit once, when you’re ready

`bad\http` is three tools:

- **headers()** — stage/mutate/emit headers + status
- **out()** — compute Content-Length, emit, optionally echo, optionally exit
- **csp_nonce()** — per-request cached nonce for CSP

No policy. No router. No framework voice.
Just a controlled exit ramp to the network.

---

## Quick taste

```php
use function bad\http\{headers, out, csp_nonce};
use const bad\http\{ONE, COOKIE, CSP, LOCK, QUIT};

headers(ONE, 'Content-Type', 'text/html; charset=utf-8');

// Set-Cookie must be multi-line.
headers(COOKIE, 'Set-Cookie', 'a=1; Path=/; HttpOnly');
headers(COOKIE, 'Set-Cookie', 'b=2; Path=/; HttpOnly');

// CSP is commonly a directive list.
$nonce = csp_nonce();
headers(CSP, 'Content-Security-Policy', "script-src 'nonce-$nonce' 'strict-dynamic'");

// Lock something you never want overwritten later.
headers(LOCK, 'X-Frame-Options', 'DENY');

out(QUIT | 200, '<h1>Hello</h1>');
````

---

## Status + flags live in one int

Every call packs:

* **low 10 bits**: status code (0..1023)
* **high bits**: behavior flags

So these are the same idea:

```php
use const bad\http\EMIT;

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

### Return values

* `0` = staged/mutated (or no-op)
* `200..599` = emitted status (when you EMIT)
* **negative** = refused (a “why” code)

### Scalar headers (overwrite)

```php
headers(ONE, 'Content-Type', 'application/json');
headers(ONE, 'Content-Type', 'text/plain'); // overwrites
```

### List headers (append)

The **first call sets the shape**.

* `ADD` → multiple header lines
* `CSV` → one line, values joined with `, `
* `SSV` → one line, values joined with `; `

```php
headers(ADD, 'X-Tag', 'a');
headers(ADD, 'X-Tag', 'b');
```

```php
headers(CSV, 'Vary', 'Accept');
headers(CSV, 'Vary', 'Origin'); // "Vary: Accept, Origin"
```

```php
headers(SSV, 'Cache-Control', 'no-store');
headers(SSV, 'Cache-Control', 'private'); // "Cache-Control: no-store; private"
```

### Set-Cookie is strict

`Set-Cookie` must be list-mode, and specifically **ADD-mode**.
Use `COOKIE` (alias of `ADD`).

```php
headers(COOKIE, 'Set-Cookie', 'a=1');
headers(COOKIE, 'Set-Cookie', 'b=2');
```

### Lock / drop / reset

```php
headers(LOCK, 'X-Frame-Options', 'DENY');
headers(DROP, 'X-Tag');
headers(RESET); // clear staged headers + staged status
```

Locked entries refuse mutation (including DROP).

### Emit

```php
headers(EMIT | 204);
```

If `headers_sent()` is already true, EMIT is refused (negative return).

### Remove-on-emit

If something else already set a header and you want to neutralize it, use `REMOVE`.
It calls `header_remove(name)` for each staged name just before sending staged values.

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

`out()`:

1. derives status from `$behave`
2. decides whether a body is allowed
3. stages `Content-Length`
4. `headers(EMIT | status)`
5. echoes body only when length > 0
6. exits when `QUIT` is set (0 on success, 1 on failure)

### Simplest response

```php
use function bad\http\out;
use const bad\http\QUIT;

out(QUIT | 200, 'ok');
```

### Body suppression

No payload when:

* body is `null` or `''`, or
* status is 1xx, or
* status is `204`, `205`, `304`

In those cases, `Content-Length` becomes `0` and nothing is echoed.

---

## csp_nonce()

### Signature

```php
csp_nonce(int $bytes = 16): string
```

* first call generates and caches a nonce for the request
* later calls return the same cached value
* pass a **negative** value to reset and regenerate using `abs($bytes)`

```php
$nonce = csp_nonce();     // 16 bytes → 32 hex chars
$nonce = csp_nonce(24);   // same cached value

$nonce = csp_nonce(-24);  // reset + new 24-byte nonce
```

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
* `EMIT` — send staged headers + status
* `QUIT` — `out()` only: emit then exit

### Aliases

* `COOKIE` — alias for `ADD` (required shape for `Set-Cookie`)
* `CSP` — alias for `ADD | SSV` (handy for directive lists)
