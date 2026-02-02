<?php

namespace bad\csrf;

const TTL_BITS   = 20;
const TTL_MASK   = (1 << TTL_BITS) - 1;

const SETUP      = 1 << TTL_BITS;
const CHECK      = 2 << TTL_BITS;
const TOKEN      = 4 << TTL_BITS;

const FLAGS_MASK = SETUP | CHECK | TOKEN;

function csrf(int $ttl_behave, string $key, $param = null)
{
    $behave = $ttl_behave & FLAGS_MASK;
    $ttl   = $ttl_behave & TTL_MASK;

    $key !== ''                                                     || throw new \InvalidArgumentException('key is empty');
    session_status() === PHP_SESSION_ACTIVE                         || throw new \LogicException('missing active session');

    $_SESSION[__FUNCTION__] ??= [];
    $session = &$_SESSION[__FUNCTION__];

    $now = time();                                                  // current timestamp for expiry checks
    if (SETUP & $behave && !isset($session[$key][1])) {
        ($ttl > 0)                                                  || throw new \InvalidArgumentException("'{$key}' TTL must be encoded in behave low bits");
        $token = bin2hex(random_bytes(32));
        $session[$key] = [$token, $now + $ttl];                     // [token, expiry]
        return $token;
    }

    [$expect, $expire] = ($session[$key]                            ?? throw new \BadFunctionCallException("'{$key}' not initialized"));

    if ($now > $expire) {                                           // expired: cleanup and fail
        unset($session[$key]);
        return null;
    }

    if (CHECK & $behave) {
        $actual = is_string($param) ? $param : ($_POST[$key] ?? null);
        $actual                                                     || throw new \InvalidArgumentException("'{$key}' token required");

        return hash_equals($expect, $actual);                       // timing-safe comparison
    }

    return $expect;
}// return token for form embedding