<?php

define('BADGE_MIME_BASE', '');
define('BADGE_MIME_DEFAULT', 'text/html');
define('BADGE_MIME_ACCEPT', 'application/vnd.BADGE+json, text/html, text/plain');

function http_request(): array
{
    return [
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
        'path' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
        'body'   => file_get_contents('php://input') ?: '',
    ];
}

function http_response(int $http_code, string $body, array $http_headers = []): array
{
    return [
        'status'  => $http_code,
        'headers' => $http_headers,
        'body'    => $body,
    ];
}

function http_guard($request): array
{
    if (!preg_match('/^(GET|POST|PUT|DELETE|PATCH|HEAD|OPTIONS)$/', $request['method']))
        http_echo(405, "Method Not Allowed: " . $request['method']);

    // CSRF check
    if (!empty($_POST) && function_exists('csrf') && !csrf($_POST['csrf_token'] ?? ''))
        http_echo(403, 'Invalid CSRF token.');

    // Rate limit by IP
    // if (function_exists('check_rate_limit') && !check_rate_limit($_SERVER['REMOTE_ADDR'])) {
    //     trigger_error('429 Too Many Requests: Rate limit exceeded', E_USER_WARNING);
    //     return [
    //         'status' => 429,
    //         'body' => render(['error' => 'Too many requests, try again later.'])
    //     ];
    // }

    $path = urldecode($request['path']);
    $path = preg_replace('#/+#', '/', trim($path, '/')) ?: '';
    if (preg_match('#(\.{2}|[\/]\.)#', $path))
        http_echo(403, 'Forbidden: Path Traversal');

    if (!empty($path)) {
        $map = explode('/', $path);
        foreach ($map as $seg)
            if ($seg && !preg_match('/^[a-zA-Z0-9_\-]+$/', $seg))
                http_echo(400, 'Bad Request: Invalid Segment /' . $seg . '/');
        return $map;
    }

    return [];
}

function http_echo(int $status, string $body, array $headers = []): void
{
    http_response_code($status);

    foreach ($headers as $h => $v)
        header("$h: $v");

    echo $body;
    exit;
}

function request_mime(?string $http_accept, ?string $requested_format): string
{
    return strpos("$requested_format$http_accept", 'application/vnd.BADGE+json') !== false ? 'application/vnd.BADGE+json' : 'text/html';
}
