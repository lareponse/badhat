# BADHAT Authentication

Session-based auth via `checkin()`. Bitmask-driven, no classes.

## Constants

```php
const AUTH_SETUP  = 1;    // Configure username field + password query
const AUTH_ENTER  = 2;    // Login attempt via POST
const AUTH_LEAVE  = 4;    // Destroy session

const AUTH_DUMMY_HASH = '$2y$12$...';  // Timing-safe comparison fallback
```

---

## Function

```php
function checkin(int $behave = 0, ?string $u = null, $p = null): ?string
```

**Returns:** username string or `null`

**Requires:** `session_status() === PHP_SESSION_ACTIVE` (except for AUTH_SETUP)

**Throws:**
- `RuntimeException` (500) if no active session
- `BadFunctionCallException` (400) if invalid parameters for action

---

## Usage Patterns

### 1. Setup (once, at bootstrap)

```php
$stmt = qp("SELECT password FROM users WHERE username = ?", []);
checkin(AUTH_SETUP, 'username', $stmt);
```

- `$u` = session key for username storage
- `$p` = prepared PDOStatement (expects single `?` for username)

### 2. Login Route

```php
// app/io/route/login.php
return function($args) {
    if ($_POST) {
        $user = checkin(AUTH_ENTER, 'username', 'password');
        $user && http_out(302, null, ['Location' => ['/dashboard']]);
        // login failed, fall through to form
    }
    return ['error' => $_POST ? 'Invalid credentials' : null];
};
```

- Reads `$_POST[$u]` and `$_POST[$p]`
- Verifies against DB hash
- Regenerates session ID on success

### 3. Get Current User

```php
$user = checkin();  // returns username or null
```

### 4. Protected Route

```php
// app/io/route/dashboard.php
return function($args) {
    checkin() ?? http_out(302, null, ['Location' => ['/login']]);
    return ['user' => checkin()];
};
```

### 5. Logout

```php
// app/io/route/logout.php
return function($args) {
    checkin(AUTH_LEAVE);
    http_out(302, null, ['Location' => ['/']]);
};
```

- Clears `$_SESSION`
- Destroys session cookie
- Calls `session_destroy()`

---

## Internal Functions

```php
auth_login(string $username_field, PDOStatement $password_query, string $u, string $p): ?string
auth_session_cookie_destroy(): void
auth_verify(PDOStatement $password_query, string $user, string $pass): ?string
```

---

## Security Notes

- **Timing-safe:** Uses `AUTH_DUMMY_HASH` when user not found
- **Session fixation:** Regenerates ID on successful login
- **No plaintext:** Expects `password_hash()` in DB
- **Cookie cleanup:** Properly expires session cookie on logout

---

## Recommendation Matrix

| Scenario                      | Method                          |
|-------------------------------|---------------------------------|
| Web app with sessions         | `checkin()` + redirect guard    |
| Stateless API                 | Custom HMAC implementation      |
| Behind SSO proxy              | Trust headers after validation  |
| Internal/staging              | Apache Basic Auth               |