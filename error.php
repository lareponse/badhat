<?php

const EH_ERROR     = 1;    // set_error_handler
const EH_EXCEPTION = 2;    // set_exception_handler
const EH_SHUTDOWN  = 4;    // register_shutdown_function
const EH_SUPPRESS  = 8;    // register_shutdown_function

const EH_HANDLE_ALL = EH_ERROR | EH_EXCEPTION | EH_SHUTDOWN;

function badhat_install_error_handlers(int $behave = EH_HANDLE_ALL, ?string $request_id = null): string
{
    $request_id ??= bin2hex(random_bytes(4));
    $prefix = "[req=$request_id] ";
    $format = $prefix . '%s (%s) %s in %s:%d';
    $fatal_exit = function () use ($prefix): void {
        $start  = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        error_log(
            $prefix
            . 'EXEC:'   . (microtime(true) - $start)
            . ' MEM:'   . memory_get_peak_usage(true)
            . ' URI:'   . ($_SERVER['REQUEST_URI']     ?? 'cli')
            . ' REMOTE:'. ($_SERVER['REMOTE_ADDR']     ?? 'n/a')
            . ' AGENT:' . ($_SERVER['HTTP_USER_AGENT'] ?? '')
            . ' METHOD:'. ($_SERVER['REQUEST_METHOD']  ?? 'cli')
            . ' GET:'   . count($_GET)
            . ' POST:'  . count($_POST)
            . ' SESSION:' . (isset($_SESSION) ? count($_SESSION) : 0)
            . ' COOKIES:' . count($_COOKIE)
            . ' FILES:' . count($_FILES)
        );
        ob_get_length() && ob_clean();
        http_response_code(500);
        exit(1);
    };

    (EH_ERROR & $behave)       && set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use ($format, $behave): bool {
            error_log(sprintf($format, 'Error', "errno={$errno}", $errstr, $errfile, $errline));
            return (bool)(EH_SUPPRESS & $behave);
        }
    );

    (EH_EXCEPTION & $behave)   && set_exception_handler(function (Throwable $e) use ($format, $prefix, $fatal_exit): void {
            error_log(sprintf($format, 'Uncaught', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));
            error_log($prefix . $e->getTraceAsString());
            $fatal_exit();
        }
    );

    (EH_SHUTDOWN & $behave)    && register_shutdown_function(function () use ($format, $fatal_exit): void {
            $err = error_get_last();
            if (!$err || !($err['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR)))
                return;
            error_log(sprintf($format, 'Shutdown', "type={$err['type']}", $err['message'], $err['file'], $err['line']));
            $fatal_exit();
        }
    );

    return $request_id;
}
