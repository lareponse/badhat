<?php

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../..');

require 'add/bad/error.php';
require 'add/bad/quest.php';
require 'add/bad/dad/html.php';

require 'add/bad/dad/auth.php';

$path = http_guard(4096, 9);
$path = io_guard($path);
$way_in = realpath(__DIR__ . '/../io') . '/route';
$way_out = realpath(__DIR__ . '/../io') . '/render';

$quest = quest($path, $way_in, $way_out);
if (function_exists('is_dev') && track(QST_CORE) === 0) {
    [$ret, $buf] = ob_inc_out('add/bad/dad/scaffold.php');
    http(404, $buf, ['Content-Type' => 'text/plain; charset=UTF-8']);
    exit;
}
tray('main', $quest[QST_PUSH_INVOKE] ?? $quest[QST_PUSH | QST_ECHO] ?? $quest[QST_PULL | QST_ECHO]);
$layout = scout($way_out, chart($path, 'layout'));
http(200, $layout[QST_ECHO], ['Content-Type' => 'text/html; charset=UTF-8']);

exit;
