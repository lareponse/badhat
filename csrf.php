<?php

namespace bad\csrf;

const TTL_BITS   = 28;                                              // max ttl: 
const TTL_MASK   = (1 << TTL_BITS) - 1;

const TOKEN      = 1 << TTL_BITS;
const CHECK      = 2 << TTL_BITS;

function csrf(int $ttl_behave, string $key, $param = null)
{
    $behave = $ttl_behave & ~TTL_MASK;

    $key !== ''                                                     || throw new \InvalidArgumentException('csrf:key empty');
    \session_status() === PHP_SESSION_ACTIVE                        || throw new \LogicException('csrf:session not active');

    $_SESSION[__NAMESPACE__][__FUNCTION__] ??= [];
    $scoped = &$_SESSION[__NAMESPACE__][__FUNCTION__];

    $now = \time();                                                 // current timestamp for expiry checks
    if (TOKEN & $behave) {

        if(isset($scoped[$key][0], $scoped[$key][1]) && $now < $scoped[$key][1])
            return $scoped[$key][0];
        
        $token = \bin2hex(\random_bytes(32));
        $ttl = $ttl_behave & TTL_MASK;                              // no check on TTL duration, expire is the truth.
        $scoped[$key] = [$token, $now + $ttl];                      // [token, expiry]
        // dont return token, it will go through expire validation
    }

    isset($scoped[$key][0], $scoped[$key][1])                       || throw new \BadFunctionCallException("csrf:{$key}:not initialized");

    if ($now >= $scoped[$key][1]) {                                 // expired: cleanup and fail
        unset($scoped[$key]);
        return null;
    }

    if (CHECK & $behave) {
        $actual = \is_string($param) ? $param : ($_POST[$key] ?? null);
        if($actual !== null && \hash_equals($scoped[$key][0], $actual)){
            unset($scoped[$key]);
            return true;
        }
        return false;
    }
    return $scoped[$key][0];
}// return token for form embedding