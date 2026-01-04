<?php

namespace bad\io;

// io_in
const IO_PATH_ONLY  = 1;
const IO_ROOTLESS   = 2;
const IO_ABSOLUTE   = 4 | IO_ROOTLESS;


// io_map (io_look, io_seek)
const IO_NEST       = 8;
const IO_TAIL       = 16;
const IO_HEAD       = 32;

function io_in(string $raw, string $forbidden = '', int $behave = 0): string
{
    $path = $raw;

    if (IO_PATH_ONLY & $behave) {
        // strip authority (//host:port)
        if (str_starts_with($path, '//')) {
            $auth_end = strcspn($path, '/?#', 2) + 2;
            $path = isset($path[$auth_end]) ? substr($path, $auth_end) : '';
        }

        $stop = strcspn($path, "?#");
        if (isset($path[$stop]))
            $path = substr($path, 0, $stop);
    }

    ($forbidden !== '' && isset($path[strcspn($path, $forbidden)])) && throw new \InvalidArgumentException('Bad Request', 400);

    (IO_ABSOLUTE & $behave) && ($path = '/' . ltrim($path, '/'));
    (IO_ROOTLESS & $behave) && ($path = ltrim($path, '/'));

    return $path;
}

function io_map(string $base_dir, string $url_path, string $execution_suffix, int $behave = 0): ?array
{ // resolves an execution path
    if ((IO_TAIL | IO_HEAD) & $behave)
        return io_seek($base_dir, $url_path, $execution_suffix, $behave);

    return ($look_up = io_look($base_dir, $url_path, $execution_suffix, $behave)) !== null
        ? [$look_up, null]
        : null;
} // an array: [string path, ?array args]

function io_look(string $base_dir, string $url_path, string $execution_suffix, int $behave = 0): ?string
{
    (!$base_dir || $base_dir[-1] !== DIRECTORY_SEPARATOR) && throw new \InvalidArgumentException('base_dir must end with directory separator '. DIRECTORY_SEPARATOR); // for valid strpos security check

    $path = $base_dir . $url_path;
    $file = $path . $execution_suffix;
    if (!is_file($file) && (IO_NEST & $behave)) {
        $file = $path . DIRECTORY_SEPARATOR . basename($url_path) . $execution_suffix;
        is_file($file) || ($file = null);
    }

    return $file !== null && ($real = realpath($file)) && strpos($real, $base_dir) === 0
        ? $real
        : null;
} // returns a real, in-base direct execution path, or null

function io_seek(string $base_dir, string $url_path, string $execution_suffix, int $behave = 0): ?array
{ // resolves an execution path by segment walk (and remaining segments), or null
    $slashes_positions = [];
    $slashes = 0;
    for ($pos = -1; ($pos = strpos($url_path, '/', $pos + 1)) !== false; ++$slashes)
        $slashes_positions[] = $pos;

    $segments = $slashes + 1;

    $depth  = IO_HEAD & $behave ? 1 : $segments;
    $end    = IO_HEAD & $behave ? $segments + 1 : 0; // +1 ? off-by-one workaround for $depth !== $end

    for ($step = (IO_HEAD & $behave) ? 1 : -1; $depth !== $end; $depth += $step) {
        $candidate = $depth <= $slashes
            ? substr($url_path, 0, $slashes_positions[$depth - 1])
            : $url_path;

        if ($path = io_look($base_dir, $candidate, $execution_suffix, $behave)) {
            $args = $depth > $slashes ? [] : explode('/', substr($url_path, $slashes_positions[$depth - 1] + 1));
            return [$path, $args];
        }
    }
    return null;
}
