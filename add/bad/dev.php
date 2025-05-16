<?php

putenv('DEV_MODE=true');

/**
 * Scaffold for missing routes (DEV_MODE only)
 */


function vd()
{
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    $caller = $backtrace[0];

    echo "\n" . $caller['file'] . ' (line ' . $caller['line'] . "):\n";

    foreach (func_get_args() as $arg)
        var_dump($arg);
}

// var_dump + debug_backtrace
function vdt()
{
    vd(func_get_args());
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
}
