<?php

function is_dev(): bool
{
    return true;
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

    error_log($dump);
    if (PHP_SAPI !== 'cli' && is_dev()) {
        echo '<pre class="vd">' . $dump . PHP_EOL . '</pre>';
    }

    return $first; // allows chaining like vd($var)->someMethod() or if(vd($var, 'label')->anotherMethod())
}
