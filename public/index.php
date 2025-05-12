<?php
putenv('DEV_MODE=true');

require '../add/core.php';

try{
    $response = handle(route(realpath(__DIR__ . '/../route')));
}
catch (Throwable $e) {
    // Handle the exception
    $response = [
        'status' => 500,
        'body' => 'Internal Server Error: ' . $e->getMessage()
    ];
}
finally {
    respond($response);
}
