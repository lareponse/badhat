# badhat\auth

Your app needs login.

Session-based, bitmask-driven. No classes, no middleware, no "authentication guards."

> One function, three behaviors. Configure once, then enter, check, or leave.

---

## 1) First, you configure

Somewhere in bootstrap—before any login attempts:

```php
use function bad\auth\checkin;
use const bad\auth\SETUP;

$stmt = qp("SELECT password FROM users WHERE username = ?", []);
checkin(SETUP, 'username', $stmt);
```

Two things stored:
- `'username'` — the session key where authenticated usernames live
- `$stmt` — a prepared statement expecting one `?` for username, returning the password hash

No connection happens. No query runs. Just setup.

---

## 2) Then, you authenticate

When the login form submits:

```php
use const bad\auth\ENTER;

$user = checkin(ENTER, 'username', 'password');

if ($user) {
    header('Location: /dashboard');
    exit;
}
// login failed
```

Parameters are POST field names. Reads `$_POST['username']` and `$_POST['password']`, verifies against the DB hash.

On success:
- Session ID regenerated (fixation defense)
- Username stored in session
- Returns the username

On failure:
- Returns `null`
- Timing remains constant (dummy hash comparison when user missing)

---

## 3) Check who's logged in

```php
$user = checkin();  // username string or null
```

No flags, no parameters. Returns whatever's in the session, or `null`.

---

## 4) Protected routes

```php
// app/io/route/admin/dashboard.php
return function($args) {
    checkin() ?? (header('Location: /login') && exit);
    return ['user' => checkin()];
};
```

---

## 5) Logout

```php
use const bad\auth\LEAVE;

checkin(LEAVE);
header('Location: /');
exit;
```

What happens:
- `$_SESSION` cleared
- Session cookie destroyed (if `session.use_cookies` enabled)
- `session_destroy()` called

Returns `null`.

---

## Reference

### Constants

| Constant | Value | Behavior |
|----------|-------|----------|
| `SETUP` | 1 | Store username field + password query |
| `ENTER` | 2 | Authenticate via POST |
| `LEAVE` | 4 | Destroy session |
| `DUMMY_HASH` | bcrypt string | Timing-safe fallback |

### Function

```php
checkin(int $behave = 0, ?string $u = null, $p = null): ?string
```

| `$behave` | `$u` | `$p` | Returns |
|-----------|------|------|---------|
| `0` | ignored | ignored | Username from session, or `null` |
| `SETUP` | Session key for username | PDOStatement | `null` |
| `ENTER` | POST field: username | POST field: password | Username on success, `null` on failure |
| `LEAVE` | ignored | ignored | `null` |

### Throws

### Throws

| Exception | Code | When |
|-----------|------|------|
| `BadFunctionCallException` | `0` | `checkin()` called before `SETUP` |
| `BadFunctionCallException` | `0xBADC0DE` | Invalid parameters for action (caught Error wrapped) |
| `LogicException` | `0` | No active session |
| `RuntimeException` | `0` | Password query execution failed |
| `RuntimeException` | `0` | session_regenerate_id failed (fixation risk) |
| `RuntimeException` | `0` | session_destroy failed |

### Internal Functions

```php
auth_login(string $username_field, PDOStatement $password_query, string $u, string $p): ?string
auth_session_cookie_destroy(): void
auth_verify(PDOStatement $password_query, string $user, string $pass): ?string
```

### Session Storage

Authenticated username stored at `$_SESSION['bad\auth'][$username_field]`.

---

## Security

- **Timing-safe:** `DUMMY_HASH` ensures `password_verify` always runs, even for nonexistent users
- **Session fixation:** ID regenerated on successful login
- **No plaintext:** Expects `password_hash()` output in DB
- **Cookie cleanup:** Session cookie explicitly destroyed on logout with proper params