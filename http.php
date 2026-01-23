<?php
namespace bad\http;

const H_SET = 1;                                                    // replace (single)
const H_ADD = 2;                                                    // append (lines)
const H_CSV = 4;                                                    // append (csv)
const H_OUT = 8;                                                    // output + clear

const H_LOCK = 16;                                                  // lock (one-time set or no further changes)

const CTRL_ASCII = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x7F"; // RFC 5234, p.14
const HTTP_TCHAR = "!#$%&'*+-.^_`|~0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"; // RFC 9110 token characters

function headers(int $behave, ?string $name = null, $value = null): array
{
    static $headers = [];

    if (H_OUT === $behave) {
        $_ = $headers;
        $headers = [];
        return $_;
    }

    ((H_SET | H_ADD) & $behave) !== (H_SET | H_ADD)                 || throw new \BadFunctionCallException('H_SET and H_ADD are mutually exclusive', 400);
    (strpbrk((string)$value, CTRL_ASCII) === false)                 || throw new \InvalidArgumentException('forbid ASCII controls in field-value (CR/LF/NUL etc.)', 400); //  (RFC 9110 field-value safety)
    $name = strtolower(trim((string)$name))                         // field-name is case-insensitive; normalize to lowercase for map keys (RFC 9110)
    $name                                                           || throw new \InvalidArgumentException('header name cannot be empty', 400);
    (!isset($name[strspn($name, HTTP_TCHAR)]))                      || throw new \InvalidArgumentException('field-name must be a token (tchar alphabet)', 400); //(RFC 9110 token / field-name)
    (($headers[H_LOCK][$name] ?? false) !== true)                   || throw new \BadFunctionCallException("header '{$name}' is locked, further writes are rejected", 400);

    ($name === 'set-cookie')                                        // Set-Cookie must not be combined into a single CSV header line
        && ($behave & H_ADD) && !($behave & (H_SET | H_CSV))        
        && (($behave & ~(H_ADD | H_LOCK)) === 0)                    || throw new \InvalidArgumentException('Set-Cookie requires H_ADD and optionally H_LOCK', 400);

    if (H_SET & $behave)
        $headers[H_SET][$name] = $value;                            // single-valued header (last write wins unless locked)

    if ((H_ADD | H_CSV) & $behave) {                                // multi-valued header: store values for later emission/merging
        $store = H_CSV & $behave ? H_CSV : H_ADD;                   // select storage type
        $headers[$store][$name][] = $value;
    }

    (H_LOCK & $behave) && ($headers[H_LOCK][$name] = true);         // lock header-name after this write (one-time set)

    return $headers;                                                // convenience: inspect current staged headers
}

function in($url = null): string
{
    $url ??= $_SERVER['REQUEST_URI'] ?? '/';                        // request-target (usually origin-form): "/path?query" (RFC 9112 §3.2.1; RFC 9110 §7.1)

    $end = strcspn($url, ':/?#');                                   // locate first of ": / ? #" to detect "scheme:" before any "/?#" (absolute-form)
    if (isset($url[$end]) && $url[$end] === ':')                    // if ":" wins, input looks like "scheme:..." (RFC 9110 request-target absolute-form)
        $url = substr($url, $end + 1);                              // drop "scheme:" => leaves possible "//authority/path?query"

    if (isset($url[1]) && $url[1] === '/' && $url[0] === '/') {     // leading "//" => authority present (authority-form / absolute-form authority prefix)
        $end = strcspn($url, '/?#', 2) + 2;                         // skip authority up to next "/?#" (RFC 9110 §7.1 request-target forms)
        $url = isset($url[$end]) ? substr($url, $end) : '';         // keep origin-form tail: "/path?query" (or "" if only authority)
    }

    return $url;                                                    // normalized request-target: no scheme, no authority (router-friendly)
}

function out($code, $body = null, $header = null): void
{
    http_response_code($code);                                      // set the status line before emitting headers (PHP headers_sent rule)

    $headers = headers(H_OUT);                                      // capture and clear headers
    foreach (($headers[H_SET] ?? []) as $n => $v)                   header($n . ': ' . $v, false);
    foreach (($headers[H_CSV] ?? []) as $n => $vals)                header($n . ': ' . implode(', ', $vals), false);
    foreach (($headers[H_ADD] ?? []) as $n => $vals)
        foreach ($vals as $v)                                       header($n . ': ' . $v, false);
    if ($header)                                                    header($header);

    if ($body === null || $code < 200 || $code == 204 || $code == 205 || $code == 304) 
        return;                                                     // RFC 7230: bodyless status codes 

    echo $body;
}

function csp_nonce($bytes = 16): string
{
    static $nonce;
    return $nonce ??= bin2hex(random_bytes($bytes));
}