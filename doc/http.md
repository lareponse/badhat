# badhat\http

Your app needs to speak HTTP.

Not a framework. Not a router. Not a response object hierarchy. Just a few sharp helpers to normalize an incoming URL, collect headers safely, and emit a response without repeating the same `header()` / `http_response_code()` ceremony everywhere.

> One nonce, cached. One output function that returns an exit status.
> One optional header helper that refuses bad input. That's it.

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

## 2) Then, you collect headers (only if you want validation)

You can always pass headers directly to `out()`. But if you want a helper that refuses invalid header names/values, `headers()` gives you that.

```php
use function bad\http\headers;

headers('Content-Type', 'application/json');
headers('Set-Cookie', 'a=1; Path=/', false);
headers('Set-Cookie', 'b=2; Path=/', false);
```

`headers()` validates and stores headers in a static map:

* name must be non-empty and contain only `HTTP_TCHAR`
* value must not contain any `ASCII_CTL`

On success it returns the full map. On invalid input it returns `null`.

```php
$h = headers('X-Test', "ok");
if ($h === null) {
    // invalid header input
}
```

**Default story:**
"I want a tiny guardrail for header injection and bad names."

---

## 3) Finally, you emit a response

`out()` does the usual HTTP bits, and returns a process exit status so you can `exit(...)` cleanly when you want.

```php
use function bad\http\out;

exit(out(404, 'Not found', [
    'Content-Type' => 'text/plain; charset=utf-8',
]));
```

What it does:

1. `http_response_code($code)`
2. emits each header value via `header("$name: $v", false)`
3. echoes `$body` only when it makes sense (no body for `204/205/304`, and only for `>= 200`)
4. returns an exit status derived from the HTTP code

Exit status mapping:

* `< 400` → `0`
* `400–499` → `4`
* `500–599` → `5`
* otherwise → `1`

Header values can be a string or an array of strings:

```php
out(200, 'ok', [
    'Set-Cookie' => ['a=1; Path=/', 'b=2; Path=/'],
]);
```

`out()` does **not** validate headers. If you want validation, build your map with `headers()` (or validate yourself) and pass it in.

**Default story:**
"Emit the response. Give me an exit code. Don't make me remember the rules."

---

## 4) CSP nonce, when you need it

`csp_nonce()` gives you a per-request nonce, cached after the first call.

```php
use function bad\http\{csp_nonce, out};

$nonce = csp_nonce();

out(200, $html, [
    'Content-Security-Policy' => "script-src 'nonce-$nonce'",
    'Content-Type'           => 'text/html; charset=utf-8',
]);
```

**Default story:**
"I need a nonce once. Don't generate it twice."

---

## Reference

### Constants

| Constant           | Value               | Effect                    |
| ------------------ | ------------------- | ------------------------- |
| `ASCII_CTL`        | `"\x00...\x1F\x7F"` | All ASCII control chars   |
| `HTTP_PATH_UNSAFE` | `' ' . ASCII_CTL`   | Space + all control chars |
| `HTTP_TCHAR`       | token chars         | Allowed header-name chars |

> `HTTP_PATH_UNSAFE` is a good *forbidden set* to pass to `bad\io\hook(...)`.

### Functions

| Function                           | Purpose                            |
| ---------------------------------- | ---------------------------------- |
| `in($url)`                         | Strip scheme/authority for routing |
| `headers($name, $value, $replace)` | Validate + accumulate headers      |
| `out($code, $body, $headers)`      | Emit response + return exit status |
| `csp_nonce()`                      | Per-request CSP nonce              |

### Returns

`out()` returns an exit status derived from the HTTP status code:

* `< 400` → `0`
* `400–499` → `4`
* `500–599` → `5`
* otherwise → `1`

### Notes

`headers()` returns `null` when input is invalid. `out()` always emits what you pass it (it does not validate).
