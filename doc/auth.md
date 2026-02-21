# bad\auth

Your app needs login.

Session-based. No classes, no middleware, no "authentication guards."

> Two functions. `checkin` authenticates and checks. `checkout` leaves.

---

## 1) Authenticate

```php
use function bad\auth\checkin;
use function bad\pdo\qp;

$select = qp("SELECT password FROM users WHERE username = ?", []);
$update = qp("UPDATE users SET last_login = NOW() WHERE username = ?", []);

$user = checkin($_POST['username'], $_POST['password'], $update, $select);

if ($user !== '') {
    header('Location: /dashboard');
    exit;
}
// login failed — $user is ''
```

Statements are stored on first call and reused for the request lifetime. You can also initialize them early in bootstrap and authenticate later:

```php
// bootstrap
checkin(null, null, $update, $select);

// later, in login route
$user = checkin($_POST['username'], $_POST['password']);
```

On success:
- Password verified against DB hash
- Session ID regenerated (fixation defense)
- Update statement executed (e.g. last_login timestamp)
- Username stored in session
- Returns the username

On failure:
- Returns `''` (empty string)
- Timing remains constant (dummy hash comparison when user missing)

---

## 2) Check who's logged in

```php
$user = checkin();  // username string or ''
```

No parameters. Returns whatever's in the session, or `''`.

---

## 3) Protected routes

```php
// app/io/route/admin/dashboard.php
return function($args) {
    checkin() !== '' || (header('Location: /login') && exit);
    return ['user' => checkin()];
};
```

---

## 4) Logout

```php
use function bad\auth\checkout;

checkout();
header('Location: /');
exit;
```

What happens:
- `$_SESSION` cleared
- Session cookie destroyed (if `session.use_cookies` enabled)
- `session_destroy()` called

---

## Reference

### Constants

| Constant | Value | Purpose |
|----------|-------|---------|
| `DUMMY_HASH` | bcrypt string | Timing-safe fallback for missing users |

### Functions

```php
checkin(?string $username = null, ?string $password = null, ?\PDOStatement $_update = null, ?\PDOStatement $_select = null): string
checkout(): void
```

#### `checkin()`

| Arguments | Returns |
|-----------|---------|
| none | Username from session, or `''` |
| `$username, $password` | Username on success, `''` on failure |
| `$username, $password, $update, $select` | Same, initializing statements on first call |
| `null, null, $update, $select` | `''` (init only) |

Statements are stored once. Passing them again after initialization throws.

#### `checkout()`

Clears session, destroys cookie, calls `session_destroy()`.

### Throws

| Function | Exception | When |
|----------|-----------|------|
| `checkin` | `BadFunctionCallException` | Not initialized (no statements, no session user) |
| `checkin` | `BadFunctionCallException` | Session not active |
| `checkin` | `BadFunctionCallException` | Empty username or password |
| `checkin` | `BadFunctionCallException` | Already initialized (double init) |
| `checkin` | `RuntimeException` | Select query execution failed |
| `checkin` | `RuntimeException` | `session_regenerate_id` failed |
| `checkin` | `RuntimeException` | Update query execution failed |
| `checkout` | `BadFunctionCallException` | Session not active |
| `checkout` | `RuntimeException` | `session_destroy` failed |

### Session Storage

Authenticated username stored at `$_SESSION['bad\auth']['bad\auth\checkin']`.

---

## Security

- **Timing-safe:** `DUMMY_HASH` ensures `password_verify` always runs, even for nonexistent users
- **Session fixation:** ID regenerated on successful login
- **No plaintext:** Expects `password_hash()` output in DB
- **Cookie cleanup:** Session cookie explicitly destroyed on logout with proper params
- **Single init:** Statements can only be registered once per request
```