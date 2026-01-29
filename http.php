<?php

namespace bad\http;

const HTTP_CODE_MASK = 0x3FF;                                       // low 10 bits: 0..1023 (as of 2026, we use http code between 100..511, so 9 bits suffice, but lets not Y2K this)
                                                                    
const SET           = 1    << 10;                                   // replace (single)
const ADD           = 2    << 10;                                   // append (lines)
const CSV           = 4    << 10;                                   // append (csv)
const MODE_MASK     = SET | ADD | CSV;                              // flags start at bit 10 (so they never collide with status code)

const KEEP_CASE     = 8    << 10;                                   // caller casing as key (after trim check), beware
const READ_ONLY     = 16   << 10;                                   // lock (one-time set or no further changes)
const KEEP_FIRST    = 32   << 10;                                   

const NO_REPLACE    = 128  << 10;                                   // header(..., $replace=true|false)

const E_IGNORE      = 256  << 10;                                   // dont change, it is deprecation poetry
const E_WARNING     = 512  << 10;                                   // dont change, also works with \E_USER_WARNING
const E_NOTICE      = 1024 << 10;                                   // dont change, also works with \E_USER_NOTICE

const HEADER_RAW    = 2048 << 10;
const EMIT          = 1048576 << 10;                                // output + clear (BADHAT supports 32-bit PHP. Flags MUST NOT use bit 31. EMIT uses bit 30 and is reserved as the last flag)

const RFC_TCHAR     = "!#$%&'*+-.^_`|~0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";                                                                      // RFC 9110 5.1.  Field Names
const RFC_VCHAR     = "!\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~";                                                   // RFC 9110 5.5.  Field Values
const RFC_OBS_TEXT  = "\x80\x81\x82\x83\x84\x85\x86\x87\x88\x89\x8A\x8B\x8C\x8D\x8E\x8F\x90\x91\x92\x93\x94\x95\x96\x97\x98\x99\x9A\x9B\x9C\x9D\x9E\x9F\xA0\xA1\xA2\xA3\xA4\xA5\xA6\xA7\xA8\xA9\xAA\xAB\xAC\xAD\xAE\xAF\xB0\xB1\xB2\xB3\xB4\xB5\xB6\xB7\xB8\xB9\xBA\xBB\xBC\xBD\xBE\xBF\xC0\xC1\xC2\xC3\xC4\xC5\xC6\xC7\xC8\xC9\xCA\xCB\xCC\xCD\xCE\xCF\xD0\xD1\xD2\xD3\xD4\xD5\xD6\xD7\xD8\xD9\xDA\xDB\xDC\xDD\xDE\xDF\xE0\xE1\xE2\xE3\xE4\xE5\xE6\xE7\xE8\xE9\xEA\xEB\xEC\xED\xEE\xEF\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9\xFA\xFB\xFC\xFD\xFE\xFF";
const RFC_OWS       = " \t"; // SP and HTAB

const HTTP_VALUE_SAFE_BYTES = RFC_VCHAR . RFC_OBS_TEXT . RFC_OWS;

const RFC_CTL_NO_HTAB  = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x7F";               // RFC 9110 §5.5; RFC 5234 App B.1
const LAST_CODE     = 0;

function csp_nonce($bytes = 16): string
{// generate (once per request) and return a cached random hex nonce for CSP
    static $nonce;
    return $nonce ??= bin2hex(random_bytes($bytes));
}// returns a per-request cached random hex nonce string

function in($url = null): string
{// normalize a URL/request URI to an origin-form request-target by stripping any scheme and authority
    $url ??= $_SERVER['REQUEST_URI'] ?? '/';                        // request-target (usually origin-form): "/path?query" (RFC 9112 §3.2.1; RFC 9110 §7.1)

    $end = strcspn($url, ':/?#');                                   // locate first of ": / ? #" to detect "scheme:" before any "/?#" (absolute-form)
    if (isset($url[$end]) && $url[$end] === ':')                    // if ":" wins, input looks like "scheme:..." (RFC 9110 request-target absolute-form)
        $url = substr($url, $end + 1);                              // drop "scheme:" => leaves possible "//authority/path?query"

    if (isset($url[1]) && $url[1] === '/' && $url[0] === '/') {     // leading "//" => authority present (authority-form / absolute-form authority prefix)
        $end = strcspn($url, '/?#', 2) + 2;                         // skip authority up to next "/?#" (RFC 9110 §7.1 request-target forms)
        $url = isset($url[$end]) ? substr($url, $end) : '';         // keep origin-form tail: "/path?query" (or "" if only authority)
    }

    return $url;
}// returns normalized request-target: no scheme, no authority (router-friendly)

function out(int $code_behave, $body = null, $ignored_header = null): void
{// emit queued headers, set an HTTP status code, and conditionally echo a response body
    $code   = $code_behave &  HTTP_CODE_MASK;                       // response code (0 => "do not set")
    $behave = $code_behave & ~HTTP_CODE_MASK;                       // behavior only
    ($code === 0 || ($code >= 100 && $code <= 599))                 || throw new \InvalidArgumentException(__FUNCTION__.":invalid_http_status#{$code}");

    foreach (headers(EMIT) as $params)
        header(...$params);
    
    if ($ignored_header && (HEADER_RAW & $behave))
        header($ignored_header, !(NO_REPLACE & $behave));
    
    if ($code) http_response_code($code);                           // Set status last so out() overrides any response_code passed by queued header() calls.


    if ($body !== null && ($code === 0 || ($code >= 200 && $code != 204 && $code != 205 && $code != 304)))
        echo $body;
}// effects: calls header(), http_response_code() and echo

function headers(int $code_behave, ?string $field = null, ?string $token = null): iterable
{// stage, validate, and manage HTTP headers (set/add/CSV/readonly) with optional status-code tracking, and emit/clear them when requested
    static $headers = [];

    $code   = $code_behave &  HTTP_CODE_MASK;                                                  // response code (0 => "do not set")
    $behave = $code_behave & ~HTTP_CODE_MASK;                                                  // behavior only

    if (EMIT & $behave) {                                                                      // emit mode: yield all staged headers and clear
        $backups = $headers;
        $headers = [LAST_CODE => ($backups[LAST_CODE] ?? 0)];
        return _emit_headers($backups);
    }
    
    $mode = MODE_MASK & $behave;
    ($mode && ($mode & ($mode - 1)) === 0)                          || throw new \BadFunctionCallException('SET, ADD or CSV, no combos');
    
    ($code === 0 || ($code >= 100 && $code <= 599))                 || throw new \InvalidArgumentException(__FUNCTION__.":invalid_http_status#{$code}");
    
    $canon = _rfc_compliance($behave, $code, (string)$field, (string)$token);
    $canon = KEEP_CASE & $behave  ? $canon : strtolower($canon);

    if (isset($headers[READ_ONLY][$canon]) && $headers[READ_ONLY][$canon] === true)
        throw new \LogicException("header '{$field}' is read only");

    if (strtolower($canon) === 'set-cookie')
         $behave = ($behave | ADD | NO_REPLACE) & ~(SET | CSV);                                // set-cookie is always multi-valued append-only

    (READ_ONLY & $behave) && ($headers[READ_ONLY][$canon] = true);                             // lock field-name after this write

    $code && ($headers[LAST_CODE] = $code);
    $header_param = ["$canon: $token", !(NO_REPLACE & $behave), $code];                        // header(...$header_param)

    (SET & $behave) && ($headers[SET][$canon] = $header_param);                                // single-valued header
    (ADD & $behave) && ($headers[ADD][$canon][] = $header_param);                              // multi-line header

    if (CSV & $behave) {                                                                       // merge values on emit
        $replace = !(NO_REPLACE & $behave);

        if (!isset($headers[CSV][$canon]))
            $headers[CSV][$canon] = [$canon, [], $replace, $code];
        
        $headers[CSV][$canon][1][] = $token;
        
        if(!(KEEP_FIRST & $behave)){
            $headers[CSV][$canon][0] = $canon;
            $headers[CSV][$canon][2] = $replace;
            $headers[CSV][$canon][3] = $code;
        }
    }
    return $headers;
}// returns an iterable of staged headers (and, in emit mode, an iterable yielding the staged headers while clearing them)

function _rfc_compliance($behave, $code, $field, $token): string
{// called from headers() only, use at own risk
    $canon = trim((string)$field);
    try{
        $canon === $field                                                                      || throw new \InvalidArgumentException('field name must not include leading/trailing whitespace');
        $canon !== ''                                                                          || throw new \InvalidArgumentException('field name is empty');
        (!isset($canon[strspn($canon, RFC_TCHAR)]))                                            || throw new \InvalidArgumentException('field name must be a token (tchar alphabet)');
        (!isset($token[strspn($token, HTTP_VALUE_SAFE_BYTES)]))                                || throw new \InvalidArgumentException('field value must be VCHAR/obs-text plus SP/HTAB (OWS)');
        (strpbrk($token, RFC_CTL_NO_HTAB) === false)                                           || throw new \InvalidArgumentException('field value forbids CR/LF/NUL and other CTL');

        ($code === 0 || ($code >= 100 && $code <= 599))                                        || throw new \InvalidArgumentException("invalid http status {$code}");
    }
    catch(\InvalidArgumentException $e){
        if((E_NOTICE | E_WARNING) & $behave)
            trigger_error($e->getMessage(), E_NOTICE & $behave ? E_USER_NOTICE : E_USER_WARNING);

        (E_IGNORE & $behave) || throw $e;
    }
    return $canon;
}// returns a canonical field name

function _emit_headers(array $backups): iterable
{// called from headers() only, use at own risk
    foreach (($backups[SET] ?? []) as $header_param)
        yield $header_param;

    foreach (($backups[CSV] ?? []) as $header_param_values) {
        [$field, $values, $replace, $code] = $header_param_values;
        yield [$field . ': ' . implode(', ', $values), $replace, $code];
    }
    foreach (($backups[ADD] ?? []) as $header_param_list)
        foreach ($header_param_list as $header_param)
            yield $header_param;
}// yields header() call params as array, ready for header(...params)