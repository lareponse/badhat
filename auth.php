<?php

const AUTH_SETUP  = 1;
const AUTH_ENTER  = 2;
const AUTH_CHECK  = 4;
const AUTH_LEAVE  = 8;
const AUTH_BOUNCE = 16;

const AUTH_GUARD  = AUTH_CHECK | AUTH_BOUNCE;

const AUTH_REQUIRE_SESSION = AUTH_ENTER | AUTH_CHECK | AUTH_LEAVE;
const AUTH_CONSUME_PARAM   = AUTH_SETUP | AUTH_ENTER | AUTH_BOUNCE;

const AUTH_DUMMY_HASH = '$2y$12$8NidQXAmttzUc23lTnUDAuC.JoxuJtdG0NQTjhh3Y7C442uVQ4FTy';

function checkin(int $behave = 0, ?string $u = null, $p = null): ?string
{
    static $username_field = null;
    static $password_query = null;

    $consumers = $behave & AUTH_CONSUME_PARAM;
    ($consumers & ($consumers - 1))
        && throw new BadFunctionCallException('Multiple param-consuming flags not allowed', 400);

    if (AUTH_SETUP & $behave) {
        (!is_string($u) || $u === '')
            && throw new InvalidArgumentException('Username field must be non-empty string', 400);
        $username_field = $u;
        $password_query = $p;
        return null;
    }

    (AUTH_REQUIRE_SESSION & $behave) && session_status() === PHP_SESSION_NONE
        && (session_start() || throw new RuntimeException('session_start failed', 500));

    try {
        if (AUTH_ENTER & $behave) {
            auth_login($username_field, $password_query, $u, $p);
        }

        if (AUTH_CHECK & $behave) {
            if (auth_check($username_field) === null) {
                if (AUTH_BOUNCE & $behave) {
                    auth_bounce($u);
                }
                throw new RuntimeException('Authentication required', 401);
            }
            $behave &= ~AUTH_BOUNCE;
        }

        if (AUTH_LEAVE & $behave) {
            auth_leave();
            return null;
        }

        (AUTH_BOUNCE & $behave) && auth_bounce($u);

    } catch (Error $e) {
        throw new BadFunctionCallException('Invalid parameters for AUTH action', 400, $e);
    }

    return $_SESSION[$username_field] ?? null;
}

function auth_bounce(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function auth_check(string $username_field): ?string
{
    return $_SESSION[$username_field] ?? null;
}

function auth_leave(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(),'',time() - 3600,$p['path'],$p['domain'],$p['secure'],$p['httponly']);
    }

    session_destroy() || throw new RuntimeException('session destruction error', 500);
}

function auth_login(string $username_field, PDOStatement $password_query, string $u, string $p): ?string
{
    $user = isset($_POST[$u], $_POST[$p])
        ? auth_verify($password_query, $_POST[$u], $_POST[$p])
        : null;

    if ($user !== null) {
        session_regenerate_id(true) || throw new RuntimeException('Session ID regeneration failed', 500);
        $_SESSION[$username_field] = $user;
    }

    return $user;
}

function auth_verify(PDOStatement $password_query, string $user, string $pass): ?string
{
    $password_query->execute([$user]) || throw new RuntimeException('Password query execution failed', 500);

    $db_password = $password_query->fetchColumn() ?: AUTH_DUMMY_HASH;
    $password_query->closeCursor();

    return (password_verify($pass, $db_password) && AUTH_DUMMY_HASH !== $db_password)
        ? $user
        : null;
}