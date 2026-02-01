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

const RFC_TCHAR     = "!#$%&'*+-.^_`|~0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";                                                                      // RFC 9110 5.1.  Field Names
const RFC_VCHAR     = "!\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~";                                                   // RFC 9110 5.5.  Field Values
const RFC_OBS_TEXT  = "\x80\x81\x82\x83\x84\x85\x86\x87\x88\x89\x8A\x8B\x8C\x8D\x8E\x8F\x90\x91\x92\x93\x94\x95\x96\x97\x98\x99\x9A\x9B\x9C\x9D\x9E\x9F\xA0\xA1\xA2\xA3\xA4\xA5\xA6\xA7\xA8\xA9\xAA\xAB\xAC\xAD\xAE\xAF\xB0\xB1\xB2\xB3\xB4\xB5\xB6\xB7\xB8\xB9\xBA\xBB\xBC\xBD\xBE\xBF\xC0\xC1\xC2\xC3\xC4\xC5\xC6\xC7\xC8\xC9\xCA\xCB\xCC\xCD\xCE\xCF\xD0\xD1\xD2\xD3\xD4\xD5\xD6\xD7\xD8\xD9\xDA\xDB\xDC\xDD\xDE\xDF\xE0\xE1\xE2\xE3\xE4\xE5\xE6\xE7\xE8\xE9\xEA\xEB\xEC\xED\xEE\xEF\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9\xFA\xFB\xFC\xFD\xFE\xFF";
const RFC_OWS       = " \t";                                       // SP and HTAB

const HTTP_VALUE_SAFE_BYTES = RFC_VCHAR . RFC_OBS_TEXT . RFC_OWS;

const RFC_CTL_NO_HTAB  = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x7F";               // RFC 9110 §5.5; RFC 5234 App B.1

function csp_nonce(int $bytes = 16): ?string
{// generate (once per request) and return a cached random hex nonce for CSP

    static $nonce = null;
    if ($bytes < 0) {                                               // reset sentinel
        $nonce = null;
        $bytes = -1 * $bytes;
    }
    return $nonce ??= \bin2hex(\random_bytes($bytes ?: 16));
}// returns a per-request cached random hex nonce string

function in($url = null): string
{// normalize a URL/request URI to an origin-form request-target by stripping any scheme and authority
    $had_scheme = null;                                             // unknown yet

    if ($url === null) {
        $url = $_SERVER['REQUEST_URI'] ?? '/';                      // origin-form: "/path?query"
        $had_scheme = false;                                        // prevents authority stripping on REQUEST_URI
    }

    if ($had_scheme !== false) {
        $end = \strcspn($url, ':/?#');                               // find first of ": / ? #"
        $had_scheme = isset($url[$end]) && $url[$end] === ':';      // ":" wins => "scheme:..."
        $had_scheme && ($url = \substr($url, $end + 1));             // drop "scheme:" => maybe "//authority/..."
    }

    if ($had_scheme && \strpos($url, '//') === 0) {                  // only treat "//" as authority after scheme
        $end = \strcspn($url, '/?#', 2) + 2;                         // skip authority up to next "/?#"
        $url = isset($url[$end]) ? \substr($url, $end) : '';         // keep origin-form tail (or "" if only authority)
    }

    return $url;                                                    // normalized request-target: no scheme, no authority
}// returns normalized request-target: no scheme, no authority (router-friendly)


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
            $read_only = [];                                        // reset
            $cs_values = [];
            $last_code = 0;
            $auto_emit = false;
            if(RESET & $behave)                                     
                return true;
        }

        ($status === 0 || ($status >= 100 && $status <= 599))       || throw new \InvalidArgumentException("http status invalid: `{$status}`");
        ($status !== 0 && ($last_code = $status));
        
        $canon = \trim($field);                                     // $field comes from header(?string $field), so null or string, so string given _rfc_compliance signature
        ($canon === $field)                                         || \trigger_error('field name has leading/trailing whitespace', \E_USER_NOTICE);
        ($canon !== '' || (EMIT & $behave) || (AUTO & $behave))     || throw new \InvalidArgumentException('field name is empty');
        
        if (EMIT & $behave) {
            !\headers_sent($file, $line)                            || throw new \InvalidArgumentException("headers already sent ({$file}:{$line})");
            foreach ($cs_values as [$name, $values])
                \header($name . ': ' . \implode(', ', $values), true);
            $last_code && \http_response_code($last_code);
            headers('','', RESET);
            return true;
        }

        if ((AUTO & $behave) && !$auto_emit){
            $auto_emit = \header_register_callback(function (){ 
                try { headers('', '', EMIT | E_TRIGGER); } 
                catch (\Throwable) {}
            })                                                      || throw new \RuntimeException(__FUNCTION__.':header_register_callback');

        }
        if ((AUTO & $behave) && $canon === '')                      // control-only call (idempotent)
            return true;    

        !isset($canon[\strspn($canon, RFC_TCHAR)])                  || throw new \InvalidArgumentException('field name must be a token (tchar alphabet)');
        $key = \strtolower($canon);

        !($read_only[$key] ?? false)                                || throw new \InvalidArgumentException("header locked: `{$key}`");
        (LOCK & $behave) && ($read_only[$key] = true);

        if ((DROP & $behave) && !(LOCK & $behave)) {
            unset($cs_values[$key]);                                // remove staged csv values (if any)
            unset($read_only[$key]);                                // release lock
            !\headers_sent($file, $line)                            || throw new \InvalidArgumentException("headers already sent ({$file}:{$line})");
            \header_remove($canon);                                 // or $key; $canon is cleaner since validated
            return true;                                            // terminal: do NOT re-add/emit
        }

        ($value !== '' || (BLANK & $behave))                        || throw new \InvalidArgumentException('field value is empty (use BLANK flag to allow)');
        !isset($value[\strspn($value, HTTP_VALUE_SAFE_BYTES)])      || throw new \InvalidArgumentException('field value must be VCHAR/obs-text plus SP/HTAB (OWS)');
        \strpbrk($value, RFC_CTL_NO_HTAB) === false                 || throw new \InvalidArgumentException('field value forbids CR/LF/NUL and other CTL');

        if($key === 'set-cookie')
            $behave = ($behave | ADD) & ~(CSV);                     // set-cookie is always multi-valued append-only

        if (CSV & $behave) {
            $cs_values[$key] ??= [$canon, []];
            $cs_values[$key][1][] = $value;
            return true;
        }

        !\headers_sent($file, $line)                                || throw new \InvalidArgumentException("headers already sent ({$file}:{$line})");
        
        \header($canon . ': ' . $value, !($behave & ADD), $status);
        
        return true;
     } 
     catch (\InvalidArgumentException $e) {
        if ((E_NOTICE | E_WARNING | E_TRIGGER) & $behave)
            \trigger_error($e->getMessage(), E_WARNING & $behave ? \E_USER_WARNING : \E_USER_NOTICE);
        (E_TRIGGER & $behave) || throw $e;
        return false;
    }
}