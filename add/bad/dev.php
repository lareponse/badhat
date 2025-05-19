<?php

putenv('DEV_MODE=true');

/**
 * Scaffold for missing routes (DEV_MODE only)
 */

function vd(...$args)
{
    $bt_depth = 1;

    if($args[0] && $args[0] === __FUNCTION__) {
        array_shift($args);
        $bt_depth = 0;
    }

    ob_start();{
        foreach ($args as $arg) {
            var_dump($arg);
            echo PHP_EOL;
        }
    }
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $bt_depth);
    $_ = ob_get_clean();
    echo '<pre>'. $_.'</pre>';
    return $_;
}
