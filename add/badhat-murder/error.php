<?php

namespace bad\error;

// install handlers
const HND_ERR   = 1;   // set_error_handler
const HND_EXC   = 2;   // set_exception_handler
const HND_SHUT  = 4;   // register_shutdown_function
const HND_ALL   = HND_ERR | HND_EXC | HND_SHUT; // (default)

// behavior flags
const ALLOW_INTERNAL   = 8;  // allow PHP internal error handler to run
const ERR_LOG          = 16;   // write to error_log (default)
const ERR_OSD          = 32;   // print to stdout/stderr (on-screen display)
const FATAL_OB_FLUSH   = 64;   // flush output buffers on fatal exit (else discard)
const FATAL_HTTP_500   = 128;  // emit 500 http code

// badhat classification code, for reference
const CODE_ACE = 0xACE;  // PHP execution, missing or failing env   (2766 - Abnormal Computing Environment)
const CODE_BAD = 0xBAD;  // badhat misuse or edge case              (2989 - Broken Abstraction Detected)
const CODE_COD = 0xC0D;  // userland origin (invoked callable)      (3085 - Code Of Doom)

const PHP_FATAL_ERRORS = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR;

$report = function ($behave, string $message): void {
        (ERR_OSD & $behave) && print $message;
        (ERR_LOG & $behave) && error_log($message);
};

$fatal_exit = function ($behave, $prefix, $start) use($report): void {
    $report(
        $behave,
        $prefix
        . 'EXEC:'   . (microtime(true) - $start). ' MEM:'   . memory_get_peak_usage(true)
        . ' URI:'   . ($_SERVER['REQUEST_URI']     ?? 'cli') . ' REMOTE:'. ($_SERVER['REMOTE_ADDR'] ?? 'n/a') . ' AGENT:' . ($_SERVER['HTTP_USER_AGENT'] ?? '')
        . ' METHOD:'. ($_SERVER['REQUEST_METHOD']  ?? 'cli') . ' #GET:' . count($_GET) . ' #POST:'  . count($_POST)
        . ' #SESSION:' . (isset($_SESSION) ? count($_SESSION) : 0) . ' #COOKIES:' . count($_COOKIE) . ' #FILES:' . count($_FILES)
    );

    (FATAL_HTTP_500 & $behave) && (headers_sent() || http_response_code(500));

    while (ob_get_level())
        $behave & FATAL_OB_FLUSH ? ob_end_flush() : ob_end_clean();

    exit(1);
};

return function (int $behave = HND_ALL | ERR_LOG, ?string $request_id = null) use($report, $fatal_exit): callable
{
    $start  = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);

    $request_id ??= bin2hex(random_bytes(4));
    $prefix = "[req=$request_id] ";
    $format = $prefix . '%s (%s) %s in %s:%d';

    $prev_err_handler = null;
    $prev_exc_handler = null;

    (HND_ERR & $behave) && ($prev_err_handler = set_error_handler(
        function (int $errno, string $errstr, string $errfile, int $errline) use ($format, $behave, $report): bool {
            $message = sprintf($format, 'Error', "errno={$errno}", $errstr, $errfile, $errline);
            $report($behave, $message);
            return !(ALLOW_INTERNAL & $behave);
        }
    ));

    (HND_EXC & $behave) && ($prev_exc_handler = set_exception_handler(
        function (\Throwable $e) use ($format, $behave, $prefix, $fatal_exit, $start, $report): void {
            $message = sprintf($format, 'Uncaught', $e::class, $e->getMessage(), $e->getFile(), $e->getLine());
            $report($behave, $message);
            $report($behave, $prefix . $e->getTraceAsString());
            $fatal_exit($behave, $prefix, $start);
        }
    ));

    (HND_SHUT & $behave) && register_shutdown_function(
        function () use ($format, $behave, $prefix, $start, $fatal_exit, $report): void {
            $err = error_get_last();

            if ($err && $err['type'] & PHP_FATAL_ERRORS){
                $message = sprintf($format, 'Shutdown', "type={$err['type']}", $err['message'], $err['file'], $err['line']);
                $report($behave, $message);
                $fatal_exit($behave, $prefix, $start);
            }
        }
    );

    return function () use ($prev_err_handler, $prev_exc_handler): void {
        $prev_err_handler ? set_error_handler($prev_err_handler) : restore_error_handler();
        $prev_exc_handler ? set_exception_handler($prev_exc_handler) : restore_exception_handler();
    };
}
