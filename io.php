<?php

namespace bad\io;

const IO_URL   = 1;
const IO_NEST  = 2;
const IO_HEAD  = 4;

function path(string $raw, string $forbidden = '', int $behave = 0): string
{
    $path = $raw;
    
    if (IO_URL & $behave) {
        $scheme_end = strcspn($path, ':/?#');  //strcspn ensures : appears before any /?#
        if (isset($path[$scheme_end]) && $path[$scheme_end] === ':')
            $path = substr($path, $scheme_end + 1);

        if (isset($path[1]) && $path[0] === '/' && $path[1] === '/') {
            $auth_end = strcspn($path, '/?#', 2) + 2;
            $path = isset($path[$auth_end]) ? substr($path, $auth_end) : '';
        }
    }
    $stop = strcspn($path, '?#');
    isset($path[$stop]) && $path = substr($path, 0, $stop);

    ($forbidden !== '' && isset($path[strcspn($path, $forbidden)])) && throw new \InvalidArgumentException('Bad Request', 400);

    return ltrim($path, '/');
}

function look(string $base_dir, string $url_path, string $execution_suffix, int $behave = 0): ?string
{
    (!$base_dir || $base_dir[-1] !== DIRECTORY_SEPARATOR) && throw new \InvalidArgumentException('base_dir must end with directory separator '. DIRECTORY_SEPARATOR, 400); // trailing separator prevents /var/www matching /var/www-evil

    $path = $base_dir . $url_path;
    $file = $path . $execution_suffix;
    if (!is_file($file) && (IO_NEST & $behave)) {
        $file = $path . DIRECTORY_SEPARATOR . basename($url_path) . $execution_suffix;
        is_file($file) || ($file = null);
    }

    return $file !== null && ($real = realpath($file)) && strpos($real, $base_dir) === 0 ? $real : null;
} // returns a real, in-base direct execution path, or null

function seek(string $base_dir, string $url_path, string $execution_suffix, int $behave = 0): ?array
{ // resolves an execution path by segment walk
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

        if ($path = look($base_dir, $candidate, $execution_suffix, $behave)) {
            $args = $depth > $slashes ? [] : explode('/', substr($url_path, $slashes_positions[$depth - 1] + 1));
            return [$path, $args];
        }
    }
    return null;
}