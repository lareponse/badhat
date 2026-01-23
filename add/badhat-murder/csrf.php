<?php
namespace bad\csrf;

const SETUP = 1;
const CHECK = 2;
const FORCE = 4;

function csrf(string $key, $param = null, int $behave = 0)
{
    ((SETUP | CHECK) & $behave) !== (SETUP | CHECK)                 || throw new \BadFunctionCallException(__FUNCTION__.' cannot SETUP and CHECK at the same time', 400);
    $key !== ''                                                     || throw new \InvalidArgumentException(__FUNCTION__.' key cannot be empty', 400);
    session_status() === PHP_SESSION_ACTIVE                         || throw new \LogicException(__FUNCTION__.' requires an active session', 400);
    
    $_SESSION[__NAMESPACE__][__FUNCTION__] ??= [];                  // init storage
    $session = &$_SESSION[__NAMESPACE__][__FUNCTION__];             // reference for mutation
    
    $now = time();                                                  // current timestamp for expiry checks
    if (SETUP & $behave) {
        is_int($param) && $param > 0                                || throw new \InvalidArgumentException(__FUNCTION__." '{$key}' TTL must be a positive integer", 400);
        
        $active = isset($session[$key][1]) && $now <= $session[$key][1];
        !$active || (FORCE & $behave)                               || throw new \LogicException(__FUNCTION__." '{$key}' is not expired (use the FORCE)", 400);

        $session[$key] = [bin2hex(random_bytes(32)), $now + $param]; // [token, expiry]
    }                                                       

    [$expect, $expire] = ($session[$key]                            ?? throw new \BadFunctionCallException(__FUNCTION__." '{$key}' not initialized", 400));

    if ($now > $expire) {                                           // expired: cleanup and fail
        unset($session[$key]);
        return false;
    }
    
    if (CHECK & $behave) {
        $actual = is_string($param) ? $param : ($_POST[$key] ?? null);
        $actual                                                     || throw new \InvalidArgumentException(__FUNCTION__." '{$key}' token required", 400);
        
        return hash_equals($expect, $actual);                       // timing-safe comparison
    }

    return $expect;                                        
}// return token for form embedding