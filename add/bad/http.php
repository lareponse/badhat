<?php


// check if the request is a valid beyond webserver .conf
function http_guard($max_path_length = 4096, $max_url_decode = 9)
{
    // CSRF check
    if (!empty($_POST) && function_exists('csrf') && !csrf($_POST['csrf_token'] ?? ''))
        http_respond(403, 'Invalid CSRF token.', ['Content-Type' => 'text/plain; charset=UTF-8']);
}

// no http_request(), we are in the request, just ask $_SERVER, $_POST, $_GET, $_COOKIE, they know

function http_respond(int $status, string $body, array $headers = []): void
{
    http_response_code($status);

    foreach ($headers as $h => $v)
        header("$h: $v");

    echo $body;
    exit;
}
