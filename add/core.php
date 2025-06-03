<?php

declare(strict_types=1);

require 'add/io.http.php';
require 'add/io.file.php';

function deliver($quest): array
{
    // vd($quest, 'deliver()');
    $in = io();
    $out = io_out($in);
    $view =  str_replace($in, $out, $quest['execute']['handler']);
    $html = render($quest, $view);
    return http_response(200, $html, ['Content-Type' => 'text/html; charset=UTF-8']);
}
