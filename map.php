<?php

namespace bad\map;

const REBASE = 1;                                                   // enables nested path/filename/filename pattern fallback in look()
const ASCEND = 2;                                                   // forward scan in seek() instead of reverse (default shrinks from end)
const E_THROW = 256;                                                //

// receive a request: extract and validate the path to navigate
function hook($url, $reject = '', int $behave = 0): string | \InvalidArgumentException
{
    $end = \strcspn($url, ':/?#');                                  // find first of ": / ? #"
    $had_scheme = isset($url[$end]) && $url[$end] === ':';          // ":" wins => "scheme:..."
    $had_scheme && ($url = \substr($url, $end + 1));                // drop "scheme:" => maybe "//authority/..."

    if ($had_scheme && \strpos($url, '//') === 0) {                 // only treat "//" as authority after scheme
        $end = \strcspn($url, '/?#', 2) + 2;                        // skip authority up to next "/?#"
        $url = isset($url[$end]) ? \substr($url, $end) : '';        // keep origin-form tail (or "" if only authority)
    }

    $end = \strcspn($url, '?#');                                    // query/fragment may contain slashes that aren't path separators
    isset($url[$end]) && ($url = \substr($url, 0, $end));           // must strip before path operations to avoid misinterpretation

    for ($i = 0, $n = \strlen($url); $i < $n; $i++) {              // MUST validate percent-escapes syntactically (no decode)
        if ($url[$i] === '%') {
            if ($i + 2 >= $n
            || !\ctype_xdigit($url[$i + 1])
            || !\ctype_xdigit($url[$i + 2]))                        return (($t = new \InvalidArgumentException(__FUNCTION__.':invalid pct-encoding'))  && !(E_THROW & $behave)) ? $t : throw $t;
            $i += 2;
        }
    }

    $path = \trim($url, '/');
    if ($reject !== '' && isset($path[\strcspn($path, $reject)]))   return (($t = new \InvalidArgumentException(__FUNCTION__.':forbidden chars'))       && !(E_THROW & $behave)) ? $t : throw $t;
    return $path;
}// returns a rootless path extracted from url (can be empty string)

function look($base, $path, $shim = '', $behave = 0): ?string
{                                                                   // direct lookup: does a file exist at this exact location? or in that nested location (with REBASE) ?
    if (isset($base[0]) && $base[-1] !== '/')
        $base .= '/';
    
    $file = null;                                                   // prove me wrong.
    
    $leaf = $base . $path . $shim;
    if ((REBASE & $behave) && !\is_file($leaf))
        $leaf = $base . $path . '/' . \basename($path) . $shim;     // rebased shape: path/filename/filename pattern

    if(\is_file($leaf)){                                            // cheap stat guards against expensive realpath on miss
        $real = \realpath($leaf);
        if($real !== false && \strpos($real, $base) === 0)
            $file = $real;                                          // canonical is within base boundary
    }

    return $file;
}

function seek($base, $path, $shim = '', $behave = 0): ?array        // progressive search: test path segments to find executable file
{                                                                   
    if (!isset($base[0]) || $base[-1] !== '/') return null;

    $len = \strlen($path);
    $pos  = (ASCEND & $behave) ? 0 : $len;                          // begin at start for forward scan, at end for reverse

    do {
        if (ASCEND & $behave) {                                     // locate the next path separator
            $cut = \strpos($path, '/', $pos);                       // scanning forward from current position
            $end = ($cut === false) ? $len : $cut;                  // forward: at separator or path end
        } else {
            $back = $pos - $len - 1;                                // negative offset window ending at $pos; “-1” => strictly before $pos
            $cut = ($pos > 0) ? \strrpos($path, '/', $back) : false;// scanning backward before current position
            $end = $pos;                                            // reverse: at current position
        }

        $test = ($end === $len) ? $path : \substr($path, 0, $end);  // isolate the segment to test for existence
        if ($file = look($base, $test, $shim, $behave)) {           // collect remaining path parts as arguments
            $args = null;
            if($end + 1 < $len)
                $args = \explode('/', \substr($path, $end + 1));
            return [$file, $args ?? []];
        }

        if($cut !== false)                                          // if separator was found
            $pos = (ASCEND & $behave) ? $cut + 1 : $cut;            // move to next segment

    } while ($cut !== false);                                       // keep searching while separators remain

    return null;
}