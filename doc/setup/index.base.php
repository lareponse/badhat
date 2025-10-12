<?php

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/..');

require 'add/badhat/build.php';
require 'add/badhat/error.php';
require 'add/badhat/http.php';
require 'add/badhat/io.php';
require 'add/badhat/db.php';
require 'add/badhat/auth.php';

require 'add/arrow/arrow.php';  // Load arrow library

try {

    $io = __DIR__ . '/../app/io';
    $in_path    = $io . '/route';
    $out_path   = $io . '/render';
    $request = http_in();


    // Phase 2: Presentation
    [$render_path, $args]   = io_map($out_path, $re_quest, 'php', IO_DEEP | IO_NEST);
    $out_quest              = io_run($render_path ?? $out_path, $in_quest[IO_RETURN] ?? $args ?? []);

    if (is_string($out_quest[IO_BUFFER])) {
        http_out(200, $out_quest[IO_BUFFER], ['Content-Type' => 'text/html; charset=utf-8']);
        exit;
    }

} catch (LogicException | RuntimeException $t) {
    header('HTTP/1.1 500 Forbidden');
    die;
} catch (Throwable $t) {
    header('HTTP/1.1 503 Service Unavailable');
    die;
}

http_out(404, 'Not Found');
die;
