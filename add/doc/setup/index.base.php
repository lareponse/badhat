<?php

require '../add/core.php';
require '../add/bad/dev.php';
require '../add/bad/db.php';
require '../add/bad/ui.php';
require '../add/bad/guard_auth.php';
require '../add/bad/auth_backend_sql.php';

// no env ? need these 2
list($dsn, $u, $p) = require '../app/data/credentials.php';
db(new PDO($dsn, $u, $p, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));


$route_root = __DIR__ . '/../app/io/route';
$route = route($route_root);
$response = handle($route);
respond($response);
