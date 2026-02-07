# bad\http

Tiny HTTP helpers. No magic. No policy.

- `path()` — turn “whatever came in” into a **rootless routing key**
- `csp_nonce()` — one **per-request** CSP nonce

---

## path()

Normalize a URL or path into something safe to route on.

```php
use function bad\http\path;

$key = path($_SERVER['REQUEST_URI'] ?? '/');
````

### What it returns

Always **rootless** (no leading or trailing `/`):

```php
path('/a/b?x=1');          // 'a/b'
path('/a/b#frag');         // 'a/b'
path('/');                 // ''
path('');                  // ''
```

If you need a leading slash, add it yourself:

```php
'/' . path($uri);
```

### Full URLs are accepted

Scheme and authority are stripped **only if a scheme is present**:

```php
path('https://ex.com/a/b');     // 'a/b'
path('https://ex.com');         // ''
path('mailto:user@ex.com');     // 'user@ex.com'
```

Schemeless `//` is just a path:

```php
path('//ex.com/a/b');           // 'ex.com/a/b'
```

---

## Rejecting characters

Second argument = **set of forbidden characters**.
If any appear, it throws.

```php
path('/a/b', '/');      // throws
path('/a.b', '.');      // throws
path("/a\0b", "\0");    // throws
```

Typical hardening:

```php
$clean = path($uri, "\0.\\");   // null, dot, backslash
```

This blocks **all dots**, not just `..`.

---

## Percent-encoding

Validated for **syntax only** in the final path:

```php
path('/a%2Fb');   // 'a%2Fb'
path('/a%2');     // throws
path('/a%GG');    // throws
```

Query and fragment are stripped first:

```php
path('/a?x=%GG'); // 'a'
path('/a#%GG');   // 'a'
```

---

## csp_nonce()

Generate a CSP nonce, cached for the request.

```php
use function bad\http\csp_nonce;

$nonce = csp_nonce();    // 32 hex chars (16 bytes)
```

### Cache behavior

First call wins:

```php
csp_nonce();      // generated
csp_nonce(24);    // same value, still cached
```

Reset (mostly for tests):

```php
csp_nonce(-24);   // reset + new 24-byte nonce
```

`csp_nonce(0)` behaves like default (16 bytes).

---

## Reference

```php
path($url, $reject = ''): string
```

* strips scheme (if present), authority (if scheme present), query, fragment
* trims leading/trailing `/`
* validates `%` encoding
* rejects characters in `$reject`
* returns **rootless** path
* throws `InvalidArgumentException` on failure

```php
csp_nonce(int $bytes = 16): ?string
```

* hex-encoded random nonce
* cached per request
* negative `$bytes` resets cache
