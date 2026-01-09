<?php
namespace bad\error;

const HND_ERR  = 1;
const HND_EXC  = 2;
const HND_SHUT = 4;
const HND_ALL  = HND_ERR | HND_EXC | HND_SHUT;
const ERR_LOG  = 8;
const ERR_OSD  = 16;
const MSG_WITH_TRACE = 32;
const ALLOW_INTERNAL = 64;
const FATAL_OB_FLUSH = 128;
const FATAL_HTTP_500 = 256;

const PHP_FATAL_ERRORS = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR;

const CODE_ACE = 0xACE;  // 2766 - Abnormal Computing Environment
const CODE_BAD = 0xBAD;  // 2989 - Broken Abstraction Detected
const CODE_COD = 0xC0D;  // 3085 - Code Of Doom

return function (int $behave = HND_ALL | ERR_LOG, ?string $request_id = null): callable {
    $start          = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
    $request_id   ??= bin2hex(random_bytes(4));

    $report = function ($source, $code, $message, $file, $line, $trace) use ($behave, $request_id) {
        $msg = "[req=$request_id] $source ($code) $message";
        ($file || $line)            && ($msg .= PHP_EOL."in $file($line)");
        ($behave & MSG_WITH_TRACE)  && ($msg .= PHP_EOL . $trace);
        ($behave & ERR_OSD)         && print '<pre>'.$msg.'</pre>';
        ($behave & ERR_LOG)         && error_log($msg);
    };

    $fatal_exit = function () use ($behave, $report, $start) {
        $report('Fatal', 1, 'EXEC:' . (microtime(true) - $start) . ' MEM:' . memory_get_peak_usage(true)
            . ' URI:' . ($_SERVER['REQUEST_URI'] ?? 'cli') . ' REMOTE:' . ($_SERVER['REMOTE_ADDR'] ?? '-')
            . ' METHOD:' . ($_SERVER['REQUEST_METHOD'] ?? 'cli') . ' AGENT:' . ($_SERVER['HTTP_USER_AGENT'] ?? '-'), 0, 0, '');

        ($behave & FATAL_HTTP_500) && !headers_sent() && http_response_code(500);
        for ($n = ob_get_level(); $n > 0; --$n)($behave & FATAL_OB_FLUSH) ? @ob_end_flush() : @ob_end_clean(); // buffers dont always close, infinite loop is possible
        exit(1);
    };

    ($behave & HND_ERR) && ($prev_err_hnd = set_error_handler(function ($code, $message, $file, $line) use ($behave, $report) {
        ($behave & MSG_WITH_TRACE) && ob_start() && !debug_print_backtrace() && ($trace = ob_get_clean());
        $report('HND_ERR', "errno=$code", $message, $file, $line, $trace ?? '');
        return !($behave & ALLOW_INTERNAL);
    })) || ($prev_err_hnd = null);

    ($behave & HND_EXC) && ($prev_exc_hnd = set_exception_handler(function ($e) use ($behave, $report, $fatal_exit) {
        $trace = ($behave & MSG_WITH_TRACE) ? $e->getTraceAsString() : '';
        do $report($e::class, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine(), $trace);
        while ($e = $e->getPrevious());
        $fatal_exit();
    })) || ($prev_exc_hnd = null);

    ($behave & HND_SHUT) && register_shutdown_function(function () use ($report, $fatal_exit) {
        if (($err = error_get_last()) && ($err['type'] & PHP_FATAL_ERRORS)) {
            $report('HND_SHUT', "type={$err['type']}", $err['message'], $err['file'], $err['line'], '');
            $fatal_exit();
        }
    });

    return function () use ($prev_err_hnd, $prev_exc_hnd) {
        $prev_err_hnd ? set_error_handler($prev_err_hnd) : restore_error_handler();
        $prev_exc_hnd ? set_exception_handler($prev_exc_hnd) : restore_exception_handler();
    };
};