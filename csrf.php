<?php
namespace badhat\csrf;

const CSRF_SETUP = 1;
const CSRF_CHECK = 2;
const CSRF_INPUT = 4;

function csrf(string $name, int $behave, $param = null)
{
    session_status() === PHP_SESSION_ACTIVE     || throw new RuntimeException('No active session for CSRF', 500);
    $name                                       ?: throw new InvalidArgumentException('CSRF name required', 500);

    if (CSRF_SETUP & $behave) {
        $ttl = is_int($param) && $param > 0 ? $param : 3600;
        $now = time();

        if (!isset($_SESSION[__NAMESPACE__][$name][1]) || $now > $_SESSION[__NAMESPACE__][$name][1])
            $_SESSION[__NAMESPACE__][$name] = [bin2hex(random_bytes(32)),$now + $ttl];
    }

    $_SESSION[__NAMESPACE__][$name]          ?? throw new BadFunctionCallException("CSRF '$name' not initialized", 403);

    if (CSRF_CHECK & $behave) {
        [$master, $exp] = $_SESSION[__NAMESPACE__][$name];

        $token = is_string($param) ? $param : ($_POST[$name] ?? '');
        $token                                  || throw new BadFunctionCallException("CSRF '$name' token required", 403);

        return time() <= $exp && hash_equals($master, $token);
    }

    if (CSRF_INPUT & $behave)
        return '<input type="hidden" name="' . htmlspecialchars($name, ENT_QUOTES). '" value="' . $_SESSION[__NAMESPACE__][$name][0] . '" />';

    return $_SESSION[__NAMESPACE__][$name][0];
}
