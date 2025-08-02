<?php

const IO_RETURN = -1;  // Quest result: included file return value
const IO_BUFFER = -2;  // Quest result: output buffer content

const IO_DEEP = 1;   // Deep-first route lookup
const IO_ROOT = 2;   // Root-first route lookup
const IO_FLEX = 4;   // Flexible routing: try file + file/file patterns

const IO_INVOKE = 8; // Quest behavior: call return value with args
const IO_ABSORB = 16; // Quest behavior: call return value with buffer+args


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

function io_map(string $base, string $guarded_uri, string $ext = 'php', int $behave = 0): array
{
    $guarded_uri = trim($guarded_uri, '/');

    if ($behave & (IO_DEEP | IO_ROOT)) 
        return io_find($base, $guarded_uri, $ext, $behave);

    // mirroring mode: REQUEST_URI is filesystem path
    $path = io_path($base, $guarded_uri, $ext, $behave);
    return $path ? [$path] : [];
}

function io_run(string $io_path, array $io_args, $behave = 0): array
{
    [$return, $buffer] = ob_ret_get($io_path, $io_args);
    $loot = [IO_RETURN => $return, IO_BUFFER => $buffer];

    if (($behave & (IO_INVOKE | IO_ABSORB)) && is_callable($return)) {
        ($behave & IO_INVOKE) && ($loot[IO_INVOKE] = $return($io_args));
        ($behave & IO_ABSORB) && ($loot[IO_ABSORB] = $return($loot[IO_BUFFER], $io_args));
    }

    return $loot;
}

// params: no trailing / for base, no trailing / for candidate, no . for extension
// return: ? full path to an existing file
function io_path(string $base, string $candidate, string $ext, int $behave = 0): ?string
{
    $path = $base . DIRECTORY_SEPARATOR . $candidate;
    if(is_file($res = $path . '.' . $ext))
        return $res;

    if (($behave & IO_FLEX) && is_file($res = $path . DIRECTORY_SEPARATOR . basename($candidate) . '.' . $ext))
        return $res;

    return null;
}

function io_find(string $base, string $guarded_uri, string $ext, int $behave = 0): array
{
    $slashes_positions = [];
    $slashes = 0;
    for ($pos = -1; ($pos = strpos($guarded_uri, '/', $pos+1)) !== false; ++$slashes)
        $slashes_positions[] = $pos;

    $segments = $slashes + 1;

    $depth  = $behave & IO_ROOT ? 1 : $segments;
    $end    = $behave & IO_ROOT ? $segments + 1 : 0; // +1 ? off-by-one workaround for !==

    for($step = $behave & IO_ROOT ? 1 : -1; $depth !== $end; $depth += $step) {
        $candidate = $depth <= $slashes
            ? substr($guarded_uri, 0, $slashes_positions[$depth - 1])
            : $guarded_uri;

        if ($path = io_path($base, $candidate, $ext, $behave)) {
            $args = $depth > $slashes ? [] : explode('/', substr($guarded_uri, $slashes_positions[$depth - 1] + 1));
            return [$path, $args];
        }
    }
    return [];
}

function ob_ret_get($path, array $include_vars = []): array
{
    foreach ($include_vars as $k => $v) $$k = $v;
    ob_start();
    return $path ? [@include($path), ob_get_clean()] : [];
}
