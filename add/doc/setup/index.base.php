<?php

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../..');

require 'add/build.php';
require 'add/error.php';
require 'add/io.php';
require 'add/db.php';
require 'add/auth.php';

require 'app/morph/html.php';

// config: where to start (io), where to go (re_quest)
$io        = realpath(__DIR__ . '/../io');
$re_quest  = http_in(4096, 9);

// coding: find the route and invoke it
$in_route  = io_route("$io/route", $re_quest, 'index');
$in_quest  = io_quest($in_route, [], IO_INVOKE);

// render: find the render file and absorb it
$out_route = io_route("$io/render/", $re_quest, 'index');
$out_quest = io_quest($out_route, $in_quest[IO_INVOKE], IO_ABSORB);

if (is_string($out_quest[IO_ABSORB])) {
    http_out(200, $out_quest[IO_ABSORB], ['Content-Type' => 'text/html; charset=utf-8']);
    exit;
}

error_log('404 Not Found for ' . $re_quest . ' in ' . $io);

http_out(404, 'Not Found');
