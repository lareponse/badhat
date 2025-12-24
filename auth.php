<?php

const AUTH_SETUP = 1;
const AUTH_START = 2;
const AUTH_BEGIN = AUTH_SETUP | AUTH_START;

const AUTH_LOGIN = 4;
const AUTH_GUARD = 8;
const AUTH_UNLOG = 16;

const AUTH_SINGLE_ACTIONS = AUTH_LOGIN | AUTH_GUARD | AUTH_UNLOG;

// Any VALID bcrypt hash is fine. This one is valid ($2y$, cost 12, 60 chars).
const AUTH_DUMMY_HASH = '$2y$12$8NidQXAmttzUc23lTnUDAuC.JoxuJtdG0NQTjhh3Y7C442uVQ4FTy';


function auth(int $behave = 0, ?string $u = null, $p = null): ?string
{
    static $username_field = null;
    static $password_query = null; // PDOStatement

    if (AUTH_SETUP & $behave) {
        (!is_string($u) || $u === '' || !($p instanceof PDOStatement)) && throw new InvalidArgumentException('AUTH_SETUP requires non-empty string and PDOStatement', 500);
        $username_field = $u;
        $password_query = $p;
    }

    $username_field === null
        && throw new LogicException('AUTH_SETUP must be called first', 500);

    AUTH_START & $behave && session_status() === PHP_SESSION_NONE && (session_start() || throw new RuntimeException('session_start failed', 500));

    // $x & ($x - 1) is non-zero if more than one bit is set
    (($actions = $behave & AUTH_SINGLE_ACTIONS) && ($actions & ($actions - 1)))  && throw new BadFunctionCallException('Multiple AUTH actions are not allowed');

    session_status() !== PHP_SESSION_ACTIVE && ($behave & (AUTH_GUARD | AUTH_UNLOG | AUTH_LOGIN))
        && throw new LogicException('AUTH_START must be called after AUTH_SETUP', 500);

    AUTH_LOGIN & $behave && auth_login($username_field, $password_query, $u, $p);
    AUTH_GUARD & $behave && auth_guard($username_field, $u);
    AUTH_UNLOG & $behave && auth_unlog($username_field, $u);

    return $_SESSION[$username_field] ?? null;
}

function auth_unlog(string $username_field, ?string $redirect_location = null): void
{
    unset($_SESSION[$username_field]);
    $_SESSION = [];
    session_destroy();

    $redirect_location && header('Location: ' . $redirect_location);
    exit;
}

function auth_guard(string $username_field, ?string $redirect_location = null): void
{
    if (isset($_SESSION[$username_field]))
        return;

    $redirect_location && header('Location: ' . $redirect_location);
    exit;
}

function auth_login(string $username_field, PDOStatement $password_query, string $u, string $p): ?string
{
    session_status() === PHP_SESSION_NONE
        && throw new LogicException('Missing AUTH_START', 500);

    $user = $_POST[$u] ?? null;
    $pass = $_POST[$p] ?? null;
    (!is_string($user) || !is_string($pass) || trim($user) === '')
        && throw new BadMethodCallException('Invalid login request', 400);

    $password_query->execute([$_POST[$u]])
        || throw new RuntimeException('Password query execution failed', 500);

    $db_password = $password_query->fetchColumn() ?: AUTH_DUMMY_HASH; // dummy hash to keep timing consistent when user is not found
    $password_query->closeCursor();

    if (password_verify($pass, $db_password) && AUTH_DUMMY_HASH !== $db_password) {
        session_regenerate_id(true);
        return $_SESSION[$username_field] = $user;
    }
    return null;
}
