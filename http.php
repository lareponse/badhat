<?php

function http_no(string $raw): string
{
    $path = parse_url($raw, PHP_URL_PATH) ?: '';
    $path = rawurldecode($path);
    
    strpos($path, "\0") === false || throw new RuntimeException('Bad Request', 400);  // Reject null byte explicitly

    while (strpos($path, '//') !== false)
        $path = str_replace('//', '/', $path);                      // Normalize slashes

    return $path;
}

// Normalize an inbound HTTP path and extract its representation suffix.
function http_in(string $safe_path, string $accept = 'html', string $default = 'index'): array
{
    while (strpos($safe_path, '//') !== false)
        $safe_path = str_replace('//', '/', $safe_path);

    $uri_path = trim($safe_path, '/');

    $last_dot = strrpos($uri_path, '.');
    $last_slash = strrpos($uri_path, '/');
    if ($last_dot !== false && $last_dot > ($last_slash === false ? 0 : $last_slash + 1)) {
        return [
            substr($uri_path, 0, $last_dot),
            substr($uri_path, $last_dot + 1)
        ];
    }

    return [$uri_path ?: $default, $accept];
}

// http response, side effect: exits
function http_out(int $status, string $body, array $headers = []): void
{
    http_response_code($status);
    foreach ($headers as $h => $v){
        (strpbrk($h.$v, "\r\n") === false) || throw new RuntimeException('Invalid Header', 500);
        header("$h: $v");
    } 
    echo $body;
    exit;
}
