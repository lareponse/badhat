<?php

namespace bad\error;

// install handlers
const HND_ERR   = 1;   // set_error_handler
const HND_EXC   = 2;   // set_exception_handler
const HND_SHUT  = 4;   // register_shutdown_function
const SET_ALL = HND_ERR | HND_EXC | HND_SHUT;

// behavior flags
const ERR_SUPPRESS_PHP = 8;    // return true from error handler (hide PHP internal handler)
const LOG_ERR          = 16;   // write to error_log (default off if you want explicit)
const OSD_ERR          = 32;   // print to stdout/stderr (on-screen display)
const OB_FLUSH_FATAL   = 64;   // flush output buffers on fatal exit (else discard)


const PHP_FATAL_ERRORS = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR;

function report(int $behave , string $message)
{
    (OSD_ERR & $behave)  && print $message;
    (LOG_ERR & $behave)  && error_log($message);
}

function register(int $behave=SET_ALL|LOG_ERR, ?string $request_id = null): array
{
    $start  = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);

    $request_id ??= bin2hex(random_bytes(4));
    $prefix = "[req=$request_id] ";
    $format = $prefix . '%s (%s) %s in %s:%d';


    $fatal_exit = function () use ($prefix, $behave, $start): void {
        report(
            $behave,
            $prefix
            . 'EXEC:'   . (microtime(true) - $start). ' MEM:'   . memory_get_peak_usage(true)
            . ' URI:'   . ($_SERVER['REQUEST_URI']     ?? 'cli') . ' REMOTE:'. ($_SERVER['REMOTE_ADDR'] ?? 'n/a') . ' AGENT:' . ($_SERVER['HTTP_USER_AGENT'] ?? '')
            . ' METHOD:'. ($_SERVER['REQUEST_METHOD']  ?? 'cli') . ' #GET:' . count($_GET) . ' #POST:'  . count($_POST)
            . ' #SESSION:' . (isset($_SESSION) ? count($_SESSION) : 0) . ' #COOKIES:' . count($_COOKIE) . ' #FILES:' . count($_FILES)
        );

        headers_sent() || http_response_code(500);

        while (ob_get_level())
            $behave & OB_FLUSH_FATAL ? ob_end_flush() : ob_end_clean();

        exit(1);
    };

    $prev_err_handler = null;
    $prev_exc_handler = null;

    (HND_ERR & $behave)       && ($prev_err_handler = set_error_handler(
        function (int $errno, string $errstr, string $errfile, int $errline) use ($format, $behave): bool {
            $message = sprintf($format, 'Error', "errno={$errno}", $errstr, $errfile, $errline);
            report($behave, $message);
            return (bool)(ERR_SUPPRESS_PHP & $behave);
        }
    ));

    (HND_EXC & $behave)   && ($prev_exc_handler = set_exception_handler(
        function (\Throwable $e) use ($format, $behave, $prefix, $fatal_exit): void {
            $message = sprintf($format, 'Uncaught', $e::class, $e->getMessage(), $e->getFile(), $e->getLine());
            report($behave, $message);
            report($behave, $prefix . $e->getTraceAsString());
            $fatal_exit();
        }
    ));

    (HND_SHUT & $behave) && register_shutdown_function(
        function () use ($format, $behave, $fatal_exit): void {
            $err = error_get_last();

            if (!$err || !($err['type'] & PHP_FATAL_ERRORS))
                return; // set_error_handler()
            report($behave, sprintf($format, 'Shutdown', "type={$err['type']}", $err['message'], $err['file'], $err['line']));

            $fatal_exit();
        }
    );

    return [
        'request_id' => $request_id,
        'previous' => ['set_error_handler' => $prev_err_handler, 'set_exception_handler' => $prev_exc_handler],
        'restore' => function () use ($prev_err_handler, $prev_exc_handler): void {
            $prev_err_handler ? set_error_handler($prev_err_handler) : restore_error_handler();
            $prev_exc_handler ? set_exception_handler($prev_exc_handler) : restore_exception_handler();
        }
    ];
}
