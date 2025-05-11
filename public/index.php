<?php
require '../add/core.php';

try{
    $response = handle(route(__DIR__ . '/route'));
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
