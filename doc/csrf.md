# badhat\csrf

Tokens that expire and validate.

> One function, encoded flags. TTL and behavior packed into a single integer.

---

## 1) Setup a token

Rendering a form:

```php
use function bad\csrf\csrf;
use const bad\csrf\SETUP;

$token = csrf(SETUP | 900, 'checkout');  // 15 minutes
```

Token created, stored in session, returned for embedding.

```html
<input type="hidden" name="checkout" value="<?= htmlspecialchars($token) ?>">
```

If a valid token already exists for that key, it throws.

---

## 2) Check it

Form submission handler:

```php
use const bad\csrf\CHECK;

$valid = csrf(CHECK, 'checkout');

if (!$valid) {
    // expired or wrong token
}
```

`CHECK` pulls from `$_POST[$key]` automatically. Or pass explicitly:

```php
$valid = csrf(CHECK, 'checkout', $_SERVER['HTTP_X_CSRF_TOKEN']);
```

Returns `true` if valid, `false` if expired or mismatched. Throws if token missing entirely.

---

## 3) Retrieve token

Need the value without setup or check:

```php
$token = csrf(0, 'checkout');  // returns existing token, or throws if not initialized
```

Returns `null` if expired.

---

## 4) Encoding

The first parameter packs **TTL** (low 20 bits) and **flags** (high bits):

```
[ behavior flags | TTL in seconds ]
    bits 20+          bits 0-19
```

| Flag | Value | Effect |
|------|--------|--------|
| `SETUP` | `1 << 20` | Create new token |
| `CHECK` | `2 << 20` | Validate submitted token |

**Maximum TTL:** `(1 << 20) - 1` = 1,048,575 seconds (~12 days)

Combine with bitwise OR:

```php
csrf(SETUP | 300, 'key');     // create, 5 min TTL
csrf(CHECK, 'key');           // validate
csrf(SETUP | 600, 'key');     // recreate (throws if active token exists)
```

`SETUP | CHECK` is invalid and throws.

---

## 5) Requirements

Active session.

```php
session_start();
// now csrf() works
```

---

## Reference

### Constants

| Constant | Value |
|----------|-------|
| `SETUP` | `1 << 20` |
| `CHECK` | `2 << 20` |
| `TTL_BITS` | 20 |
| `TTL_MASK` | `(1 << 20) - 1` |
| `FLAGS_MASK` | `SETUP \| CHECK` |

### Function

```php
csrf(int $ttl_behave, string $key, $param = null): string|bool|null
```

| `$ttl_behave` | `$param` | Returns |
|-----------|----------|---------|
| `0` | ignored | Token string, or `null` if expired |
| `SETUP \| ttl` | ignored | Token string |
| `CHECK` | Token string or `null` (uses `$_POST[$key]`) | `true` or `false` |

### Throws

| Exception | When |
|-----------|------|
| `InvalidArgumentException` | Empty key |
| `InvalidArgumentException` | TTL not provided or zero when using SETUP |
| `InvalidArgumentException` | Token required on CHECK but missing |
| `LogicException` | No active session |
| `BadFunctionCallException` | Token not initialized (retrieve or CHECK before SETUP) |

### Storage

Tokens live in `$_SESSION['bad\csrf'][$key]` as `[$token, $expiry]`. Expired tokens return `null` on access.

---

## Examples

### Login form

```php
// Render login page
use const bad\csrf\SETUP;

$token = csrf(SETUP | 1800, 'login');  // 30 minutes
?>
<form method="post">
    <input name="username" required>
    <input name="password" type="password" required>
    <input type="hidden" name="login" value="<?= htmlspecialchars($token) ?>">
    <button>Login</button>
</form>
```

```php
// Process login
use const bad\csrf\CHECK;

if ($_POST) {
    csrf(CHECK, 'login') || exit('Invalid token');
    // ... authenticate user
}
```

### API endpoint with custom header

```php
use const bad\csrf\{SETUP, CHECK};

// Generate token for client
header('Content-Type: application/json');
exit(json_encode(['csrf' => csrf(SETUP | 3600, 'api')]));
```

```php
// Validate on subsequent request
$valid = csrf(CHECK, 'api', $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
$valid || exit(json_encode(['error' => 'Invalid CSRF token']));
```

### Checkout flow

```php
// Shopping cart page
use const bad\csrf\SETUP;

$token = csrf(SETUP | 900, 'checkout');  // 15 minutes
?>
<form method="post" action="/checkout">
    <!-- cart items -->
    <input type="hidden" name="checkout" value="<?= htmlspecialchars($token) ?>">
    <button>Proceed to Checkout</button>
</form>
```

```php
// Checkout handler
use const bad\csrf\CHECK;

csrf(CHECK, 'checkout') || exit('Session expired or invalid token');
// ... process payment
```