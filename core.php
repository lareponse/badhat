<?php

const IO_PATH = 1;    // Route result: resolved file path
const IO_ARGS = 2;    // Route result: remaining path segments

const IO_RETURN = 4;  // Quest result: included file return value
const IO_OB_GET = 8;  // Quest result: output buffer content

const IO_INVOKE = 16; // Quest behavior: call return value with args
const IO_ABSORB = 32; // Quest behavior: call return value with buffer+args

const IO_DEEP = 128;   // Deep-first route lookup
const IO_ROOT = 256;   // Root-first route lookup
const IO_FLEX = 512;   // Flexible routing: try file + file/file patterns

// check if the request is a valid beyond webserver .conf
function http_in(int $max_length = 4096, int $max_decode = 9): string
{
    // CSRF check
    !empty($_POST) && function_exists('csrf_validate') && !csrf_validate() && http_out(403, 'Invalid CSRF token.', ['Content-Type' => 'text/plain; charset=utf-8']);

    $coded = $_SERVER['REQUEST_URI'] ?? '';
    do {
        $path = rawurldecode($coded);
    } while ($max_decode-- > 0 && $path !== $coded && ($coded = $path));

    $max_decode                    ?: throw new DomainException('Path decoding loop detected', 400);
    strlen($path) > $max_length    && throw new DomainException('Path exceeds allowed length', 400);

    return parse_url(preg_replace('#\/\/+#', '/', $path ?: ''), PHP_URL_PATH) ?? '';
}

function http_out(int $status, string $body, array $headers = []): void
{
    http_response_code($status);
    foreach ($headers as $h => $v) header("$h: $v");
    echo $body;
    exit;
}

function io_route(string $base, string $guarded_uri, int $behave = 0): array
{
    $guarded_uri = trim($guarded_uri, '/');

    if ($behave & (IO_DEEP | IO_ROOT)) 
        return io_find($base, $guarded_uri, $behave);

    // mirroring mode REQUEST_URI is filesystem path  
    $path = io_path($base, $guarded_uri, strlen($base), $behave);
    return $path ? [IO_PATH => $path, IO_ARGS => []] : [];
}

function io_fetch($io_route = [], $include_vars = [], $behave = 0): array
{
    [$return, $buffer] = ob_ret_get($io_route[IO_PATH] ?? null, $include_vars);
    $quest = $io_route + [IO_RETURN => $return, IO_OB_GET => $buffer];

    if (($behave & (IO_INVOKE | IO_ABSORB)) && is_callable($return)) {
        $args = $quest[IO_ARGS] ?? [];
        ($behave & IO_INVOKE) && ($quest[IO_INVOKE] = $return($args));
        ($behave & IO_ABSORB) && ($quest[IO_ABSORB] = $return($quest[IO_OB_GET], $args));
    }

    return $quest;
}

function io_path(string $base, string $candidate, int $base_len, int $behave): ?string
{
    $attempted = false;
    do {
        $attempted && ($candidate = $candidate . DIRECTORY_SEPARATOR . basename($candidate));

        $real = realpath($base . DIRECTORY_SEPARATOR . $candidate . '.php');
        if ($real && strncmp($real, $base, $base_len) === 0)
            return $real;
    } while (($behave & IO_FLEX) && ($attempted = !$attempted));

    return null;
}

function io_find(string $base, string $guarded_uri, int $behave = 0)
{
    $count  = substr_count($guarded_uri, '/') + 1;
    $base_len = strlen($base);

    $step   = $behave & IO_ROOT ? 1 : -1;
    $depth  = $behave & IO_ROOT ? 1 : $count;
    $end    = $behave & IO_ROOT ? $count + 1 : 0;

    while ($depth !== $end) {
        $pos = -1;
        for ($i = 0; $i < $depth && $pos !== false; $i++)
            $pos = strpos($guarded_uri, '/', $pos + 1);

        $candidate = $pos ? substr($guarded_uri, 0, $pos) : $guarded_uri;
        if ($path = io_path($base, $candidate, $base_len, $behave)) {
            $args_start = $pos === false ? strlen($guarded_uri) : $pos + 1;
            $args = $args_start >= strlen($guarded_uri) ? [] : explode('/', substr($guarded_uri, $args_start));

            return [IO_PATH => $path, IO_ARGS => $args];
        }
        $depth += $step;
    }

    return [];
}

function ob_ret_get($path, array $include_vars = []): array
{
    foreach ($include_vars as $k => $v) $$k = $v;
    ob_start();
    return $path ? [@include($path), ob_get_clean()] : [];
}
