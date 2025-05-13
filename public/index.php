<?php
putenv('DEV_MODE=true');

require '../add/core.php';
require '../add/bad/ui.php';
require '../add/bad/security.php';

try{
    $response = handle(route(realpath(__DIR__ . '/../app/route')));
    respond($response);
}
catch (Throwable $e) {
    // Handle the exception
    respond([
        'status' => 500,
        'body' => 'Internal Server Error: ' . $e->getMessage()
    ]);
}



