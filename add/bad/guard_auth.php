<?php
require_once 'add/bad/qb.php';



function whoami(): ?string
{
    static $username = null;

    if ($username === null)
        $username = auth_http() ?? auth_token('auth');

    return $username;
}

function auth_http(): ?string
{
    $secret = getenv('BADGE_AUTH_HMAC_SECRET');
    if (!$secret) {
        throw new DomainException('Auth HMAC secret is missing', 500);
    }

    $user = $_SERVER['HTTP_X_AUTH_USER'] ?? '';
    $sig  = $_SERVER['HTTP_X_AUTH_SIG'] ?? '';

    if (!$user || !$sig) return null;

    $hmac = hash_hmac('sha256', $user, $secret);
    return hash_equals($sig, $hmac) ? $user : null;
}

function auth_token(?string $cookie_index): ?string
{
    if (!empty($cookie_index) && !empty($_COOKIE[$cookie_index])) {

        $token = dbq("SELECT * FROM tokens WHERE token = ? AND expires_at > ?", [$_COOKIE[$cookie_index], time()])->fetch(PDO::FETCH_ASSOC);
        if ($token) {
            if ($token['expires_at'] > time())
                return dbq("SELECT username FROM users WHERE id = ?", [$token['user_id']])->fetch(PDO::FETCH_COLUMN) ?: null;

            auth_token_purge();
        }
    }

    if (empty($cookie_index)) {
        // $token_id =;
        // if ($token_id) {
        // }

        // Clear cookie
        setcookie('auth', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    return null;
}

function check_rate_limit(string $key, int $max_attempts = 5, int $window = 300): bool
{
    $cache_key = 'rate_limit:' . $key;

    // Get current attempts from database or cache
    $result = dbq(
        "SELECT attempts, last_attempt FROM rate_limits 
         WHERE cache_key = ? AND last_attempt > ?",
        [$cache_key, time() - $window]
    )->fetch();

    if (!$result) {
        // First attempt
        dbq(
            "INSERT INTO rate_limits (cache_key, attempts, last_attempt) 
             VALUES (?, 1, ?) 
             ON DUPLICATE KEY UPDATE attempts = 1, last_attempt = ?",
            [$cache_key, time(), time()]
        );
        return true;
    }

    if ($result['attempts'] >= $max_attempts) {
        return false;
    }

    // Increment attempts
    dbq(
        "UPDATE rate_limits SET attempts = attempts + 1, last_attempt = ? 
         WHERE cache_key = ?",
        [time(), $cache_key]
    );

    return true;
}

function auth_post(string $username, string $password, string $csrf_name = 'csrf_token'): bool
{
    $_SERVER['REQUEST_METHOD'] === 'POST'   ?: throw new DomainException('Invalid request method: ' . $_SERVER['REQUEST_METHOD'], 405);
    $_POST[$csrf_name]                      ?: throw new DomainException('CSRF token missing', 403);
    csrf($_POST[$csrf_name] ?? '')          ?: throw new DomainException('Invalid CSRF', 403);

    $username ?: throw new DomainException('Username required', 403);
    $password ?: throw new DomainException('Password required', 403);

    $user = dbq("SELECT * FROM users WHERE username = ?", [$username])->fetch(PDO::FETCH_ASSOC);
    if (is_dev()) {
        if (!$user || $password !== $user['password']) {
            vd($user);
            return false;
        }
    } else {
        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }
    }

    $token = bin2hex(random_bytes(32));
    $user_id = $user['id'];
    $expires_at = time() + (30 * 24 * 3600);

    setcookie('auth', $token, [
        'expires' => $expires_at,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => isset($_SERVER['HTTPS']) // Only if HTTPS
    ]);
    return true;

    // return dbt(function () use ($token, $user_id, $expires_at) {
    //     // Clean up expired tokens first
    //     dbq("DELETE FROM tokens WHERE expires_at < ?", [time()]);

    //     $stmt = dbq("UPDATE tokens SET user_id = ?, expires_at = ? WHERE token = ?", [$user_id, $expires_at, $token]);

    //     if ($stmt->rowCount() === 0) {
    //         dbq(...qb_create('tokens', null, [['token' => $token, 'user_id' => $user_id, 'expires_at' => $expires_at]]));
    //     }

    //     return true;
    // });
}

function auth_revoke(): bool
{
    // Clear HTTP headers
    unset($_SERVER['HTTP_X_AUTH_USER'], $_SERVER['HTTP_X_AUTH_SIG']);

    if (!empty($_COOKIE['auth'])) {

        dbq("DELETE FROM tokens WHERE token = ?", [$_COOKIE['auth']])->rowCount();

        // Clear auth token cookie
        setcookie('auth', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}

function auth_token_purge(): bool
{
    // Remove all expired tokens
    return dbq("DELETE FROM tokens WHERE expires_at < ?", [time()])->rowCount() > 0;
}

/**
 * Generate a per-request nonce for CSP.
 */
function csp_nonce(string $key = 'default'): string
{
    static $nonces = [];
    return $nonces[$key] ??= bin2hex(random_bytes(16));
}

/**
 * Generate or validate a CSRF token.
 * - No args → returns new token (string)
 * - With token → returns bool
 */
function csrf(?string $token = null, ?string $csrf_secret = null, int $max_age = 3600): string|bool
{
    static $secret = null;
    $secret ??= $csrf_secret ?? getenv('CSRF_SECRET') ?: throw new DomainException('A secret is missing to prevent cross-site request forgery', 500);

    if ($token === null) {
        $time = time();
        $sig  = hash_hmac('sha256', $time, $secret);
        return base64_encode("$time|$sig");
    }

    $decoded = base64_decode($token, true);
    if (!$decoded || strpos($decoded, '|') === false) return false;

    [$t, $s] = explode('|', $decoded, 2) + [null, null];
    if (!$t || !$s || abs(time() - (int)$t) > $max_age) return false;

    return hash_equals(hash_hmac('sha256', $t, $secret), $s);
}


function csrf_field(string $name = 'csrf_token'): string
{
    return "<input type='hidden' name='$name' value='" . htmlspecialchars(csrf()) . "'>";
}
