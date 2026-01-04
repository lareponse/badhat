# BADHAT CSRF — Token Protection

CSRF tokens via `csrf()`. Requires active session.

---

## Constants

```php
const CSRF_SETUP = 1;   // generate/refresh token
const CSRF_CHECK = 2;   // validate token
const CSRF_INPUT = 4;   // return hidden input (implies setup)
```

---

## Function

```php
function csrf(string $name, int $behave, $param = null)
```

**Parameters:**
- `$name` — session key and form field name (required)
- `$behave` — bitmask of CSRF_* flags
- `$param` — TTL (int) for SETUP, or token string for CHECK

**Returns:** token string, hidden input HTML, or bool for CHECK

**Requires:** `session_status() === PHP_SESSION_ACTIVE`

**Throws:** 
- `RuntimeException` (500) if no active session
- `InvalidArgumentException` (500) if name empty
- `BadFunctionCallException` (403) if token not initialized or missing

---

## Usage

### 1. Setup Token

```php
session_start();
csrf('_csrf', CSRF_SETUP);           // default: 3600s TTL
csrf('_csrf', CSRF_SETUP, 7200);     // custom TTL
```

Token regenerates when expired.

### 2. Get Token Value

```php
$token = csrf('_csrf', 0);  // returns token string
```

### 3. Generate Hidden Input

```php
echo csrf('_csrf', CSRF_INPUT);
// <input type="hidden" name="_csrf" value="abc123..." />

echo csrf('_csrf', CSRF_INPUT | CSRF_SETUP, 7200);
// setup with custom TTL + return input
```

### 4. Validate Token

```php
// Check $_POST['_csrf'] (default)
$valid = csrf('_csrf', CSRF_CHECK);

// Check specific value
$valid = csrf('_csrf', CSRF_CHECK, $_POST['token']);
```

**Returns:** `true` if valid and not expired, `false` otherwise

**Throws:** `BadFunctionCallException` (403) if:
- Token not initialized
- Token missing from request

---

## Patterns

### Form with CSRF

```php
// app/render/form.php
session_start();
?>
<form method="post">
    <?= csrf('_csrf', CSRF_INPUT | CSRF_SETUP) ?>
    <input name="email" type="email" required>
    <button>Submit</button>
</form>
```

### Route with Validation

```php
// app/route/update.php
return function($args) {
    if ($_POST) {
        csrf('_csrf', CSRF_CHECK) || http_out(403, 'Invalid token');
        // ... process form ...
    }
    return [];
};
```

### Multiple Forms (different tokens)

```php
<?= csrf('login_token', CSRF_INPUT | CSRF_SETUP) ?>
<?= csrf('payment_token', CSRF_INPUT | CSRF_SETUP) ?>
```

---

## Session Storage

Tokens stored as:

```php
$_SESSION['bad\csrf'][$name] = [$token, $expiry];
// ['abc123...', 1704067200]
```

---

## Bootstrap

```php
// public/index.php
session_start();
csrf('_csrf', CSRF_SETUP);  // init token

// ... routing ...
```