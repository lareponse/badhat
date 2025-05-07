<?php

// --- CSRF: Stateless HMAC token with env-based secret
define('CSRF_SECRET', getenv('CSRF_SECRET') ?: 'replace-this-in-prod');
define('CSRF_FIELD', '_csrf');

function csrf_token(string $form_id = ''): string {
    $time = time();
    $data = $form_id . '|' . $time;
    $sig  = hash_hmac('sha256', $data, CSRF_SECRET);
    return base64_encode($data . '|' . $sig);
}

function csrf_input(string $form_id = ''): string {
    return '<input type="hidden" name="' . CSRF_FIELD . '" value="' . htmlspecialchars(csrf_token($form_id)) . '">';
}

function csrf_validate(?string $token, string $form_id = '', int $max_age = 3600): bool {
    if (!$token) return false;
    $decoded = base64_decode($token, true);
    if (!$decoded) return false;

    [$data_id, $timestamp, $sig] = explode('|', $decoded) + [null, null, null];

    if ($data_id !== $form_id || !$timestamp || !$sig) return false;
    if (abs(time() - (int)$timestamp) > $max_age) return false;

    $expected_sig = hash_hmac('sha256', $form_id . '|' . $timestamp, CSRF_SECRET);
    return hash_equals($expected_sig, $sig);
}

// --- CSP Nonce (per request, per type)
function csp_nonce(string $key = 'default'): string {
    static $nonces = [];
    if (!isset($nonces[$key])) {
        $nonces[$key] = bin2hex(random_bytes(16));
    }
    return $nonces[$key];
}

// --- Secure HTTP Headers
function apply_security_headers(): void {
    $script_nonce = csp_nonce('script');
    $style_nonce  = csp_nonce('style');

    header("Content-Security-Policy: " .
        "default-src 'self'; " .
        "script-src 'self' 'nonce-$script_nonce'; " .
        "style-src 'self' 'nonce-$style_nonce'; " .
        "report-uri /csp-report");

    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: no-referrer");

    if (!empty($_SERVER['HTTPS'])) {
        header("Strict-Transport-Security: max-age=63072000; includeSubDomains; preload");
    }
}

// --- Secure cookie helper
function secure_cookie(string $name, string $value, array $opts = []): void {
    setcookie($name, $value, [
        'httponly' => true,
        'secure'   => !empty($_SERVER['HTTPS']),
        'samesite' => $opts['samesite'] ?? 'Strict',
        'expires'  => $opts['expires'] ?? 0,
        'path'     => $opts['path'] ?? '/',
    ]);
}
