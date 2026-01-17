<?php

namespace bad\io;

const IO_NEST  = 1;                                                                                                     // enables nested path/filename/filename pattern fallback in look()
const IO_GROW  = 2;                                                                                                     // forward scan in seek() instead of reverse (default shrinks from end)

function hook(string $base, string $url, string $forbidden = '', int $behave = 0): string
{                                                                                                                       // receive a request: extract and validate the path to navigate
    (!$base || $base[-1] !== DIRECTORY_SEPARATOR)                   && throw new \InvalidArgumentException('base has no trailing separator to prevent directory name extension exploit', 400);
    $base && ($base !== realpath($base) . DIRECTORY_SEPARATOR)      && throw new \InvalidArgumentException('base is not real, symlinks and relative paths would bypass boundary checks', 400);

    $stop = strcspn($url, '?#');                                                                                        // query/fragment may contain slashes that aren't path separators
    isset($url[$stop]) && ($url = substr($url, 0, $stop));                                                              // must strip before path operations to avoid misinterpretation

    ($forbidden !== '' && isset($url[strcspn($url, $forbidden)]))   && throw new \InvalidArgumentException('request has explicitly forbidden chars that could exploit file system APIs', 400);

    return $url;                                                                                                        // returns clean path ready for navigation
}

function look(string $base, string $path, string $shim = '', int $behave = 0): ?string
{                                                                                                                       // direct lookup: does a file exist at this exact location?
    $leaf = $base . $path . $shim;                                                                                      // construct the primary candidate path
    if ((IO_NEST & $behave) && !is_file($leaf))                                                                         // if nesting allowed and primary doesn't exist
        $leaf = $base . $path . DIRECTORY_SEPARATOR . basename($path) . $shim;                                          // try nested: path/filename/filename pattern

    return is_file($leaf) && ($real = realpath($leaf)) && strpos($real, $base) === 0 ? $real : null;                    // verify exists, resolve canonical path, ensure within base boundary
}                                                                                                                       // returns resolved path if valid file within base, null otherwise

function seek(string $base, string $path, string $shim = '', int $behave = 0): ?array
{                                                                                                                       // progressive search: test path segments to find executable file
    $len = strlen($path);
    $pos  = (IO_GROW & $behave) ? 0 : $len;                                                                             // begin at start for forward scan, at end for reverse

    do {
        $cut = (IO_GROW & $behave)                                                                                      // locate the next path separator
             ? strpos($path, '/', $pos)                                                                                 // scanning forward from current position
             : (($pos > 0) ? strrpos($path, '/', $pos - $len - 1) : false);                                             // scanning backward before current position

        $end = (IO_GROW & $behave)                                                                                      // determine where this segment ends
             ? (($cut === false) ? $len : $cut)                                                                         // forward: at separator or path end
             : $pos;                                                                                                    // reverse: at current position

        $test = ($end === $len) ? $path : substr($path, 0, $end);                                                       // isolate the segment to test for existence
        if ($file = look($base, $test, $shim, $behave)) {                                                               // check if this segment resolves to a file
            $args = ($end + 1 < $len) ? explode('/', substr($path, $end + 1)) : [];                                     // collect remaining path parts as arguments
            return [$file, $args];                                                                                      // found: return the file with its arguments
        }

        ($cut !== false) && ($pos = (IO_GROW & $behave) ? $cut + 1 : $cut);                                             // move to next segment if separator was found
    } while ($cut !== false);                                                                                           // keep searching while separators remain

    return null;                                                                                                        // no file found along this path, returns null
}