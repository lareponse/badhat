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

function io_route(string $start, string $guarded_uri, string $default): array
{
    // vd(1, __FUNCTION__, func_get_args());
    $start = realpath($start) ?: throw new DomainException("Invalid start path: $start", 400);

    // $segments = explode('/', trim($guarded_uri, '/'));
    $uri = trim($guarded_uri, '/');
    do{
        $path = $uri !== '' ? $uri : $default;
        $files = [
            $path . '.php',
            $path . DIRECTORY_SEPARATOR . basename($path) . '.php'
        ];
        foreach ($files as $relative) {
            $file =  realpath($start . DIRECTORY_SEPARATOR . $relative);
            if ($file && strpos($file, $start) === 0) {
                $args = explode('/', substr($uri, strlen($path) + 1));
                return [IO_PATH => $file, IO_ARGS => $args];
            }
        }
        $uri = substr($uri, 0, (int)strrpos($uri, '/'));
    } while($uri);
    return [];
}

function io(string $file, $io = null): array
{
    ob_start();
    return [@include($file), ob_get_clean()];
}

function io_invoke(string $file, $args=null)
{
    [$i, $o] = io($file, $args);
    return is_callable($i) ? [$i($args), $o] : [$i, $o];
}

function io_absorb(string $file, $args=null)
{
    [$i, $o] = io($file, $args);
    return is_callable($i) ? $i($o, $args) ?? $o : $o;
}

function http(int $status, string $body, array $headers = []): void
{
    http_response_code($status);
    foreach ($headers as $h => $v) header("$h: $v");
    echo $body;
    exit;
}
