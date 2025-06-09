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
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return dbq(
        "SELECT username FROM users WHERE id = ?",
        [$_SESSION['user_id']]
    )->fetchColumn() ?: null;
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
        throw new DomainException('POST required', 405);
    }
    if (! csrf_validate($_POST['csrf_token'] ?? '')) {
        throw new DomainException('CSRF invalid', 403);
    }

    $user = dbq(
        "SELECT id, password FROM users WHERE username = ?",
        [$username]
    )->fetch();
    if (!$user || ! password_verify($password, $user['password'])) {
        throw new DomainException('Auth failed', 401);
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

function csrf(?string $token = null): string|bool
{
    static $secret = null;
    $secret ??= getenv('CSRF_SECRET') ?: throw new DomainException('CSRF secret missing', 500);
    if ($token === null) {
        $time = time();
        $sig = hash_hmac('sha256', $time, $secret);
        return base64_encode("$time|$sig");
    }
    
    $decoded = base64_decode($token, true);
    if (!$decoded || !str_contains($decoded, '|')) return false;
    
    [$t, $s] = explode('|', $decoded, 2);
    if (abs(time() - (int)$t) > 3600) return false;
    
    return hash_equals(hash_hmac('sha256', $t, $secret), $s);
}

function csp_nonce(): string
{
    static $nonce = null;
    return $nonce ??= bin2hex(random_bytes(16));
}