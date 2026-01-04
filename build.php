<?php

// if build.php is included, then we are in dev mode
function is_dev(): bool
{
    return true;
}


// vd($name):                    backtrace depth 1 with $name content var_dump()
// vd($city, $zip, $street):     backtrace depth 1 with $city, $zip and $street content in separate var_dump calls
// vd(0, $var, 'debug msg'):     backtrace depth 0 with $var and label
// vd(2, $var1, $var2):          backtrace depth 2 with $var1 and $var2
function vd($first, ...$variad)
{
    $die = false;
    if (!empty($variad) && is_int($first)) {
        $die = $first < 0;
        $dpb_limit = $die ? 0 : $first;
    } else {
        array_unshift($variad, $first);
        $dpb_limit = 1;
    }

    ob_start();
    debug_print_backtrace(0, $dpb_limit);
    echo str_repeat('-', 80).PHP_EOL;
    foreach ($variad as $value) {
        echo str_repeat(' -', 40).PHP_EOL;
        var_dump($value);
    }
    $ob = ob_get_clean();

    if (PHP_SAPI !== 'cli' && is_dev())
        echo '<pre class="vd">' . $ob . PHP_EOL . '</pre>';

    $die && die;

    return $first; // allows chaining like vd($var)->someMethod() or if(vd($var, 'label')->anotherMethod())
}

function assert_that(bool $cond, string $label): void
{
    ($cond && print htmlspecialchars("ASSERT SUCCESS: $label").PHP_EOL)  || throw new \RuntimeException("ASSERT FAILED: $label", 500);
}

// recovered from scaffold.php
function scaffold()
{
    /*
    echo 'Missing ' .  (($quest[QST_CORE] & QST_PULL) ? 'render' : 'route' .' end point ') . http_in();
    echo 'Choose file to create in: '.realpath(__DIR__ . '/../../../app/io/route');
    foreach ((io_map(http_in())) as $handler => $args){
        echo PHP_EOL . htmlspecialchars($handler);

        $handlerArgs = empty($args) ? 'no arguments' : "Expected arguments: '" . implode(',', $args) . "'";
        $templateCode = "<?php\n// $handlerArgs\nreturn function (\$quest) {\n\treturn ['status' => 200, 'body' => __FILE__];\n};";

        echo PHP_EOL . htmlspecialchars($templateCode);
    }
    */
}