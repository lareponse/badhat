<?php

declare(strict_types=1);


function &get_tests(): array
{
    static $tests = [];
    return $tests;
}

function test(string $name, callable $test_func): void
{
    get_tests()[] = ['name' => $name, 'func' => $test_func];
}

function run_tests(): void
{
    $tests = &get_tests();
    $passed = $failed = 0;

    echo "Running tests...\n\n";

    foreach ($tests as $test) {
        try {
            $test['func']();
            echo "✓ {$test['name']}\n";
            $passed++;
        } catch (Throwable $e) {
            echo "✗ {$test['name']}: {$e->getMessage()}\n";
            $failed++;
        }
    }

    echo "\n" . ($passed + $failed) . " tests, {$passed} passed, {$failed} failed\n";
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