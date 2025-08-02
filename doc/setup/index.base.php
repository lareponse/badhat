<?php

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../..');

require 'add/badhat/build.php';
require 'add/badhat/error.php';
require 'add/badhat/io.php';
require 'add/badhat/db.php';
require 'add/badhat/auth.php';

require 'add/arrow/arrow.php';  // Load arrow library

$io      = __DIR__ . '/../io';
$request = http_in();

// Phase 1: Logic
$route = io_map("$io/route", $request, 'index');
$data  = io_run($route, [], IO_INVOKE);

// Phase 2: Presentation
$render = io_map("$io/render", $request, 'index');
$html   = io_run($render, $data[IO_INVOKE] ?? [], IO_ABSORB);

// Output with 404 fallback
$output = $html[IO_ABSORB] ?? null;
$output && is_string($output)
    ? http_out(200, $output, ['Content-Type' => 'text/html; charset=utf-8'])
    : http_out(404, 'Not Found');
