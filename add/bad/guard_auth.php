<?php

/**
 * Authentication wall
 */
function auth(): bool
{
    if (empty($_SERVER['HTTP_X_AUTH_USER'])) {
        trigger_error('401 Unauthorized', E_USER_ERROR);
    }
    return true;
}

function operator(): ?string
{
    $secret = getenv('BADGE_AUTH_HMAC_SECRET');
    if (!$secret) {
        trigger_error('500 Auth HMAC secret is missing', E_USER_ERROR);
    }

    $user = $_SERVER['HTTP_X_AUTH_USER'] ?? '';
    $sig  = $_SERVER['HTTP_X_AUTH_SIG'] ?? '';

    if (!$user || !$sig) return null;

    $hmac = hash_hmac('sha256', $user, $secret);
    return hash_equals($sig, $hmac) ? $user : null;
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
function csrf(?string $token = null, int $max_age = 3600, string $env_key = 'CSRF_SECRET'): string|bool
{
    $secret = getenv($env_key);
    if (!$secret) {
        trigger_error('500 CSRF secret is missing', E_USER_ERROR);
    }

    if ($token === null) {
        $time = time();
        $sig  = hash_hmac('sha256', $time, $secret);
        return base64_encode("$time|$sig");
    }

    $decoded = base64_decode($token, true);
    if (!$decoded || strpos($decoded, '|') === false) return false;

    [$t, $s] = explode('|', $decoded, 2) + [null, null];
    if (!$t || !$s || abs(time() - (int)$t) > $max_age) return false;

    return hash_equals(hash_hmac('sha256', $t, $secret), $s);
}
