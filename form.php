<?php

namespace bad\form;

const REJECT_UNKNOWN = 1;                                           // reject POST keys outside required + optional fields
const ALLOW_FILES    = 2;                                           // caller accepts responsibility for validating $_FILES

const PASS               = 'ok';
const FORM_INVALID       = 'form.invalid';
const TOKEN_MISSING      = 'csrf.missing';
const TOKEN_MALFORMED    = 'csrf.malformed';
const TOKEN_INVALID      = 'csrf.invalid';
const TOKEN_FUTURE       = 'csrf.future';
const TOKEN_EXPIRED      = 'csrf.expired';
const SUBMISSION_EARLY   = 'submission.early';
const HONEYPOT_MISSING   = 'honeypot.missing';
const HONEYPOT_SCALAR    = 'honeypot.scalar';
const HONEYPOT_FILLED    = 'honeypot.filled';
const SHAPE_INVALID      = 'shape.invalid';
const FIELD_MISSING      = 'field.missing';
const FIELD_SCALAR       = 'field.scalar';
const FIELD_UTF8         = 'field.utf8';
const FIELD_NULL         = 'field.null';
const FIELD_UNKNOWN      = 'field.unknown';
const FILE_UNEXPECTED    = 'file.unexpected';
const RATE_LIMITED       = 'rate.limited';

const TOKEN_VERSION      = 'v1';
const TOKEN_NONCE_BYTES  = 16;
const TOKEN_MAC_BYTES    = 32;
const TOKEN_MAX_LENGTH   = 256;
const SECRET_MIN_BYTES   = 32;

function token(string $secret, string $purpose, int $ttl = 900, ?int $_now = null): string
{// create an expiring, purpose-bound, session-bound CSRF proof
    config($secret, $purpose);
    $session = session();

    $ttl > 0                                                        || throw new \InvalidArgumentException('token:ttl must be positive');
    $now = clock($_now, __FUNCTION__);
    $now <= \PHP_INT_MAX - $ttl                                    || throw new \InvalidArgumentException('token:expiry overflow');

    $expires = $now + $ttl;
    $nonce = \random_bytes(TOKEN_NONCE_BYTES);
    $mac = \hash_hmac('sha256', message($now, $expires, $nonce, $purpose, $session), $secret, true);

    return TOKEN_VERSION . '.' . $now . '.' . $expires . '.' . b64($nonce) . '.' . b64($mac);
}

function verify(string $secret, string $purpose, $proof, int $minimum = 0, ?int $_now = null): array
{// validate CSRF integrity, expiry, purpose, session binding, and minimum submission age
    config($secret, $purpose);
    $session = session();

    $minimum >= 0                                                    || throw new \InvalidArgumentException('verify:minimum below zero');
    $now = clock($_now, __FUNCTION__);

    if ($proof === null || $proof === '')
        return result(false, TOKEN_MISSING);
    if (!\is_string($proof) || \strlen($proof) > TOKEN_MAX_LENGTH)
        return result(false, TOKEN_MALFORMED);

    $parts = \explode('.', $proof);
    if (\count($parts) !== 5 || $parts[0] !== TOKEN_VERSION)
        return result(false, TOKEN_MALFORMED);

    $issued  = integer($parts[1]);
    $expires = integer($parts[2]);
    $nonce   = unb64($parts[3]);
    $actual  = unb64($parts[4]);

    if ($issued === null || $expires === null
        || $nonce === null || \strlen($nonce) !== TOKEN_NONCE_BYTES
        || $actual === null || \strlen($actual) !== TOKEN_MAC_BYTES)
        return result(false, TOKEN_MALFORMED);

    $expect = \hash_hmac('sha256', message($issued, $expires, $nonce, $purpose, $session), $secret, true);
    if (!\hash_equals($expect, $actual))
        return result(false, TOKEN_INVALID);

    if ($expires <= $issued)
        return result(false, TOKEN_MALFORMED);
    if ($issued > $now)
        return result(false, TOKEN_FUTURE, ['issued_at' => $issued, 'expires_at' => $expires]);
    if ($now >= $expires)
        return result(false, TOKEN_EXPIRED, ['issued_at' => $issued, 'expires_at' => $expires]);

    $age = $now - $issued;
    if ($age < $minimum)
        return result(false, SUBMISSION_EARLY, [
            'issued_at'   => $issued,
            'expires_at'  => $expires,
            'age'         => $age,
            'retry_after' => $minimum - $age,
        ]);

    return result(true, PASS, [
        'issued_at'  => $issued,
        'expires_at' => $expires,
        'age'        => $age,
    ]);
}

function honeypot(array $input, string $field): array
{// require one present, scalar, empty honeypot field
    field($field, __FUNCTION__);

    if (!\array_key_exists($field, $input))
        return result(false, HONEYPOT_MISSING, ['field' => $field]);
    if (!\is_string($input[$field]))
        return result(false, HONEYPOT_SCALAR, ['field' => $field]);
    if ($input[$field] !== '')
        return result(false, HONEYPOT_FILLED, ['field' => $field]);

    return result(true, PASS, ['field' => $field]);
}

function shape(
    array $input,
    array $required,
    array $optional = [],
    int $behave = 0,
    ?array $files = null
): array
{// validate field names and scalar UTF-8 values; reject uploads unless explicitly delegated
    $required = fields($required, __FUNCTION__ . ':required');
    $optional = fields($optional, __FUNCTION__ . ':optional');

    $allowed = [];
    foreach ($required as $name)
        $allowed[$name] = true;
    foreach ($optional as $name) {
        !isset($allowed[$name])                                      || throw new \InvalidArgumentException("shape:duplicate field:$name");
        $allowed[$name] = true;
    }

    $errors = [];
    foreach ($required as $name)
        if (!\array_key_exists($name, $input))
            $errors[] = ['code' => FIELD_MISSING, 'field' => $name];

    foreach ($input as $name => $value) {
        if (!isset($allowed[$name])) {
            if (REJECT_UNKNOWN & $behave)
                $errors[] = ['code' => FIELD_UNKNOWN, 'field' => label($name)];
            continue;
        }

        if (!\is_string($value)) {
            $errors[] = ['code' => FIELD_SCALAR, 'field' => label($name)];
            continue;
        }
        if (\strpos($value, "\0") !== false) {
            $errors[] = ['code' => FIELD_NULL, 'field' => label($name)];
            continue;
        }
        if (\preg_match('//u', $value) !== 1)
            $errors[] = ['code' => FIELD_UTF8, 'field' => label($name)];
    }

    if ($files === null) {
        $files = $_FILES ?? [];
        \is_array($files)                                            || throw new \UnexpectedValueException('shape:files not array');
    }
    if (!(ALLOW_FILES & $behave))
        foreach ($files as $name => $_file)
            $errors[] = ['code' => FILE_UNEXPECTED, 'field' => label($name)];

    return result(!$errors, $errors ? SHAPE_INVALID : PASS, ['errors' => $errors]);
}

function rate(
    string $key,
    int $limit,
    int $window,
    ?callable $hit = null,
    int $cost = 1,
    ?int $_now = null
): array
{// fixed-window limiter; defaults to session storage or delegates atomic hits to caller
    $key !== ''                                                       || throw new \InvalidArgumentException('rate:key empty');
    \strlen($key) <= 1024                                            || throw new \InvalidArgumentException('rate:key too long');
    $limit > 0                                                        || throw new \InvalidArgumentException('rate:limit must be positive');
    $window > 0                                                       || throw new \InvalidArgumentException('rate:window must be positive');
    $cost > 0                                                         || throw new \InvalidArgumentException('rate:cost must be positive');

    $now = clock($_now, __FUNCTION__);
    $now <= \PHP_INT_MAX - $window                                  || throw new \InvalidArgumentException('rate:reset overflow');
    $slot = \intdiv($now, $window);
    $reset = ($slot + 1) * $window;
    $ttl = $reset - $now;
    $bucket = 'badhat-form-rate-v1:' . \hash('sha256', $key) . ':' . $slot;

    if ($hit) {
        $count = $hit($bucket, $cost, $ttl);
        if (\is_string($count) && $count !== '' && \ctype_digit($count))
            $count = integer($count);
        \is_int($count) && $count >= $cost                            || throw new \UnexpectedValueException('rate:hit must return cumulative integer count');
    } else {
        session();
        $_SESSION[__NAMESPACE__] ??= [];
        \is_array($_SESSION[__NAMESPACE__])                           || throw new \UnexpectedValueException('rate:session namespace corrupted');
        $_SESSION[__NAMESPACE__][__FUNCTION__] ??= [];
        \is_array($_SESSION[__NAMESPACE__][__FUNCTION__])            || throw new \UnexpectedValueException('rate:session store corrupted');
        $store = &$_SESSION[__NAMESPACE__][__FUNCTION__];

        foreach ($store as $stored => $entry)
            if (!\is_array($entry) || !isset($entry[0], $entry[1]) || !\is_int($entry[1]) || $entry[1] <= $now)
                unset($store[$stored]);

        $previous = $store[$bucket][0] ?? 0;
        \is_int($previous) && $previous >= 0                          || throw new \UnexpectedValueException('rate:session bucket corrupted');
        $previous <= \PHP_INT_MAX - $cost                             || throw new \OverflowException('rate:count overflow');

        $count = $previous + $cost;
        $store[$bucket] = [$count, $reset];
    }

    $remaining = \max(0, $limit - $count);
    $allowed = $count <= $limit;

    return result($allowed, $allowed ? PASS : RATE_LIMITED, [
        'limit'       => $limit,
        'count'       => $count,
        'remaining'   => $remaining,
        'reset_at'    => $reset,
        'retry_after' => $allowed ? 0 : $ttl,
    ]);
}

function combine(array ...$checks): array
{// aggregate uniform results without exposing submitted values
    $failures = [];
    foreach ($checks as $check) {
        isset($check['ok'], $check['code'])
            && \is_bool($check['ok'])
            && \is_string($check['code'])                            || throw new \UnexpectedValueException('combine:invalid result');
        if (!$check['ok'])
            $failures[] = $check;
    }

    return result(!$failures, $failures ? FORM_INVALID : PASS, [
        'checks'   => $checks,
        'failures' => $failures,
    ]);
}

function result(bool $ok, string $code = PASS, array $meta = []): array
{// common result shape; useful for application checks composed with BADHAT checks
    return ['ok' => $ok, 'code' => $code] + $meta;
}

function config(string $secret, string $purpose): void
{
    \strlen($secret) >= SECRET_MIN_BYTES                              || throw new \InvalidArgumentException('form:secret shorter than 32 bytes');
    field($purpose, 'form:purpose');
}

function session(): string
{
    \session_status() === \PHP_SESSION_ACTIVE                        || throw new \LogicException('form:session not active');
    $id = \session_id();
    $id !== ''                                                        || throw new \LogicException('form:session id empty');
    return $id;
}

function clock(?int $now, string $caller): int
{
    $now ??= \time();
    $now >= 0                                                         || throw new \InvalidArgumentException("$caller:time below zero");
    return $now;
}

function field(string $name, string $caller): void
{
    $name !== ''                                                      || throw new \InvalidArgumentException("$caller:field empty");
    \strlen($name) <= 255                                             || throw new \InvalidArgumentException("$caller:field too long");
    \preg_match('/[\x00-\x1F\x7F]/', $name) !== 1                  || throw new \InvalidArgumentException("$caller:field contains control");
    \preg_match('//u', $name) === 1                                  || throw new \InvalidArgumentException("$caller:field invalid utf8");
}

function label($name): string
{
    $name = (string)$name;
    if (\strlen($name) > 255
        || \preg_match('//u', $name) !== 1
        || \preg_match('/[\x00-\x1F\x7F]/', $name) === 1)
        return 'sha256:' . \hash('sha256', $name);
    return $name;
}

function fields(array $names, string $caller): array
{
    $seen = [];
    $clean = [];
    foreach ($names as $name) {
        \is_string($name)                                             || throw new \InvalidArgumentException("$caller:field not string");
        field($name, $caller);
        !isset($seen[$name])                                          || throw new \InvalidArgumentException("$caller:duplicate field:$name");
        $seen[$name] = true;
        $clean[] = $name;
    }
    return $clean;
}

function integer(string $value): ?int
{
    if ($value === '' || \preg_match('/\A(?:0|[1-9][0-9]*)\z/D', $value) !== 1)
        return null;
    $integer = \filter_var($value, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    return $integer === false ? null : $integer;
}

function message(int $issued, int $expires, string $nonce, string $purpose, string $session): string
{
    return TOKEN_VERSION . "\0" . $issued . "\0" . $expires . "\0" . $nonce
        . \hash('sha256', $purpose, true)
        . \hash('sha256', $session, true);
}

function b64(string $bytes): string
{
    return \rtrim(\strtr(\base64_encode($bytes), '+/', '-_'), '=');
}

function unb64(string $value): ?string
{
    $canonical = $value;
    if ($value === '' || \preg_match('/\A[A-Za-z0-9_-]+\z/D', $value) !== 1)
        return null;

    $remainder = \strlen($value) % 4;
    if ($remainder === 1)
        return null;
    if ($remainder)
        $value .= \str_repeat('=', 4 - $remainder);

    $decoded = \base64_decode(\strtr($value, '-_', '+/'), true);
    if ($decoded === false || b64($decoded) !== $canonical)
        return null;
    return $decoded;
}
