<?php

// first call, routes (in): 
//      io(route_base)                sets the route base directory, CoC sets render base
// [OR] io(route_base, render_base)   sets the route base directory, and sets render base
//
// subsequent calls:
//      io()             returns the real path of the current base (route or render)
//      io(path)         based on URI path, returns map of candidates (call io_map() with current base)
//      io(null)         switch route base for render base, and vice versa
function io(?string $io_in = null, ?string $io_out = null)
{
    static $io = [];

    if (empty($io) && $io_in) { // first call, set the bases
        ($in = realpath($io_in)) ?: throw new RuntimeException('Route Base Reality Rescinded', 500);
        ($out = realpath($io_out ?: io_other($in))) ?: throw new RuntimeException('Render Base Reality Rescinded', 500);
        $io = [$in, $out];
    } else if (func_num_args() === 1 && $io_in === null)
        $io = array_reverse($io);

    else if (func_num_args() === 1 && $io_in !== null)
        return io_map(current($io), $io_in);

    return current($io);
}

function io_map(string $home, ?string $path = null)
{
    $map = [];

    $path ??= parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

    if (empty($path) || $path === '/')
        $map[] = [$home . DIRECTORY_SEPARATOR . 'index.php'];
    else {
        $cur = '';
        $segments = explode('/', trim($path, '/'));
        foreach ($segments as $depth => $seg) {
            $cur .= DIRECTORY_SEPARATOR . $seg;
            $base_path = $home . $cur;
            $args = array_slice($segments, $depth + 1);

            if (!empty($seg))
                $map[] = [$base_path . '.php', $args];

            $map[] = [$base_path . DIRECTORY_SEPARATOR . $seg . '.php', $args];
        }

        krsort($map); //
    }

    return $map;
}

function io_try($map): ?array
{
    foreach ($map as $quest)
        if ($loot = io_dig(current($quest)))
            return [$loot, ...$quest];

    return null;
}

function io_dig(string $file)
{
    ob_start();
    $callable = @include $file;
    $content = trim(ob_get_clean()); // significant whitespaces are in tags

    return is_callable($callable) ? $callable : ($content ?: null);
}

function io_other(string $one)
{
    $io = realpath(dirname($one))     ?: throw new RuntimeException('IO Root Reality Rescinded', 500);

    $ios = glob($io . '/*', GLOB_ONLYDIR)   ?: [];
    count($ios) === 2                       || throw new RuntimeException('One folder containing in (route) and out (render) files', 500);

    $other = $ios[0] === $one ? $ios[1] : $ios[0];
    $other = is_readable($other) ? $other   : throw new RuntimeException('Other folder Not Readable', 500);

    return $other;
}

function io_scaffold($addbad_scaffold_mode = 'in'): string
{
    ob_start(); {
        require_once 'add/dad/scaffold.php';
    }
    return  ob_get_clean();  // Scaffold response
}
