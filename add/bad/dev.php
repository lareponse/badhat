<?php

putenv('DEV_MODE=true');

/**
 * Scaffold for missing routes (DEV_MODE only)
 */

function vd()
{
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    $backtrace = array_shift($backtrace);

    echo $backtrace['file'] ?? '?' . ' #' . $backtrace['line'] ?? '?' . ":\n";
    foreach (func_get_args() as $arg)
        var_dump($arg);
}

// var_dump + debug_backtrace
function vdt()
{
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    foreach (func_get_args() as $arg)
        var_dump($arg);
}
