<?php

define('IO_FILE_PREPARE', 'prepare.php');
define('IO_FILE_DEFAULT', 'index.php');
define('IO_FILE_CONCLUDE', 'conclude.php');

function handler(string $path, array $args = []): array
{
    return ['handler' => $path, 'args' => $args];
}

function summon(string $file): ?callable
{
    ob_start();
    $callable = @include $file;
    ob_end_clean();

    if (is_callable($callable))
        return $callable;

    error_log("Invalid Callable in $file", E_USER_NOTICE);
    return null;
}

function io(?string $arg = null): array
{
    static $io = [];

    if (!$io) {
        $arg ?: throw new BadFunctionCallException('IO Requires Real Route Root', 500);
        $d = glob(dirname($arg) . '/*', GLOB_ONLYDIR) ?: [];
        count($d) === 2 || throw new RuntimeException('One folder containing in (route) and out (render) files', 500);
        $in = realpath($arg) ?: throw new RuntimeException('Route Reality Rescinded', 500);
        $out = realpath($d[0] === $in ? $d[0] : $d[1]) ?: throw new RuntimeException('Render Reality Rescinded', 500);

        $io = [$in, $out];
    }

    return $io;
}
function io_map(array $plan): array
{
    // root path

    $route_root = io()[0];
    if (empty($plan))
        return [
            [handler($route_root . DIRECTORY_SEPARATOR . IO_FILE_PREPARE)],
            [handler($route_root . DIRECTORY_SEPARATOR . IO_FILE_DEFAULT)],
            [handler($route_root . DIRECTORY_SEPARATOR . IO_FILE_CONCLUDE)]
        ];

    $cur = '';
    $prepares = $candidates = $concludes = [];
    foreach ($plan as $depth => $seg) {
        $cur .= '/' . $seg;
        $base_path = $route_root . $cur;

        $args = array_slice($plan, $depth + 1);

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
        'map' => $map,
    ];

    [$prepares, $candidates, $concludes] = $map;

    foreach ($prepares as $prepare)
        if ($prepare['closure'] = summon($prepare['handler']))
            $quest['prepare'][] = $prepare;

    foreach ($candidates as $candidate) {
        if ($candidate['closure'] = summon($candidate['handler'])) {
            $quest['execute'] = $candidate;
            break;
        }
    }

    foreach ($concludes as $conclude)
        if ($conclude['closure'] = summon($conclude['handler']))
            $quest['conclude'][] = $conclude;


    return $quest;
}

function io_mirror(array $quest): string
{
    return str_replace(io()[0], io()[1], $quest['execute']['handler']);
}

// function io_candidate(string $in_or_out): array
// {
//     $app_root = dirname(dirname(__DIR__));

//     foreach (io_candidates($in_or_out) as $candidate) {
//         $real = realpath($candidate['handler']);
//         if (!$real) continue;

//         if (strpos($real, $app_root) === 0 && route_exists($real) || is_readable($real) && is_file($real))
//             return $candidate;
//     }
//     return [];
// }
// function io_candidates(string $in_or_out): array
// {
//     $candidates = [];
//     $cur = '';
//     $in_or_out = $in_or_out === 'in' ? io()[0] : io()[1];

//     foreach (request()['segments'] as $depth => $seg) {
//         $cur .= '/' . $seg;
//         $args = array_slice(request()['segments'], $depth + 1);

//         $candidates[] = handler($in_or_out . $cur . '.php', $args);
//         $candidates[] = handler($in_or_out . $cur . DIRECTORY_SEPARATOR . 'index.php', $args);
//     }

//     krsort($candidates);

//     return $candidates;
// }



function route_exists(string $file): bool
{
    static $routes = null;

    if ($routes === null) {
        $cache_file = dirname(io()[0]) . '/routes.cache';
        $routes = file_exists($cache_file) ? file_get_contents($cache_file) : '';
    }

    return strpos($routes, $file) !== false || file_exists($file);
}

// function hooks(string $handler): array
// {
//     $base = rtrim(io()[0], '/');
//     $before = $after = [];

//     // Figure out the path segments under $base
//     $rel = substr($handler, strlen($base) + 1);
//     $parts = explode('/', $rel);

//     array_unshift($parts, ''); // add empty string to the start of the array
//     foreach ($parts as $seg) {
//         $base .= '/' . $seg;
//         $before[$base . '/prepare.php'] = summon($base . '/prepare.php');
//         $after[$base . '/conclude.php'] = summon($base . '/conclude.php');
//     }
//     return [
//         'prepare' => array_filter($before),
//         'conclude' => array_reverse(array_filter($after))
//     ];
// }
function io_scaffold($addbad_scaffold_mode = 'in'): string
{
    ob_start(); {
        require_once 'add/bad/scaffold.php';
    }
    return  ob_get_clean();  // Scaffold response
}