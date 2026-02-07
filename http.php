<?php

namespace bad\http;

function csp_nonce(int $bytes = 16): ?string
{// generate (once per request) and return a cached random hex nonce for CSP

    static $nonce = null;
    if ($bytes < 0) {                                               // reset sentinel
        $nonce = null;
        $bytes = -$bytes;
    }
    return $nonce ??= \bin2hex(\random_bytes($bytes ?: 16));
}// returns a per-request cached random hex nonce string

function path($url, $reject = ''): string                           // receive a request: extract and validate the path to navigate
{
    $end = \strcspn($url, ':/?#');                                  // find first of ": / ? #"
    $had_scheme = isset($url[$end]) && $url[$end] === ':';          // ":" wins => "scheme:..."
    $had_scheme && ($url = \substr($url, $end + 1));                // drop "scheme:" => maybe "//authority/..."

    if ($had_scheme && \strpos($url, '//') === 0) {                 // only treat "//" as authority after scheme
        $end = \strcspn($url, '/?#', 2) + 2;                        // skip authority up to next "/?#"
        $url = isset($url[$end]) ? \substr($url, $end) : '';        // keep origin-form tail (or "" if only authority)
    }

    $end = \strcspn($url, '?#');                                    // query/fragment may contain slashes that aren't path separators, look for end of file path
    isset($url[$end]) && ($url = \substr($url, 0, $end));           // must strip before path operations to avoid misinterpretation
    
    for ($i = 0, $n = strlen($url); $i < $n; $i++) {                // MUST validate percent-escapes syntactically (no decode)
        if ($url[$i] === '%') {
            ($i + 2 < $n)                                           || throw new \InvalidArgumentException('map path has invalid % encoding');
            ctype_xdigit($url[$i+1]) && ctype_xdigit($url[$i+2])    || throw new \InvalidArgumentException('map path has invalid % encoding');
            $i += 2;
        }
    }
    // now checking url as rootless path
    $path = \trim($url, '/');                                                                                                        
    ($reject === '' || !isset($path[\strcspn($path, $reject)]))     || throw new \InvalidArgumentException('path has explicitly forbidden chars');
    return $path;
} // returns a rootless path extracted from url (can be empty string)
