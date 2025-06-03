<?php
require_once 'add/dad/qb.php';

function whoami(): ?string
{
    static $username = null;

    if ($username === null)
        $username = auth_http() ?? auth_token('auth_token');

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
        setcookie('auth_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    return null;
}

function auth_post(string $username, string $password, bool $remember_me = false): bool
{
    session_start();

    $_SERVER['REQUEST_METHOD'] === 'POST' ?: throw new DomainException('Invalid method', 405);
    csrf($_POST['csrf_token'] ?? '') ?: throw new DomainException('Invalid CSRF', 403);

    $user = dbq("SELECT * FROM users WHERE username = ?", [$username])->fetch(PDO::FETCH_ASSOC);
    // if (!$user || ($password != $user['password'])) return false;
    if (!$user || !password_verify($password, $user['password'])) return false;

    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $token = bin2hex(random_bytes(32));
    $expires = time() + 30 * 24 * 3600;

    [$sql, $bindings] = qb_create('tokens', null, [
        'token' => $token,
        'user_id' => $user['id'],
        'expires_at' => $expires,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    dbq($sql, $bindings);


    setcookie('auth_token', $token, [
        'expires' => $expires,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443,

    ]);

    return true;
}

function auth_revoke(): void
{
    session_start();
    session_destroy();

    if (!empty($_COOKIE['auth_token'])) {
        dbq("DELETE FROM tokens WHERE token = ?", [$_COOKIE['auth_token']]);
        setcookie('auth_token', '', [
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
