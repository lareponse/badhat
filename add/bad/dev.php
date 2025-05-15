<?php

putenv('DEV_MODE=true');

/**
 * Scaffold for missing routes (DEV_MODE only)
 */


function vd()
{
    foreach (func_get_args() as $arg)
        var_dump($arg);
}
// var_dump + debug_backtrace
function vdt()
{
    vd(func_get_args());
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
}


function scaffold(string $path, array $candidates): array
{
    $body = "<h1>Missing route: $path</h1>\n\n";
    $body .= "Choose route file to create:\n";
    $body .= "<dl>";
    foreach ($candidates as $depth => $response) {
        $handler = $response['handler'];
        $handlerArgs = empty($response['args']) ? 'none' : implode(',', $response['args']);
        $templateCode = "<?php\nreturn function (...\$args) {\n\t// Expected arguments: function($handlerArgs)\n\treturn ['status' => 200, 'body' => __FILE__];\n};";
        $body .= "<dt><strong>$handler</strong></dt>";
        $body .= '<dd><pre>' . htmlspecialchars($templateCode) . '</pre></dd>';
    }
    $body .= "</dl>";


    return response(404, $body);
}
