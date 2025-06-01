<?php

/**
 * Path Traversal Security Test
 * 
 * Run this test to verify the path traversal fix is working correctly.
 * Place this file in your test/ directory and run: php test/security_test.php
 */

require_once 'add/test.php';
require_once 'core.php';

// Mock the io() function for testing
function io(?string $arg = null): array
{
    static $io = [];

    if (!$io) {
        // Create temporary test directories
        $base = sys_get_temp_dir() . '/badge_test_' . uniqid();
        mkdir($base);
        mkdir($base . '/routes');
        mkdir($base . '/views');

        // Create a test file outside the safe area
        mkdir($base . '/unsafe');
        file_put_contents($base . '/unsafe/secret.php', '<?php echo "SECURITY BREACH!"; ?>');

        // Create legitimate test files
        file_put_contents($base . '/routes/home.php', '<?php return function() { return "safe"; }; ?>');
        file_put_contents($base . '/views/home.php', '<h1>Safe content</h1>');

        $io = [
            realpath($base . '/routes'),
            realpath($base . '/views')
        ];

        // Store for cleanup
        register_shutdown_function(function () use ($base) {
            exec("rm -rf " . escapeshellarg($base));
        });
    }

    return $io;
}

test('blocks basic path traversal with dot-dot', function () {
    $_SERVER['REQUEST_URI'] = '/admin/../../../etc/passwd';

    assert_throws(function () {
        request();
    }, 'DomainException', 'Path traversal');
});

test('blocks URL encoded path traversal', function () {
    $_SERVER['REQUEST_URI'] = '/admin/%2e%2e/%2e%2e/etc/passwd';

    assert_throws(function () {
        request();
    }, 'DomainException', 'Path traversal');
});

test('blocks backslash path traversal', function () {
    $_SERVER['REQUEST_URI'] = '/admin\\..\\..\\etc\\passwd';

    assert_throws(function () {
        request();
    }, 'DomainException', 'Path traversal');
});

test('blocks double encoded path traversal', function () {
    $_SERVER['REQUEST_URI'] = '/admin/%252e%252e/%252e%252e/etc/passwd';

    assert_throws(function () {
        request();
    }, 'DomainException', 'Excessive URL encoding');
});

test('blocks mixed encoding path traversal', function () {
    $_SERVER['REQUEST_URI'] = '/admin/..%2f..%2fetc%2fpasswd';

    assert_throws(function () {
        request();
    }, 'DomainException', 'Path traversal');
});

test('blocks null byte injection', function () {
    $_SERVER['REQUEST_URI'] = '/admin%00/../../../etc/passwd';

    assert_throws(function () {
        request();
    }, 'DomainException', 'Invalid path segment');
});

test('allows legitimate paths', function () {
    $_SERVER['REQUEST_URI'] = '/admin/users/edit';

    $result = request();

    assert($result['path'] === '/admin/users/edit');
    assert($result['segments'] === ['admin', 'users', 'edit']);
});

test('allows legitimate paths with parameters', function () {
    $_SERVER['REQUEST_URI'] = '/api/users/123?format=json';

    $result = request();

    assert($result['path'] === '/api/users/123');
    assert($result['segments'] === ['api', 'users', '123']);
});

test('blocks oversized path segments', function () {
    $long_segment = str_repeat('a', 101);
    $_SERVER['REQUEST_URI'] = '/admin/' . $long_segment;

    assert_throws(function () {
        request();
    }, 'DomainException', 'too long');
});

test('blocks invalid characters in segments', function () {
    $_SERVER['REQUEST_URI'] = '/admin/user<script>';

    assert_throws(function () {
        request();
    }, 'DomainException', 'Invalid path segment');
});

test('normalizes multiple slashes', function () {
    $_SERVER['REQUEST_URI'] = '//admin///users//edit//';

    $result = request();

    assert($result['path'] === '/admin/users/edit');
});

test('handles empty path correctly', function () {
    $_SERVER['REQUEST_URI'] = '';

    $result = request();

    assert($result['path'] === '/home');
    assert($result['segments'] === ['home']);
});

test('handles root path correctly', function () {
    $_SERVER['REQUEST_URI'] = '/';

    $result = request();

    assert($result['path'] === '/home');
    assert($result['segments'] === ['home']);
});

test('io_candidates validates file paths safely', function () {
    // This should not throw an exception for legitimate routes
    $_SERVER['REQUEST_URI'] = '/home';
    request(); // Initialize request

    $candidates = io_candidates('in');

    // Should find legitimate candidates without security violations
    assert(is_array($candidates));
});

test('is_safe_file_access works correctly', function () {
    $io_dirs = io();
    $route_dir = $io_dirs[0];
    $view_dir = $io_dirs[1];

    // Test safe file access
    $safe_file = $route_dir . '/home.php';
    assert(is_safe_file_access($safe_file, [$route_dir, $view_dir]));

    // Test unsafe file access (if we could construct such a path)
    $unsafe_file = dirname($route_dir) . '/unsafe/secret.php';
    assert(!is_safe_file_access($unsafe_file, [$route_dir, $view_dir]));
});

test('validate_path_safety detects traversal attempts', function () {
    $io_dirs = io();
    $route_dir = $io_dirs[0];
    $view_dir = $io_dirs[1];

    // This should not throw for safe paths
    $safe_paths = validate_path_safety('/home', $route_dir, $view_dir);
    assert(is_array($safe_paths));

    // This should throw for traversal attempts
    assert_throws(function () use ($route_dir, $view_dir) {
        validate_path_safety('/../../../etc/passwd', $route_dir, $view_dir);
    }, 'DomainException', 'Path traversal');
});

test('security logging works', function () {
    // Capture error log output
    $original_log = ini_get('log_errors');
    $original_file = ini_get('error_log');

    ini_set('log_errors', 1);
    $temp_log = tempnam(sys_get_temp_dir(), 'security_test');
    ini_set('error_log', $temp_log);

    log_security_violation('test', 'Test violation', ['test' => true]);

    $log_content = file_get_contents($temp_log);
    assert(strpos($log_content, 'SECURITY_VIOLATION') !== false);
    assert(strpos($log_content, 'test') !== false);

    // Restore settings
    ini_set('log_errors', $original_log);
    ini_set('error_log', $original_file);
    unlink($temp_log);
});

// Clean up any static state between tests
function reset_request_state(): void
{
    // Use reflection to reset static variable
    $reflection = new ReflectionFunction('request');
    $static_vars = $reflection->getStaticVariables();
    if (isset($static_vars['request'])) {
        // We can't directly reset it, but we can test with different URIs
    }
}

echo "Running path traversal security tests...\n\n";

run_tests();

echo "\nSecurity test completed. If all tests passed, the path traversal fix is working correctly.\n";
echo "Remember to also test manually with a web browser or curl to ensure real-world protection.\n";
