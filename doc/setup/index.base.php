<?php
// public/index.php — BADHAT Entry Point

set_include_path(__DIR__ . '/..' . PATH_SEPARATOR . get_include_path());

require 'add/badhat/error.php';
require 'add/badhat/io.php';
require 'add/badhat/run.php';
require 'add/badhat/http.php';
require 'add/badhat/db.php';
require 'add/badhat/auth.php';
require 'add/badhat/csrf.php';

use function bad\io\{io_in, io_map};
use function bad\run\run;
use function bad\http\http_out;

use const bad\error\{SET_ALL, MESSAGE_LOG};
use const bad\io\{IO_PATH_ONLY, IO_ROOTLESS, IO_TAIL, IO_NEST};
use const bad\run\{RUN_INVOKE, RUN_ABSORB, RUN_RETURN};

bad\error\register(SET_ALL | MESSAGE_LOG);

session_start();

bad\csrf\csrf('_', bad\csrf\CSRF_SETUP);

$stmt = bad\db\qp("SELECT password FROM users WHERE username = ?", []);
bad\auth\checkin(bad\auth\AUTH_SETUP, 'username', $stmt);

$io = __DIR__ . '/../app/io';
$path = io_in($_SERVER['REQUEST_URI'], "\0", IO_PATH_ONLY | IO_ROOTLESS);

// Phase 1: Route (logic)
$route = io_map($io . '/route/', $path, '.php', IO_TAIL);
$loot = $route ? run($route, [], RUN_INVOKE) : [];

// Phase 2: Render (presentation)
$render = io_map($io . '/render/', $path, '.php', IO_TAIL | IO_NEST);
$loot = $render ? run($render, $loot, RUN_ABSORB) : $loot;

// Output
isset($loot[RUN_RETURN]) && is_string($loot[RUN_RETURN])
    ? http_out(200, $loot[RUN_RETURN], ['Content-Type' => ['text/html; charset=utf-8']])
    : http_out(404, 'Not Found');