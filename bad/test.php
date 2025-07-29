<?php

declare(strict_types=1);

/* 
use PHP assert() and addbad assert_throws() to test exceptions
test('named test', function () {
    assert(true, 'This is a dummy test that always passes');
});

run all tests with tests() function, which will output the results
tests();
*/
function test(?string $name = null, ?callable $test_func = null): array
{
    static $tests = [];

    isset($name, $test_func) && $tests[] = ['name' => $name, 'func' => $test_func];

    return $tests;
}

function tests(): void
{
    $tests = test();
    $passed = $failed = 0;

    foreach ($tests as $test) {
        try {
            $test['func'](); // If it completes without throwing, it passed
            ++$passed;
            echo PHP_EOL . " | {$test['name']}";
        } catch (Throwable $e) {
            ++$failed;
            echo PHP_EOL . " x {$test['name']}: {$e->getMessage()}";
        }
    }
    echo PHP_EOL . ($passed + $failed) . " tests, {$passed} passed, {$failed} failed" . PHP_EOL;
    if ($failed > 0) exit(1);
}

// Assert that a function throws an expected exception
function assert_throws(callable $func, string $exception_class = '', string $expected_message = ''): void
{
    try {
        $func();
        assert(false, 'Expected exception but none was thrown');
    } catch (Throwable $e) {
        if ($exception_class) {
            assert(
                $e instanceof $exception_class,
                "Expected {$exception_class}, got " . get_class($e)
            );
        }
        if ($expected_message) {
            assert(
                strpos($e->getMessage(), $expected_message) !== false,
                "Expected message containing '{$expected_message}', got: {$e->getMessage()}"
            );
        }
    }
}
