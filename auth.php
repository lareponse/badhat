<?php

const AUTH_SETUP = 1;
const AUTH_VERIFY = 2;
const AUTH_GUARD = 4;
const AUTH_REVOKE = 8;

const CSRF_KEY = '_csrf_token';

session_status() === PHP_SESSION_NONE  && (session_start() || throw new RuntimeException('Session cannot be started', 500));

// 1. auth(AUTH_SETUP, 'username', PDOStatement); init the field name and the statement to fetch the password hash
// 3. auth(AUTH_VERIFY, 'myusername', 'superhashedunreadablepassword'); for login POST route
// 4. auth(AUTH_REVOKE); for logout route
// 5. auth(AUTH_GUARD, url); redirect to url if not logged in
// 2. auth(); check if logged in return username or null
function auth(int $behave=0, ?string $u = null, $p = null): ?string
{
    static $username_field = null;
    static $password_query = null; // PDOStatement

    // vd(0, $behave, $u, $p, $username_field, $password_query, $_SESSION);
    $behave & AUTH_SETUP    && $u && $p && ($username_field = $u) && ($password_query = $p);
    $behave & AUTH_REVOKE   && session_destroy() && ($password_query = $username_field = null);
    
    if($behave & AUTH_VERIFY){
        !isset($u, $p, $username_field, $password_query)        && throw new BadFunctionCallException('Missing AUTH_SETUP or function params', 500);
        (empty($_POST[$u]) || empty($_POST[$p]))                && throw new BadMethodCallException('Login must be a valid POST request');
        
        $password_query instanceof PDOStatement                 || throw new InvalidArgumentException('Password query must be a valid PDOStatement');
        $password_query->execute([$_POST[$u]])                  || throw new RuntimeException('Password query execution failed', 500);
        
        $db_password = $password_query->fetchColumn();
        if($db_password !== false && password_verify($_POST[$p], ($db_password)) && session_regenerate_id(true))
            return ($_SESSION[$username_field] = $_POST[$u]);
    }

    if($behave & AUTH_GUARD && !isset($username_field, $_SESSION[$username_field])){
        header('Location: ' . ($u ?: '/'));
        exit;
    }

    return $_SESSION[$username_field] ?? null;
}

function csp_nonce(): string
{
    static $nonce = null;
    return $nonce ??= bin2hex(random_bytes(16));
}

function csrf_token(int $ttl = 3600): string
{
    $ttl || throw new InvalidArgumentException('CSRF token TTL must be a positive integer', 400);
    $now  = time();
    if (empty($_SESSION[CSRF_KEY]) || $now > $_SESSION[CSRF_KEY][1]) {
        $master_token       = bin2hex(random_bytes(32));
        $expires_at         = $now + $ttl;
        $_SESSION[CSRF_KEY] = [$master_token, $expires_at];
    }

    return $_SESSION[CSRF_KEY][0] ?? throw new RuntimeException('CSRF token cannot be initialized', 500);
}

function csrf_validate(?string $token = null): bool
{
    $_SESSION[CSRF_KEY]                             ?? throw new BadFunctionCallException('CSRF token not initialized', 403);
    $token = $token ?: ($_POST[CSRF_KEY] ?? '')     ?: throw new BadFunctionCallException('CSRF token is required', 400);

    [$master_token, $expires_at] = $_SESSION[CSRF_KEY];

    return time() <= $expires_at && hash_equals($master_token, $token);
}

function csrf_field(int $ttl = 3600): string
{
    return '<input type="hidden" name="' . CSRF_KEY . '" value="' . ($_SESSION[CSRF_KEY][0] ?? csrf_token($ttl)) . '" />';
}

// HTTP-HMAC AUTH
function auth_http(): ?string
{
    $user = $_SERVER['HTTP_X_AUTH_USER'] ?? '';
    $sig  = $_SERVER['HTTP_X_AUTH_SIG']  ?? '';
    if ($user === '' || $sig === '') {
        return null;
    }

    $secret = $_SERVER['BADHAT_AUTH_HMAC_SECRET'] ?? getenv('BADHAT_AUTH_HMAC_SECRET') ?: throw new DomainException('HMAC secret missing', 500);

    $expected = hash_hmac('sha256', $user, $secret);
    return hash_equals($expected, $sig) ? $user : null;
}
