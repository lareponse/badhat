<?php

// if build.php is included, then we are in dev mode

function is_dev(): void {} //    existence is the flag. define it, you're dev. remove it, production.

// vd($name):                    backtrace depth 1 with $name content var_dump()
// vd($city, $zip, $street):     backtrace depth 1 with $city, $zip and $street content in separate var_dump calls
// vd(0, $var, 'debug msg'):     backtrace depth 0 with $var and label
// vd(2, $var1, $var2):          backtrace depth 2 with $var1 and $var2
function vd(...$variad)
{
    $die = false;
    $dpb_limit = 1;

    if (isset($variad[1]) && is_int($variad[0])) {
        $die = $variad[0] < 0;
        $dpb_limit = $die ? 0 : array_shift($variad);
    }

    echo '<pre class="vd">';

    debug_print_backtrace(0, $dpb_limit);
    echo str_repeat('-', 80) . PHP_EOL;
    var_dump(...$variad);

    echo '</pre>';

    $die && die;

    return $variad[0] ?? null; // allows chaining/inserting in incode without new line or rewrites (for single var only)
}

function assert_that(bool $cond, string $label): void
{
    !$cond && throw new \RuntimeException("ASSERT FAILED: $label", 500);
    echo htmlspecialchars("ASSERT SUCCESS: $label") . PHP_EOL;
}

