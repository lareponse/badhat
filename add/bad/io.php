<?php

function io(string $in, ?string $out = null)
{
    $out ??= io_other($in); // convention costs, use $out

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $map = vd(io_map($path));
    $log = (io_run($in, $map));
    $see = (io_run($out, $map));

    if (is_array($see) && is_array($log))
        array_unshift($log, ...$see);
    elseif (is_array($log))
        array_unshift($log, $see);

    return $log;
}

function io_path(?string $path = null, $rx_remove = '#[^A-Za-z0-9\/\.\-\_]+#')
{
    $coded = $path ?? parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';

    (strlen($coded) > 4096)  && throw new DomainException('Path Exceeds Maximum Allowed', 400);

    $loop_limit = 9;              // max iterations to prevent infinite loop
    while ($loop_limit-- > 0 && ($decoded = rawurldecode($coded)) !== $coded)
        $coded = $decoded;
    $loop_limit             ?: throw new DomainException('Path decoding loop detected', 400);
    $path = $coded;

    $path = $rx_remove ? preg_replace($rx_remove, '', $path) : $path;       // removes non alphanum /.-_
    $path = preg_replace('#\.\.+#', '', $path);                             // remove serial dots
    $path = preg_replace('#(?:\./|/\.|/\./)#', '/', $path);                 // replace(/): /. ./ /./
    $path = preg_replace('#\/\/+#', '/', $path);                            // replace(/): //+, 
    $path = trim($path, '/');                                               // remove leading and trailing slashes
    $path = str_replace('/', DIRECTORY_SEPARATOR, $path);                   // convert to system directory separator

    return $path;
}

function io_map(string $path, ?string $when_empty = 'index'): array
{
    $map = [];
    $segments = (empty($path) || $path === '/')
        ? [$when_empty] // default to index if path is empty
        : explode('/', trim($path, '/'));

    $cur = '';
    foreach ($segments as $depth => $seg) {
        $cur .= DIRECTORY_SEPARATOR . $seg;
        $args = array_slice($segments, $depth + 1);

        if (!empty($seg))  // group match
            $map[] = [$cur . DIRECTORY_SEPARATOR . $seg . '.php', $args];
        $map[] = [$cur . '.php', $args]; // direct match
    }

    krsort($map); // deep first, keep depth data

    return $map;
}

function io_run($start, $map): ?array
{
    // vd($map, 'io_run'); // debug output
    foreach ($map as $quest)
        if ($loot = io_dig($start . DIRECTORY_SEPARATOR . current($quest)))
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
    return (
        true                                                                // return other child if
        && ($io = realpath(dirname($one)))                                  //      the parent is real
        && ($ios = glob($io . '/*', GLOB_ONLYDIR))                          //      the parent has children
        && isset($ios[0], $ios[1]) && !isset($ios[2]))                      //      exactly two children
        ? ($ios[0] === $one ? $ios[1] : $ios[0])
        : throw new RuntimeException('IO Other Reality Rescinded', 500);
}
