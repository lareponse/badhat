<?php

function io(string $route, string $render, $when_empty = 'index')
{
    $quest = [];

    $quest['path'] = $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

    $quest['map'] = $map = (io_map($path, $when_empty));

    $quest['route'] = io_run($route, $map); // initialize quest with default values
    if (isset($quest['route']['status']))
        return $quest['route'];

    $quest['render'] = io_run($render, $map); // initialize quest with default values
    if (isset($quest['render']['status']))
        return $quest['render']; // if the quest has a response, return it directly

    return $quest;
}

function io_map(string $path, string $when_empty): array
{
    $map = [];
    $segments = (empty($path) || $path === '/') ? [$when_empty] : explode('/', trim($path, '/'));

    $cur = '';
    vd($segments);
    foreach ($segments as $depth => $seg) {
        $cur .= DIRECTORY_SEPARATOR . $seg;
        vd($cur);
        $args = array_slice($segments, $depth + 1);
        vd($cur);
        if (!empty($seg))  // group match
            $map[] = [$cur . DIRECTORY_SEPARATOR . $seg . '.php', $args];
        $map[] = [$cur . '.php', $args]; // direct match
    }

    krsort($map); // deep first, keep depth data
    return $map;
}

function io_run($start, $map): ?array
{
    foreach ($map as $checkpoint)
        if ($closure_or_content = io_dig(vd($start . $checkpoint[0]))) {
            $quest = [
                'file' => $checkpoint[0],
                'args' => $checkpoint[1] ?? [],
            ];
            if (is_callable($closure_or_content)) {
                $quest['func'] = $closure_or_content; // store callable closure
                $quest['load'] = $closure_or_content(...$quest['args']); // execute closure with args
            } else {
                $quest['load'] = $closure_or_content; // store content
            }

            return $quest; // return the first found closure or content
        }
    return null;
}

function io_dig(string $file)
{
    ob_start();
    $callable   = @include $file;
    $content    = trim(ob_get_clean()); // trim helps return ?: null (no opinion, significant whitespaces are in tags)

    return is_callable($callable) ? $callable : ($content ?: null);
}

function io_path(?string $path = null, $rx_remove = '#[^A-Za-z0-9\/\.\-\_]+#')
{
    $max_path_length = 4096; // max path length
    $max_url_decode  = 9;   // max number of rawurldecode iterations
    $coded = $path ?? parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';

    (strlen($coded) > $max_path_length)  && throw new DomainException('Path Exceeds Maximum Allowed', 400);

    while ($max_url_decode-- > 0 && ($decoded = rawurldecode($coded)) !== $coded)
        $coded = $decoded;
    $max_url_decode             ?: throw new DomainException('Path decoding loop detected', 400);
    $path = $coded;
    
    $path = $rx_remove ? preg_replace($rx_remove, '', $path) : $path;       // removes non alphanum /.-_
    $path = preg_replace('#\.\.+#', '', $path);                             // remove serial dots
    $path = preg_replace('#(?:\./|/\.|/\./)#', '/', $path);                 // replace(/): /. ./ /./
    $path = preg_replace('#\/\/+#', '/', $path);                            // replace(/): //+, 
    $path = trim($path, '/');                                               // remove leading and trailing slashes

    return $path;
}
