<?php

namespace bad\form;

const TOKEN_FIELD = '__badhat';
const TRAP_PREFIX = '__badhat_';

const TOKEN     = 0;                                                 // issue() result: signed form mark
const HONEYPOT  = 1;                                                 // issue() result: per-mark honeypot field name

const FAULTS    = 0;                                                 // verify()/shape() result: fault bitmask
const DATA      = 1;                                                 // verify()/shape() result: accepted expected fields
const META      = 2;                                                 // verify()/shape() result: internal details

const ALLOW_UNKNOWN = 1 << 16;                                      // shape(): ignore unknown scalar fields
const ALLOW_UPLOADS = 2 << 16;                                      // verify(): delegate expected upload handling to the application

const BAD_TOKEN         = 1;
const EXPIRED_TOKEN     = 2;
const HONEYPOT_TRIPPED  = 4;
const TOO_FAST          = 8;
const MISSING_FIELD     = 16;
const NON_SCALAR        = 32;
const BAD_UTF8          = 64;
const NULL_BYTE         = 128;
const UNKNOWN_FIELD     = 256;
const RATE_LIMITED      = 512;
const UNEXPECTED_UPLOAD = 1024;

const TOKEN_VERSION = '1';
const SECRET_BYTES  = 32;
const NONCE_BYTES   = 16;

function issue(string $purpose, string $secret, int $ttl = 900, ?int $now = null): array
{
    _guard($purpose, $secret);

    $now ??= \time();
    $now >= 0                                                      || throw new \InvalidArgumentException('issue:now negative');
    $ttl > 0                                                       || throw new \InvalidArgumentException('issue:ttl not positive');
    $now <= \PHP_INT_MAX - $ttl                                   || throw new \InvalidArgumentException('issue:ttl overflow');

    $binding = _binding(true);
    $expires = $now + $ttl;
    $body = TOKEN_VERSION . '.' . $now . '.' . $expires . '.' . \bin2hex(\random_bytes(NONCE_BYTES));
    $mac = _digest('token', $purpose, $binding, $body, $secret);

    return [$body . '.' . $mac, _trap($purpose, $binding, $body, $secret)];
}

function verify(
    string $purpose,
    string $secret,
    array $input,
    array $expected,
    array $required = [],
    int $minimum = 0,
    int $behave = 0,
    iterable $limiters = [],
    ?array $files = null,
    ?int $now = null
): array {
    _guard($purpose, $secret);

    $minimum >= 0                                                  || throw new \InvalidArgumentException('verify:minimum negative');
    $now ??= \time();
    $now >= 0                                                      || throw new \InvalidArgumentException('verify:now negative');

    $meta = [
        'issued_at' => null,
        'expires_at' => null,
        'age' => null,
        'retry_after' => 0,
        'missing' => [],
        'unknown' => [],
        'non_scalar' => [],
        'bad_utf8' => [],
        'null_byte' => [],
        'uploads' => [],
    ];

    $wire = $input[TOKEN_FIELD] ?? null;
    if (!\is_string($wire) || \strlen($wire) > 192 || \strpos($wire, "\0") !== false)
        return [BAD_TOKEN, [], $meta];

    $parts = \explode('.', $wire);
    if (\count($parts) !== 5)
        return [BAD_TOKEN, [], $meta];

    [$version, $issued_raw, $expires_raw, $nonce, $mac] = $parts;
    if ($version !== TOKEN_VERSION
     || !\preg_match('/\A[0-9]{1,19}\z/', $issued_raw)
     || !\preg_match('/\A[0-9]{1,19}\z/', $expires_raw)
     || !\preg_match('/\A[a-f0-9]{32}\z/', $nonce)
     || !\preg_match('/\A[a-f0-9]{64}\z/', $mac))
        return [BAD_TOKEN, [], $meta];

    $binding = _binding(false);
    $body = $version . '.' . $issued_raw . '.' . $expires_raw . '.' . $nonce;
    if ($binding === null || !\hash_equals(_digest('token', $purpose, $binding, $body, $secret), $mac))
        return [BAD_TOKEN, [], $meta];

    $issued = (int)$issued_raw;
    $expires = (int)$expires_raw;
    if ((string)$issued !== $issued_raw || (string)$expires !== $expires_raw)
        return [BAD_TOKEN, [], $meta];

    $meta['issued_at'] = $issued;
    $meta['expires_at'] = $expires;
    $meta['age'] = $now - $issued;

    $faults = 0;
    if ($expires <= $issued || $issued > $now)                      $faults |= BAD_TOKEN;
    else {
        if ($now >= $expires)                                      $faults |= EXPIRED_TOKEN;
        if ($now - $issued < $minimum)                             $faults |= TOO_FAST;
    }

    $honeypot = _trap($purpose, $binding, $body, $secret);
    if (!\array_key_exists($honeypot, $input)
     || !\is_string($input[$honeypot])
     || $input[$honeypot] !== '')                                  $faults |= HONEYPOT_TRIPPED;

    unset($input[TOKEN_FIELD], $input[$honeypot]);

    $shaped = shape($input, $expected, $required, $behave);
    $faults |= $shaped[FAULTS];
    $data = $shaped[DATA];
    $meta = \array_replace($meta, $shaped[META]);

    $files ??= isset($_FILES) && \is_array($_FILES) ? $_FILES : [];
    foreach ($files as $raw_name => $_file) {
        $name = (string)$raw_name;
        $label = _label($name);
        $meta['uploads'][] = $label;

        if (\strpos($name, "\0") !== false) {
            $faults |= NULL_BYTE;
            $meta['null_byte'][] = $label;
        }
        elseif (\preg_match('//u', $name) !== 1) {
            $faults |= BAD_UTF8;
            $meta['bad_utf8'][] = $label;
        }
    }
    if ($files && !(ALLOW_UPLOADS & $behave))                       $faults |= UNEXPECTED_UPLOAD;

    if ($faults === 0) {
        $retry_after = 0;
        foreach ($limiters as $i => $limiter) {
            \is_callable($limiter)
                || throw new \InvalidArgumentException("verify:limiter:$i:not callable");

            $wait = $limiter($purpose, $data, $now);
            \is_int($wait) && $wait >= 0
                || throw new \UnexpectedValueException("verify:limiter:$i:bad result");

            $retry_after = \max($retry_after, $wait);
        }

        if ($retry_after > 0)                                      $faults |= RATE_LIMITED;
        $meta['retry_after'] = $retry_after;
    }

    return [$faults, $data, $meta];
}

function shape(array $input, array $expected, array $required = [], int $behave = 0): array
{
    $allowed = _field_set($expected, 'shape:expected');
    $needed = _field_set($required, 'shape:required');

    foreach ($needed as $slot => $name)
        isset($allowed[$slot])
            || throw new \InvalidArgumentException("shape:required:$name:not expected");

    $faults = 0;
    $data = [];
    $seen = [];
    $meta = [
        'missing' => [],
        'unknown' => [],
        'non_scalar' => [],
        'bad_utf8' => [],
        'null_byte' => [],
    ];

    foreach ($input as $raw_name => $value) {
        $name = (string)$raw_name;
        $slot = ':' . $name;
        $known = isset($allowed[$slot]);
        if ($known) $seen[$slot] = true;

        if (\strpos($name, "\0") !== false) {
            $faults |= NULL_BYTE;
            $meta['null_byte'][] = _label($name);
            continue;
        }
        if (\preg_match('//u', $name) !== 1) {
            $faults |= BAD_UTF8;
            $meta['bad_utf8'][] = _label($name);
            continue;
        }
        if (!\is_string($value)) {
            $faults |= NON_SCALAR;
            $meta['non_scalar'][] = _label($name);
            if (!$known) {
                $faults |= UNKNOWN_FIELD;
                $meta['unknown'][] = _label($name);
            }
            continue;
        }
        if (\strpos($value, "\0") !== false) {
            $faults |= NULL_BYTE;
            $meta['null_byte'][] = _label($name);
            continue;
        }
        if (\preg_match('//u', $value) !== 1) {
            $faults |= BAD_UTF8;
            $meta['bad_utf8'][] = _label($name);
            continue;
        }
        if (!$known) {
            if (!(ALLOW_UNKNOWN & $behave)) {
                $faults |= UNKNOWN_FIELD;
                $meta['unknown'][] = _label($name);
            }
            continue;
        }

        $data[$name] = $value;
    }

    foreach ($needed as $slot => $name)
        if (!isset($seen[$slot])) {
            $faults |= MISSING_FIELD;
            $meta['missing'][] = _label($name);
        }

    return [$faults, $data, $meta];
}

function rate(array &$store, string $key, int $limit, int $window, ?int $now = null): int
{
    $key !== ''                                                     || throw new \InvalidArgumentException('rate:key empty');
    \strpos($key, "\0") === false                                 || throw new \InvalidArgumentException('rate:key null byte');
    $limit > 0                                                      || throw new \InvalidArgumentException('rate:limit not positive');
    $window > 0                                                     || throw new \InvalidArgumentException('rate:window not positive');

    $now ??= \time();
    $now >= 0                                                       || throw new \InvalidArgumentException('rate:now negative');
    $now <= \PHP_INT_MAX - $window                                 || throw new \InvalidArgumentException('rate:window overflow');

    $slot = 'r:' . \hash('sha256', $key . "\0" . $limit . "\0" . $window);
    $bucket = $store[$slot] ?? null;

    if (!\is_array($bucket)
     || !isset($bucket[0], $bucket[1])
     || !\is_int($bucket[0])
     || !\is_int($bucket[1])
     || $bucket[0] < 0
     || $bucket[0] > \PHP_INT_MAX - $window
     || $bucket[1] < 0
     || $now < $bucket[0]
     || $now >= $bucket[0] + $window)
        $bucket = [$now, 0];                                       // [window start, accepted count]

    if ($bucket[1] >= $limit) {
        $store[$slot] = $bucket;
        return \max(1, $bucket[0] + $window - $now);
    }

    ++$bucket[1];
    $store[$slot] = $bucket;
    return 0;
}

function session_rate(string $key, int $limit, int $window, ?int $now = null): int
{
    \session_status() === \PHP_SESSION_ACTIVE
        || throw new \LogicException('session_rate:session not active');

    if (!isset($_SESSION[__NAMESPACE__]) || !\is_array($_SESSION[__NAMESPACE__]))
        $_SESSION[__NAMESPACE__] = [];
    if (!isset($_SESSION[__NAMESPACE__][__FUNCTION__]) || !\is_array($_SESSION[__NAMESPACE__][__FUNCTION__]))
        $_SESSION[__NAMESPACE__][__FUNCTION__] = [];

    return rate($_SESSION[__NAMESPACE__][__FUNCTION__], $key, $limit, $window, $now);
}

function _guard(string $purpose, string $secret): void
{
    $purpose !== ''                                                 || throw new \InvalidArgumentException('form:purpose empty');
    \strpos($purpose, "\0") === false                              || throw new \InvalidArgumentException('form:purpose null byte');
    \preg_match('//u', $purpose) === 1                              || throw new \InvalidArgumentException('form:purpose bad utf8');
    \strlen($secret) >= SECRET_BYTES                                || throw new \InvalidArgumentException('form:secret too short');
    \session_status() === \PHP_SESSION_ACTIVE                       || throw new \LogicException('form:session not active');
}

function _binding(bool $create): ?string
{
    if (!isset($_SESSION[__NAMESPACE__]) || !\is_array($_SESSION[__NAMESPACE__])) {
        if (!$create) return null;
        $_SESSION[__NAMESPACE__] = [];
    }

    $value = $_SESSION[__NAMESPACE__]['binding'] ?? null;
    if (\is_string($value) && \preg_match('/\A[a-f0-9]{64}\z/', $value))
        return $value;

    if (!$create) return null;
    return $_SESSION[__NAMESPACE__]['binding'] = \bin2hex(\random_bytes(32));
}

function _digest(string $kind, string $purpose, string $binding, string $body, string $secret): string
{
    return \hash_hmac('sha256', $kind . "\0" . $purpose . "\0" . $binding . "\0" . $body, $secret);
}

function _trap(string $purpose, string $binding, string $body, string $secret): string
{
    return TRAP_PREFIX . \substr(_digest('honeypot', $purpose, $binding, $body, $secret), 0, 24);
}

function _label(string $name): string
{
    return \preg_match('/\A[^\x00-\x1F\x7F]*\z/u', $name) === 1
        ? $name
        : 'hex:' . \bin2hex($name);
}

function _field_set(array $fields, string $caller): array
{
    $set = [];
    foreach ($fields as $i => $name) {
        \is_string($name)                                           || throw new \InvalidArgumentException("$caller:$i:not string");
        $name !== ''                                                || throw new \InvalidArgumentException("$caller:$i:empty");
        \strpos($name, "\0") === false                             || throw new \InvalidArgumentException("$caller:$i:null byte");
        \preg_match('//u', $name) === 1                             || throw new \InvalidArgumentException("$caller:$i:bad utf8");
        $name !== TOKEN_FIELD && !\str_starts_with($name, TRAP_PREFIX)
            || throw new \InvalidArgumentException("$caller:$i:reserved");

        $slot = ':' . $name;
        !isset($set[$slot])                                         || throw new \InvalidArgumentException("$caller:$i:duplicate");
        $set[$slot] = $name;
    }
    return $set;
}
