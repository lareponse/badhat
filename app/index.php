<?php

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/..');

require 'add/badhat-murder/build.php';
require 'add/badhat-murder/map.php';
require 'add/badhat-murder/run.php';
require 'add/badhat-murder/pdo.php';
require 'add/badhat-murder/http.php';
require 'add/badhat-murder/auth.php';

use const bad\map\{IO_NEST, IO_GROW};
use const bad\error\{HND_ALL, FATAL_OB_FLUSH, MSG_WITH_TRACE};
use const bad\run\{BUFFER, RUN_OUTPUT, RUN_RETURN};

$register = require 'add/badhat-murder/error.php';
$register(HND_ALL | FATAL_OB_FLUSH | MSG_WITH_TRACE);

bad\pdo\db(require('PDO.php'));

$re_quest   = bad\map\hook(__DIR__.'/', $_SERVER['REQUEST_URI']) ?: 'index';
$pipeline = [];
// business: find the route and invoke it
$in_path    = __DIR__ . '/decide/';
[$route_path, $args]   = bad\map\seek($in_path, $re_quest, '.php', IO_NEST) ?: bad\map\seek($in_path, 'index', '.php');

if($route_path)
    $pipeline [] = $route_path;

$out_path   = __DIR__ . '/montre/';
// render: match route file and absorb it when possible
[$render_path, $render_args]   = bad\map\seek($out_path, $re_quest, '.php', IO_NEST) ?: bad\map\seek($out_path, 'index', '.php');
// $out_quest              = run($render_path, $in_quest[IO_RETURN] ?? $args ?? [], ABSORB);
if($render_path)
    $pipeline [] = $render_path;

$res = bad\run\run($pipeline, $args ?? [], BUFFER);
$main = $res[RUN_OUTPUT];
$css = $res[RUN_RETURN];

$breadcrumb = explode('/', trim($re_quest, '/'));
$page_id = implode('-', $breadcrumb) ?: 'index';
$page_class = implode(' ', $breadcrumb) ?: 'index';
require('app/layout.php');
