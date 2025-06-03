<?php

define('IO_FILE_PREPARE', 'prepare.php');
define('IO_FILE_DEFAULT', 'index.php');
define('IO_FILE_CONCLUDE', 'conclude.php');

function handler(string $path, array $args = []): array
{
    return ['handler' => $path, 'args' => $args];
}

function io(?string $io_in = null): string
{
    static $in = null;
    return $in ?? ($in = realpath($io_in)) ?: throw new RuntimeException('Route Base Reality Rescinded', 500);
}

function io_plan(string $path, string $base)
{
    $plan = [];
    $cur = '';

    foreach (explode('/', trim($path, '/')) as $depth => $seg) {
        $cur .= DIRECTORY_SEPARATOR . $seg;
        $args = array_slice($plan, $depth + 1);
        $plan[] = [
            'path' => $base . $cur,
            'args' => $args,
            'segment' => $seg
        ];
    }

    return $plan;
}

function io_look(string $starting_point): array
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';

    // you are here. mini-map
    if (empty($path) || $path === '/') {
        return [
            'prepare' => [handler($starting_point . DIRECTORY_SEPARATOR . IO_FILE_PREPARE)],
            'execute' => [handler($starting_point . DIRECTORY_SEPARATOR . IO_FILE_DEFAULT)],
            'conclude' => [handler($starting_point . DIRECTORY_SEPARATOR . IO_FILE_CONCLUDE)],
        ];
    }

    $map = [
        'prepare' => [],
        'execute' => [],
        'conclude' => [],
    ];

    $plan = io_plan($path, $starting_point);
    foreach ($plan as $item) {
        $base_path = $item['path'];
        $args = $item['args'];
        $seg = $item['segment'];

        $map['prepare'][] = handler($base_path . DIRECTORY_SEPARATOR . IO_FILE_PREPARE);

        if (!empty($seg))
            $map['execute'][] = handler($base_path . '.php', $args);
        $map['execute'][] = handler($base_path . DIRECTORY_SEPARATOR . IO_FILE_DEFAULT, $args);

        $map['conclude'][] = handler($base_path . DIRECTORY_SEPARATOR . IO_FILE_CONCLUDE);
    }

    krsort($map['execute']);
    $map['conclude'] = array_reverse($map['conclude']);

    return $map;
}

function io_read(array $map): array
{
    $quest = [];
    foreach ($map as $part => $missions)
        foreach ($missions as $mission)
            if (($path = $mission['handler']) && $mission['handler'] = io_summon($path))
                $quest[$part][$path] = $mission; // no stack for execute

    $last_path = array_pop(array_keys($quest['execute']));
    $quest['execute'] = [$last_path => $quest['execute'][$last_path]];
    return $quest;
}

function io_walk(array $quest): array
{
    foreach ($quest as $part => $missions)
        foreach ($missions as $path => $mission) 
            $quest[$part][$path] = $mission['handler']($quest, ...($mission['args'] ?? []));
    return $quest;
}

function io_out(string $in)
{
    $io = realpath(dirname($in))    ?: throw new RuntimeException('IO Root Reality Rescinded', 500);

    $ios = glob($io . '/*', GLOB_ONLYDIR) ?: [];
    count($ios) === 2               || throw new RuntimeException('One folder containing in (route) and out (render) files', 500);

    $out = $ios[0] === $in ? $ios[1] : $ios[0];
    $out = is_readable($out) ? $out  : throw new RuntimeException('Render Base Reality Rescinded', 500);

    return $out;
}



function io_scaffold($addbad_scaffold_mode = 'in'): string
{
    ob_start(); {
        require_once 'add/dad/scaffold.php';
    }
    return  ob_get_clean();  // Scaffold response
}

function io_summon(string $file): ?callable
{
    ob_start();
    $callable = @include $file;
    ob_end_clean();

    if (is_callable($callable))
        return $callable;

    error_log("Invalid Callable in $file", E_USER_NOTICE);
    return null;
}
