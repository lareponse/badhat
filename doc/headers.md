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
headers(int $flags, string $name, string|int $value)
```

| Call | Effect |
|------|--------|
| `headers(0, 'X-Foo', 'bar')` | Set scalar header (overwrites) |
| `headers(ADD, 'Set-Cookie', 'a=1')` | Append to multi-value header |
| `headers(CSV, 'Vary', 'Accept')` | Append, emit as comma-separated |
| `headers(DROP, 'X-Foo', '')` | Remove staged header |
| `headers(LOCK, 'X-Frame-Options', 'DENY')` | Set and freeze against mutation |

**First call determines shape.** A header started with `ADD` stays a list. A header started as scalar stays scalar. Mixing shapes throws.

**`Set-Cookie` requires `ADD`.** Scalar assignment and `CSV` are rejected.

---

## Staging status

```php
headers(int $status_or_flags)
```

| Call | Effect |
|------|--------|
| `headers(200)` | Stage status 200 |
| `headers(0)` | No-op (preserve current) |

Last non-zero status wins. `RESET` clears it.

---

## Emitting

```php
headers(int $flags)
```

| Call | Effect |
|------|--------|
| `headers(EMIT)` | Send staged headers + status |
| `headers(EMIT \| 404)` | Set status 404, then emit |
| `headers(RESET)` | Clear staging without emitting |
| `headers(RESET \| EMIT)` | Emit, then clear |
| `headers(REMOVE \| EMIT)` | Clear PHP's sent headers, then emit staged |
| `headers(AUTO)` | Register shutdown emit |
| `headers(NOTO)` | Cancel `AUTO` |

After `EMIT`, headers are sent. Further staging has no effect on that response.

---

## Cleaning existing headers

`REMOVE` calls `header_remove()` before emitting, clearing any headers sent via direct `header()` calls or legacy code.

```php
header('X-Legacy: leftover');  // elsewhere in codebase

headers(REMOVE | EMIT | 200);  // wipes X-Legacy, emits only staged
```

Does not replace direct `header()` usage—but can neutralize it.

---

## Flag reference

**Mutation:**
- `ADD` — multi-value (each call appends)
- `CSV` — multi-value, joined with `,` on emit
- `DROP` — remove by name
- `LOCK` — freeze against further changes

**Emission:**
- `EMIT` — send now
- `RESET` — clear staging
- `REMOVE` — clear PHP's output headers before emit
- `AUTO` — emit on shutdown
- `NOTO` — disable `AUTO`

---

## Parameter encoding

`$status_behave` packs flags and status into one integer:

```
[  behavior flags  |  status code  ]
     high bits         bits 0-9
```

```php
headers(EMIT | 302, 'Location', '/login');  // stage, set status, emit
headers(200);                                // stage status only
```

Status `0` means "don't change." Values 1–1023 are valid status codes.

---

## Typing

Loosely typed. Accepts numeric strings, bools coerce. Empty string `''` for name/value means no mutation.

---

## Constraints

- Not thread-safe
- Per-request state only