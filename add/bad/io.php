<?php

const IO_PATH = 1;
const IO_ARGS = 2;

const IO_RETURN = 4;
const IO_OB_GET = 8;
const IO_INVOKE = 16;
const IO_ABSORB = 32;

// check if the request is a valid beyond webserver .conf
function http_in($max_length = 4096, $max_decode = 9): string
{
    // CSRF check
    if (!empty($_POST) && function_exists('csrf_validate') && !csrf_validate())
        http_out(403, 'Invalid CSRF token.', ['Content-Type' => 'text/plain; charset=UTF-8']);

    $coded = $_SERVER['REQUEST_URI'] ?? '';
    do {
        $path = rawurldecode($coded);
    } while ($max_decode-- > 0 && $path !== $coded && ($coded = $path));
    
    $max_decode                    ?: throw new DomainException('Path decoding loop detected', 400);
    strpos($path, '://') === false || throw new DomainException('Stream wrappers not allowed', 400);
    (strlen($path) > $max_length)  && throw new DomainException('Path exceeds allowed length', 400);
    
    $path = preg_replace('#\/\/+#', '/', $path);
    return parse_url($path ?: '', PHP_URL_PATH) ?? '';
}

function http_out(int $status, string $body, array $headers = []): void
{
    http_response_code($status);
    foreach ($headers as $h => $v) header("$h: $v");
    echo $body;
    exit;
}
// requires glibc AFTER version 2.26
function io_route(string $start, string $guarded_uri, string $default): array
{
    $start = realpath($start) ?: throw new DomainException("Invalid start path: $start", 400);
    
    $baseSlash = rtrim($start, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $safe_path = function (...$chunks) use ($baseSlash): ?string {
        $file = implode(DIRECTORY_SEPARATOR, $chunks) . '.php';
        return ($real = realpath($file)) && strncmp($real, $baseSlash, strlen($baseSlash)) === 0
        ? $real
        : null;
    };
    
    $segments = explode('/', trim($guarded_uri, '/'));

    for ($depth = count($segments); $depth >= 0; --$depth) {
        if ($depth) {
            $relative = implode(DIRECTORY_SEPARATOR, array_slice($segments, 0, $depth));
            $path = $safe_path($start, $relative) ?: $safe_path($start, $relative, basename($relative));
        } 
        else $path = $safe_path($start, $default); // depth=0 fallback
        
        if ($path)
            return [IO_PATH => $path, IO_ARGS => array_slice($segments, $depth)];
    }
    return [];
}

function io_quest($io_route = [], $include_vars = [], $behave = 0): array
{
    [$return, $buffer] = ob_ret_get($io_route[IO_PATH], $include_vars);
    $quest = $io_route + [IO_RETURN => $return, IO_OB_GET => $buffer];

    if ($behave & (IO_INVOKE | IO_ABSORB) && is_callable($return)) {

        $behave & IO_INVOKE && ($quest[IO_INVOKE] = $return($quest[IO_ARGS]));
        $behave & IO_ABSORB && ($quest[IO_ABSORB] = $return($quest[IO_OB_GET], $quest[IO_ARGS]));
    }

    return $quest;
}

function ob_ret_get($path, $include_vars = []): array
{
    ob_start() && $include_vars && extract($include_vars);
    return $path ? [@include($path), ob_get_clean()] : [];
}