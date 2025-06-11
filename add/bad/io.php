<?php
// check if the request is a valid beyond webserver .conf
function http_guard($max_length = 4096, $max_decode = 9): string
{
    // CSRF check
    if (!empty($_POST) && function_exists('csrf') && !csrf($_POST['csrf_token'] ?? ''))
        http(403, 'Invalid CSRF token.', ['Content-Type' => 'text/plain; charset=UTF-8']);

    $coded = $_SERVER['REQUEST_URI'] ?? '';
    do {
        $path = rawurldecode($coded);
    } while ($max_decode-- > 0 && $path !== $coded && ($coded = $path));

    $max_decode                     ?: throw new DomainException('Path decoding loop detected', 400);
    (strlen($path) > $max_length)   && throw new DomainException('Path Exceeds Maximum Allowed', 400);

    return parse_url($path ?? '', PHP_URL_PATH) ?? '';
}

function io_guard(string $guarded_path, string $default_url_path, string $rx_remove = '#[^A-Za-z0-9\/\.\-\_]+#'): string
{
    $path = $rx_remove ? preg_replace($rx_remove, '', $guarded_path) : $guarded_path;       // removes non alphanum /.-_
    $path = preg_replace('#\.\.+#', '', $path);                             // remove serial dots
    $path = preg_replace('#(?:\./|/\.|/\./)#', '/', $path);                 // replace(/): /. ./ /./
    $path = preg_replace('#\/\/+#', '/', $path);                            // replace(/): //+, 
    $path = trim($path, '/');                                               // trim leading and trailing slashes
    return $path ?: $default_url_path ?: throw new DomainException('Empty URL path and empty default', 400);
}

function io(string $file): array
{
    ob_start();
    return [@include($file), ob_get_clean()];
}

function http(int $status, string $body, array $headers = []): void
{
    http_response_code($status);

    foreach ($headers as $h => $v)
        header("$h: $v");
    echo $body;
    exit;
}
