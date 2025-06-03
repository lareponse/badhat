<?php

require 'add/bad/error.php';
require 'add/bad/http.php';
require 'add/bad/io.php';
require 'add/bad/db.php';

function deliver($quest)
{
    // vd($quest, 'deliver()');
    $in = io();
    $out = io_out($in);
    $view =  str_replace($in, $out, key($quest['execute']));
    $html = render($quest, $view);
    return http_response(200, $html, ['Content-Type' => 'text/html; charset=UTF-8']);
}
