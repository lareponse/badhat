<?php

namespace bad\http;

const HTTP_CODE_MASK = 0x3FF;                                       // low 10 bits: 0..1023 (as of 2026, we use http code between 100..511, so 9 bits suffice, but lets not Y2K this)

const ADD           = 1         << 10;                              // multivalue in multiline 
const CSV           = 2         << 10;                              // multivalue in csv
const LOCK          = 4         << 10;                              // prevents further emission and csv alteration (disables DROP)
const DROP          = 8         << 10;                              // drops all value(s) of a field-name (unset for csv or header_remove)
const BLANK         = 16        << 10;                              // allow empty field-value (explicit RFC-compatible)
const RESET         = 32        << 10;                              // reset all static variables

const E_TRIGGER     = 256       << 10;                              // dont change, it is deprecation poetry, and prevents E_USER_ERROR usage
const E_WARNING     = 512       << 10;                              // dont change, also works with \E_USER_WARNING
const E_NOTICE      = 1024      << 10;                              // dont change, also works with \E_USER_NOTICE

const AUTO          = 524288    << 10;                              // use header_register_callback to call headers('','', EMIT);
const EMIT          = 1048576   << 10;                              // output + clear (BADHAT supports 32-bit PHP. Flags MUST NOT use bit 31. EMIT uses bit 30 and is reserved as the last flag)

const RFC_H_TCHAR     = "!#$%&'*+-.^_`|~0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";                                                                      // RFC 9110 5.1.  Field Names
const RFC_H_VCHAR     = "!\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~";                                                   // RFC 9110 5.5.  Field Values
const RFC_H_OBS_TEXT  = "\x80\x81\x82\x83\x84\x85\x86\x87\x88\x89\x8A\x8B\x8C\x8D\x8E\x8F\x90\x91\x92\x93\x94\x95\x96\x97\x98\x99\x9A\x9B\x9C\x9D\x9E\x9F\xA0\xA1\xA2\xA3\xA4\xA5\xA6\xA7\xA8\xA9\xAA\xAB\xAC\xAD\xAE\xAF\xB0\xB1\xB2\xB3\xB4\xB5\xB6\xB7\xB8\xB9\xBA\xBB\xBC\xBD\xBE\xBF\xC0\xC1\xC2\xC3\xC4\xC5\xC6\xC7\xC8\xC9\xCA\xCB\xCC\xCD\xCE\xCF\xD0\xD1\xD2\xD3\xD4\xD5\xD6\xD7\xD8\xD9\xDA\xDB\xDC\xDD\xDE\xDF\xE0\xE1\xE2\xE3\xE4\xE5\xE6\xE7\xE8\xE9\xEA\xEB\xEC\xED\xEE\xEF\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9\xFA\xFB\xFC\xFD\xFE\xFF";
const RFC_H_OWS       = " \t";                                      // SP and HTAB

const RFC_H_VALUE_SAFE_BYTES = RFC_H_VCHAR . RFC_H_OBS_TEXT . RFC_H_OWS;

const RFC_H_CTL_NO_HTAB  = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x7F";               // RFC 9110 §5.5; RFC 5234 App B.1

// RFC 3986 (URI) alphabets (ASCII only)
const RFC_U_HEX        = "0123456789ABCDEFabcdef";
const RFC_U_UNRESERVED = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~";
const RFC_U_SUBDELIMS  = "!$&'()*+,;=";

const RFC_U_PCHAR      = RFC_U_UNRESERVED . RFC_U_SUBDELIMS . ":@"; // pchar = unreserved / pct-encoded / sub-delims / ":" / "@"
const RFC_U_PATH_SAFE  = RFC_U_PCHAR . "/%";                        // path = *( "/" segment ) ; segment = *pchar


function csp_nonce(int $bytes = 16): ?string
{// generate (once per request) and return a cached random hex nonce for CSP

    static $nonce = null;
    if ($bytes < 0) {                                               // reset sentinel
        $nonce = null;
        $bytes = -1 * $bytes;
    }
    return $nonce ??= \bin2hex(\random_bytes($bytes ?: 16));
}// returns a per-request cached random hex nonce string


function headers(string $field, string $value='', int $status_behave = 0): bool
{
    static $read_only = null;
    static $cs_values = null;
    static $last_code = null;
    static $auto_emit = null;

    $status = $status_behave & HTTP_CODE_MASK;
    $behave = $status_behave & ~HTTP_CODE_MASK;

    try {
        if ((RESET & $behave) || !isset($read_only)) {
            !isset($read_only) && ($read_only = []);
            $cs_values = [];
            $last_code = 0;
            if(RESET & $behave){
                headers_sent() && ($read_only = []);
                return true;
            }
        }

        ($status === 0 || ($status >= 100 && $status <= 599))       || throw new \InvalidArgumentException("http status invalid: `{$status}`");
        
        $canon = \trim($field);                                     // $field comes from header(?string $field), so null or string, so string given _rfc_compliance signature
        ($canon === $field)                                         || \trigger_error('field name has leading/trailing whitespace', \E_USER_NOTICE);
        ($canon !== '' || (EMIT & $behave) || (AUTO & $behave))     || throw new \InvalidArgumentException('field name is empty');
        
        if (EMIT & $behave) {
            !\headers_sent($file, $line)                            || throw new \InvalidArgumentException("headers already sent ({$file}:{$line})");
            foreach ($cs_values as [$name, $values])
                \header($name . ': ' . \implode(', ', $values), true);
            
            $cs_values = [];

            $last_code && \http_response_code($last_code);
            return true;
        }

        if (AUTO & $behave) {
            ($canon === '') || throw new \InvalidArgumentException('AUTO is control-only; use headers("", "", AUTO)');

            if ($auto_emit !== true) {
                $auto_emit = \header_register_callback(function () {
                    try {
                        headers('', '', EMIT | E_TRIGGER);
                        headers('', '', RESET | E_TRIGGER);
                    } catch (\Throwable) {}
                }) || throw new \RuntimeException(__FUNCTION__ . ':header_register_callback');
            }

            return $auto_emit === true;
        }

        !isset($canon[\strspn($canon, RFC_H_TCHAR)])                  || throw new \InvalidArgumentException('field name must be a token (tchar alphabet)');
        $key = \strtolower($canon);

        !($read_only[$key] ?? false)                                || throw new \InvalidArgumentException("header locked: `{$key}`");

        if ((DROP & $behave) && !(LOCK & $behave)) {
            !\headers_sent($file, $line)                            || throw new \InvalidArgumentException("headers already sent ({$file}:{$line})");
            \header_remove($canon);                                 // external op first (now known-possible)
            unset($cs_values[$key]);                                // commit internal changes after guard/op
            unset($read_only[$key]);
            return true;
        }


        $len = \strlen($value);
        if (!(BLANK & $behave)) {
            // must contain at least one byte that is not OWS (SP/HTAB)
            ($len !== 0 && \strspn($value, RFC_H_OWS) !== $len) || throw new \InvalidArgumentException('field value is empty/OWS-only (use BLANK to allow)');
        }




        ($value !== '' || (BLANK & $behave))                        || throw new \InvalidArgumentException('field value is empty (use BLANK flag to allow)');
        !isset($value[\strspn($value, RFC_H_VALUE_SAFE_BYTES)])     || throw new \InvalidArgumentException('field value must be VCHAR/obs-text plus SP/HTAB (OWS)');
        \strpbrk($value, RFC_H_CTL_NO_HTAB) === false               || throw new \InvalidArgumentException('field value forbids CR/LF/NUL and other CTL');

        if($key === 'set-cookie')
            $behave = ($behave | ADD) & ~(CSV);                     // set-cookie is always multi-valued append-only

        if (CSV & $behave) {
            $cs_values[$key] ??= [$canon, []];
            $cs_values[$key][1][] = $value;
            (LOCK & $behave) && ($read_only[$key] = true);
            return true;
        }

        !\headers_sent($file, $line)                                || throw new \InvalidArgumentException("headers already sent ({$file}:{$line})");
        \header($canon . ': ' . $value, !($behave & ADD), $status);
        unset($cs_values[$key]); 
        ($status !== 0 && ($last_code = $status));

        (LOCK & $behave) && ($read_only[$key] = true);
        return true;
     } 
     catch (\InvalidArgumentException $e) {
        if ((E_NOTICE | E_WARNING | E_TRIGGER) & $behave){
            \trigger_error($e->getMessage(), E_WARNING & $behave ? \E_USER_WARNING : \E_USER_NOTICE);
            return false;
        }
        throw $e;
    }
}

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
            ($i + 2 < $n)                                           || throw new InvalidArgumentException('map path has invalid % encoding');
            ctype_xdigit($url[$i+1]) && ctype_xdigit($url[$i+2])    || throw new InvalidArgumentException('map path has invalid % encoding');
            $i += 2;
        }
    }
    // now checking url as rootless path
    $path = \trim($url, '/');                                                                                                        
    \strpbrk($path, RFC_H_CTL_NO_HTAB . RFC_H_OWS) === false            || throw new \InvalidArgumentException('path forbids CTL/SP/HTAB');
    \strpos($path, "\\") === false                                  || throw new \InvalidArgumentException('path forbids backslash');
    ($reject === '' || !isset($path[\strcspn($path, $reject)]))     || throw new \InvalidArgumentException('path has explicitly forbidden chars');

    return $path;
} // returns a rootless path extracted from url (can be empty string)