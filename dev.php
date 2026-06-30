<?php
// vd(...) dumps values + a short backtrace and allow inline use: foo(vd($x))
// Modes:
//   vd($name)                    backtrace depth 1, dump $name
//   vd($city, $zip, $street)     backtrace depth 1, dump each arg
//   vd(0, $var, 'debug msg')     backtrace depth 0, dump args
//   vd(2, $var1, $var2)          backtrace depth 2, dump args
//
// Death variants:
//   vd(-1, ...)                  same as vd(1, ...), then die
//   vd(-2, ...)                  same as vd(2, ...), then die
//
// Footgun:
//   If the first argument is an int AND there is at least one more argument,
//   that int is consumed as the backtrace depth and is NOT dumped.
//   Example: vd(404, $body) dumps only $body (frames=404), not 404.
//
// Fix:
//   If you need to dump an int first, pass the depth explicitly:
//   vd(1, 404, $body) dumps 404 and $body with a 1-frame backtrace.

function vd(...$v)
{// provides a visual dump of variables with custom-depth backtrace
    $frames = isset($v[1]) && is_int($v[0]) ? array_shift($v) : 1;  // debug_print_backtrace frame limit (0 => none)

    $frames && debug_print_backtrace(0, abs($frames));              // frame list: where vd() was called from
    echo str_repeat('-', 80) . PHP_EOL;                             // visual separator
    var_dump(...$v);                                                // payload dump
    
    $frames < 0 && die('bad\die');

    return $v[0] ?? null;                                           // (only meaningful for 1st value)
}// dies or returns the first value (for inline chaining).

function vdh(...$v): void
{// HTML wrapper for readable dumps
    echo '<pre class="vd">'; 
    vd(...$v);
    echo '</pre>';
}

