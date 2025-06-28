<?php
// no need for declare(strict_types=1);
error_reporting(E_ALL);

$random_error_id = function () {
    return bin2hex(random_bytes(8)); // 16-hex characters, ~2⁵⁶ possibilities
};
$message = "An error occurred [%s]; the administrator has been notified. Please try again later.";
$log = "%s [%s] (%s) %s in %s:%d";

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use ($random_error_id, $log): bool {
    error_log(sprintf($log, 'Error', $random_error_id(), $errno, $errstr, $errfile, $errline));
    return true;  // don’t invoke PHP’s native handler
});

set_exception_handler(function (Throwable $e) use ($random_error_id, $message, $log) {
    $error_id = $random_error_id();
    $message  = "A fatal error occurred [$error_id]; the administrator has been notified.";
    $log = sprintf("Uncaught [%s] (%s) %s in %s:%d", $error_id, get_class($e), $e->getMessage(), $e->getFile(), $e->getLine());
    error_log($message);
    error_log($log);
    error_log_context($e);

    http_response_code(500);
    echo $message;
});

register_shutdown_function(function () use ($random_error_id, $message, $log) {
    [$type, $message, $file, $line] = error_get_last();

    if (! in_array($type, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) //error_get_last() can return notices/warnings
        return;

    $error_id = $random_error_id();
    $message    = "A fatal error occurred [$error_id]; the administrator has been notified.";
    $log        = "Shutdown [$error_id] ($type) $message in $file:$line";

    error_log($message);
    error_log($log);

    http_response_code(500);
    echo $message;
    exit(1);
});

function error_log_context(?Throwable $t = null): void
{
    if ($t) {
        foreach ($t->getTrace() as $i => $frame) {
            $func = ($frame['class'] ?? '') . ($frame['type'] ?? '') . $frame['function'];
            $args = implode(", ", array_map(function ($arg) {
                return is_scalar($arg) ? var_export($arg, true) : gettype($arg);
            }, $frame['args'] ?? []));
            error_log(sprintf("#%d %s(%s) called at [%s:%s]", $i, $func, $args, $frame['file'] ?? '[internal]', $frame['line'] ?? ''));
        }
    }

    // Superglobals
    error_log('PID: ' . getmypid());
    error_log('REQUEST_URI: ' . ($_SERVER['REQUEST_URI']   ?? 'cli'));
    error_log('METHOD: '      . ($_SERVER['REQUEST_METHOD'] ?? 'cli'));
    error_log('REMOTE_ADDR: ' . ($_SERVER['REMOTE_ADDR']   ?? 'n/a'));
    error_log('USER_AGENT: '  . ($_SERVER['HTTP_USER_AGENT'] ?? ''));

    error_log('$_GET: '     . var_export($_GET,     true));
    error_log('$_POST: '    . var_export($_POST,    true));
    error_log('$_COOKIE: '  . var_export($_COOKIE,  true));
    error_log('$_SESSION: ' . (isset($_SESSION)
        ? var_export($_SESSION, true)
        : ''));
    error_log('$_FILES: '   . var_export($_FILES,   true));

    // All request headers
    if (function_exists('getallheaders')) {
        error_log('HEADERS: ' . var_export(getallheaders(), true));
    } else {
        // Fallback for non-Apache environments
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headers[$key] = $value;
            }
        }
        error_log('HEADERS (fallback): ' . var_export($headers, true));
    }
}
