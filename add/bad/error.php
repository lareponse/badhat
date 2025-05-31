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

function vd($first, $second = null, ...$others)
{
    $arity = func_num_args();

    [$label, $depth, $vars] = match ($arity) {
        1 => ['', 1, [$first]],
        2 => [is_string($second) ? $second : '', is_int($second) ? $second : 1, [$first]],
        default => [$first, is_int($second) ? $second : 1, array_slice(func_get_args(), 2)]
    };

    $label = htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8');
    $params = http_build_query(['frames' => $depth, 'arity' => $arity], '', ', ');

    ob_start(); {
        echo str_repeat('-', 23) . __FUNCTION__;
        if (count($vars) > 2)
            printf('[%s, %s] ' . PHP_EOL, $label, $params);
        else
            printf('(%s, %s) ' . PHP_EOL, $label, $params);

        foreach ($vars as $valueToDump) {
            var_dump($valueToDump);
            echo PHP_EOL;
        }

        // 8) Clean up output: remove “root” prefix from absolute paths
    }
    $output = ob_get_clean();

    ob_start(); {
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $depth);
    }
    $frames = '| ' . str_replace(PHP_EOL, PHP_EOL . '| ', trim(ob_get_clean()));
    $rootDir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR;
    $output = $output
        . str_repeat('-', 3)
        . PHP_EOL
        . str_replace($rootDir, DIRECTORY_SEPARATOR, $frames)
        . PHP_EOL
        . str_repeat('-', 23) . '|';

    // 9) Echo as HTML (<pre>) if web, or error_log() if CLI
    if (PHP_SAPI !== 'cli') {
        echo '<pre class="vd">' . $output . PHP_EOL . '</pre>';
    } else {
        error_log($output);
    }

    // 10) Return the very first argument, unchanged
    return $first;
}
