# badhat\csrf

Tokens that expire and validate.

> One function, three behaviors. Bitwise flags control what it does.

---

## 1) Setup a token

Rendering a form:

```php
use function bad\csrf\csrf;
use const bad\csrf\{SETUP, CHECK, FORCE};

$token = csrf('checkout', 900, SETUP);  // 15 minutes
```

Token created, stored in session, returned for embedding.

```html
<input type="hidden" name="checkout" value="<?= htmlspecialchars($token) ?>">
```

If a valid token already exists for that key, it throws. Unless:

```php
$token = csrf('checkout', 900, SETUP | FORCE);  // overwrite existing
```

---

## 2) Check it

Form submission handler:

```php
$valid = csrf('checkout', null, CHECK);

if (!$valid) {
    // expired or wrong token
}
```

`CHECK` pulls from `$_POST[$key]` automatically. Or pass explicitly:

```php
$valid = csrf('checkout', $_SERVER['HTTP_X_CSRF_TOKEN'], CHECK);
```

Returns `true` if valid, `false` if expired. Throws if token missing entirely.

---

## 3) Retrieve token

Need the value without setup or check:

```php
$token = csrf('checkout');  // returns existing token, or throws if not initialized
```

Returns `false` if expired.

---

## 4) Flags

| Flag | Effect |
|------|--------|
| `SETUP` | Create new token with TTL (second param, seconds) |
| `CHECK` | Validate submitted token |
| `FORCE` | With SETUP: overwrite unexpired token |

Combine with bitwise OR:

```php
csrf('key', 300, SETUP);           // create, 5 min TTL
csrf('key', null, CHECK);          // validate
csrf('key', 600, SETUP | FORCE);   // recreate even if active
```

`SETUP | CHECK` throws.

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
| `SETUP` | 1 |
| `CHECK` | 2 |
| `FORCE` | 4 |

### Function

```php
csrf(string $key, $param = null, int $behave = 0): string|bool
```

| `$behave` | `$param` | Returns |
|-----------|----------|---------|
| `0` | ignored | Token string, or `false` if expired |
| `SETUP` | TTL (int, seconds) | Token string |
| `CHECK` | Token string or null (uses `$_POST[$key]`) | `true` or `false` |

### Throws

| Exception | When |
|-----------|------|
| `InvalidArgumentException` | Empty key |
| `InvalidArgumentException` | TTL not a positive integer |
| `InvalidArgumentException` | Token required on CHECK but missing |
| `LogicException` | No active session |
| `LogicException` | Overwriting unexpired token without FORCE |
| `BadFunctionCallException` | `SETUP \| CHECK` combined |
| `BadFunctionCallException` | Token not initialized (retrieve or CHECK before SETUP) |

### Storage

Tokens live in `$_SESSION['bad\csrf']['csrf'][$key]`. Expired tokens cleaned on access.