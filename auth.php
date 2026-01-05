<?php
namespace bad\auth;

const AUTH_SETUP  = 1;
const AUTH_ENTER  = 2;
const AUTH_LEAVE  = 4;

const AUTH_DUMMY_HASH = '$2y$12$8NidQXAmttzUc23lTnUDAuC.JoxuJtdG0NQTjhh3Y7C442uVQ4FTy';

function checkin(int $behave = 0, ?string $u = null, $p = null): ?string
{
    static $username_field = null;
    static $password_query = null;

    if (AUTH_SETUP & $behave) {
        $username_field = $u;
        $password_query = $p;
        return null;
    }

    ($username_field && $password_query)        || throw new \BadFunctionCallException('AUTH not initialized (call with AUTH_SETUP first)', 500);
    session_status() === PHP_SESSION_ACTIVE     || throw new \RuntimeException('no active session for auth', 500);

    try {
        if(AUTH_LEAVE & $behave){
            $_SESSION = [];
            ini_get('session.use_cookies') && auth_session_cookie_destroy();
            session_destroy() || trigger_error('session_destroy failed', E_USER_WARNING);
        }

        if (AUTH_ENTER & $behave)
            return auth_login($username_field, $password_query, $u, $p);

        return $_SESSION[__NAMESPACE__][$username_field] ?? null;

    } catch (\Error $e) {
        throw new \BadFunctionCallException('Invalid parameters for AUTH action', 400, $e);
    }
}

function auth_login(string $username_field, \PDOStatement $password_query, string $u, string $p): ?string
{
    $user = isset($_POST[$u], $_POST[$p])
        ? auth_verify($password_query, $_POST[$u], $_POST[$p])
        : null;

    if ($user !== null) {
        session_regenerate_id(true) || trigger_error('Session ID regeneration failed - fixation risk', E_USER_WARNING);
        $_SESSION[__NAMESPACE__][$username_field] = $user;
    }

    return $user;
}

function auth_session_cookie_destroy()
{
    $opts = ['expires' => time() - 3600]
            + session_get_cookie_params()
            + ['path' => '/', 'domain' => '', 'secure' => false, 'httponly' => true];

    unset($opts['lifetime']); // not a setcookie() option
    $opts['samesite'] ??= (ini_get('session.cookie_samesite') ?: 'Lax');

    setcookie(session_name(), '', $opts) || trigger_error('session cookie destruction failed', E_USER_WARNING);
}

function auth_verify(\PDOStatement $password_query, string $user, string $pass): ?string
{
    $password_query->execute([$user]) || throw new \RuntimeException('Password query execution failed', 500);

    $db_password = $password_query->fetchColumn() ?: AUTH_DUMMY_HASH;
    $password_query->closeCursor();

    return (password_verify($pass, $db_password) && AUTH_DUMMY_HASH !== $db_password)
        ? $user
        : null;
}