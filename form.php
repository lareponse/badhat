<?php

namespace bad\form;

const VALID = 0;                                                      // structured result: bool
const CODE  = 1;                                                      // structured result: stable internal code
const DATA  = 2;                                                      // structured result: bounded metadata, never submitted values

const REJECT_UNKNOWN = 1;                                             // shape(): reject fields outside required + optional

const TTL_MAX           = (1 << 28) - 1;
const TOKEN_VERSION     = '1';
const TOKEN_NONCE_BYTES = 16;
const TOKEN_MAX_BYTES   = 256;
const SECRET_MIN_BYTES  = 32;
const NAME_MAX_BYTES    = 255;
const CODE_MAX_BYTES    = 128;
const CLOCK_SKEW        = 5;

function result(bool $valid, string $code = 'ok', array $data = []): array
{
    ($code !== '' && \strlen($code) <= CODE_MAX_BYTES && text_valid($code))
                                                                        || throw new \InvalidArgumentException('form:result:code invalid');
    return [VALID => $valid, CODE => $code, DATA => $data];
}

function verify(callable ...$checks): array
{// execute checks in order; each receives the previous successful result and failures short-circuit
    $current = result(true);

    foreach ($checks as $check) {
        $next = $check($current);
        \is_array($next)                                              || throw new \UnexpectedValueException('form:verify:check did not return array');
        result_guard($next);
        $current = $next;
        if (!$current[VALID])
            break;
    }

    return $current;
}

function token(string $purpose, string $secret, int $ttl = 900, ?int $now = null): string
{// issue a reusable-until-expiry CSRF token bound to purpose, secret and the active session
    purpose_guard($purpose);
    secret_guard($secret);
    $session = session_binding();
    ($ttl > 0 && $ttl <= TTL_MAX)                                     || throw new \InvalidArgumentException('form:token:ttl out of range');

    $now ??= \time();
    $now >= 0                                                         || throw new \InvalidArgumentException('form:token:time negative');
    $now <= \PHP_INT_MAX - $ttl                                      || throw new \InvalidArgumentException('form:token:time overflow');

    $expires = $now + $ttl;
    $nonce = \bin2hex(\random_bytes(TOKEN_NONCE_BYTES));
    $payload = TOKEN_VERSION . '.' . $now . '.' . $expires . '.' . $nonce;
    $mac = \hash_hmac('sha256', signing_input($payload, $purpose, $session), $secret);

    return $payload . '.' . $mac;
}

function csrf($token, string $purpose, string $secret, ?int $now = null): array
{// verify signature, session binding, purpose binding and expiration without consuming the token
    purpose_guard($purpose);
    secret_guard($secret);
    $session = session_binding();

    if ($token === null || $token === '')                             return result(false, 'csrf:missing');
    if (!\is_string($token) || \strlen($token) > TOKEN_MAX_BYTES)    return result(false, 'csrf:malformed');

    $parts = \explode('.', $token);
    if (\count($parts) !== 5)                                        return result(false, 'csrf:malformed');

    [$version, $issued_raw, $expires_raw, $nonce, $mac] = $parts;
    if ($version !== TOKEN_VERSION)                                  return result(false, 'csrf:malformed');

    $issued = decimal($issued_raw);
    $expires = decimal($expires_raw);
    if ($issued === null || $expires === null || $expires <= $issued
     || $expires - $issued > TTL_MAX)                                  return result(false, 'csrf:malformed');
    if (\strlen($nonce) !== TOKEN_NONCE_BYTES * 2
     || \strspn($nonce, '0123456789abcdef') !== TOKEN_NONCE_BYTES * 2)return result(false, 'csrf:malformed');
    if (\strlen($mac) !== 64 || \strspn($mac, '0123456789abcdef') !== 64)
                                                                        return result(false, 'csrf:malformed');

    $payload = $version . '.' . $issued_raw . '.' . $expires_raw . '.' . $nonce;
    $expected = \hash_hmac('sha256', signing_input($payload, $purpose, $session), $secret);
    if (!\hash_equals($expected, $mac))                               return result(false, 'csrf:invalid');

    $now ??= \time();
    $now >= 0                                                         || throw new \InvalidArgumentException('form:csrf:time negative');
    if ($issued > $now && $issued - $now > CLOCK_SKEW)                return result(false, 'csrf:future');
    if ($now >= $expires)                                             return result(false, 'csrf:expired');

    return result(true, 'ok', [
        'issued_at'  => $issued,
        'expires_at' => $expires,
        'age'        => \max(0, $now - $issued),
    ]);
}

function timing(array $csrf, int $minimum): array
{// enforce a minimum elapsed time using the signed issue time returned by csrf()
    $minimum >= 0                                                     || throw new \InvalidArgumentException('form:timing:minimum negative');
    result_guard($csrf);
    if (!$csrf[VALID])
        return $csrf;

    $age = $csrf[DATA]['age'] ?? null;
    \is_int($age) && $age >= 0                                       || throw new \UnexpectedValueException('form:timing:csrf age missing');

    if ($age < $minimum)
        return result(false, 'timing:too_fast', ['minimum' => $minimum, 'age' => $age]);

    $data = $csrf[DATA];
    $data['minimum'] = $minimum;
    return result(true, 'ok', $data);
}

function honeypot(array $input, string $field): array
{// require the trap field to exist, remain scalar and remain exactly empty
    name_guard($field, 'honeypot');

    if (!\array_key_exists($field, $input))                           return result(false, 'honeypot:missing', ['field' => $field]);
    if (!\is_string($input[$field]))                                 return result(false, 'honeypot:non_scalar', ['field' => $field]);
    if ($input[$field] !== '')                                       return result(false, 'honeypot:filled', ['field' => $field]);

    return result(true);
}

function shape(array $input, array $required, array $optional = [], int $behave = 0): array
{// enforce expected names and string/UTF-8/null-byte shape; length and semantic checks stay with the application
    $required = name_set($required, 'required');
    $optional = name_set($optional, 'optional');

    foreach ($optional as $field => $_)
        !isset($required[$field])                                    || throw new \InvalidArgumentException("form:shape:duplicate field:$field");

    $allowed = $required + $optional;

    foreach ($input as $field => $value) {
        $field = (string)$field;
        $known = isset($allowed[$field]);
        if ((REJECT_UNKNOWN & $behave) && !$known)                   return result(false, 'shape:unknown');
        if (\strlen($field) > NAME_MAX_BYTES || !text_valid($field)) return result(false, 'shape:field_name');

        $data = $known ? ['field' => $field] : [];
        if (!\is_string($value))                                     return result(false, 'shape:non_scalar', $data);
        if (\strpos($value, "\0") !== false)                        return result(false, 'shape:null_byte', $data);
        if (\preg_match('//u', $value) !== 1)                        return result(false, 'shape:utf8', $data);
    }

    foreach ($required as $field => $_)
        if (!\array_key_exists($field, $input))                       return result(false, 'shape:missing', ['field' => $field]);

    return result(true, 'ok', ['fields' => \count($input)]);
}

function rate(array &$buckets, string $key, int $limit, int $window, ?int $now = null): array
{// fixed-window primitive; caller owns persistence and atomicity of the bucket array
    name_guard($key, 'rate key');
    $limit > 0                                                        || throw new \InvalidArgumentException('form:rate:limit not positive');
    $window > 0                                                       || throw new \InvalidArgumentException('form:rate:window not positive');

    $now ??= \time();
    $now >= 0                                                         || throw new \InvalidArgumentException('form:rate:time negative');
    $now <= \PHP_INT_MAX - $window                                   || throw new \InvalidArgumentException('form:rate:time overflow');

    $bucket = $buckets[$key] ?? null;
    if ($bucket !== null) {
        \is_array($bucket)
        && \is_int($bucket['count'] ?? null)
        && \is_int($bucket['reset_at'] ?? null)
        && \is_int($bucket['window'] ?? null)
        && $bucket['count'] >= 0
        && $bucket['reset_at'] >= 0
        && $bucket['window'] > 0                                     || throw new \UnexpectedValueException("form:rate:bucket invalid:$key");
    }

    if ($bucket === null || $bucket['reset_at'] <= $now || $bucket['window'] !== $window)
        $bucket = ['count' => 0, 'reset_at' => $now + $window, 'window' => $window];

    if ($bucket['count'] <= $limit)
        ++$bucket['count'];                                           // cap naturally at limit + 1 while blocked

    $buckets[$key] = $bucket;

    if ($bucket['count'] > $limit)
        return result(false, 'rate:limited', [
            'limit'       => $limit,
            'reset_at'    => $bucket['reset_at'],
            'retry_after' => \max(1, $bucket['reset_at'] - $now),
        ]);

    return result(true, 'ok', [
        'limit'     => $limit,
        'remaining' => $limit - $bucket['count'],
        'reset_at'  => $bucket['reset_at'],
    ]);
}

function session_rate(string $key, int $limit, int $window, ?int $now = null): array
{// session-scoped wrapper around rate(); different keys compose independent form/application limits
    \session_status() === \PHP_SESSION_ACTIVE                        || throw new \LogicException('form:session_rate:session not active');

    $_SESSION[__NAMESPACE__] ??= [];
    \is_array($_SESSION[__NAMESPACE__])                              || throw new \UnexpectedValueException('form:session scope invalid');
    $_SESSION[__NAMESPACE__]['rate'] ??= [];
    \is_array($_SESSION[__NAMESPACE__]['rate'])                      || throw new \UnexpectedValueException('form:session rate scope invalid');

    $buckets = &$_SESSION[__NAMESPACE__]['rate'];
    return rate($buckets, $key, $limit, $window, $now);
}

function result_guard(array $result): void
{
    \array_key_exists(VALID, $result)
    && \array_key_exists(CODE, $result)
    && \array_key_exists(DATA, $result)
    && \is_bool($result[VALID])
    && \is_string($result[CODE])
    && $result[CODE] !== ''
    && \strlen($result[CODE]) <= CODE_MAX_BYTES
    && text_valid($result[CODE])
    && \is_array($result[DATA])                                      || throw new \UnexpectedValueException('form:result malformed');
}

function purpose_guard(string $purpose): void
{
    name_guard($purpose, 'purpose');
}

function secret_guard(string $secret): void
{
    \strlen($secret) >= SECRET_MIN_BYTES                             || throw new \InvalidArgumentException('form:secret shorter than 32 bytes');
}

function name_guard(string $name, string $what): void
{
    $name !== ''                                                      || throw new \InvalidArgumentException("form:$what empty");
    \strlen($name) <= NAME_MAX_BYTES                                || throw new \InvalidArgumentException("form:$what too long");
    text_valid($name)                                                 || throw new \InvalidArgumentException("form:$what invalid text");
}

function name_set(array $names, string $what): array
{
    $set = [];
    foreach ($names as $name) {
        \is_string($name)                                            || throw new \InvalidArgumentException("form:shape:$what field not string");
        name_guard($name, "shape:$what field");
        !isset($set[$name])                                          || throw new \InvalidArgumentException("form:shape:duplicate field:$name");
        $set[$name] = true;
    }
    return $set;
}

function text_valid(string $value): bool
{
    return \strpos($value, "\0") === false && \preg_match('//u', $value) === 1;
}

function decimal(string $value): ?int
{
    if ($value === '' || !\ctype_digit($value))
        return null;

    $normalized = \ltrim($value, '0');
    $normalized = $normalized === '' ? '0' : $normalized;
    $max = (string)\PHP_INT_MAX;
    if (\strlen($normalized) > \strlen($max)
     || (\strlen($normalized) === \strlen($max) && \strcmp($normalized, $max) > 0))
        return null;

    return (int)$normalized;
}

function signing_input(string $payload, string $purpose, string $session): string
{
    return \strlen($purpose) . ':' . $purpose . "\0"
         . \strlen($session) . ':' . $session . "\0"
         . $payload;
}

function session_binding(): string
{
    \session_status() === \PHP_SESSION_ACTIVE                        || throw new \LogicException('form:session not active');

    $session = \session_id();
    $session !== ''                                                   || throw new \LogicException('form:session id empty');
    return $session;
}
