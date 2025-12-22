<?php
const IO_RETURN = 0;
const IO_BUFFER = 1;

const IO_INVOKE = 2;                           // Call fn(args) and store return in IO_RETURN (if callable)
const IO_ABSORB = 4 | IO_BUFFER | IO_INVOKE;   // Call fn(args + output buffer) and store return value IO_RETURN

const IO_NEST   = 8;                            // Flexible routing: try file + file/file
const IO_DEEP   = 16;                            // Deep-first seek
const IO_ROOT   = 32;                            // Root-first seek

const IO_CHAIN  = 64;                           // Chain loot results as args for next included file

function io_in(string $raw, string $accept = 'html', string $default = 'index'): array
{
    $path = parse_url($raw, PHP_URL_PATH) ?: '';
    $path = rawurldecode($path);
    
    strpos($path, "\0") === false || throw new RuntimeException('Bad Request', 400);    // Reject null byte explicitly

    while (strpos($path, '//') !== false)
        $path = str_replace('//', '/', $path);                                          // Normalize slashes

    $path = trim($path, '/');

    if(($last_dot = strrpos($path, '.')) !== false)
        if ($last_dot > ((strrpos($path, '/') ?: -1) + 1))
            return [substr($path, 0, $last_dot), substr($path, $last_dot + 1)];

    return [$path ?: $default, $accept];
}

// IO resolution happens in three stages: direct lookup, nested lookup, then path-aware seeking
// resolves an execution path (and remaining segments), or null
function io_map(string $base_dir, string $uri_path, string $file_ext = 'php', int $behave = 0): ?array
{
    $path = io_look($base_dir, $uri_path, $file_ext, $behave);

    if(!$path && ((IO_DEEP | IO_ROOT) & $behave))
        return io_seek($base_dir, $uri_path, $file_ext, $behave);
    
    return $path ? [$path] : $path;
}

// executes one or more resolved execution paths and returns last execution result
// propagates arguments, captures output, and optionally invokes returned callables
function io_run(array $file_paths, array $io_args, int $behave = 0): array
{
    $loot = $io_args;

    foreach ($file_paths as $file_path) {
        $args = (IO_CHAIN & $behave) ? $loot : $io_args;

        (IO_BUFFER & $behave) && ob_start();
        $loot = [IO_RETURN => @include $file_path];
        (IO_BUFFER & $behave) && ($loot[IO_BUFFER] = ob_get_clean());

        if ((IO_INVOKE & $behave) && is_callable($loot[IO_RETURN])) {
            if ((IO_ABSORB & $behave) === IO_ABSORB) $args[] = $loot[IO_BUFFER];
            $loot[IO_RETURN] = $loot[IO_RETURN]($args);
        }
    }

    return $loot;
}

function io_die(int $status, string $body, array $headers = []): void
{
    http_response_code($status);
    foreach ($headers as $h => $v){
        strpbrk($h, "\r\n") === false && strpbrk($v, "\r\n") === false || throw new RuntimeException('Invalid Header', 500);
        header("$h: $v");
    } 
    echo $body;
    exit;
}

// returns a direct execution path, or null
function io_look(string $base_dir, string $candidate, string $file_ext, int $behave = 0): ?string
{
    // Construct the base path (without extension)
    $base = rtrim($base_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $base_path = $base . $candidate;
    $file = $base_path . '.' . $file_ext;
    $file = is_file($file) ? $file : null;
    if(!$file && (IO_NEST & $behave)){
        $file = $base_path . DIRECTORY_SEPARATOR . basename($candidate) . '.' . $file_ext;
        $file = is_file($file) ? $file : null;
    }
    return $file && ($real = realpath($file)) && strpos($real, $base) === 0 ? $real : null;
}

// resolves an execution path by segment walk (and remaining segments), or null
function io_seek(string $base_dir, string $uri_path, string $file_ext, int $behave = 0): ?array
{
    $slashes_positions = [];
    $slashes = 0;
    for ($pos = -1; ($pos = strpos($uri_path, '/', $pos + 1)) !== false; ++$slashes)
        $slashes_positions[] = $pos;

    $segments = $slashes + 1;

    $depth  = IO_ROOT & $behave ? 1 : $segments;
    $end    = IO_ROOT & $behave ? $segments + 1 : 0; // +1 ? off-by-one workaround for $depth !== $end

    for ($step = (IO_ROOT & $behave) ? 1 : -1; $depth !== $end; $depth += $step) {
        $candidate = $depth <= $slashes
            ? substr($uri_path, 0, $slashes_positions[$depth - 1])
            : $uri_path;

        if ($path = io_look($base_dir, $candidate, $file_ext, $behave)) {
            $args = $depth > $slashes ? [] : explode('/', substr($uri_path, $slashes_positions[$depth - 1] + 1));
            return [$path, $args];
        }
    }
    return null;
}