<?php

namespace bad\http;

const STATUS_BITS = 10;                                                                // low bits reserved for status code
const STATUS_MASK = (1 << STATUS_BITS) - 1;                                            // extract status (0..1023)

const ADD       = 1     << STATUS_BITS;                             //    [1024]       // list-mode: append another value (entry must start as list)
const CSV       = 2     << STATUS_BITS;                             //    [2048]       // list-mode + emit as "v1, v2, v3" (entry must start as CSV list)
const SSV       = 4     << STATUS_BITS;                             //    [4096]       // list-mode + emit as "v1; v2; v3" (entry must start as SSV list)

const LOCK      = 8     << STATUS_BITS;                             //    [8192]       // lock entry against mutation (set per entry)
const DROP      = 16    << STATUS_BITS;                             //   [16384]       // drop one staged header by name
const READ      = 32    << STATUS_BITS;                             //   [32768]       // reserved (not implemented)

const RESET     = 64    << STATUS_BITS;                             //   [65536]       // clear stage (can be combined with EMIT to emit previous stage)
const REMOVE    = 128   << STATUS_BITS;                             //  [131072]       // on EMIT: header_remove(name) before sending staged value(s)

const EMIT      = 256   << STATUS_BITS;                             //  [262144]       // send staged headers + status (fails if headers_sent)
const EXIT      = 512   << STATUS_BITS;                             //  [524288]       // out() only: emit then exit

const META_MASK = CSV | SSV | LOCK;                                                    // meta persisted per entry (not per call)

const ONE       = 0;                                                                   // placeholder: headers(ONE, field, value)
const COOKIE    = ADD;                                                                 // alias: Set-Cookie must be list-mode (multi header lines)
const CSP       = ADD | SSV;                                                           // alias: CSP commonly built as directive list ("a; b; c")

function out(int $behave, $body = null): int
{
    $status = $behave & STATUS_MASK;
    $len = ($body === null || $body === '' || ($status >= 100 && $status < 200) || $status === 204 || $status === 205  || $status === 304) ? 0 : \strlen($body);

    headers(ONE, 'Content-Length', $len);
    $res = headers(EMIT | ($behave & ~EXIT));

    if($len)
        echo $body;

    if (EXIT & $behave)                                                                exit($res < 0 ? 1 : 0);

    return $res;
}

function headers($status_behave = 0, $field = '', $value = '') : int                   // stage/mutate/emit headers
{
    static $stage = [];                                                                // staged headers: key => [field, value|[], meta]
    static $last_status = 0;                                                           // staged status (kept until RESET/EMIT)

    $status = $status_behave & STATUS_MASK;                                            // status code payload (0 means "no change")
    $behave = $status_behave & ~STATUS_MASK;                                           // behavior flags (everything above status bits)
    if (RESET & $behave) {                                                             // reset path (optionally capture previous stage for EMIT|RESET calls)
        $emit_status = $status ?: $last_status;                                        // pick explicit status or last staged
        $last_status = 0;                                                              // if last_status was set, unset it
        ($emit_stage = $stage) && ($stage = []);                                       // if stage is already empty, dont create a new empty array
    }
    else if (DROP & $behave) {                                                         // drop one staged header by name
        $key = \strtolower($field);                                                    // normalize lookup key
        if (isset($stage[$key])){                                                      // only if present
            if (LOCK & ($stage[$key][2] ?? 0))                                         return -(DROP|LOCK);

            \headers_sent() || \header_remove($stage[$key][0]);
            unset($stage[$key]);                                                       // remove from stage
        }
    }
    else if ($field !== '') {                                                          // stage/mutate one header entry
        $key = \strtolower($field);                                                    // normalize lookup key
        $ent = $stage[$key] ?? null;                                                   // existing entry (or null)

        if (LOCK & ($ent[2] ?? 0))                                                     return -LOCK;
        if ($key === 'set-cookie' && ((CSV & $behave) || !(COOKIE & $behave)))         return -COOKIE;

        $stage_value = $ent[1] ?? null;                                                // existing value (string|array|null)

        if (((ADD | CSV | SSV) & $behave) && $ent && !\is_array($stage_value))         return -(ADD | CSV);
        if ((CSV & $behave) && $ent && !(CSV & ($ent[2] ?? 0)))                        return -CSV;
        if ((SSV & $behave) && $ent && !(SSV & ($ent[2] ?? 0)))                        return -SSV;

        if (((ADD | CSV | SSV) & $behave) || \is_array($stage_value)) {                // ensure list mode (ADD/CSV or already list)
            $stage[$key] ??= [0 => $field, 1 => [], 2 => ($behave & META_MASK)];       // init entry in list mode with meta
            $stage[$key][1][] = $value;                                                // append value
        } else {                                                                       // scalar mode: overwrite
            $stage[$key] = [0 => $field, 1 => $value, 2 => ($behave & META_MASK)];     // set single value + meta
        }

        if (LOCK & $behave) $stage[$key][2] |= LOCK;                                   // lock entry after mutation
    }

    (RESET & $behave) || ($status && ($last_status = $status));                        // stage status unless RESET (0 means "keep")

    if (EMIT & $behave){                                                               // emit path (headers + status)
        if (\headers_sent())                                                           return -EMIT;

        $emit_status ??= $status ?: $last_status;
        $emit_stage  ??= $stage;

        $emit_remove = (bool)(REMOVE & $behave);
        foreach ($emit_stage as $ent){
            if($emit_remove)                                                           \header_remove($ent[0]);

            if     (!\is_array($ent[1]))                                               \header($ent[0] . ': ' . $ent[1], true);
            elseif (CSV & ($ent[2] ?? 0))                                              \header($ent[0] . ': ' . \implode(', ', $ent[1]), true);
            elseif (SSV & ($ent[2] ?? 0))                                              \header($ent[0] . ': ' . \implode('; ', $ent[1]), true);
            else   foreach ($ent[1] as $v)                                             \header($ent[0] . ': ' . $v, false);
        }

        if($emit_status)                                                               \http_response_code($emit_status);
        return $emit_status;
    }

    return 0;
}

function csp_nonce(int $bytes = 16): string
{// generate (once per request) and return a cached random hex nonce for CSP

    static $nonce = null;
    if ($bytes < 0) {                                               // reset sentinel
        $nonce = null;                                              // provoke regeneration
        $bytes = -$bytes;                                           // extract bytes from sentinel
    }
    return $nonce ??= \bin2hex(\random_bytes($bytes ?: 16));
}// returns a per-request cached random hex nonce string
