<?php
// no need for declare(strict_types=1);
error_reporting(E_ALL);

$request_id = bin2hex(random_bytes(4));
$log_format = "[req=$request_id] %s (%s) %s in %s:%d";
$message = "A fatal error occurred [{$request_id}]; the administrator has been notified.";

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use ($log_format): bool {
    error_log(sprintf($log_format, 'Error', "errno={$errno}", $errstr, $errfile, $errline));
    return false; // yield to PHP's native handler
});

// 5) Exception handler
set_exception_handler(function (Throwable $e) use ($log_format, $message) {
    error_log(sprintf($log_format, 'Uncaught', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));
    error_log_context($e);  // if you have a back-trace logger

    if (ob_get_length()) ob_clean();
    header('Content-Type: text/plain');
    http_response_code(500);
    echo $message;
});

// 6) Shutdown handler (for fatal errors)
register_shutdown_function(function () use ($log_format, $message) {
    $err = error_get_last();
    if (! $err || ! in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true))
        return;

    error_log(sprintf($log_format, 'Shutdown', "type={$err['type']}", $err['message'], $err['file'], $err['line']));

    if (ob_get_length()) ob_clean();
    header('Content-Type: text/plain');
    http_response_code(500);
    echo $message;
});

function error_log_context(?Throwable $t = null): void
{
    if ($t) {
        error_log($t->getTraceAsString());
    }

    error_log('EXECUTION_TIME: ' . (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']));
    error_log('MEMORY_PEAK: ' . memory_get_peak_usage(true));
    
    // Superglobals
    error_log('REQUEST_URI: ' . ($_SERVER['REQUEST_URI']   ?? 'cli'));
    error_log('METHOD: '      . ($_SERVER['REQUEST_METHOD'] ?? 'cli'));
    error_log('REMOTE_ADDR: ' . ($_SERVER['REMOTE_ADDR']   ?? 'n/a'));
    error_log('USER_AGENT: '  . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    error_log('GET_keys: ' . implode(',', array_keys($_GET)));
    error_log('POST_keys: ' . implode(',', array_keys($_POST)));
    error_log('SESSION_keys: ' . (isset($_SESSION) ? implode(',', array_keys($_SESSION)) : 'none'));
    error_log('COOKIE_count: ' . count($_COOKIE));
    error_log('FILES_count: ' . count($_FILES));
    error_log('HEADER_count: ' . (function_exists('getallheaders') ? count(getallheaders()) : 0));
}
