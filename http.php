<?php

namespace bad\http;

const STATUS_BITS   = 10;                                           // low 10 bits: 0..1023 (status code payload)
const STATUS_MASK   = (1 << STATUS_BITS) - 1;

const ADD           = 1         << STATUS_BITS;                     // multivalue in multiline (append; disables replace)
const CSV           = 2         << STATUS_BITS;                     // multivalue staged for later comma-join on EMIT
const LOCK          = 4         << STATUS_BITS;                     // prevents further emission and csv alteration (disables DROP)
const DROP          = 8         << STATUS_BITS;                     // drops all value(s) of a field-name (unset for csv or header_remove)
const BLANK         = 16        << STATUS_BITS;                     // (for callers/wrappers) allow empty field-value (no validation performed here)
const RESET         = 32        << STATUS_BITS;                     // reset all static variables

const E_TRIGGER     = 256       << STATUS_BITS;                     // dont change, it is deprecation poetry, and prevents E_USER_ERROR usage
const E_WARNING     = 512       << STATUS_BITS;                     // dont change, also works with \E_USER_WARNING
const E_NOTICE      = 1024      << STATUS_BITS;                     // dont change, also works with \E_USER_NOTICE

const AUTO          = 524288    << STATUS_BITS;                     // use header_register_callback to call headers(EMIT);
const EMIT          = 1048576   << STATUS_BITS;                     // output + clear (BADHAT supports 32-bit PHP. Flags MUST NOT use bit 31. EMIT uses bit 30 and is reserved as the last flag)

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
    // extract path portion from url
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

function headers($status_behave = 0, $field='', $value = ''): bool
{// Emit/manage HTTP headers. IMPORTANT: $field and $value are assumed SAFE (already validated/canonicalized elsewhere).
    static $read_only = null;
    static $cs_values = null;
    static $last_code = null;
    static $auto_emit = false;                                      
    // vd(15,$field, $value, $status_behave);
    
    $status = $status_behave & STATUS_MASK;
    $behave = $status_behave & ~STATUS_MASK;
    $throw_on_sent = static fn (): bool => !\headers_sent($f, $l) ? true  : throw new \InvalidArgumentException("headers already sent ({$f}:{$l})");
    
    try {
        if ((RESET & $behave) || $read_only === null) {
            $read_only ??= [];
            $cs_values = [];
            $last_code = 0;
            if(RESET & $behave){
                \headers_sent() && ($read_only = []);               // if headers were already sent, locks cannot matter anymore; clear them
                return true;
            }
        }
        // status sanity (HTTP concern, not RFC concern)
        ($status === 0 || ($status >= 100 && $status <= 599))       || throw new \InvalidArgumentException("http status invalid: `{$status}`");

        if (EMIT & $behave) {
            $throw_on_sent();
            foreach ($cs_values as [$name, $values])
                \header($name . ': ' . \implode(', ', $values), true);

            $cs_values = [];
            $last_code && \http_response_code($last_code);
            return true;
        }

        // control: AUTO register callback once
        if (AUTO & $behave) {
            ($field === '' && $value === '')                        || throw new \BadFunctionCallException('AUTO is control-only; use headers(AUTO)');

            if ($auto_emit !== true) {
                $auto_emit = \header_register_callback(static function (): void {
                    try {
                        headers(EMIT | E_TRIGGER);
                        headers(RESET | E_TRIGGER);
                    } catch (\Throwable) {}                         // swallow: shutdown-time best effort
                })                                                  || throw new \RuntimeException(__FUNCTION__ . ': header_register_callback');
            }
            return $auto_emit === true;
        }

        ($field !== '')                                             || throw new \InvalidArgumentException('field name is empty');
        $key = \strtolower($field);

        if($read_only[$key] ?? false)
            return false;

        if ((DROP & $behave) && !(LOCK & $behave)) {
            $throw_on_sent();
            \header_remove($field);// external op first (now known-possible)
            unset($cs_values[$key], $read_only[$key]);// internal state commit
            return true;
        }
        
        if ($key === 'set-cookie')
            $behave = ($behave | ADD) & ~(CSV);                     // HTTP semantics: Set-Cookie is append-only and not comma-joinable

        if (CSV & $behave) {                                        // CSV staging (emitted later on EMIT)
            $cs_values[$key] ??= [$field, []];
            $cs_values[$key][1][] = $value;
            (LOCK & $behave) && ($read_only[$key] = true);
            return true;
        }

        $throw_on_sent();
        \header($field . ': ' . $value, !($behave & ADD), $status);
        unset($cs_values[$key]);                                    // if this key was previously staged in CSV, immediate emission wins
        ($status !== 0) && ($last_code = $status);

        (LOCK & $behave) && ($read_only[$key] = true);
        return true;
    }
    catch (\InvalidArgumentException $e) {
        if ((E_NOTICE | E_WARNING | E_TRIGGER) & $behave){
            \trigger_error($e->getMessage(), (E_WARNING & $behave) ? \E_USER_WARNING : \E_USER_NOTICE);
            return false;
        }
        throw $e;
    }
}
