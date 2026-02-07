<?php

namespace bad\http;                                                             // header staging + emission (bitmask)

const STATUS_BITS = 10;                                                                // low bits reserved for status code
const STATUS_MASK = (1 << STATUS_BITS) - 1;                                            // extract status (0..1023)

const ADD    = 1    << STATUS_BITS;                                                    // allow multi-value add (must be first call)
const CSV    = 2    << STATUS_BITS;                                                    // treat value list as CSV (must be first call)
const DROP   = 4    << STATUS_BITS;                                                    // removes staged data
const LOCK   = 8    << STATUS_BITS;                                                    // lock entry against mutation

const META_MASK = CSV | LOCK;                                                          // meta persisted per entry (not per call)

const RESET  = 16   << STATUS_BITS;                                                    // clear stage (optionally emit)

const EMIT   = 32   << STATUS_BITS;                                                    // emit staged headers/status

const REMOVE = 64  << STATUS_BITS;                                                    // remove then emit (stage-driven)

const E_THROW = 128  << STATUS_BITS;                                                    // throw on failure instead of returning false
const COOKIE = ADD;                                                                    // alias, self-documenting at call site

// stage/mutate/emit headers
function headers($status_behave = 0, $field = '', $value = '') : bool
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
            if (LOCK & ($stage[$key][2] ?? 0))                                         return (E_THROW & $behave) && throw new \LogicException(__FUNCTION__.':entry locked');

            \headers_sent() || \header_remove($stage[$key][0]);
            unset($stage[$key]);                                                       // remove from stage
        }
    }
    else if ($field !== '') {                                                          // stage/mutate one header entry
        $key = \strtolower($field);                                                    // normalize lookup key
        $ent = $stage[$key] ?? null;                                                   // existing entry (or null)

        if (LOCK & ($ent[2] ?? 0))                                                     return (E_THROW & $behave) && throw new \LogicException(__FUNCTION__.':entry locked');
        if ($key === 'set-cookie' && ((CSV & $behave) || !(COOKIE & $behave)))         return (E_THROW & $behave) && throw new \LogicException(__FUNCTION__.':set-cookie requires COOKIE flag');

        $stage_value = $ent[1] ?? null;                                                // existing value (string|array|null)

        if (((ADD | CSV) & $behave) && $ent && !\is_array($stage_value))               return (E_THROW & $behave) && throw new \LogicException(__FUNCTION__.':ADD/CSV on scalar entry');
        if ((CSV & $behave) && $ent && !(CSV & ($ent[2] ?? 0)))                        return (E_THROW & $behave) && throw new \LogicException(__FUNCTION__.':CSV flag mismatch');

        if (((ADD | CSV) & $behave) || \is_array($stage_value)) {                      // ensure list mode (ADD/CSV or already list)
            $stage[$key] ??= [0 => $field, 1 => [], 2 => ($behave & META_MASK)];       // init entry in list mode with meta
            $stage[$key][1][] = $value;                                                // append value
        } else {                                                                       // scalar mode: overwrite
            $stage[$key] = [0 => $field, 1 => $value, 2 => ($behave & META_MASK)];     // set single value + meta
        }

        if (LOCK & $behave) $stage[$key][2] |= LOCK;                                   // lock entry after mutation
    }

    (RESET & $behave) || ($status && ($last_status = $status));                        // stage status unless RESET (0 means "keep")

    if (EMIT & $behave){                                                               // emit path (headers + status)
        if (\headers_sent())                                                           return (E_THROW & $behave) && throw new \LogicException(__FUNCTION__.':headers already sent');

        $emit_status ??= $status ?: $last_status;
        $emit_stage  ??= $stage;

        $emit_remove = (bool)(REMOVE & $behave);
        foreach ($emit_stage as $ent){
            if($emit_remove)                                                           \header_remove($ent[0]);

            if      (!\is_array($ent[1]))                                              \header($ent[0] . ': ' . $ent[1], true);
            elseif  (CSV & ($ent[2] ?? 0))                                             \header($ent[0] . ': ' . \implode(', ', $ent[1]), true);
            else    foreach ($ent[1] as $v)                                            \header($ent[0] . ': ' . $v, false);
        }

        if($emit_status)                                                               \http_response_code($emit_status);
    }

    return true;
}

function csp_nonce(int $bytes = 16): ?string
{// generate (once per request) and return a cached random hex nonce for CSP

    static $nonce = null;
    if ($bytes < 0) {                                               // reset sentinel
        $nonce = null;
        $bytes = -$bytes;
    }
    return $nonce ??= \bin2hex(\random_bytes($bytes ?: 16));
}// returns a per-request cached random hex nonce string

function out($body, $status = 0)
{
    $no_payload = $body === null || $body === ''
               || $status < 200
               || $status === 204 || $status === 205  || $status === 304;

    headers(SET, 'Content-Length', ($no_payload ? 0 : \strlen($body)));
    headers(EMIT | $status);

    if($no_payload !== false)
        echo $body;
}