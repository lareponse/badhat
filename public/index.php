<?php

require '../add/core.php';
require '../add/bad/dev.php';
require '../add/bad/ui.php';
require '../add/bad/security.php';

$route_root = realpath(__DIR__ . '/../app/io/route');
$route = route($route_root);
$response = handle($route);
respond($response);
