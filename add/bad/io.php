<?php

function io(string $in, ?string $out=null)
{
    $out ??= io_other($in); // convention costs, use $out

    $log = io_run(io_map($in));
    $see = io_run(io_map($out));
    if (is_array($see) && is_array($log))
        array_unshift($log, ...$see);
    elseif (is_array($log))
        array_unshift($log, $see);

    return $log;
}

function io_map(string $home, ?string $path = null): array
{
    $map = [];
    $home = realpath($home) ?: throw new RuntimeException('Home is not real', 500);
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

function io_run($map): ?array
{
    // vd($map, 'io_run'); // debug output
    foreach ($map as $quest)
        if ($loot = io_dig(current($quest)))
            return [$loot, ...$quest];

    return null;
}

function io_dig(string $file)
{
    ob_start();
    $callable   = @include $file;
    $content    = trim(ob_get_clean()); // trim helps return ?: null (no opinion, significant whitespaces are in tags)

    return is_callable($callable) ? $callable : ($content ?: null);
}

function io_other(string $one)
{
    return true                                         // return other child if
        && ($io = realpath(dirname($one)))              //      the parent is real
        && ($ios = glob($io . '/*', GLOB_ONLYDIR))      //      the parent has children
        && isset($ios[0], $ios[1]) && !isset($ios[2])   //      exactly two children
        ? ($ios[0] === $one ? $ios[1] : $ios[0])
        : throw new RuntimeException('IO Other Reality Rescinded', 500);
}

