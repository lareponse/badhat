<?php

// Normalize DEV_MODE to a boolean once, so getenv() isn’t called on every debug call
define('IS_DEV', filter_var(getenv('DEV_MODE'), FILTER_VALIDATE_BOOLEAN));

/**
 * Dump a single value + backtrace, either to screen (dev) or to error_log.
 */
function vd($arg, int $bt_depth = 1)
{
    // Capture both var_dump and debug_print_backtrace into a string
    ob_start(); {
        var_dump($arg);
        echo PHP_EOL;
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $bt_depth);
    }
    $buffer = ob_get_clean();

    if (IS_DEV) {
        // In dev mode, show on the page
        echo '<pre class="vd">' . $buffer . '</pre>';
    } else {
        // In production, send to the error log
        error_log($buffer);
    }

    return $arg;
}

//Dump any number of values + 1-frame backtrace, either to screen (dev) or to error_log.
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

    if (IS_DEV) {
        echo '<pre class="vvd">' . $buffer . '</pre>';
    } else {
        error_log($buffer);
    }
}