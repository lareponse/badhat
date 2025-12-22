<?php
const IO_NEST = 1;                              // Flexible routing: try file + file/file
const IO_DEEP = 2;                              // Deep-first seek
const IO_ROOT = 4;                              // Root-first seek

const IO_CHAIN  = 16;                           // Chain loot results as args for next included file

const IO_RETURN = 64;                           // Value of the included file return statement
const IO_BUFFER = 128;                          // Value of the included file output buffer
const IO_INVOKE = 256;                          // Call fn(args) and store return in IO_RETURN (if callable)
const IO_ABSORB = 512 | IO_BUFFER | IO_INVOKE;  // Call fn(args + output buffer) and store return value IO_RETURN

// executes one or more resolved execution paths and returns last execution result
// propagates arguments, captures output, and optionally invokes returned callables
function io_send(array $file_paths, array $io_args, int $behave = 0): array
{
    $loot = $io_args;

    foreach ($file_paths as $file_path) {
        $args = (IO_CHAIN & $behave) ? $loot : $io_args;

        (IO_BUFFER & $behave) && ob_start();
        $loot = [IO_RETURN => @include $file_path];
        (IO_BUFFER & $behave) && ($loot[IO_BUFFER] = ob_get_clean());

        if ((IO_INVOKE & $behave) && is_callable($loot[IO_RETURN])) {
            (IO_ABSORB & $behave) && ($args[IO_BUFFER] = $loot[IO_BUFFER]);
            $loot[IO_RETURN] = $loot[IO_RETURN]($args);
        }
    }

    return $loot;
}

// IO resolution happens in three stages: direct lookup, nested lookup, then path-aware seeking
// resolves an execution path (and remaining segments), or null
function io_bind(string $base_dir, string $uri_path, string $file_ext = 'php', int $behave = 0): ?array
{
    if ($path = io_look($base_dir, $uri_path, $file_ext, $behave))
        return [$path];                 // direct lookup succeeded  

    if ((IO_DEEP | IO_ROOT) & $behave)
        if ($path_and_args = io_seek($base_dir, $uri_path, $file_ext, $behave))
            return $path_and_args;          // seeking succeeded     

    return null;                            // resolution failed
}

// no trailing / for $base or $candidate
// no . for $extension
// resolves an execution path directly, or null
function io_look(string $base_dir, string $candidate, string $file_ext, int $behave = 0): ?string
{
    // Construct the base path (without extension)
    $path = $base_dir . DIRECTORY_SEPARATOR . $candidate;

    if (is_file($base_path = $path . '.' . $file_ext))
        return $base_path;

    if ((IO_NEST & $behave) && is_file($nested_path = $path . DIRECTORY_SEPARATOR . basename($candidate) . '.' . $file_ext))
        return $nested_path;

    return null;
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