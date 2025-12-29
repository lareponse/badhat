<?php

const IO_OUTPUT = -2;                           // stores include output buffer
const IO_RETURN = -1;                           // stores include return value

const IO_BUFFER = 1;                            // activate buffering

const IO_INVOKE = 2;                            // Call fn(args) and store return in IO_RETURN (if callable)
const IO_ABSORB = 4 | IO_BUFFER | IO_INVOKE;    // Call fn(args + output buffer) and store return value IO_RETURN

const IO_NEST   = 8;                            // Flexible routing: try file + file/file
const IO_DEEP   = 16;                           // Deep-first seek
const IO_ROOT   = 32;                           // Root-first seek

const IO_CHAIN  = 64;                           // Chain loot results as args for next included file

const USERLAND_ERROR = 0xBAD;                   // 2989


function io_in(string $raw): string
{
    $path = parse_url($raw, PHP_URL_PATH) ?: '';
    $path = rawurldecode($path);

    strpos($path, "\0") === false || throw new InvalidArgumentException('Bad Request', 400);    // Reject null byte explicitly

    return trim($path, '/');   // eats all trailing slashes
}

function io_map(string $base_dir, string $uri_path, string $execution_suffix, int $behave = 0): ?array
{ // resolves an execution path
    if ((IO_DEEP | IO_ROOT) & $behave)
        return io_seek($base_dir, $uri_path, $execution_suffix, $behave);

    return ($look_up = io_look($base_dir, $uri_path, $execution_suffix, $behave)) !== null
        ? [$look_up, null] 
        : null;
} // an array: [string path, ?array args]

function io_run(array $file_paths, array $io_args, int $behave = 0): array
{ // executes one or more resolved execution paths and returns last execution result
    $loot = $io_args;

    foreach ($file_paths as $file_path) {
        $args = (IO_CHAIN & $behave) ? $loot : $io_args;

        (IO_BUFFER & $behave) && ob_start();
        try {
            $loot[IO_RETURN] = include $file_path;
        } catch (Throwable $t) {
            (IO_BUFFER & $behave) && ob_end_clean();
            throw new RuntimeException("include:$file_path", USERLAND_ERROR, $t);
        }

        $include_output = (IO_BUFFER & $behave) ? ob_get_contents() : null;

        if ((IO_INVOKE & $behave) && is_callable($loot[IO_RETURN])) {
            (IO_ABSORB & $behave) === IO_ABSORB && ($args[] = $include_output);

            try {
                $loot[IO_RETURN] = $loot[IO_RETURN]($args);
            } catch (Throwable $t) {
                (IO_BUFFER & $behave) && ob_end_clean();
                throw new RuntimeException("invoke:$file_path", USERLAND_ERROR, $t);
            }
        }
        (IO_BUFFER & $behave) && ($loot[IO_OUTPUT] = ob_get_clean());
    }

    return $loot;
}

function io_die(int $status, string $body, array $headers): void
{
    http_response_code($status);
    foreach ($headers as $h => $v)
        header("$h: $v"); // header() already detects CR/LF injection, drop them and logs a warning
    echo $body;
    exit;
}

function io_look(string $base_dir, string $candidate, string $execution_suffix, int $behave = 0): ?string
{
    (!$base_dir || $base_dir[-1] !== DIRECTORY_SEPARATOR) && throw new InvalidArgumentException('base_dir must end with directory separator '. DIRECTORY_SEPARATOR); // for valid strpos security check 
    
    $path = $base_dir . $candidate;
    $file = $path . $execution_suffix;
    
    if (!is_file($file) && (IO_NEST & $behave)) {
        $file = $path . DIRECTORY_SEPARATOR . basename($candidate) . $execution_suffix;
        is_file($file) || ($file = null);
    }

    return $file !== null && ($real = realpath($file)) && strpos($real, $base_dir) === 0 
        ? $real 
        : null;
} // returns a real, in-base direct execution path, or null

function io_seek(string $base_dir, string $uri_path, string $execution_suffix, int $behave = 0): ?array
{ // resolves an execution path by segment walk (and remaining segments), or null
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

        if ($path = io_look($base_dir, $candidate, $execution_suffix, $behave)) {
            $args = $depth > $slashes ? [] : explode('/', substr($uri_path, $slashes_positions[$depth - 1] + 1));
            return [$path, $args];
        }
    }
    return null;
}
