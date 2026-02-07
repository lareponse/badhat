# bad\http\header

Stage HTTP headers and status codes. Emit once, when ready.

```php
use bad\http\header as h;

h\headers(h\ADD, 'Set-Cookie', 'a=1');
h\headers(h\ADD, 'Set-Cookie', 'b=2');
h\headers(h\CSV, 'Accept', 'text/html');
h\headers(h\EMIT | 200);
```

Redirect:

```php
h\headers(h\EMIT | 302, 'Location', '/dashboard');
```

---

## Staging headers

```php
headers(int $flags, string $name, mixed $value): bool
```

| Call                                       | Effect                          |
| ------------------------------------------ | ------------------------------- |
| `headers(0, 'X-Foo', 'bar')`               | Set scalar header (overwrites)  |
| `headers(ADD, 'Set-Cookie', 'a=1')`        | Append to multi-value header    |
| `headers(CSV, 'Vary', 'Accept')`           | Append, emit as comma-separated |
| `headers(DROP, 'X-Foo', '')`               | Remove staged header            |
| `headers(LOCK, 'X-Frame-Options', 'DENY')` | Set and freeze against mutation |

**First call determines shape.** A header started with `ADD` / `CSV` stays a list. A header started as scalar stays scalar. Mixing shapes returns `false` (or throws with `E_THROW`).

**`Set-Cookie` requires `ADD` (aka `COOKIE`) on every call.** Scalar assignment and `CSV` are rejected.

---

## Staging status

```php
headers(int $status_or_flags): bool
```

| Call           | Effect                   |
| -------------- | ------------------------ |
| `headers(200)` | Stage status 200         |
| `headers(0)`   | No-op (preserve current) |

Last non-zero status wins. `RESET` clears it.

> Encoding range is `0..1023` (10 low bits). Use real HTTP status codes (`100..599`) in practice.

---

## Emitting

```php
headers(int $flags): bool
```

| Call                      | Effect                                                       |
| ------------------------- | ------------------------------------------------------------ |
| `headers(EMIT)`           | Send staged headers + staged status                          |
| `headers(EMIT \| 404)`    | Stage status 404, then emit                                  |
| `headers(RESET)`          | Clear staging without emitting                               |
| `headers(RESET \| EMIT)`  | Emit, then clear                                             |
| `headers(REMOVE \| EMIT)` | Remove *matching* already-set header names, then emit staged |

After `EMIT`, headers are sent. Further staging may still mutate the in-memory stage, but cannot be emitted (PHP will reject emission once `headers_sent()` becomes true).

---

## Registering an auto-emit callback

If you want headers emitted automatically at the end of the request, register a callback with `header_register_callback()` and call `headers(EMIT ...)` inside it.

```php
use bad\http\header as h;

header_register_callback(static fn() => h\headers(h\EMIT));
```

To enforce a default status at shutdown (only if one was staged, or by explicitly staging one beforehand), you can encode it in the callback:

```php
header_register_callback(static fn() => h\headers(h\EMIT | 200));
```

If you need to *disable* a previously registered header callback, register a no-op callback later (the last registered callback wins):

```php
header_register_callback(static fn() => null);
```

---

## Removing previously set headers

`REMOVE` calls `header_remove(<name>)` **only for the names present in the staged set**, just before emitting them. This neutralizes duplicate/legacy headers *for those same names*, but does not clear unrelated headers.

```php
header('X-Legacy: leftover');           // elsewhere in codebase
h\headers(0, 'X-Legacy', 'clean');      // stage replacement
h\headers(h\REMOVE | h\EMIT | 200);     // removes X-Legacy then emits staged X-Legacy
```

---

## Flag reference

**Mutation:**

* `ADD` — multi-value (each call appends)
* `CSV` — multi-value, joined with `, ` on emit
* `DROP` — remove by name (also removes from PHP header list if not yet sent)
* `LOCK` — freeze against further changes

**Emission:**

* `EMIT` — send now
* `RESET` — clear staging
* `REMOVE` — remove already-set headers for staged names before re-sending them

**Errors:**

* `E_THROW` — throw on failure instead of returning `false`

---

## Parameter encoding

`$status_behave` packs flags and status into one integer:

```
[  behavior flags  |  status code  ]
     high bits         bits 0-9
```

```php
headers(EMIT | 302, 'Location', '/login');  // stage, set status, emit
headers(200);                               // stage status only
```

Status `0` means “don’t change”.

---

## Typing

Loosely typed. Values are stringified when emitted. Empty string `''` for name means “no mutation”.

---

## Constraints

* Not thread-safe
* Per-request state only
