<?php

const IO_PATH = 0;
const IO_ARGS = 1;
const IO_INC = 2;
const IO_OUT = 4;

// check if the request is a valid beyond webserver .conf
function http_guard($max_length = 4096, $max_decode = 9): string
{
    // CSRF check
    if (!empty($_POST) && function_exists('csrf_validate') && !csrf_validate())
        http(403, 'Invalid CSRF token.', ['Content-Type' => 'text/plain; charset=UTF-8']);

    $coded = $_SERVER['REQUEST_URI'] ?? '';
    do {
        $path = rawurldecode($coded);
    } while ($max_decode-- > 0 && $path !== $coded && ($coded = $path));

    $max_decode                     ?: throw new DomainException('Path decoding loop detected', 400);
    (strlen($path) > $max_length)   && throw new DomainException('Path Exceeds Maximum Allowed', 400);

    return parse_url($path ?? '', PHP_URL_PATH) ?? '';
}

function io_guard(string $guarded_path, string $rx_remove = '#[^A-Za-z0-9\/\.\-\_]+#'): string
{
    $path = $rx_remove ? preg_replace($rx_remove, '', $guarded_path) : $guarded_path;       // removes non alphanum /.-_
    $path = preg_replace('#\.\.+#', '', $path);                             // remove serial dots
    $path = preg_replace('#(?:\./|/\.|/\./)#', '/', $path);                 // replace(/): /. ./ /./
    $path = preg_replace('#\/\/+#', '/', $path);                            // replace(/): //+, 
    $path = trim($path, '/');                                               // trim leading and trailing slashes

    return $path;
}

function ob_inc_out(string $file): array
{
    ob_start();
    return [@include($file), ob_get_clean()];
}

function ob_capture(string $file, ...$args)
{
    [$i, $o] = ob_inc_out($file);
    // vd($file, 0);
    // vd($i, 'Include Result');
    // vd($o, 'Output Buffer');
    return is_callable($i) ? $i($o, ...$args) ?? $o : $o;
}

function http(int $status, string $body, array $headers = []): void
{
    http_response_code($status);
    foreach ($headers as $h => $v) header("$h: $v");
    echo $body;
    exit;
}

function in(string $start, string $uri, string $default = 'index')
{
    $segments = explode('/', trim(parse_url($uri, PHP_URL_PATH), '/'));

    for ($i = count($segments); $i >= 0; --$i) {
        $path     = implode('/', array_slice($segments, 0, $i)) ?: $default;
        $basename = basename($path);
        $args     = array_slice($segments, $i);

        $files = [$path . '.php'];
        if ($i > 0)
            $files[] = $path . DIRECTORY_SEPARATOR . $basename . '.php';

        foreach ($files as $suffix) {
            $file = $start . DIRECTORY_SEPARATOR . $suffix;
            if (($yield = ob_inc_out($file)) && $yield[0] !== false)
                return [$suffix, $args, $yield[0] ?: null, $yield[1]?: null];
        }
    }

    http(404, '404 Not Found');
}