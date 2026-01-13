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

use function bad\io\{look, seek};
use function bad\http\http_out;
use const bad\io\{IO_NEST};
use const bad\run\{RUN_INVOKE, RUN_ABSORB, RUN_RETURN};

// --------------------------------------------------
// Bootstrap
// --------------------------------------------------

$restore = $install(bad\error\HND_ALL);

session_start();

bad\csrf\csrf(bad\csrf\CSRF_SETUP, '_csrf', 3600);

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

bad\db\db($pdo); // <- seed the static cache used by qp()

// auth example (unchanged, but now explicit)
$stmt = bad\db\qp("SELECT password FROM users WHERE username = ?", []);
bad\auth\checkin(bad\auth\AUTH_SETUP, 'username', $stmt);

// --------------------------------------------------
// Normalize request path
// --------------------------------------------------

$io_root = __DIR__ . '/../app/io';
$key = bad\io\path($_SERVER['REQUEST_URI'], "\0");

// --------------------------------------------------
// Phase 1 — Route (logic)
// --------------------------------------------------

$route = seek($io_root . '/route/', $key, '.php');
$loot  = [];

if ($route) {
    [$file, $args] = $route;
    $loot = bad\run\run([$file], $args, RUN_INVOKE);
}

// --------------------------------------------------
// Phase 2 — Render (presentation)
// --------------------------------------------------

$render = look($io_root . '/render/', $key, '.php', IO_NEST);

if ($render) {
    $loot = bad\run\run([$render], $loot, RUN_ABSORB);
}

// --------------------------------------------------
// Output
// --------------------------------------------------

isset($loot[RUN_RETURN]) && is_string($loot[RUN_RETURN])
    ? http_out(200, $loot[RUN_RETURN], [
        'Content-Type' => ['text/html; charset=utf-8']
      ])
    : http_out(404, 'Not Found');
