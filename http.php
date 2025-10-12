<?php

const CSRF_KEY = '_csrf_token';


// return: clean URI path string
function http_in(int $max_decode = 9): string
{
    // CSRF check
    !empty($_POST) && function_exists('csrf_validate') && !csrf_validate() && http_out(403, 'Invalid CSRF token.', ['Content-Type' => 'text/plain; charset=utf-8']);

    $coded = $_SERVER['REQUEST_URI'] ?? '';
    do {
        $uri_path = rawurldecode($coded);
    } while ($max_decode-- > 0 && $uri_path !== $coded && ($coded = $uri_path));

    $max_decode ?: throw new BadMethodCallException('Path decoding loop reached suspicious limit', 400);

    while (strpos($uri_path, '//') !== false)
        $uri_path = str_replace('//', '/', $uri_path);

    $uri_path = trim($uri_path, '/');

    return $uri_path ?: basename($_SERVER['SCRIPT_NAME'], '.php');
}

// http response, side effect: exits
function http_out(int $status, string $body, array $headers = []): void
{
    http_response_code($status);
    foreach ($headers as $h => $v) header("$h: $v");
    echo $body;
    exit;
}


function csp_nonce(): string
{
    static $nonce = null;
    return $nonce ??= bin2hex(random_bytes(16));
}

function csrf_token(int $ttl = 3600): string
{
    $ttl || throw new InvalidArgumentException('CSRF token TTL must be a positive integer', 400);
    $now  = time();
    if (empty($_SESSION[CSRF_KEY]) || $now > $_SESSION[CSRF_KEY][1]) {
        $master_token       = bin2hex(random_bytes(32));
        $expires_at         = $now + $ttl;
        $_SESSION[CSRF_KEY] = [$master_token, $expires_at];
    }

    return $_SESSION[CSRF_KEY][0] ?? throw new RuntimeException('CSRF token cannot be initialized', 500);
}

function csrf_validate(?string $token = null): bool
{
    $_SESSION[CSRF_KEY]                             ?? throw new BadFunctionCallException('CSRF token not initialized', 403);
    $token = $token ?: ($_POST[CSRF_KEY] ?? '')     ?: throw new BadFunctionCallException('CSRF token is required', 400);

    [$master_token, $expires_at] = $_SESSION[CSRF_KEY];
    return time() <= $expires_at && hash_equals($master_token, $token);
}

function csrf_field(int $ttl = 3600): string
{
    return '<input type="hidden" name="' . CSRF_KEY . '" value="' . csrf_token($ttl) . '" />';
}
