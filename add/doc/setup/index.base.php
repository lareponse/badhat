<?php

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../..');

require 'add/bad/error.php';
require 'add/bad/http.php';
require 'add/bad/io.php';
require 'add/bad/dad/html.php';

require 'add/bad/dad/auth.php';

http_guard();

$origin = realpath(__DIR__ . '/../io');
$in_path = $origin . '/route';
$out_path = $origin . '/render';

$quest = io($in_path, $out_path);

tray('main', $quest[IO_SEND | IO_LOAD]);
$layout = io_probe($out_path, io_draft(io_clean($_SERVER['REQUEST_URI']), 'layout'));
http_respond(200, $layout[IO_LOAD], ['Content-Type' => 'text/html; charset=UTF-8']);

if (is_dev() && empty($response) || $response >= 400) {
    ob_start();
    @include 'add/bad/dad/scaffold.php';
    $scaffold   = trim(ob_get_clean()); // trim helps return ?: null (no opinion, significant whitespaces are in tags)
    http_respond(404, $scaffold, ['Content-Type' => 'text/html; charset=UTF-8']);
}

http_respond(...$response);
exit;
