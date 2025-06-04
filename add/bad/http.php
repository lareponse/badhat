<?php

// define('BADGE_MIME_ACCEPT', 'application/vnd.BADGE+json, text/html, text/plain');

// no http_request(), we are in the request, just ask $_SERVER, $_POST, $_GET, $_COOKIE, they know
// check if the request is a valid beyond webserver .conf
function http_guard(): void
{
    // CSRF check
    if (!empty($_POST) && function_exists('csrf') && !csrf($_POST['csrf_token'] ?? ''))
        http_respond(403, 'Invalid CSRF token.');
}

function http_response(int $http_code, string $body, array $http_headers = []): array
{
    return [
        'status'  => $http_code,
        'headers' => $http_headers,
        'body'    => $body,
    ];
}

function http_respond(int $status, string $body, array $headers = []): void
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
