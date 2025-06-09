<?php

declare(strict_types=1);

error_reporting(E_ALL);


// E_USER_NOTICE and E_USER_WARNING are just logged, php handles the rest
set_error_handler(function (int $errno, string $errstr): bool {
    return ($errno === E_USER_NOTICE  && journal(200, $errstr) && error_log("E_USER_NOTICE: $errstr"))
        || ($errno === E_USER_WARNING && journal(400, $errstr) && error_log("E_USER_WARNING: $errstr"))
        || false;
});

// Uncaught exceptions are logged and a structured HTTP response is sent
set_exception_handler(function (Throwable $e) {
    $code     = $e->getCode() ?: 500;
    $error_id = base_convert((string)random_int(100000, 999999), 10, 36);
    $message = ($e->getMessage() ?: 'Uncaught exception') . " ($error_id)";
    error_log(sprintf(
        "UNCAUGHT %s: [%d] %s in %s:%d",
        get_class($e),
        $code,
        $message,
        $e->getFile(),
        $e->getLine()
    ));

    if (is_dev()) {
        vd($message, 0);
        die;
    }

    http_respond($code, "Exception: $message");
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
            http_respond(500, "Fatal Error $error_id: {$e['message']}");
        } else {
            // Fallback: raw text for CLI or broken HTTP context
            exit("500 FATAL $error_id: {$e['message']}");
        }
    }
});

function journal(int $code, string $message): bool
{
    static $entries = [];
    $entries[] = ['code' => $code, 'message' => $message, 'time' => time()];
    return true;  // For boolean chaining in error handler
}

function is_dev(): bool
{
    static $isDev;
    return $isDev ?? ($isDev = filter_var(getenv('DEV_MODE') ?: ($_ENV['DEV_MODE'] ?? '0'), FILTER_VALIDATE_BOOLEAN));
}

//var_dump with variable-depth backtrace and optional message
function vd($first, $second = null, ...$others)
{
    $arity = func_num_args(); // yeah.. my tools, my rules. 

    $arity == 1 && [$label, $depth, $vars] = ['', 1, [$first]];
    $arity == 2 && [$label, $depth, $vars] = [is_string($second) ? $second : '', is_int($second) ? $second : 1, [$first]];
    $arity >= 3 && [$label, $depth, $vars] = [$first, is_int($second) ? $second : 1, array_slice(func_get_args(), 2)];

    ob_start(); {
        if ($label)
            echo '#--' . $label . PHP_EOL;
        debug_print_backtrace(0, $depth);
    }
    $frames = trim(ob_get_clean());
    $skip_dirs = realpath(__DIR__ . '/../..') . DIRECTORY_SEPARATOR;
    $frames = str_replace($skip_dirs, DIRECTORY_SEPARATOR, $frames);

    ob_start(); {
        echo $frames;
        foreach ($vars as $valueToDump) {
            echo PHP_EOL;
            var_dump($valueToDump);
        }
        // echo $frames;
    }
    $dump = ob_get_clean();

    if (PHP_SAPI !== 'cli') {
        echo '<pre class="vd">' . $dump . PHP_EOL . '</pre>';
    } else {
        error_log($dump);
    }

    return $first; // allows chaining like vd($var)->someMethod() or vd($var, 'label')->anotherMethod()
}
