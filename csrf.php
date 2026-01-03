<?php
const CSRF_SETUP = 1;
const CSRF_CHECK = 2;
const CSRF_INPUT = 4 | CSRF_SETUP;

function csrf(int $behave = 0, $param = null, $k = null)
{
    static $key = '_csrf_token';

    session_status() === PHP_SESSION_ACTIVE || throw new RuntimeException('no active session for csrf', 500);

    if (CSRF_SETUP & $behave) {
        is_string($k) && $k && ($key = $k);
        $ttl = $param ?? 3600;
        $now = time();
        (!isset($_SESSION[$key][1]) || $now > $_SESSION[$key][1])
        && $_SESSION[$key] = [bin2hex(random_bytes(32)), $now + $ttl];

        $param = null;
    }

    if (CSRF_CHECK & $behave) {
        [$master, $exp] = $_SESSION[$key] ?? throw new BadFunctionCallException('CSRF not initialized', 403);

        $token = (is_string($param) ? $param : ($_POST[$key] ?? ''));
        $token || throw new BadFunctionCallException('CSRF token required', 403);
        return time() <= $exp && hash_equals($master, $token);
    }

    if (CSRF_INPUT & $behave)
        return '<input type="hidden" name="' . $key . '" value="' . $_SESSION[$key][0] . '" />';

    return $_SESSION[$key][0] ?? null;
}
