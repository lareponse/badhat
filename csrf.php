<?php

namespace bad\csrf;

const TTL_BITS   = 28;                                              // max ttl: 
const TTL_MASK   = (1 << TTL_BITS) - 1;

const SETUP      = 1 << TTL_BITS;
const CHECK      = 2 << TTL_BITS;

function csrf(int $ttl_behave, string $key, $param = null)
{
    $ttl    = $ttl_behave & TTL_MASK;
    $behave = $ttl_behave & ~TTL_MASK;

    $key !== ''                                                     || throw new \InvalidArgumentException(__FUNCTION__.':key empty');
    session_status() === PHP_SESSION_ACTIVE                         || throw new \LogicException(__FUNCTION__.':session not active');

    $_SESSION[__FUNCTION__] ??= [];
    $session = &$_SESSION[__FUNCTION__];

    $now = time();                                                  // current timestamp for expiry checks
    if (SETUP & $behave && !isset($session[$key][1])) {
        ($ttl > 0)                                                  || throw new \InvalidArgumentException(__FUNCTION__.":{$key}:ttl missing in behave");
        $token = bin2hex(random_bytes(32));
        $session[$key] = [$token, $now + $ttl];                     // [token, expiry]
        return $token;
    }

    [$expect, $expire] = ($session[$key]                            ?? throw new \BadFunctionCallException(__FUNCTION__.":{$key}:not initialized"));

    if ($now > $expire) {                                           // expired: cleanup and fail
        unset($session[$key]);
        return null;
    }

    if (CHECK & $behave) {
        $actual = is_string($param) ? $param : ($_POST[$key] ?? null);
        $actual                                                     || throw new \InvalidArgumentException(__FUNCTION__.":{$key}:token required");

        return hash_equals($expect, $actual);                       // timing-safe comparison
    }

    return $expect;
}// return token for form embedding