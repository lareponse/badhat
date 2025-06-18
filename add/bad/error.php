<?php
// no need for declare(strict_types=1);
error_reporting(E_ALL);

set_error_handler(function ($errno, $errstr) {
    return error_log(sprintf("ADDBAD [ER:%d] : %s (%s)", $errno, $errstr, __random_error_id()));
});

set_exception_handler(function (Throwable $e) {
    $message = sprintf("ADDBAD [%s:%d] : %s (%s)", get_class($e), $e->getCode(), $e->getMessage(), __random_error_id());
    error_log(sprintf("%s in %s:%d", $message, $e->getFile(), $e->getLine()));

    if (function_exists('http') && !headers_sent()) {
        $code = $e->getCode() >= 100 && $e->getCode() <= 500 ? (int)$e->getCode() : 500;
        http_out($code, $message);
        exit;
    }
});

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        $message = sprintf("ADDBAD [SHUTDOWN:%d] : %s (%s)", $err['type'], $err['message'], __random_error_id());
        error_log(sprintf("%s in %s:%d", $message, $err['file'], $err['line']));

        if (function_exists('http') && !headers_sent())
            http_out(500, $message);
        else
            echo PHP_SAPI === 'cli' ? $message : "<pre>$message</pre>";

        exit(1);
    }
});

function __random_error_id(): string
{
    return base_convert((string)random_int(100000, 999999), 10, 36);
}
