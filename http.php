<?php

namespace bad\http;

const ASCII_CTL = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x7F";
const HTTP_PATH_UNSAFE = ' ' . ASCII_CTL;
const HTTP_TCHAR = '!#$%&\'*+-.^_`|~0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

function http_headers(string $name, string $value, bool $replace = true): ?array
{
    static $headers = [];

    if ($name === '' || isset($name[strspn($name, HTTP_TCHAR)])) // char exists past valid span
        return null;

    if (strpbrk($value, ASCII_CTL) !== false)
        return null;

    $replace ? ($headers[$name] = [$value]) : ($headers[$name][] = $value);

    return $headers;
}

function http_in(string $url): string
{
    $end = strcspn($url, ':/?#');                           // scheme detection (strcspn ensures : appears before any /?#)
    if (isset($url[$end]) && $url[$end] === ':')
        $url = substr($url, $end + 1);                      // scheme removal

    if (isset($url[1]) && $url[1] === $url[0] && $url[0] === '/') {
        $end = strcspn($url, '/?#', 2) + 2;
        $url = isset($url[$end]) ? substr($url, $end) : '';
    }

    return $url;
}

function http_out(int $code, ?string $body = null, array $headers = [])
{
    http_response_code($code);
    foreach ($headers as $name => $values)
        foreach ((array)$values as $v)
            header("$name: $v", false);

    if ($body !== null && $code >= 200 && $code !== 204 && $code !== 205 && $code !== 304) // RFC 7230: bodyless status codes
        echo $body;

    return $code < 400 ? 0 : ($code < 500 ? 4 : ($code < 600 ? 5 : 1));
}

function csp_nonce(): string
{
    static $nonce = null;
    return $nonce ??= bin2hex(random_bytes(16));
}
