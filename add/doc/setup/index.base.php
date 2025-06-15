<?php

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../..');

require 'add/bad/dad/dev.php';
require 'add/bad/error.php';
require 'add/bad/io.php';
require 'add/bad/auth.php';

define('BASE', realpath(__DIR__ . '/../io'));
define('PATH', io_guard(http_guard(4096, 9)));

$continue   = io_start(BASE . '/route', PATH, 'index');

// no http() call made in the route, we continue with custom handling
$mirror     = BASE . '/render/' . $continue[IO_PATH];
$mirror     = io_absorb($mirror, $continue[IO_ARGS] ?: []);

if (is_array($mirror)) {
    http(...$mirror);
    exit;
}

throw new RuntimeException('Not Found', 404);
