# BADHAT CSRF

**Namespace-based CSRF token protection with explicit token names and TTL enforcement**

* Supports **multiple CSRF tokens** via explicit token names
* Designed for **session-based HTML forms**
* Not suitable for stateless APIs (use HMAC / signatures instead)


**Namespace:** `bad\csrf`
**Storage:** `$_SESSION['bad\csrf'][$name]`

---

## Constants

```php
const CSRF_SETUP = 1;   // generate or refresh token
const CSRF_CHECK = 2;   // validate token
const CSRF_INPUT = 4;   // return hidden input
```

Flags are **bitwise combinable**.

---

## Function

```php
function csrf(int $behave, string $name, $param = null)
```

⚠️ **Important:**
The `$behave` argument comes **first**, followed by `$name`.

---

## Parameters

| Name      | Type   | Description                                               |
| --------- | ------ | --------------------------------------------------------- |
| `$behave` | int    | Behavior flags (`CSRF_SETUP`, `CSRF_CHECK`, `CSRF_INPUT`) |
| `$name`   | string | **Required.** CSRF token name / form field name           |
| `$param`  | mixed  | Depends on behavior (TTL or token value)                  |

---

## Requirements

* `session_status() === PHP_SESSION_ACTIVE`
* `$name` **must be non-empty**

Violations throw immediately.

---

## Exceptions

| Exception                  | Code | When                                                |
| -------------------------- | ---- | --------------------------------------------------- |
| `RuntimeException`         | 500  | No active session                                   |
| `InvalidArgumentException` | 500  | Missing name or invalid TTL                         |
| `BadFunctionCallException` | 403  | Token not initialized or token missing during CHECK |

---

## Behavior Details

### CSRF_SETUP

```php
csrf(CSRF_SETUP, 'token_name', int $ttl);
```

**Rules:**

* `$param` **must be a positive integer TTL (seconds)**
* Token is generated **only if missing or expired**
* Token length: **64 hex characters**
* Token persists until expiration

**Storage format:**

```php
$_SESSION['bad\csrf'][$name] = [
    0 => $token,   // string
    1 => $expiry   // int (unix timestamp)
];
```

**Return value (if alone):**

```php
string $token
```

---

### CSRF_CHECK

```php
csrf(CSRF_CHECK, 'token_name');
csrf(CSRF_CHECK, 'token_name', $token);
```

**Token source priority:**

1. Explicit `$param` if it is a string
2. `$_POST[$name]`

**Validation logic:**

```php
time() <= $expiry && hash_equals($stored, $provided)
```

**Returns:**

```php
true   // token valid and not expired
false  // token expired or mismatch
```

**Throws (403) if:**

* Token was never initialized
* No token provided via `$param` or `$_POST`

⚠️ Expired tokens **do not throw** — they return `false`.

---

### CSRF_INPUT

```php
csrf(CSRF_INPUT, 'token_name');
```

**Returns HTML string:**

```html
<input type="hidden" name="token_name" value="abc123..." />
```

* `name` is escaped via `htmlspecialchars(…, ENT_QUOTES)`
* Uses the **currently stored token**
* Requires prior initialization (or combined SETUP)

---

## Combined Behaviors

### SETUP + INPUT (Recommended)

```php
csrf(CSRF_SETUP | CSRF_INPUT, '_csrf', 3600);
```

* Creates token if missing or expired
* Outputs hidden input
* Safe, idempotent, single-call pattern

---

### SETUP + CHECK

```php
csrf(CSRF_SETUP | CSRF_CHECK, '_csrf', 3600);
```

* Ensures token exists
* Immediately validates provided token

---

## Common Usage Patterns

### 1. Bootstrap (Early Init)

```php
use function bad\csrf\csrf;
use const bad\csrf\CSRF_SETUP;

session_start();
csrf(CSRF_SETUP, '_csrf', 3600);
```

---

### 2. Form Rendering

```php
use function bad\csrf\csrf;
use const bad\csrf\CSRF_INPUT;

?>
<form method="post">
    <?= csrf(CSRF_INPUT, '_csrf') ?>
    <input name="email" type="email" required>
    <button>Submit</button>
</form>
```

---

### 3. Form Handling / Route Validation

```php
use function bad\csrf\csrf;
use const bad\csrf\CSRF_CHECK;

if ($_POST) {
    csrf(CSRF_CHECK, '_csrf') || http_out(403, 'Invalid CSRF token');
    // process request
}
```

---

## Token Lifecycle

* Token persists **until expiration**
* Regenerated **only when expired**
* Expired tokens:

  * ❌ fail validation
  * ✅ regenerated on next `CSRF_SETUP`

---

## Storage Summary

```php
$_SESSION['bad\csrf'][$name] = [
    'abc123...',     // token
    1704067200       // expiry timestamp
];
```

---

## Design Guarantees

* Per-session, per-token isolation
* Timing-safe comparison (`hash_equals`)
* No auto-regeneration during validation
* Explicit, predictable control flow
* Zero dependencies
