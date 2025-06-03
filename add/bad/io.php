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

function io_look(string $starting_point): array
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';
    $quest = [
        'prepare' => [],
        'execute' => [],
        'conclude' => [],
    ];

    if (empty($path) || $path === '/') {
        $quest['prepare'][] = handler($starting_point . DIRECTORY_SEPARATOR . IO_FILE_PREPARE);
        $quest['execute'][] = handler($starting_point . DIRECTORY_SEPARATOR . IO_FILE_DEFAULT);
        $quest['conclude'][] = handler($starting_point . DIRECTORY_SEPARATOR . IO_FILE_CONCLUDE);
        return $quest;
    }
    
    $paths = io_reveal($path, $starting_point);
    foreach ($paths as $item) {
        $base_path = $item['path'];
        $args = $item['args'];
        $seg = $item['segment'];

        $quest['prepare'][] = handler($base_path . DIRECTORY_SEPARATOR . IO_FILE_PREPARE, $args);
        $quest['conclude'][] = handler($base_path . DIRECTORY_SEPARATOR . IO_FILE_CONCLUDE, $args);

        if (!empty($seg))
            $quest['execute'][] = handler($base_path . '.php', $args);
        $quest['execute'][] = handler($base_path . DIRECTORY_SEPARATOR . IO_FILE_DEFAULT, $args);
    }

    krsort($quest['execute']);
    $quest['conclude'] = array_reverse($quest['conclude']);
    
    return $quest;
}

function io_read(array $quest): array
{
    foreach($quest as $part => $missions){
        foreach($missions as $depth => $mission){
            $quest[$part][$mission['handler']] = io_summon($mission['handler'], $mission['args'] ?? []);
            unset($quest[$part][$depth]);
        }
    }
    // foreach ($prepares as $prepare)
    //     if ($prepare['closure'] = io_summon($prepare['handler']))
    //         $quest['prepare'][] = $prepare;

    // foreach ($candidates as $candidate) {
    //     if ($candidate['closure'] = io_summon($candidate['handler']))
    //         $quest['execute'] = $candidate; // no stacking
    // }

    // foreach ($concludes as $conclude)
    //     if ($conclude['closure'] = io_summon($conclude['handler']))
    //         $quest['conclude'][] = $conclude;
    vd($quest, 'io_read(quest)');
    return $quest;
}

function io_walk(array $quest): array
{ 
    foreach ($quest['prepare'] as $depth => $hook) {
        $quest['prepare'][$depth]['return'] = $hook['closure']($quest);
    }

    if (isset($quest['execute']['closure']) && is_callable($quest['execute']['closure'])) {
        $quest['execute']['return'] = $quest['execute']['closure']($quest, ...$quest['execute']['args'] ?? []);
    }

    foreach ($quest['conclude'] as $depth => $hook) {
        $quest['conclude'][$depth]['return'] = $hook['closure']($quest);
    }
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

function io_reveal(string $path, string $base)
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
