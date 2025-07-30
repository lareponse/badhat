<?php

function is_dev(): bool
{
    return true;
}

// vd($var): backtrace depth 1 with $var
// vd(0, $var): backtrace depth 0 with $var and label
// vd(2, $var1, $var2): backtrace depth 2 with $var1 and $var2
function vd($first, ...$others)
{
    $die = false;
    if(!empty($others) && is_int($first)){
        $die = $first < 0;
        $depth = $die ? 0 : $first;
    }
    else{
        array_unshift($others, $first);
        $depth = 1;
    }

    ob_start(); {
        debug_print_backtrace(0, $depth);
        foreach ($others as $valueToDump) {
            echo str_repeat('_', 80).PHP_EOL;
            var_dump($valueToDump);
        }
    }
    $dump = ob_get_clean();

    // error_log($dump);
    if (PHP_SAPI !== 'cli' && is_dev()) {
        echo '<pre class="vd">' . $dump . PHP_EOL . '</pre>';
    }

    $die && die;
    return $first; // allows chaining like vd($var)->someMethod() or if(vd($var, 'label')->anotherMethod())
}

// recovered from scaffold.php
function scaffold(){
    /*
    echo 'Missing ' .  (($quest[QST_CORE] & QST_PULL) ? 'render' : 'route' .' end point ') . http_in();
    echo 'Choose file to create in: '.realpath(__DIR__ . '/../../../app/io/route');
    foreach ((io_route(http_in())) as $handler => $args){
        echo PHP_EOL . htmlspecialchars($handler);

        $handlerArgs = empty($args) ? 'no arguments' : "Expected arguments: '" . implode(',', $args) . "'";
        $templateCode = "<?php\n// $handlerArgs\nreturn function (\$quest) {\n\treturn ['status' => 200, 'body' => __FILE__];\n};";

        echo PHP_EOL . htmlspecialchars($templateCode);
    }
    */
}