<?php

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/..');

require 'add/badhat-murder/build.php';
require 'add/badhat-murder/io.php';
require 'add/badhat-murder/run.php';
require 'add/badhat-murder/db.php';
require 'add/badhat-murder/http.php';
require 'add/badhat-murder/auth.php';

use const bad\io\{IO_NEST, IO_GROW};
use const bad\error\{HND_ALL, FATAL_OB_FLUSH, MSG_WITH_TRACE};
use const bad\run\{RUN_BUFFER, RUN_OUTPUT, RUN_RETURN};

$register = require 'add/badhat-murder/error.php';
$register(HND_ALL | FATAL_OB_FLUSH | MSG_WITH_TRACE);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$dsn = $_SERVER['DB_DSN_' ] ?: (getenv('DB_DSN_' ) ?: throw new DomainException("db-empty-dsn"));
$usr = $_SERVER['DB_USER_'] ?: (getenv('DB_USER_') ?: null);
$pwd = $_SERVER['DB_PASS_'] ?: (getenv('DB_PASS_') ?: null);

bad\db\db(new \PDO($dsn, $usr, $pwd, $options));

        
$re_quest   = bad\io\path(__DIR__.'/', $_SERVER['REQUEST_URI']) ?: 'index';

$pipeline = [];
// business: find the route and invoke it
$in_path    = __DIR__ . '/decide/';
[$route_path, $args]   = bad\io\seek($in_path, $re_quest, '.php', IO_NEST) ?: bad\io\seek($in_path, 'index', '.php');

if($route_path)
    $pipeline [] = $route_path;

$out_path   = __DIR__ . '/montre/';
// render: match route file and absorb it when possible
[$render_path, $render_args]   = bad\io\seek($out_path, $re_quest, '.php', IO_NEST) ?: bad\io\seek($out_path, 'index', '.php');
// $out_quest              = run($render_path, $in_quest[IO_RETURN] ?? $args ?? [], RUN_ABSORB);
if($render_path)
    $pipeline [] = $render_path;

$res = bad\run\run($pipeline, $args ?? [], RUN_BUFFER);
$main = $res[RUN_OUTPUT];
$css = $res[RUN_RETURN];
require('app/layout.php');
