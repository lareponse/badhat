<?php

namespace bad\rfc;

const ALPHANUM = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

// HTTP Headers: RFC 9110 + RFC 5234 (HTTP) alphabets (ASCII + obs-text)
const RFC_TCHAR     = ALPHANUM . "!#$%&'*+-.^_`|~";
const RFC_VCHAR     = ALPHANUM . "!\"#$%&'()*+,-./:;<=>?@[\\]^_`{|}~";
const RFC_OBS_TEXT  = "\x80\x81\x82\x83\x84\x85\x86\x87\x88\x89\x8A\x8B\x8C\x8D\x8E\x8F"
                    . "\x90\x91\x92\x93\x94\x95\x96\x97\x98\x99\x9A\x9B\x9C\x9D\x9E\x9F"
                    . "\xA0\xA1\xA2\xA3\xA4\xA5\xA6\xA7\xA8\xA9\xAA\xAB\xAC\xAD\xAE\xAF"
                    . "\xB0\xB1\xB2\xB3\xB4\xB5\xB6\xB7\xB8\xB9\xBA\xBB\xBC\xBD\xBE\xBF"
                    . "\xC0\xC1\xC2\xC3\xC4\xC5\xC6\xC7\xC8\xC9\xCA\xCB\xCC\xCD\xCE\xCF"
                    . "\xD0\xD1\xD2\xD3\xD4\xD5\xD6\xD7\xD8\xD9\xDA\xDB\xDC\xDD\xDE\xDF"
                    . "\xE0\xE1\xE2\xE3\xE4\xE5\xE6\xE7\xE8\xE9\xEA\xEB\xEC\xED\xEE\xEF"
                    . "\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9\xFA\xFB\xFC\xFD\xFE\xFF";
const RFC_OWS       = " \t";
const RFC_VALUE_SAFE_BYTES = RFC_VCHAR . RFC_OBS_TEXT . RFC_OWS;
const RFC_CTL_NO_HTAB  = "\x00\x01\x02\x03\x04\x05\x06\x07\x08" . "\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F" . "\x7F"; // RFC 9110 §5.5; RFC 5234 App B.1 — CTL excluding HTAB (so HTAB is allowed via OWS)

// HTTP URI: RFC 3986 (URI) alphabets (ASCII only)
const RFC_U_UNRESERVED = ALPHANUM . "-._~";
const RFC_U_SUBDELIMS  = "!$&'()*+,;="; 
const RFC_U_PCHAR      = RFC_U_UNRESERVED . RFC_U_SUBDELIMS . ":@"; 
const RFC_U_PATH_SAFE  = RFC_U_PCHAR . "/%";

const E_THROW = 1;                                                   // throw on invalid input instead of returning exception
const APP_REQUIRE_VALUE = 2;                                        // reject empty / OWS-only (app policy)

function field_name(string $canon, int $behave = 0): string | \DomainException
{
    if ($canon === '')                                              return or_throw($behave, __FUNCTION__, 'empty');
    if (isset($canon[\strspn($canon, RFC_TCHAR)]))                  return or_throw($behave, __FUNCTION__, 'invalid tchar');

    return $canon;
}

function field_value(string $value, int $behave = 0): string | \DomainException
{
    $len = \strlen($value);
    if (APP_REQUIRE_VALUE & $behave)
        if ($len === 0 || \strspn($value, RFC_OWS) === $len)      return or_throw($behave, __FUNCTION__, 'empty or OWS-only');

    if (isset($value[\strspn($value, RFC_VALUE_SAFE_BYTES)]))     return or_throw($behave, __FUNCTION__, 'invalid byte');
    // if (\strpbrk($value, RFC_CTL_NO_HTAB) !== false)              return or_throw($behave, __FUNCTION__, 'forbidden CTL');
    return $value;
}

function url_path(string $path, int $behave = 0): string | \DomainException
{
    if (\strpbrk($path, RFC_CTL_NO_HTAB . RFC_OWS) !== false)   return or_throw($behave, __FUNCTION__, 'forbidden CTL/SP/HTAB');
    if (\strpos($path, "\\") !== false)                             return or_throw($behave, __FUNCTION__, 'backslash forbidden');

    $allowed_no_pct = RFC_U_PCHAR . "/";
    for ($i = 0, $len = \strlen($path); $i < $len; ++$i) {
        $current = $path[$i];
        if ($current === '%') {
            if ($i + 2 >= $len
             || !\ctype_xdigit($path[$i + 1]) 
             || !\ctype_xdigit($path[$i + 2]))                      return or_throw($behave, __FUNCTION__, 'invalid pct-escape');
            $i += 2;
            continue;
        }
        if (\strpos($allowed_no_pct, $current) === false)           return or_throw($behave, __FUNCTION__, 'illegal byte 0x' . \strtoupper(\bin2hex($current)));
    }

    return $path;
}

function or_throw(int $behave, $caller, $message): \DomainException
{
    $t = new \DomainException("$caller:$message");
    return (E_THROW & $behave) ? throw $t : $t;
}