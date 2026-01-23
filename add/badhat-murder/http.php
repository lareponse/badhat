<?php
namespace bad\http;

const H_SET = 1;                                                    // replace (single)
const H_ADD = 2;                                                    // append (lines)
const H_CSV = 4;                                                    // append (csv)
const H_OUT = 8;                                                    // output + clear

const H_LOCK = 16;                                                  // lock (one-time set or no further changes)

const CTRL_ASCII = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x7F";
const HTTP_TCHAR = "!#$%&'*+-.^_`|~0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";

function headers(int $behave, ?string $name = null, $value = null): array
{
    static $headers = [];                                           // [H_SET][name]=value, [H_ONE][name]=true, [H_ADD][name][]=value, [H_CSV][name][]=value

    if ($behave === H_OUT) {
        $_ = $headers;
        $headers = [];
        return $_;
    }

    ((H_SET | H_ADD) & $behave) !== (H_SET | H_ADD)                 || throw new \BadFunctionCallException('H_SET XOR H_ADD', 400);
    (strpbrk((string)$value, CTRL_ASCII) === false)                 || throw new \InvalidArgumentException('invalid ASCII control char in header value', 400);

    $name = strtolower(trim((string)$name))                         ?: throw new \InvalidArgumentException('header name cannot be empty', 400);

    (!isset($name[strspn($name, HTTP_TCHAR)]))                      || throw new \InvalidArgumentException('invalid T_CHAR header name', 400);
    (($headers[H_LOCK][$name] ?? false) !== true)                   || throw new \BadFunctionCallException("header '{$name}' is locked (H_ONE)", 400);

    if ($name === 'set-cookie')
       ($behave & H_ADD) && !($behave & (H_SET | H_CSV)) && (($behave & ~(H_ADD | H_LOCK)) === 0) || throw new \InvalidArgumentException('Set-Cookie requires H_ADD and optionally H_LOCK', 400);

    if (H_SET & $behave)
        $headers[H_SET][$name] = $value;

    if ((H_ADD | H_CSV) & $behave) {
        $store = (H_CSV & $behave || isset($headers[H_CSV][$name])) ? H_CSV : H_ADD; // sticky CSV: once CSV, always CSV
        $headers[$store][$name][] = $value;
    }

    (H_LOCK & $behave) && ($headers[H_LOCK][$name] = true);

    return $headers;
}                                                                   // returns current header as text for convenience


function in($url = null): string
{
    $url ??= $_SERVER['REQUEST_URI'] ?? '/';

    $end = strcspn($url, ':/?#');                                   // scheme detection (strcspn ensures : appears before any /?#)
    if (isset($url[$end]) && $url[$end] === ':')
        $url = substr($url, $end + 1);                              // scheme removal

    if (isset($url[1]) && $url[1] === $url[0] && $url[0] === '/') {
        $end = strcspn($url, '/?#', 2) + 2;
        $url = isset($url[$end]) ? substr($url, $end) : '';
    }

    return $url;
}

function out($code, $body = null, $header = null): void
{
    http_response_code($code);

    $headers = headers(H_OUT);                                      // capture and clear headers
    foreach (($headers[H_SET] ?? []) as $n => $v)                   header($n . ': ' . $v, false);
    foreach (($headers[H_CSV] ?? []) as $n => $vals)                header($n . ': ' . implode(', ', $vals), false);
    foreach (($headers[H_ADD] ?? []) as $n => $vals)
        foreach ($vals as $v)                                       header($n . ': ' . $v, false);
    if ($header)                                                    header($header);

    $body_ok = $body !== null
        && $code >= 200
        && $code !== 204 && $code !== 205 && $code !== 304;         // RFC 7230: bodyless status codes
    if ($body_ok)
        echo $body;
}

function csp_nonce(): string
{
    static $nonce = null;
    return $nonce ??= bin2hex(random_bytes(16));
}
