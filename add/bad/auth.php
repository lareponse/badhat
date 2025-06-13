<?php

//   ENTRYPOINT: WHO’S LOGGED IN?
function whoami(): ?string
{
    static $user = null;
    return $user ??= auth_resolve();
}

function auth_resolve(): ?string
{
    // 1) Web-session users
    return auth_session()
        // 2) API clients via HMAC headers
        ?? auth_http();
}

//   1) SESSION-BASED AUTH
function auth_session(): ?string
{
    if (session_status() === PHP_SESSION_NONE)
        session_start();

    if (empty($_SESSION['user_id']))
        return null;

    return dbq(db(), "SELECT username FROM users WHERE id = ?", [$_SESSION['user_id']])->fetchColumn() ?: null;
}

//   2) HTTP-HMAC
function auth_http(): ?string
{
    $user = $_SERVER['HTTP_X_AUTH_USER'] ?? '';
    $sig  = $_SERVER['HTTP_X_AUTH_SIG']  ?? '';

    if (!$user || !$sig) {
        return null;
    }
    $hmac = getenv('BADDAD_AUTH_HMAC_SECRET');
    $hmac ?: throw new DomainException('HMAC secret missing', 500);
    $expected = hash_hmac('sha256', $user, $hmac);
    return hash_equals($sig, $expected)
        ? $user
        : null;
}

//   LOGIN / LOGOUT / CSRF
function auth_login(string $username, string $password): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new BadMethodCallException('POST required', 405);
    }

    $user = dbq(db(), "SELECT id, password FROM users WHERE username = ?", [$username])->fetch();
    if (!$user || ! password_verify($password, $user['password'])) {
        throw new RuntimeException('Auth failed', 401);
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];

    return true;
}

function auth_logout(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_destroy();
}

function csp_nonce(): string
{
    static $nonce = null;
    return $nonce ??= bin2hex(random_bytes(16));
}

function csrf_token(int $ttl=3600): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $key  = '_csrf_token';
    $data = $_SESSION[$key] ?? ['value' => '', 'expires_at' => 0];
    $now  = time();

    if (empty($data['value']) || $now > $data['expires_at']) {
        $data['value']      = bin2hex(random_bytes(32));
        $data['expires_at'] = $now + $ttl;
        $_SESSION[$key]     = $data;
    }

    return $data['value'];
}

function csrf_field(int $ttl): string
{
    $token = csrf_token($ttl);
    return "<input type='hidden' name='csrf_token' value='" .
        htmlspecialchars($token, ENT_QUOTES) .
        "'>";
}

function csrf_validate(?string $token = null): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE)
        session_start();

    $token = $token ?? ($_POST['csrf_token'] ?? '');
    if ($token === '')
        return false;

    $key  = '_csrf_token';
    $data = $_SESSION[$key] ?? ['value' => '', 'expires_at' => 0];
    $now  = time();

    return !empty($data['value'])
        && hash_equals($data['value'], $token)
        && $now <= $data['expires_at'];
}
