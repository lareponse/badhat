<?php

declare(strict_types=1);


// E_USER_NOTICE and E_USER_WARNING are just logged, php handles the rest
set_error_handler(function ($errno, $errstr) {
    return error_log("\nPHP ERR#$errno : $errstr") && ($errno === E_USER_NOTICE || $errno === E_USER_WARNING);
});

// Uncaught exceptions are logged and a structured HTTP response is sent
set_exception_handler(function ($e) {
    $message = $e->getMessage();
    $error_id = base_convert((string)mt_rand(), 10, 36);

    error_log(sprintf(
        "UNCAUGHT %s %s: %s in %s:%d",
        get_class($e),
        $error_id,
        $message,
        $e->getFile(),
        $e->getLine()
    ));

    // Extract HTTP code if formatted as "500 Message"
    if (preg_match('/^([1-5]\d{2})\s+(.*)$/s', $message, $m)) {
        respond(response((int)$m[1], $m[2]));
    }

    respond(response(500, "Exceptionnal Exception $error_id: $message"));
});


// Catch fatal errors after shutdown
register_shutdown_function(function () {
    $e = error_get_last();

    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $error_id = base_convert((string)mt_rand(), 10, 36);

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
