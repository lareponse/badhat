<?php
namespace bad\error;

const HND_ERR  = 1;
const HND_EXC  = 2;
const HND_SHUT = 4;
const HND_ALL  = HND_ERR | HND_EXC | HND_SHUT;

const ALLOW_INTERNAL = 8;
const ERR_LOG        = 16;
const ERR_OSD        = 32;
const FATAL_OB_FLUSH = 64;
const FATAL_HTTP_500 = 128;

const PHP_FATAL_ERRORS = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR;

const CODE_ACE = 0xACE;  // 2766 - Abnormal Computing Environment
const CODE_BAD = 0xBAD;  // 2989 - Broken Abstraction Detected
const CODE_COD = 0xC0D;  // 3085 - Code Of Doom

return function (int $behave = HND_ALL | ERR_LOG, ?string $request_id = null): callable {
    $start = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
    $prefix = "[req=" . ($request_id ?? bin2hex(random_bytes(4))) . "] ";
    $format = $prefix . '%s (%s) %s in %s:%d';

    $report = function($behave, $message) {
        ($behave & ERR_OSD) && print $message;
        ($behave & ERR_LOG) && error_log($message);
        return true; // <- important: permet de chaîner
    };

    $fatal_exit = function($behave) use ($report, $prefix, $start) {
        $report($behave, $prefix . 'EXEC:' . (microtime(true) - $start) . ' MEM:' . memory_get_peak_usage(true)
            . ' URI:' . ($_SERVER['REQUEST_URI'] ?? 'cli') . ' REMOTE:' . ($_SERVER['REMOTE_ADDR'] ?? '-')
            . ' METHOD:' . ($_SERVER['REQUEST_METHOD'] ?? 'cli') . ' AGENT:' . ($_SERVER['HTTP_USER_AGENT'] ?? '-'));

        ($behave & FATAL_HTTP_500) && !headers_sent() && http_response_code(500);
        while (ob_get_level()) ($behave & FATAL_OB_FLUSH) ? ob_end_flush() : ob_end_clean();
        exit(1);
    };

    ($behave & HND_ERR) && ( $prev_err_hnd = set_error_handler(
                fn($code, $msg, $file, $line) => 
                    $report($behave, sprintf($format, 'Error', "errno=$code", $msg, $file, $line)) && !($behave & ALLOW_INTERNAL)
            )
    ) || ($prev_err_hnd = null);

    ($behave & HND_EXC) && ($prev_exc_hnd = set_exception_handler(function($e) use ($format, $behave, $prefix, $report, $fatal_exit) {
        $report($behave, sprintf($format, 'Uncaught', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));
        $report($behave, $prefix . $e->getTraceAsString());
        $fatal_exit($behave);
    })) || ($prev_exc_hnd = null);

    ($behave & HND_SHUT) && register_shutdown_function(function() use ($format, $behave, $report, $fatal_exit) {
        $err = error_get_last();
        if ($err && ($err['type'] & PHP_FATAL_ERRORS)) {
            $report($behave, sprintf($format, 'Shutdown', "type={$err['type']}", $err['message'], $err['file'], $err['line']));
            $fatal_exit($behave);
        }
    });

    return function() use ($prev_err_hnd, $prev_exc_hnd) {
        $prev_err_hnd ? set_error_handler($prev_err_hnd) : restore_error_handler();
        $prev_exc_hnd ? set_exception_handler($prev_exc_hnd) : restore_exception_handler();
    };
};