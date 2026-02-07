<?php

namespace bad\map;

const REBASE = 1;                                                   // enables nested path/filename/filename pattern fallback in look()
const ASCEND = 2;                                                   // forward scan in seek() instead of reverse (default shrinks from end)

function look($base, $path, $shim = '', $behave = 0): ?string
{                                                                   // direct lookup: does a file exist at this exact location? or in that nested location (with REBASE) ?
    if(isset($base[0]) && $base[\strlen($base) - 1] !== '/')
        $base .= '/';
    
    $file = null;                                                   // not file found, unless proven otherwise
    $leaf = $base . $path . $shim;                                  // form the primary candidate path
    if ((REBASE & $behave) && !\is_file($leaf))                     // if nesting allowed and primary doesn't exist
        $leaf = $base . $path . '/' . \basename($path) . $shim;     // form nested: path/filename/filename pattern

    if(\is_file($leaf)){                                            // verify exists
        $real = \realpath($leaf);                                   // resolve canonical path
        if($real !== false && \strpos($real, $base) === 0)          // ensure within base boundary
            $file = $real;
    }

    return $file;                                                   
}                                                                   // returns resolved path if valid file within base, null otherwise

function seek($base, $path, $shim = '', $behave = 0): ?array
{                                                                   // progressive search: test path segments to find executable file
    if(isset($base[0]) && $base[\strlen($base) - 1] !== '/')
        $base .= '/';

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
}                                                                   // returns the file with its arguments or null