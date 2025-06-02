<?php

define('IO_FILE_PREPARE', 'prepare.php');
define('IO_FILE_DEFAULT', 'index.php');
define('IO_FILE_CONCLUDE', 'conclude.php');

function handler(string $path, array $args = []): array
{
    return ['handler' => $path, 'args' => $args];
}

function io(?string $base_setter = null): array
{
    static $base = [];

    $base || $base_setter && ($base = io_base($base_setter)) || throw new BadFunctionCallException('IO Requires Route Base', 500);

    return $base;
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

function io_base(string $arg): array
{
    $in = realpath($arg)            ?: throw new RuntimeException('Route Base Reality Rescinded', 500);
    $io = realpath(dirname($in))    ?: throw new RuntimeException('Route Root Reality Rescinded', 500);

    $ios = glob($io . '/*', GLOB_ONLYDIR) ?: [];
    count($ios) === 2               || throw new RuntimeException('One folder containing in (route) and out (render) files', 500);

    $out = $ios[0] === $in ? $ios[1] : $ios[0];
    $out = is_readable($out) ? $out  : throw new RuntimeException('Render Base Reality Rescinded', 500);

    return [$in, $out];
}

function io_map(array $plan, $in): array
{
    // root path
    if (empty($plan))
        return [
            [handler($in . DIRECTORY_SEPARATOR . IO_FILE_PREPARE)],
            [handler($in . DIRECTORY_SEPARATOR . IO_FILE_DEFAULT)],
            [handler($in . DIRECTORY_SEPARATOR . IO_FILE_CONCLUDE)]
        ];

    $cur = '';
    $prepares = $candidates = $concludes = [];
    foreach ($plan as $depth => $seg) {

        $cur .= DIRECTORY_SEPARATOR . $seg;
        $args = array_slice($plan, $depth + 1);
        
        $base_path = $in . $cur;

        $prepares[] = handler($base_path . DIRECTORY_SEPARATOR . IO_FILE_PREPARE, $args);
        $concludes[] = handler($base_path . DIRECTORY_SEPARATOR . IO_FILE_CONCLUDE);

        if ($seg) {
            $candidates[] = handler($base_path . '.php', $args);
        }
        $candidates[] = handler($base_path . DIRECTORY_SEPARATOR . IO_FILE_DEFAULT);
    }

    krsort($candidates);
    
    return [$prepares, $candidates, array_reverse($concludes)];
}

function io_read(array $map): array
{
    $quest = [
        'prepare' => [],
        'execute' => [],
        'conclude' => [],
    ];

    [$prepares, $candidates, $concludes] = $map;

    foreach ($prepares as $prepare)
        if ($prepare['closure'] = io_summon($prepare['handler']))
            $quest['prepare'][] = $prepare;

    foreach ($candidates as $candidate) {
        if ($candidate['closure'] = io_summon($candidate['handler']))
            $quest['execute'] = $candidate; // no stacking
    }

    foreach ($concludes as $conclude)
        if ($conclude['closure'] = io_summon($conclude['handler']))
            $quest['conclude'][] = $conclude;

    return $quest;
}

function io_mirror(array $quest): string
{
    [$in, $out] = io();
    return str_replace($in, $out, $quest['execute']['handler']);
}

function io_scaffold($addbad_scaffold_mode = 'in'): string
{
    ob_start(); {
        require_once 'add/dad/scaffold.php';
    }
    return  ob_get_clean();  // Scaffold response
}