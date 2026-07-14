<?php

require \dirname(__DIR__) . '/form.php';

use const bad\form\{VALID, CODE, DATA, REJECT_UNKNOWN, TTL_MAX};
use function bad\form\{result, verify, token, csrf, timing, honeypot, shape, rate, session_rate};

\session_id('badhat-form-' . \bin2hex(\random_bytes(6)));
\session_start();

$secret = \str_repeat('a', 32);
$other_secret = \str_repeat('b', 32);
$tests = [];

function test(string $name, callable $run): void
{
    global $tests;
    $tests[] = [$name, $run];
}

function form_session_reset(): void
{
    unset($_SESSION['bad\\form']);
}

function expect(bool $condition, string $message = 'expectation failed'): void
{
    if (!$condition)
        throw new \RuntimeException($message);
}

function same($expected, $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        $detail = $message === '' ? '' : $message . ': ';
        throw new \RuntimeException($detail . 'expected ' . \var_export($expected, true) . ', got ' . \var_export($actual, true));
    }
}

function code_is(array $result, string $code): void
{
    same($code, $result[CODE] ?? null, 'result code');
}

function throws(string $class, callable $run): void
{
    try {
        $run();
    } catch (\Throwable $fault) {
        expect($fault instanceof $class, 'expected ' . $class . ', got ' . $fault::class);
        return;
    }
    throw new \RuntimeException('expected exception ' . $class);
}

test('token verifies with signed timing metadata', function () use ($secret): void {
    form_session_reset();
    $value = token('contact', $secret, 60, 1000);
    expect((bool)\preg_match('/^1\\.1000\\.1060\\.[0-9a-f]{32}\\.[0-9a-f]{64}$/D', $value), 'token format');

    $checked = csrf($value, 'contact', $secret, 1010);
    same(true, $checked[VALID]);
    code_is($checked, 'ok');
    same(1000, $checked[DATA]['issued_at']);
    same(1060, $checked[DATA]['expires_at']);
    same(10, $checked[DATA]['age']);
});

test('token is purpose, secret and session bound', function () use ($secret, $other_secret): void {
    form_session_reset();
    $value = token('contact', $secret, 60, 1000);

    code_is(csrf($value, 'signup', $secret, 1001), 'csrf:invalid');
    code_is(csrf($value, 'contact', $other_secret, 1001), 'csrf:invalid');

    \session_regenerate_id(true) || throw new \RuntimeException('session regeneration failed');
    code_is(csrf($value, 'contact', $secret, 1001), 'csrf:invalid');
});

test('token remains reusable until expiration', function () use ($secret): void {
    form_session_reset();
    $value = token('contact', $secret, 10, 1000);
    same(true, csrf($value, 'contact', $secret, 1001)[VALID]);
    same(true, csrf($value, 'contact', $secret, 1002)[VALID]);
    code_is(csrf($value, 'contact', $secret, 1010), 'csrf:expired');
});

test('token rejects future, missing and malformed input', function () use ($secret): void {
    form_session_reset();
    $value = token('contact', $secret, 60, 1000);

    code_is(csrf($value, 'contact', $secret, 990), 'csrf:future');
    same(0, csrf($value, 'contact', $secret, 997)[DATA]['age']);
    code_is(csrf(null, 'contact', $secret, 1000), 'csrf:missing');
    code_is(csrf([], 'contact', $secret, 1000), 'csrf:malformed');
    code_is(csrf('1.2.3', 'contact', $secret, 1000), 'csrf:malformed');
});

test('timing uses authenticated token age', function () use ($secret): void {
    form_session_reset();
    $value = token('contact', $secret, 60, 1000);

    $checked = csrf($value, 'contact', $secret, 1002);
    $fast = timing($checked, 3);
    same(false, $fast[VALID]);
    code_is($fast, 'timing:too_fast');
    same(2, $fast[DATA]['age']);

    $ready = timing(csrf($value, 'contact', $secret, 1003), 3);
    same(true, $ready[VALID]);
    same(3, $ready[DATA]['minimum']);
});

test('honeypot requires a present empty string', function (): void {
    same(true, honeypot(['company' => ''], 'company')[VALID]);
    code_is(honeypot([], 'company'), 'honeypot:missing');
    code_is(honeypot(['company' => 'bot'], 'company'), 'honeypot:filled');
    code_is(honeypot(['company' => []], 'company'), 'honeypot:non_scalar');
});

test('shape enforces names, scalar strings, UTF-8 and null rejection', function (): void {
    $valid = ['_form' => 'x', 'company' => '', 'name' => 'Ada', 'message' => 'Hello'];
    same(true, shape($valid, ['_form', 'company', 'name'], ['message'], REJECT_UNKNOWN)[VALID]);

    code_is(shape(['name' => 'Ada'], ['name', 'message']), 'shape:missing');
    code_is(shape(['name' => ['Ada']], ['name']), 'shape:non_scalar');
    code_is(shape(['name' => "Ada\0Lovelace"], ['name']), 'shape:null_byte');
    code_is(shape(['name' => "\xC3\x28"], ['name']), 'shape:utf8');

    same(true, shape(['name' => 'Ada', 'submit' => 'Send'], ['name'])[VALID]);
    code_is(shape(['name' => 'Ada', 'submit' => 'Send'], ['name'], [], REJECT_UNKNOWN), 'shape:unknown');

    throws(\InvalidArgumentException::class, fn() => shape([], ['name', 'name']));
});

test('verify short-circuits and passes the previous result', function (): void {
    $ran = false;
    $stopped = verify(
        fn() => result(false, 'stop'),
        function () use (&$ran): array {
            $ran = true;
            return result(true);
        }
    );
    code_is($stopped, 'stop');
    same(false, $ran);

    $timed = verify(
        fn() => result(true, 'ok', ['age' => 1]),
        fn(array $previous) => timing($previous, 2)
    );
    code_is($timed, 'timing:too_fast');

    throws(\UnexpectedValueException::class, fn() => verify(fn() => 'bad'));
});

test('generic rate limits are keyed, bounded and reset by window', function (): void {
    $buckets = [];

    $first = rate($buckets, 'contact', 2, 10, 100);
    same(true, $first[VALID]);
    same(1, $first[DATA]['remaining']);

    $second = rate($buckets, 'contact', 2, 10, 100);
    same(true, $second[VALID]);
    same(0, $second[DATA]['remaining']);

    $blocked = rate($buckets, 'contact', 2, 10, 100);
    code_is($blocked, 'rate:limited');
    same(10, $blocked[DATA]['retry_after']);
    same(2, $buckets['contact']['count']);

    same(true, rate($buckets, 'signup', 1, 10, 100)[VALID]);
    same(true, rate($buckets, 'contact', 2, 10, 110)[VALID]);
    same(1, $buckets['contact']['count']);

    same(true, rate($buckets, 'contact', 2, 20, 111)[VALID]);
    same(20, $buckets['contact']['window']);
});

test('session rate limits compose by application key', function (): void {
    form_session_reset();

    same(true, session_rate('contact:minute', 1, 60, 100)[VALID]);
    code_is(session_rate('contact:minute', 1, 60, 100), 'rate:limited');
    same(true, session_rate('contact:hour', 2, 3600, 100)[VALID]);
});

test('unknown field names are not copied into structured results', function (): void {
    $unknown = str_repeat('x', 300);
    $checked = shape(['name' => 'Ada', $unknown => 'value'], ['name'], [], REJECT_UNKNOWN);
    code_is($checked, 'shape:unknown');
    same([], $checked[DATA]);
});

test('form token and session limits require an active session', function () use ($secret): void {
    session_write_close();
    try {
        throws(\LogicException::class, fn() => token('contact', $secret, 60, 100));
        throws(\LogicException::class, fn() => csrf('bad', 'contact', $secret, 100));
        throws(\LogicException::class, fn() => session_rate('contact', 1, 60, 100));
    } finally {
        session_start();
    }
});

test('configuration errors throw rather than becoming verification failures', function () use ($secret): void {
    throws(\InvalidArgumentException::class, fn() => token('', $secret, 60, 100));
    throws(\InvalidArgumentException::class, fn() => token('contact', 'short', 60, 100));
    throws(\InvalidArgumentException::class, fn() => token('contact', $secret, 0, 100));
    throws(\InvalidArgumentException::class, fn() => token('contact', $secret, TTL_MAX + 1, 100));
    throws(\InvalidArgumentException::class, function (): void {
        $buckets = [];
        rate($buckets, '', 1, 60, 100);
    });
});

$failures = [];
foreach ($tests as [$name, $run]) {
    try {
        $run();
    } catch (\Throwable $fault) {
        $failures[] = [$name, $fault];
    }
}

\session_destroy();

if ($failures) {
    foreach ($failures as [$name, $fault])
        \fwrite(STDERR, "FAIL $name\n  {$fault->getMessage()}\n");
    \fwrite(STDERR, \count($failures) . ' of ' . \count($tests) . " tests failed\n");
    exit(1);
}

\fwrite(STDOUT, \count($tests) . " form tests passed\n");
