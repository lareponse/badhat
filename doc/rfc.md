# bad\rfc

You accept input from the outside world.

Sometimes it *looks* like HTTP — a header name, a header value, a “path”.  
Sometimes it *is* HTTP. Sometimes it's a proxy, a framework, or a config file
pretending to be HTTP.

Either way, you want one thing:

> a cheap, byte-level **sanity filter** that rejects dangerous garbage,  
> without pretending to be a full parser.

That's the job of `bad\rfc`.

---

## 1) This is not a parser

`bad\rfc` doesn't interpret semantics.

It does not:
- unfold headers
- decode percent escapes
- normalize Unicode
- validate full ABNF productions

It *does*:
- constrain inputs to RFC-shaped byte sets
- block control bytes that cause injection bugs
- give you a clear “valid / invalid” decision with optional exceptions

“Keep the bytes boring. Let the higher layer decide meaning.”

---

## 2) Header field-names: tokens only

HTTP field-names are `tchar` tokens.

So `field_name()` is strict and simple:

```php
use bad\rfc;

$name = rfc\field_name('Content-Type');     // ok
$name = rfc\field_name('Bad Name');         // false (space)
$name = rfc\field_name('', rfc\STRICT);     // throws
````

What you can rely on:

* empty is rejected
* every byte must be RFC `tchar` (RFC 9110)
* valid input is returned unchanged

**Story:**
“If it can't be a token, it can't be a header name.”

---

## 3) Header field-values: safe bytes, no CTL tricks

Header values are the classic injection surface.

RFC 9110 allows a wide range of bytes in field-values, including `obs-text`
(`0x80..0xFF`). What it does *not* allow is control bytes like CR/LF.

So `field_value()` applies two guardrails:

1. allow-list “normal” bytes (`VCHAR`, `obs-text`, and OWS)
2. reject forbidden control bytes (`CTL` excluding HTAB)

```php
use bad\rfc;

$v = rfc\field_value('text/plain');                 // ok
$v = rfc\field_value("hello\r\nx: y");              // false (CTL)
$v = rfc\field_value("hello\r\nx: y", rfc\STRICT);  // throws
```

### Empty values are OK (RFC-compatible)

HTTP permits empty field-values.

These are valid header lines:

* `X-Test:`
* `X-Test:   `

So by default:

```php
rfc\field_value('');        // ok
rfc\field_value("   \t");   // ok
```

**Story:**
“Empty is a real value. Don't invent rules for the network.”

---

## 4) Only when you need it, you opt in

Sometimes you're not validating “wire HTTP”.
You're validating *your* configuration, or an internal policy.

That's why flags exist.

Combine them with `|`.

---

### `STRICT` — make invalid input loud

```php
rfc\field_value("\n", rfc\STRICT); // throws InvalidArgumentException
```

**Story:**
“I'd rather crash here than chase bugs downstream.”

---

### `APP_REQUIRE_VALUE` — your app wants non-empty

This is an *application policy* flag.

It rejects:

* empty (`""`)
* OWS-only (`"   "` or `"\t\t"`)

```php
use bad\rfc;

rfc\field_value('', rfc\APP_REQUIRE_VALUE);          // false
rfc\field_value(" \t", rfc\APP_REQUIRE_VALUE);       // false
rfc\field_value("ok", rfc\APP_REQUIRE_VALUE);        // ok
```

**Story:**
“HTTP allows it, but my app doesn't.”

---

## 5) URL paths: RFC 3986 shape, with strict percent escapes

When you accept a path-like string, you want it to be:

* free of CTL / SP / HTAB
* free of backslash confusion
* correctly percent-escaped where `%` appears

That's what `url_path()` enforces.

```php
use bad\rfc;

p = rfc\url_path('/a/b%20c');              // ok
p = rfc\url_path('/a/%2G');                // false (bad hex)
p = rfc\url_path("/a/\tb");                // false (HTAB)
p = rfc\url_path('/a\\b');                 // false (backslash)
```

**Story:**
“If it looks like a URI path, it should behave like one.”

---

## Practical guardrails

What `bad\rfc` guarantees:

* No CR/LF and no forbidden control bytes in header values
* Header names are token-only (`tchar`)
* URL paths contain no CTL/SP/HTAB and only valid `%HH` escapes
* Invalid inputs either return `false` or throw (with `STRICT`)

What you decide at the call-site:

* whether to trim/normalize whitespace
* whether empty header values are acceptable for your application (`APP_REQUIRE_VALUE`)
* what you do on failure (reject request, default value, log, etc.)

---

## Reference

### Constants

|            Constant | Value | Meaning                                                    |
| ------------------: | ----: | ---------------------------------------------------------- |
|            `STRICT` |   `1` | Throw `InvalidArgumentException` on invalid input          |
| `APP_REQUIRE_VALUE` |   `2` | Reject empty / OWS-only header values (application policy) |

---

### Functions

#### `field_name(string $canon, int $behave = 0): string|false`

Validates a header field-name.

Returns the input unchanged if valid, otherwise `false` (or throws with `STRICT`).

---

#### `field_value(string $value, int $behave = 0): string|false`

Validates a header field-value as a byte string.

* Allows empty / OWS-only by default (RFC-compatible)
* Optionally rejects them via `APP_REQUIRE_VALUE`
* Rejects forbidden control bytes and bytes outside the configured safe sets

Returns the input unchanged if valid, otherwise `false` (or throws with `STRICT`).

---

#### `url_path(string $path, int $behave = 0): string|false`

Validates a URI path string (RFC 3986 shape).

* Rejects CTL / SP / HTAB
* Rejects backslash
* Requires `%` to appear only as `%HH` with hex digits

Returns the input unchanged if valid, otherwise `false` (or throws with `STRICT`).
