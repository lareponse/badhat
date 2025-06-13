<?php

const IO_PATH = 0;
const IO_ARGS = 1;
const IO_YIELD = 2;

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
    strpos($guarded_path, '://') === false || throw new DomainException("Stream wrappers not allowed", 400);

    $path = $rx_remove ? preg_replace($rx_remove, '', $guarded_path) : $guarded_path;       // removes non alphanum /.-_
    // strip any double dots, collapse single dot segments and multiple slashes
    $path = preg_replace(
        ['#\.\.+#',     '#(?:\./|/\./|/\.)#',   '#\/\/+#'],
        ['',            '/',                    '/'],
        $path
    );

    return $path;
}

function io_start(string $start, string $guarded_uri, string $default, $payload = null): array
{
    $start = realpath($start) ?: throw new DomainException("Invalid start path: $start", 400);
    $segments = explode('/', trim(parse_url($guarded_uri, PHP_URL_PATH), '/'));
    for ($i = count($segments); $i >= 0; --$i) {

        $path  = implode('/', array_slice($segments, 0, $i)) ?: $default;
        $files = [
            $path . '.php',
            $path . DIRECTORY_SEPARATOR . basename($path) . '.php'
        ];
        foreach ($files as $relative) {
            $file =  realpath($start . DIRECTORY_SEPARATOR . $relative);
            if ($file && strpos($file, $start) === 0) {
                $args = array_slice($segments, $i);
                $yield = io_invoke($file, ...$args, ...($payload ?? []));
                return [IO_PATH => $file, IO_ARGS => $args, IO_YIELD => $yield];
            }
        }
    }

    return [];
}

function http(int $status, string $body, array $headers = []): void
{
    http_response_code($status);
    foreach ($headers as $h => $v) header("$h: $v");
    echo $body;
    exit;
}

function io(string $file): array
{
    ob_start();
    return [@include($file), ob_get_clean()];
}

function io_invoke(string $file, ...$args)
{
    [$i, $o] = io($file);
    return is_callable($i) ? [$i(...$args), $o] : [$i, $o];
}

function io_absorb(string $file, ...$args)
{
    [$i, $o] = io($file);
    return is_callable($i) ? $i($o, ...$args) ?? $o : $o;
}
