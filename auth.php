<?php

const AUTH_SETUP = 1;
const AUTH_VERIFY = 2;
const AUTH_GUARD = 4;
const AUTH_REVOKE = 8;

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

