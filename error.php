<?php

$request_id = bin2hex(random_bytes(4));
$log_format = "[req=$request_id] %s (%s) %s in %s:%d";

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use ($log_format): bool {
    error_log(sprintf($log_format, 'Error', "errno={$errno}", $errstr, $errfile, $errline));
    return true; // handled, prevent double logging
});

set_exception_handler(function (Throwable $e) use ($log_format) {
    error_log(sprintf($log_format, 'Uncaught', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));
    error_log(sprintf($log_format, 'UncaughtTrace', get_class($e), $e->getTraceAsString(), $e->getFile(), $e->getLine()));
    fatal_error($log_format);
});

register_shutdown_function(function () use ($log_format) {
    $err = error_get_last();
    if (!$err || !($err['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR))) return;
    error_log(sprintf($log_format, 'Shutdown', "type={$err['type']}", $err['message'], $err['file'], $err['line']));
    fatal_error($log_format);
});

function fatal_error($log_format): void
{
    $message = 'EXEC:' . (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) .
        ' MEM:' . memory_get_peak_usage(true) .
        ' URI:' . ($_SERVER['REQUEST_URI'] ?? 'cli') .
        ' METHOD:' . ($_SERVER['REQUEST_METHOD'] ?? 'cli') .
        ' REMOTE:' . ($_SERVER['REMOTE_ADDR'] ?? 'n/a') .
        ' AGENT:' . ($_SERVER['HTTP_USER_AGENT'] ?? '') .
        ' GET:' . count($_GET) .
        ' POST:' . count($_POST) .
        ' SESSION:' . (isset($_SESSION) ? count($_SESSION) : 0) .
        ' COOKIES:' . count($_COOKIE) .
        ' FILES:' . count($_FILES) .
        ' HEADERS:' . count(getallheaders());

    error_log(sprintf($log_format, 'Trace', "type={trace}", $message, '', 0));

    ob_get_length() && ob_clean();
    http_response_code(500); // Goes to ErrorDocument 500 handler
    exit(1);
}