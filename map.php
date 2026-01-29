<?php

namespace bad\map;

const REBASE = 1;                                                                              // enables nested path/filename/filename pattern fallback in look()
const ASCEND = 2;                                                                              // forward scan in seek() instead of reverse (default shrinks from end)

function hook($base, $url, $forbidden = ''): string
{// receive a request: extract and validate the path to navigate
    (DIRECTORY_SEPARATOR === '/')                                                              || throw new \RuntimeException(__NAMESPACE__.':requires a POSIX environment (Linux/macOS/WSL)');
    $eof = strcspn($url, '?#');                                                                // query/fragment may contain slashes that aren't path separators, look for end of file path
    isset($url[$eof]) && ($url = substr($url, 0, $eof));                                       // must strip before path operations to avoid misinterpretation
    
    ($forbidden === '' || !isset($url[strcspn($url, $forbidden)]))                             || throw new \InvalidArgumentException('request has explicitly forbidden chars');
    
    ($base === realpath($base) . '/')                                                          || throw new \InvalidArgumentException('invalid base or path');
    return $url;                                                                                                        
}// returns clean path ready for navigation

function look($base, $path, $shim = '', $behave = 0): ?string
{// direct lookup: does a file exist at this exact location? or in that nested location (with REBASE) ?
    $leaf = $base . $path . $shim;                                                             // form the primary candidate path
    if ((REBASE & $behave) && !is_file($leaf))                                                 // if nesting allowed and primary doesn't exist
        $leaf = $base . $path . '/' . basename($path) . $shim;                                 // form nested: path/filename/filename pattern

    if(is_file($leaf))                                                                         // verify exists, resolve canonical path, ensure within base boundary
        return ($real = realpath($leaf)) && strpos($real, $base) === 0 ? $real : null;       
    
    return null;                    
}// returns resolved path if valid file within base, null otherwise

function seek($base, $path, $shim = '', $behave = 0): ?array
{// progressive search: test path segments to find executable file
    $len = strlen($path);
    $pos  = (ASCEND & $behave) ? 0 : $len;                                                     // begin at start for forward scan, at end for reverse

    do {
        $cut = (ASCEND & $behave)                                                              // locate the next path separator
             ? strpos($path, '/', $pos)                                                        // scanning forward from current position
             : (($pos > 0) ? strrpos($path, '/', $pos - $len - 1) : false);                    // scanning backward before current position

        $end = (ASCEND & $behave)                                                              // determine where this segment ends
             ? ($cut === false ? $len : $cut)                                                  // forward: at separator or path end
             : $pos;                                                                           // reverse: at current position

        $test = ($end === $len) ? $path : substr($path, 0, $end);                              // isolate the segment to test for existence
        if ($file = look($base, $test, $shim, $behave)) {                                      // check if this segment resolves to a file
            $args = ($end + 1 < $len) ? explode('/', substr($path, $end + 1)) : [];            // collect remaining path parts as arguments
            return [$file, $args];                                                                                      
        }

        ($cut !== false) && ($pos = (ASCEND & $behave) ? $cut + 1 : $cut);                     // move to next segment if separator was found
    } while ($cut !== false);                                                                  // keep searching while separators remain

    return null;                                                                                                        
}// found: returns the file with its arguments, not found: null