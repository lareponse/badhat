<?php

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../..');

require 'add/bad/dad/dev.php';
require 'add/bad/error.php';
require 'add/bad/io.php';
require 'add/bad/db.php';
require 'add/bad/auth.php';

require 'app/morph/html.php';

define('HOME_BASE', realpath(__DIR__ . '/../io'));
define('SAFE_PATH', io_guard(http_guard(4096, 9)));
define('FILE_ROOT', 'index');

$in_route     = io_route(HOME_BASE . '/route', SAFE_PATH, FILE_ROOT);
$in_quest     = io($in_route, [], IO_INVOKE);

$out_route    = io_route(HOME_BASE . '/render/', SAFE_PATH, FILE_ROOT);
$out_quest    = io($out_route, $in_quest[IO_INVOKE], IO_ABSORB);

if (is_string($out_quest[IO_ABSORB])) {
    http(200, $out_quest[IO_ABSORB], ['Content-Type' => 'text/html; charset=utf-8']);
    exit;
}

error_log('404 Not Found for ' . SAFE_PATH . ' in ' . HOME_BASE);
http(404, 'Not Found');
