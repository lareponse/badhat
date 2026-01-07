# Authentication in BADHAT

Session-based auth via `checkin()`. Bitmask-driven, no classes.

## Constants

```php
const AUTH_SETUP  = 1;    // Configure username field + password query
const AUTH_ENTER  = 2;    // Login attempt via POST
const AUTH_CHECK  = 4;    // Return current user or null
const AUTH_LEAVE  = 8;    // Destroy session
const AUTH_BOUNCE = 16;   // Redirect if not authenticated

const AUTH_GUARD  = AUTH_CHECK | AUTH_BOUNCE;           // Check + redirect combo
const AUTH_REQUIRE_SESSION = AUTH_ENTER | AUTH_CHECK | AUTH_LEAVE;  // Auto-start session

const AUTH_DUMMY_HASH = '$2y$12$...';  // Timing-safe comparison fallback
```

---

## Function

```php
function checkin(int $behave = 0, ?string $u = null, $p = null): ?string
```

**Returns:** username string or `null`

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
        $user && header('Location: /dashboard') && exit;
        // login failed, fall through to form
    }
    return ['error' => $_POST ? 'Invalid credentials' : null];
};
```

- Reads `$_POST[$u]` and `$_POST[$p]`
- Verifies against DB hash
- Regenerates session ID on success

### 3. Protected Route (Guard)

```php
// app/io/route/admin/dashboard.php
return function($args) {
    checkin(AUTH_GUARD, '/login');  // redirect URL
    return ['user' => checkin()];
};
```

- Checks auth, redirects to `/login` if not authenticated
- Combine: `AUTH_CHECK | AUTH_BOUNCE`

### 4. Get Current User

```php
$user = checkin();  // returns username or null
$user = checkin(AUTH_CHECK);  // explicit, same result
```

### 5. Logout

```php
// app/io/route/logout.php
return function($args) {
    checkin(AUTH_LEAVE);
    header('Location: /');
    exit;
};
```

- Clears `$_SESSION`
- Destroys session cookie
- Calls `session_destroy()`

---

## Internal Functions

```php
auth_bounce(string $url)      // header + exit
auth_check(string $field)     // $_SESSION[$field] ?? null
auth_leave()                  // full session teardown
auth_login(...)               // POST verify + session setup
auth_verify(PDOStatement, user, pass)  // timing-safe password check
```

---

## Security Notes

- **Timing-safe:** Uses `AUTH_DUMMY_HASH` when user not found
- **Session fixation:** Regenerates ID on successful login
- **No plaintext:** Expects `password_hash()` in DB
- **Session auto-start:** `AUTH_REQUIRE_SESSION` behaviors call `session_start()` if needed

---

## HTTP-Based Auth (Stateless)

For API/proxy auth, use `auth_http()` from http.php:

```php
function auth_http(): ?string
```

- Reads `HTTP_X_AUTH_USER` + `HTTP_X_AUTH_SIG`
- Validates HMAC-SHA256 against `BADHAT_AUTH_HMAC_SECRET`
- Returns username or `null`

```php
// app/io/route/api/resource.php
return fn($args) => ($user = auth_http()) 
    ? json_encode(['user' => $user])
    : io_die(401, 'Unauthorized');
```

---

## Recommendation Matrix

| Scenario                      | Method                     |
|-------------------------------|----------------------------|
| Web app with sessions         | `checkin()` + `AUTH_GUARD` |
| Stateless API                 | `auth_http()` HMAC         |
| Behind SSO proxy              | Trust `HTTP_X_AUTH_USER`   |
| Internal/staging              | Apache Basic Auth          |