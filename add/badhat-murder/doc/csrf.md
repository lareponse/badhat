# badhat\csrf

Your forms need CSRF protection.

Just tokens that expire and validate.

> One function, three behaviors. Bitwise flags control what it does.

---

## 1) First, you set up a token

Somewhere you're rendering a form—the page where the user sees inputs and a submit button:

```php
use function bad\csrf\csrf;
use const bad\csrf\{SETUP, CHECK, FORCE};

$token = csrf('checkout', 900, SETUP);  // 15 minutes
```

That's it. Token created, stored in session, returned for embedding.

```html
<input type="hidden" name="checkout" value="<?= htmlspecialchars($token) ?>">
```

If a valid token already exists for that key, it throws. You probably have a bug—why are you setting up twice? Unless you meant to:

```php
$token = csrf('checkout', 900, SETUP | FORCE);  // overwrite existing
```

**Default story:**
"I need a token for this form. Warn me if I'm clobbering one accidentally."

---

## 2) Then, you check it

When the form submits—POST handler, controller, wherever:

```php
$valid = csrf('checkout', null, CHECK);

if (!$valid) {
    // expired or wrong token
}
```

`CHECK` pulls the submitted value from `$_POST[$key]` automatically. Or pass it explicitly:

```php
$valid = csrf('checkout', $_SERVER['HTTP_X_CSRF_TOKEN'], CHECK);
```

Returns `true` if valid, `false` if expired. Throws if the token is missing entirely—that's not "invalid," that's "your form is broken."

**Default story:**
"Validate the submission. Tell me if it's good, bad, or missing."

---

## 3) Just need the token?

Sometimes you need the token value without setup or check. Rendering a second form on the same page. Passing it to JavaScript.

```php
$token = csrf('checkout');  // returns existing token, or throws if not initialized
```

Returns `false` if expired.

**Default story:**
"I already set this up. Just give me the value."

---

## 4) Flags control behavior

| Flag | What it does |
|------|--------------|
| `SETUP` | Create new token with TTL (second param, seconds) |
| `CHECK` | Validate submitted token |
| `FORCE` | With SETUP: overwrite unexpired token |

Combine with bitwise OR:

```php
csrf('key', 300, SETUP);           // create, 5 min TTL
csrf('key', null, CHECK);          // validate
csrf('key', 600, SETUP | FORCE);   // recreate even if active
```

`SETUP | CHECK` throws. Pick one.

---

## 5) Requirements

Active session. That's it.

```php
session_start();
// now csrf() works
```

No session? It throws. This isn't optional—CSRF tokens without sessions don't make sense.

---

## Reference

### Constants

| Constant | Value | Use |
|----------|-------|-----|
| `SETUP` | 1 | Create token |
| `CHECK` | 2 | Validate token |
| `FORCE` | 4 | Allow overwrite |

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
| `InvalidArgumentException` | Empty key, invalid TTL, `SETUP\|CHECK` combined, missing token on CHECK |
| `BadFunctionCallException` | No session, token not initialized, overwriting without FORCE |

### Storage

Tokens live in `$_SESSION['bad\csrf']['csrf'][$key]`. They're cleaned up on access when expired. Abandoned tokens persist until session expiry—use bounded key names or short sessions.