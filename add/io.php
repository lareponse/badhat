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
    strpos($path, '://') === false || throw new DomainException('Stream wrappers not allowed', 400);
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

function io_route(string $start, string $guarded_uri, string $default, int $behave = 0): array
{
    // Common setup
    $base = realpath($start) ?: throw new DomainException("Invalid start path: $start", 400);
    $base_len = strlen($base); // micro-optimization for realpath checks

    $segments = $guarded_uri === '' ? [] : explode('/', trim($guarded_uri, '/'));

    // Delegate based on behavior
    if ($behave & (IO_DEEP | IO_ROOT)) {
        $count = count($segments);
        $step   = $behave & IO_ROOT ? 1 : -1;
        $depth  = $behave & IO_ROOT ? 0 : $count;
        $end    = $behave & IO_ROOT ? $count + 1 : -1;

        while ($depth !== $end) {
            $path_segments = $depth > 0 ? array_slice($segments, 0, $depth) : [$default];

            $path = safe_path($base, $path_segments, $base_len, $behave);
            if ($path)
                return [IO_PATH => $path, IO_ARGS => array_slice($segments, $depth)];
            $depth += $step;
        }

        return [];

    }

    // mirroring mode REQUEST_URI is filesystem path
    $path = safe_path($base, $segments ?: [$default], $base_len, $behave);
    return $path ? [IO_PATH => $path, IO_ARGS => []] : [];
}

function safe_path(string $base, array $chunks, int $base_len, int $behave = 0): ?string
{
    $real = realpath($base . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $chunks) . '.php');
    if($real && strncmp($real, $base, $base_len) === 0)
        return $real;

    return ($behave & IO_FLEX) ? safe_path($base, array_merge($chunks, [end($chunks)]), $base_len, 0) : null;
}

function io_quest($io_route = [], $include_vars = [], $behave = 0): array
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

function ob_ret_get($path, $include_vars = []): array
{
    ob_start() && $include_vars && extract($include_vars);
    return $path ? [@include($path), ob_get_clean()] : [];
}
