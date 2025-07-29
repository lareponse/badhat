<?php

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../..');

require 'add/error.php';
require 'add/io.php';
require 'add/db.php';
require 'add/auth.php';
require 'add/build.php';

$io      = realpath(__DIR__ . '/../io');
$request = http_in();

// Phase 1: Logic
$route = io_route("$io/route", $request, 'index');
$data  = io_fetch($route, [], IO_INVOKE);

// Phase 2: Presentation
$render = io_route("$io/render", $request, 'index');
$html   = io_fetch($render, $data[IO_INVOKE] ?? [], IO_ABSORB);

// Output with 404 fallback
$output = $html[IO_ABSORB] ?? null;
$output && is_string($output)
    ? http_out(200, $output, ['Content-Type' => 'text/html; charset=utf-8'])
    : http_out(404, 'Not Found');
