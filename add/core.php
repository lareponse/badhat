<?php
declare(strict_types=1);

require 'add/io.http.php';
require 'add/io.file.php';

function follow(array $quest, array $request): array
{
    foreach ($quest['prepare'] as $prepare) {
        $quest += $prepare['closure']($quest, $request);
    }

    if (isset($quest['execute']['closure']) && is_callable($quest['execute']['closure'])) {
        $quest += $quest['execute']['closure']($quest, $request, ...$quest['execute']['args'] ?? []);
    }

    foreach ($quest['conclude'] as $conclude) {
        $quest += $conclude['closure']($quest, $request);
    }

    return $quest;
}

function deliver($quest, array $request): array
{
    // vd($quest, 'deliver()');
    $view = io_mirror($quest);
    $html = render($quest, $view);
    return http_response(200, $html, ['Content-Type' => 'text/html; charset=UTF-8']);
}
