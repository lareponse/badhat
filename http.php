<?php

namespace bad\http;

const E_THROW = 1;

function csp_nonce(int $bytes = 16): ?string
{// generate (once per request) and return a cached random hex nonce for CSP

    static $nonce = null;
    if ($bytes < 0) {                                               // reset sentinel
        $nonce = null;
        $bytes = -$bytes;
    }
    return $nonce ??= \bin2hex(\random_bytes($bytes ?: 16));
}// returns a per-request cached random hex nonce string

