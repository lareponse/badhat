<?php

/**
 * Authentication wall
 */
function auth()
{
    if (empty($_SERVER['HTTP_X_AUTH_USER'])) {
        exit('401 Unauthorized');
    }
    return true;
}

function operator(): ?string
{
    if (empty(getenv('ADDBAD_AUTH_HMAC_SECRET')))
        exit('500 Auth HMAC secret is missing');

    if (empty($_SERVER['HTTP_X_AUTH_USER']) || empty($_SERVER['HTTP_X_AUTH_SIG']))
        return null;

    $hash_hmac = hash_hmac('sha256', $_SERVER['HTTP_X_AUTH_USER'], getenv('ADDBAD_AUTH_HMAC_SECRET'));

    if (hash_equals($_SERVER['HTTP_X_AUTH_SIG'], $hash_hmac))
        return $_SERVER['HTTP_X_AUTH_USER'];

    return null;
}

/**
 * Generate a per-request nonce for CSP.
 */
function csp_nonce(string $key = 'default'): string
{
    static $nonces = [];
    return $nonces[$key] ??= bin2hex(random_bytes(16));
}

/**
 * Generate or validate a CSRF token.
 * - No args → returns new token (string)
 * - With token → returns bool
 */
function csrf(?string $token = null, int $max_age = 3600, $env_key = 'CSRF_SECRET'): string|bool
{
    if (empty(getenv($env_key)))
        exit('500 CSRF secret is missing');
    
    if ($token === null) {
        $time = time();
        $sig  = hash_hmac('sha256', $time, getenv($env_key));
        return base64_encode("$time|$sig");
    }
    
    $decoded = base64_decode($token, true);
    [$t, $s] = explode('|', $decoded) + [null, null];
    
    if (!$t || !$s || abs(time() - (int)$t) > $max_age)
        return false;
    
    return hash_equals(hash_hmac('sha256', $t, getenv($env_key)), $s);
}
