<?php

namespace bad\rfc;

// RFC 9110 (HTTP) alphabets (ASCII + obs-text)
const RFC_H_TCHAR     = "!#$%&'*+-.^_`|~0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"; // RFC 9110 §5.1 (field name token chars)
const RFC_H_VCHAR     = "!\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~"; // RFC 9110 §5.5 (field value VCHAR)
const RFC_H_OBS_TEXT  = "\x80\x81\x82\x83\x84\x85\x86\x87\x88\x89\x8A\x8B\x8C\x8D\x8E\x8F\x90\x91\x92\x93\x94\x95\x96\x97\x98\x99\x9A\x9B\x9C\x9D\x9E\x9F\xA0\xA1\xA2\xA3\xA4\xA5\xA6\xA7\xA8\xA9\xAA\xAB\xAC\xAD\xAE\xAF\xB0\xB1\xB2\xB3\xB4\xB5\xB6\xB7\xB8\xB9\xBA\xBB\xBC\xBD\xBE\xBF\xC0\xC1\xC2\xC3\xC4\xC5\xC6\xC7\xC8\xC9\xCA\xCB\xCC\xCD\xCE\xCF\xD0\xD1\xD2\xD3\xD4\xD5\xD6\xD7\xD8\xD9\xDA\xDB\xDC\xDD\xDE\xDF\xE0\xE1\xE2\xE3\xE4\xE5\xE6\xE7\xE8\xE9\xEA\xEB\xEC\xED\xEE\xEF\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9\xFA\xFB\xFC\xFD\xFE\xFF";
const RFC_H_OWS       = " \t"; // SP and HTAB
const RFC_H_VALUE_SAFE_BYTES = RFC_H_VCHAR . RFC_H_OBS_TEXT . RFC_H_OWS;

// RFC 9110 §5.5; RFC 5234 App B.1 — CTL excluding HTAB (so HTAB is allowed via OWS)
const RFC_H_CTL_NO_HTAB  =
    "\x00\x01\x02\x03\x04\x05\x06\x07\x08"
  . "\x0A\x0B\x0C\x0D\x0E\x0F"
  . "\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F"
  . "\x7F";

// RFC 3986 (URI) alphabets (ASCII only)
const RFC_U_HEX        = "0123456789ABCDEFabcdef";
const RFC_U_UNRESERVED = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~";
const RFC_U_SUBDELIMS  = "!$&'()*+,;=";

const RFC_U_PCHAR      = RFC_U_UNRESERVED . RFC_U_SUBDELIMS . ":@"; // pchar = unreserved / pct-encoded / sub-delims / ":" / "@"
const RFC_U_PATH_SAFE  = RFC_U_PCHAR . "/%";                        // path = *( "/" segment ) ; segment = *pchar

function field_name(string $canon): array
{
    ($canon !== '')                                                 || throw new \InvalidArgumentException('field name is empty');
    !isset($canon[\strspn($canon, RFC_H_TCHAR)])                    || throw new \InvalidArgumentException('field name must be a token (tchar alphabet)');
    return ['canon' => $canon, 'key' => \strtolower($canon)];
}

function field_value(string $value, bool $allow_blank = false): string
{
    $len = \strlen($value);
    if (!$allow_blank) {
        ($len !== 0 && \strspn($value, RFC_H_OWS) !== $len)         || throw new \InvalidArgumentException('field value is empty/OWS-only');
    }
    !isset($value[\strspn($value, RFC_H_VALUE_SAFE_BYTES)])         || throw new \InvalidArgumentException('field value must be VCHAR/obs-text plus SP/HTAB');
    \strpbrk($value, RFC_H_CTL_NO_HTAB) === false                   || throw new \InvalidArgumentException('field value forbids CR/LF/NUL and other CTL');
    return $value;
}