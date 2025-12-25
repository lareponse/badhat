<?php
// public/index.php - BADHAT Entry Point

set_include_path(__DIR__ . '/..' . PATH_SEPARATOR . get_include_path());

require 'add/badhat/error.php';
require 'add/badhat/io.php';
require 'add/badhat/db.php';
require 'add/badhat/auth.php';

badhat_install_error_handlers();

$io = __DIR__ . '/../app/io';

[$path, $accept] = io_in($_SERVER['REQUEST_URI']);

// Phase 1: Route (logic)
$route = io_map($io . '/route', $path, 'php', IO_DEEP);
$loot = $route ? io_run($route, [], IO_INVOKE) : [];

// Phase 2: Render (presentation)
$render = io_map($io . '/render', $path, 'php', IO_DEEP | IO_NEST);
$loot = $render ? io_run($render, $loot, IO_ABSORB) : $loot;

// Output
isset($loot[IO_RETURN]) && is_string($loot[IO_RETURN])
    ? io_die(200, $loot[IO_RETURN], ['Content-Type' => 'text/html; charset=utf-8'])
    : io_die(404, 'Not Found');