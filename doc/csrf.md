# BADHAT CSRF — Token Protection

CSRF tokens via `csrf()`. Requires active session.

---

## Constants

```php
const CSRF_SETUP = 1;               // generate/refresh token
const CSRF_CHECK = 2;               // validate token
const CSRF_INPUT = 4 | CSRF_SETUP;  // setup + return hidden input
```

---

## Function

```php
function csrf(int $behave = 0, $param = null, $k = null)
```

**Returns:** varies by behavior flag

**Requires:** `session_status() === PHP_SESSION_ACTIVE`

**Throws:** `RuntimeException` (500) if no active session

---

## Usage

### 1. Setup Token

```php
session_start();
csrf(CSRF_SETUP);           // default: 3600s TTL, '_csrf_token' key
csrf(CSRF_SETUP, 7200);     // custom TTL
csrf(CSRF_SETUP, 3600, 'my_token');  // custom key
```

Token regenerates when expired.

### 2. Get Token Value

```php
$token = csrf();  // returns token string or null
```

### 3. Generate Hidden Input

```php
echo csrf(CSRF_INPUT);
// <input type="hidden" name="_csrf_token" value="abc123..." />

echo csrf(CSRF_INPUT, 7200, 'custom_key');
// <input type="hidden" name="custom_key" value="abc123..." />
```

`CSRF_INPUT` includes `CSRF_SETUP` — token is created/refreshed automatically.

### 4. Validate Token

```php
// Check $_POST['_csrf_token'] (default)
$valid = csrf(CSRF_CHECK);

// Check specific value
$valid = csrf(CSRF_CHECK, $_POST['my_token']);
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
?>
<form method="post">
    <?= csrf(CSRF_INPUT) ?>
    <input name="email" type="email" required>
    <button>Submit</button>
</form>
```

### Route with Validation

```php
// app/route/update.php
return function($args) {
    if ($_POST) {
        csrf(CSRF_CHECK) || http_out(403, 'Invalid token');
        // ... process form ...
    }
    return [];
};
```

### API Alternative

For stateless APIs, use HMAC auth instead of CSRF tokens.

---

## Session Storage

Tokens stored as:

```php
$_SESSION[$key] = [$token, $expiry];
// ['abc123...', 1704067200]
```

---

## Bootstrap

```php
// public/index.php
session_start();
csrf(CSRF_SETUP);  // init token

// ... routing ...
```

Or defer to first form render:

```php
// render/form.php
<?= csrf(CSRF_INPUT) ?>  // setup + input in one call
```