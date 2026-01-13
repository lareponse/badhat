<?php

namespace bad\io;

const IO_NEST  = 1;
const IO_GROW  = 2;

function path(string $base, string $url, string $forbidden = '', int $behave = 0): string
{
    (!$base || $base[-1] !== DIRECTORY_SEPARATOR)                   && throw new \InvalidArgumentException('BASE_NO_SLASHEND', 400); // trailing separator prevents /var/www matching /var/www-evil
    $base && ($base !== realpath($base) . DIRECTORY_SEPARATOR)      && throw new \InvalidArgumentException('BASE_IS_NOT_REAL', 400);

    $stop = strcspn($url, '?#');    // PHP REQUEST_URI
    isset($url[$stop]) && ($url = substr($url, 0, $stop));
    
    ($forbidden !== '' && isset($url[strcspn($url, $forbidden)]))   && throw new \InvalidArgumentException('PATH_IS_CORRUPTED', 400);

    return $url;
}

// real base + rootless path + shim = a real in-base file (or null)
function look(string $base, string $path, string $shim = '', int $behave = 0): ?string
{
    $leaf = $base . $path . $shim;
    if ((IO_NEST & $behave) && !is_file($leaf))     // test !is_file or NEST becomes a preference instead of fallback
        $leaf = $base . $path . DIRECTORY_SEPARATOR . basename($path) . $shim;

    return is_file($leaf) && ($real = realpath($leaf)) && strpos($real, $base) === 0 ? $real : null;
}

function seek(string $base, string $path, string $shim = '', int $behave = 0): ?array
{
    $len = strlen($path);
    $pos  = (IO_GROW & $behave) ? 0 : $len;

    do {
        if ((IO_GROW & $behave)) {
            $cut = strpos($path, '/', $pos);
            $end = ($cut === false) ? $len : $cut;
        } else {
            $cut = ($pos > 0) ? strrpos($path, '/', $pos - $len - 1) : false; // negative offset
            $end = $pos;
        }

        $test = ($end === $len) ? $path : substr($path, 0, $end);
        if ($file = look($base, $test, $shim, $behave)) {
            $args = ($end + 1 < $len) ? explode('/', substr($path, $end + 1)) : [];
            return [$file, $args];
        }

        ($cut !== false) && ($pos = (IO_GROW & $behave) ? $cut + 1 : $cut);
    } while ($cut !== false);

    return null;
}
