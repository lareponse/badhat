<?php
const IO_DEEP = 1;      // Deep-first seek
const IO_ROOT = 2;      // Root-first seek
const IO_NEST = 4;      // Flexible routing: try file + file/file patterns

const IO_RETURN = 16;   // Value of the included file return statement
const IO_BUFFER = 32;   // Value of the included file output buffer
const IO_INVOKE = 64;   // Call fn(args) and store return value
const IO_ABSORB = 128;  // Call fn(buffer, args) and store return value

const IO_EXTRACT = 256; // Extract args to local scope for included file

// return: clean URI path string
function http_in(int $max_decode = 9): string
{
    // CSRF check
    !empty($_POST) && function_exists('csrf_validate') && !csrf_validate() && http_out(403, 'Invalid CSRF token.', ['Content-Type' => 'text/plain; charset=utf-8']);

    $coded = $_SERVER['REQUEST_URI'] ?? '';
    do {
        $path = rawurldecode($coded);
    } while ($max_decode-- > 0 && $path !== $coded && ($coded = $path));

    $max_decode                    ?: throw new BadMethodCallException('Path decoding loop detected', 400);

    return parse_url(preg_replace('#\/\/+#', '/', $path ?: ''), PHP_URL_PATH) ?? '';
}

// http response, side effect: exits
function http_out(int $status, string $body, array $headers = []): void
{
    http_response_code($status);
    foreach ($headers as $h => $v) header("$h: $v");
    echo $body;
    exit;
}

// return: array with filepath or filepath+args or empty
function io_map(string $base_dir, string $uri_path, string $file_ext = 'php', int $behave = 0): array
{
    $uri_path = trim($uri_path, '/');

    if ($behave & (IO_DEEP | IO_ROOT))
        return io_seek($base_dir, $uri_path, $file_ext, $behave);

    // mirroring mode: REQUEST_URI is filesystem path
    $path = io_look($base_dir, $uri_path, $file_ext, $behave);
    return $path ? [$path] : [];
}

// return: loot array with IO_RETURN and IO_BUFFER/IO_INVOKE/IO_ABSORB keys
function io_run(string $file_path, array $io_args, int $behave = 0): array
{
    [$return, $buffer] = ob_ret_get($file_path, $io_args, $behave);

    $loot = [IO_RETURN => $return, IO_BUFFER => $buffer];

    $behave & IO_INVOKE && is_callable($return) && ($loot[IO_INVOKE] = $return($io_args));
    $behave & IO_ABSORB && is_callable($return) && ($loot[IO_ABSORB] = $return($buffer, $io_args));

    return $loot;
}

// no trailing / for $base or $candidate
// no . for $extension
// return: ? full path to an -existing- file
function io_look(string $base_dir, string $candidate, string $file_ext, int $behave = 0): ?string
{
    $path = $base_dir . DIRECTORY_SEPARATOR . $candidate;

    return is_file($base_path = $path . '.' . $file_ext)
        ? $base_path
        : ($behave & IO_NEST && is_file($base_path = $path . DIRECTORY_SEPARATOR . basename($candidate) . '.' . $file_ext)
            ? $base_path
            : null);
}

// return: array with filepath+args or empty
function io_seek(string $base_dir, string $uri_path, string $file_ext, int $behave = 0): array
{
    $slashes_positions = [];
    $slashes = 0;
    for ($pos = -1; ($pos = strpos($uri_path, '/', $pos + 1)) !== false; ++$slashes)
        $slashes_positions[] = $pos;

    $segments = $slashes + 1;

    $depth  = $behave & IO_ROOT ? 1 : $segments;
    $end    = $behave & IO_ROOT ? $segments + 1 : 0; // +1 ? off-by-one workaround for !==

    for ($step = $behave & IO_ROOT ? 1 : -1; $depth !== $end; $depth += $step) {
        $candidate = $depth <= $slashes
            ? substr($uri_path, 0, $slashes_positions[$depth - 1])
            : $uri_path;

        if ($path = io_look($base_dir, $candidate, $file_ext, $behave)) {
            $args = $depth > $slashes ? [] : explode('/', substr($uri_path, $slashes_positions[$depth - 1] + 1));
            return [$path, $args];
        }
    }
    return [];
}

// return: array with [include return value, output buffer] or empty
// include value is 1 if no return statement, false if include failed
function ob_ret_get($path, array $args = [], int $behave = 0): array
{
    if ($behave & IO_EXTRACT) foreach ($args as $k => $v) $$k = $v;
    ob_start();
    return $path ? [@include($path), ob_get_clean()] : [];
}
