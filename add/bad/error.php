<?php

declare(strict_types=1);

error_reporting(E_ALL);


// E_USER_NOTICE and E_USER_WARNING are just logged, php handles the rest
set_error_handler(function (int $errno, string $errstr): bool {
    return ($errno === E_USER_NOTICE  && quest(200, $errstr) && error_log("E_USER_NOTICE: $errstr"))
        || ($errno === E_USER_WARNING && quest(400, $errstr) && error_log("E_USER_WARNING: $errstr")) 
        || false;
});

// Uncaught exceptions are logged and a structured HTTP response is sent
set_exception_handler(function (Throwable $e) {
    $message  = $e->getMessage();
    $code     = $e->getCode() ?: 500;
    $error_id = base_convert((string)random_int(100000, 999999), 10, 36);

    error_log(sprintf(
        "UNCAUGHT %s %s: [%d] %s in %s:%d",
        get_class($e),
        $error_id,
        $code,
        $message,
        $e->getFile(),
        $e->getLine()
    ));

    if (is_dev()) {
        vd("$message in {$e->getFile()}:{$e->getLine()}", 0);
        die;
    }

    respond(response($code, "Exception $error_id: $message"));
});


// Catch fatal errors after shutdown
register_shutdown_function(function () {
    $e = error_get_last();

    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $error_id = base_convert((string)random_int(100000, 999999), 10, 36);

        error_log(sprintf(
            "SHUTDOWN FATAL %s: [%d] %s in %s:%d",
            $error_id,
            $e['type'],
            $e['message'],
            $e['file'],
            $e['line']
        ));

        // Attempt to respond with structured HTTP response if headers not sent
        if (function_exists('respond') && !headers_sent()) {
            respond(response(500, "Fatal Error $error_id: {$e['message']}"));
        } else {
            // Fallback: raw text for CLI or broken HTTP context
            exit("500 FATAL $error_id: {$e['message']}");
        }
    }
});

function quest(?int $code = null, ?string $message = null): array
{
    static $journal = [];

    if ($code === null && $message === null) {
        return $journal;
    }

    if ($code === null) {
        $journal = [];
        return [];
    }

    $journal[] = ['code' => $code, 'message' => $message];
    return $journal;
}


function is_dev(): bool
{
    static $isDev;
    return $isDev ?? ($isDev = filter_var(getenv('DEV_MODE') ?: ($_ENV['DEV_MODE'] ?? '0'), FILTER_VALIDATE_BOOLEAN));
}

// dump a single value + backtrace, either to screen (dev) or to error_log.
function vd($arg, int $bt_depth = 1)
{
    // Capture both var_dump and debug_print_backtrace into a string
    ob_start(); {
        var_dump($arg);
        echo PHP_EOL;
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $bt_depth);
    }
    $buffer = ob_get_clean();

    if (is_dev()) {
        // In dev mode, show on the page
        echo '<pre class="vd">' . $buffer . '</pre>';
    } else {
        // In production, send to the error log
        error_log($buffer);
    }

    return $arg;
}

// dump any number of values + 1-frame backtrace, either to screen (dev) or to error_log.
function vvd(...$args)
{
    ob_start(); {
        foreach ($args as $arg) {
            var_dump($arg);
            echo PHP_EOL;
        }
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    }
    $buffer = ob_get_clean();

    if (is_dev()) {
        echo '<pre class="vvd">' . $buffer . '</pre>';
    } else {
        error_log($buffer);
    }
}

