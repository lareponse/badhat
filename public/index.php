<?php

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/..');

require 'add/badhat/build.php';
require 'add/badhat/error.php';
require 'add/badhat/core.php';
require 'add/badhat/db.php';
require 'add/badhat/auth.php';

try {
    $io = __DIR__ . '/../app';
    $in_path    = $io . '/decide';
    $out_path   = $io . '/montre';

    $re_quest   = http_in();

    // business: find the route and invoke it
    [$route_path, $args]   = io_map($in_path, $re_quest, 'php', IO_DEEP | IO_NEST) ?: io_map($in_path, 'index');
    $in_quest              = $route_path ? io_run($route_path, $args ?? []) : [];

    // render: match route file and absorb it when possible
    [$render_path, $args]   = io_map($out_path, $re_quest, 'php', IO_DEEP | IO_NEST) ?: io_map($out_path, 'index');
    $out_quest              = io_run($render_path, $in_quest[IO_RETURN] ?? $args ?? [], IO_ABSORB | IO_EXTRACT);

    // absorption is optional, http_body() settles the output
    if (is_string($out_quest[IO_BUFFER]) || is_string($out_quest[IO_ABSORB])) {
        http_out(200, $out_quest[IO_ABSORB] ?? $out_quest[IO_BUFFER] , ['Content-Type' => 'text/html; charset=utf-8']);
        exit;
    }

    error_log('404 Not Found for ' . $re_quest);
    http_out(404, 'Not Found at all');
} catch (LogicException | RuntimeException $t) {
    vd(-1, $t);
    header('HTTP/1.1 500 Forbidden');
} catch (Throwable $t) {
    vd(-1, $t);
    // out quest that fetch an error page within the layout, firsttests if error page has 200 
    die;
}
