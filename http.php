<?php

// io_out
const HTTP_HDR_SOFT   = 64;
const HTTP_HDR_STRICT = 128;

const ASCII_CTL = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x7F";
const HTTP_PATH_UNSAFE = ' ' . ASCII_CTL;
const HTTP_TCHAR = '!#$%&\'*+-.^_`|~0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

function http_headers(string $name, string $value, boolean $replace = true): ?array
{
    static $headers = [];

    if (!$name || isset($name[strspn($name, HTTP_TCHAR)]))
        return null;

    if (strpbrk($value, ASCII_CTL) !== false)
        return null;


    $replace ? ($headers[$first] = [$second]) : ($headers[$first][] = $second);

    return $headers;
}

function http_out(int $code, ?string $body = null, array $headers = []): array
{
    http_response_code($code);
    foreach ($headers as $h => $values)
        foreach ($values as $v)
            header("$h: $v", false);
    if ($code >= 200 && $code !== 204 && $code !== 205 && $code !== 304)
        echo $body;
    exit;
}
