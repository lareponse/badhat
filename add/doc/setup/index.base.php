<?php

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../..');

require 'add/http.php';
require 'add/io.php';
require 'add/core.php';
require 'add/bad/error.php';
require 'add/bad/db.php';
require 'add/bad/ui.php';
require 'add/bad/guard_auth.php';


$request   = http_request();
// vd($request, 'http_request()');

$plan       = http_guard($request);
$base       = io(__DIR__ . '/../io/route');
$map        = io_look($plan, $base[0]);
$quest      = io_read($map);
$quest      = io_walk($quest);
$response   = deliver($quest) ?: http_response(404, "Not Found", ['Content-Type' => 'text/plain']);

if (is_dev() && empty($response['status']) || $response['status'] >= 400) {
    $response = http_response(404, io_scaffold('in'), ['Content-Type' => 'text/html; charset=UTF-8']);
}

http_echo(...$response);
exit;
