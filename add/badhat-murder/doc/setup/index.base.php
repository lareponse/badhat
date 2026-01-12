<?php
// public/index.php — BADHAT Entry Point

set_include_path(__DIR__ . '/..' . PATH_SEPARATOR . get_include_path());

$install = require 'add/badhat/error.php';
require 'add/badhat/io.php';
require 'add/badhat/run.php';
require 'add/badhat/http.php';
require 'add/badhat/db.php';
require 'add/badhat/auth.php';
require 'add/badhat/csrf.php';

use function bad\io\{path, io_map};
use function bad\run\run;
use function bad\http\http_out;
use function bad\db\{db, qp};
use function bad\auth\checkin;
use function bad\csrf\csrf;

use const bad\error\{HND_ALL};
use const bad\io\{IO_URL, IO_NEST};
use const bad\run\{RUN_INVOKE, RUN_ABSORB, RUN_RETURN};
use const bad\auth\AUTH_SETUP;
use const bad\csrf\CSRF_SETUP;

$install(HND_ALL);

session_start();

csrf(CSRF_SETUP, '_csrf', 3600);

$stmt = qp("SELECT password FROM users WHERE username = ?", []);
checkin(AUTH_SETUP, 'username', $stmt);

$io = __DIR__ . '/../app/io';
$path = path($_SERVER['REQUEST_URI'], "\0", IO_URL);

// Phase 1: Route (logic)
$route = io_map($io . '/route/', $path, '.php');
$loot = $route ? run($route, [], RUN_INVOKE) : [];

// Phase 2: Render (presentation)
$render = io_map($io . '/render/', $path, '.php', IO_NEST);
$loot = $render ? run($render, $loot, RUN_ABSORB) : $loot;

// Output
isset($loot[RUN_RETURN]) && is_string($loot[RUN_RETURN])
    ? http_out(200, $loot[RUN_RETURN], ['Content-Type' => ['text/html; charset=utf-8']])
    : http_out(404, 'Not Found');