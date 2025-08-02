<?php

const IO_PATH = -1;    // Route result: resolved file path
const IO_ARGS = -2;    // Route result: remaining path segments

const IO_RETURN = -3;  // Quest result: included file return value
const IO_BUFFER = -4;  // Quest result: output buffer content

const IO_INVOKE = 1; // Quest behavior: call return value with args
const IO_ABSORB = 2; // Quest behavior: call return value with buffer+args

const IO_DEEP = 4;   // Deep-first route lookup
const IO_ROOT = 8;   // Root-first route lookup
const IO_FLEX = 16;   // Flexible routing: try file + file/file patterns

// check if the request is a valid beyond webserver .conf
function http_in(int $max_decode = 9): string
{
    // CSRF check
    !empty($_POST) && function_exists('csrf_validate') && !csrf_validate() && http_out(403, 'Invalid CSRF token.', ['Content-Type' => 'text/plain; charset=utf-8']);

    $coded = $_SERVER['REQUEST_URI'] ?? '';
    do {
        $path = rawurldecode($coded);
    } while ($max_decode-- > 0 && $path !== $coded && ($coded = $path));

    $max_decode                    ?: throw new DomainException('Path decoding loop detected', 400);

    return parse_url(preg_replace('#\/\/+#', '/', $path ?: ''), PHP_URL_PATH) ?? '';
}

function http_out(int $status, string $body, array $headers = []): void
{
    http_response_code($status);
    foreach ($headers as $h => $v) header("$h: $v");
    echo $body;
    exit;
}

function io_route(string $base, string $guarded_uri, string $ext = 'php', int $behave = 0): array
{
    $guarded_uri = trim($guarded_uri, '/');

    if ($behave & (IO_DEEP | IO_ROOT)) 
        return io_find($base, $guarded_uri, $ext, $behave);

    // mirroring mode REQUEST_URI is filesystem path  
    $path = io_path($base, $guarded_uri, $ext, $behave);
    return $path ? [IO_PATH => $path, IO_ARGS => []] : [];
}

function io_fetch($io_route = [], $include_vars = [], $behave = 0): array
{
    [$return, $buffer] = ob_ret_get($io_route[IO_PATH] ?? null, $include_vars ?? []);
    $quest = $io_route + [IO_RETURN => $return, IO_BUFFER => $buffer];

    if (($behave & (IO_INVOKE | IO_ABSORB)) && is_callable($return)) {
        $args = $quest[IO_ARGS] ?? [];
        ($behave & IO_INVOKE) && ($quest[IO_INVOKE] = $return($args));
        ($behave & IO_ABSORB) && ($quest[IO_ABSORB] = $return($quest[IO_BUFFER], $args));
    }

    return $quest;
}

// params: no trailing / for base, no trailing / for candidate, no . for extension
// return: ? full path to an existing file
function io_path(string $base, string $candidate, string $ext, int $behave = 0): ?string
{
    $path = $base . DIRECTORY_SEPARATOR . $candidate;
    if(is_file(vd($res = $path . '.' . $ext))){

    }
        return $res;

    if (($behave & IO_FLEX) && is_file($res = $path . DIRECTORY_SEPARATOR . basename($candidate) . '.' . $ext))
        return $res;

    return null;
}

function io_find(string $base, string $guarded_uri, string $ext, int $behave = 0): array
{
    $count  = substr_count($guarded_uri, '/') + 1;
    $step   = $behave & IO_ROOT ? 1 : -1;
    $depth  = $behave & IO_ROOT ? 1 : $count;
    $end    = $behave & IO_ROOT ? $count + 1 : 0;

    while ($depth !== $end) {
        $pos = -1;
        for ($i = 0; $i < $depth && $pos !== false; ++$i)
            $pos = strpos($guarded_uri, '/', $pos + 1);

        $candidate = $pos ? substr($guarded_uri, 0, $pos) : $guarded_uri;
        
        if ($path = io_path($base, $candidate, $ext, $behave)) {
            
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
