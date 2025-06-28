<?php

// 1. auth('username', 'hashed_password'); init the field names
// 2. auth(); check if logged in return username or null
// 3. auth('myusername', 'superhashedunreadablepassword'); logins
// 4. auth(null, null); logout
function auth(?string $u=null, ?string $p=null): ?string
{
    static $username_field = null;
    static $password_field = null;
    
    session_status() === PHP_SESSION_NONE   && session_start();

    // auth('username', 'hashed_password'); init the field names, returns username is already loggedin
    if(!isset($username_field, $password_field)) {
        $username_field = $u;
        $password_field = $p;

        return $_SESSION[$username_field] ?? null;
    }

    //auth(null, null); logout 
    if(func_num_args() === 2 && !isset($u, $p) && session_destroy()){
        return null;
    }

    // Not logged in ?
    if(!isset($_SESSION[$username_field]) && $_SERVER['REQUEST_METHOD'] === 'POST'){

        $user = dbq(db(), "SELECT `$username_field`, `$password_field` FROM `operator` WHERE `$username_field` = ?", [$u])->fetch();

        !$user || !password_verify($p, $user[$password_field]) || throw new RuntimeException('Auth failed', 401);

        session_regenerate_id(true);
        $_SESSION['username'] = $user['username'];
    }

    return $_SESSION[$username_field];
}

function csp_nonce(): string
{
    static $nonce = null;
    return $nonce ??= bin2hex(random_bytes(16));
}

function csrf_token(int $ttl = 3600): string
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

function csrf_field(int $ttl = 3600): string
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
