<?php
// public/index.php — BADHAT Entry Point

set_include_path(__DIR__ . '/..' . PATH_SEPARATOR . get_include_path());

fireff$install = require 'add/badhat/trap.php';
require 'add/badhat/map.php';
require 'add/badhat/run.php';
require 'add/badhat/http.php';
require 'add/badhat/pdo.php';
require 'add/badhat/auth.php';
require 'add/badhat/csrf.php';

use const bad\map\REBASE;
use const bad\run\{INVOKE, ABSORB, RESULT};
use const bad\http\ONE;

// --------------------------------------------------
// Bootstrap
// --------------------------------------------------

$restore = $install(bad\trap\HND_ALL);

session_start();

$pdo = new PDO(
    getenv('DB_DSN')  ?: 'mysql:host=127.0.0.1;dbname=app;charset=utf8mb4',
    getenv('DB_USER') ?: 'root',
    getenv('DB_PASS') ?: '',
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
);

bad\pdo\db($pdo);

// auth setup (optional)
$stmt = bad\pdo\qp("SELECT password FROM users WHERE username = ?", []);
bad\auth\checkin(bad\auth\SETUP, 'username', $stmt);

// --------------------------------------------------
// Normalize request path
// --------------------------------------------------

$io_root = __DIR__ . '/../app/io';
$base = realpath($io_root . '/route') . '/';
$key = bad\map\hook($_SERVER['REQUEST_URI'], "\0");

// --------------------------------------------------
// Phase 1 — Route (logic)
// --------------------------------------------------

$route = bad\map\seek($base, $key, '.php');
$loot  = [];

if ($route) {
    [$file, $args] = $route;
    $loot = bad\run\loot([$file], $args, INVOKE);
}

// --------------------------------------------------
// Phase 2 — Render (presentation)
// --------------------------------------------------

$render = bad\map\look($io_root . '/render/', $key, '.php', REBASE);

if ($render) {
    $loot = bad\run\loot([$render], $loot, ABSORB);
}

// --------------------------------------------------
// Output
// --------------------------------------------------

if (isset($loot[RESULT]) && is_string($loot[RESULT])) {
    bad\http\headers(ONE, 'Content-Type', 'text/html; charset=utf-8');
    exit(bad\http\out(200, $loot[RESULT]));
}

exit(bad\http\out(404, 'Not Found'));