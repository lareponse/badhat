<?php

declare(strict_types=1);

const IO_PATH = 1;      // Init flags
const IO_PLAN = 2;

const IO_SEEK = 4;      // Phase flags
const IO_SEND = 8;
const IO_HTTP = 16;

const IO_FILE = 32;     // Operation flags
const IO_ARGS = 64;
const IO_FUNC = 128;
const IO_LOAD = 256;

function io(string $route, string $render, $when_empty = 'index')
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
    $quest[IO_PATH] = io_guard($path);
    $quest[IO_PLAN] = io_draft($quest[IO_PATH], $when_empty);

    foreach (io_probe($route, $quest) as $flag => $value) $quest[IO_SEEK | $flag] = $value;
    $quest[IO_SEEK] = true;

    if (!(io_state($quest) & IO_HTTP)) // quest is ready for HTTP response
        foreach (io_probe($render, $quest) as $flag => $value) $quest[IO_SEND | $flag] = $value;

    return $quest;
}

function io_guard(string $coded, $max_length = 4096, $max_decode = 9, $rx_remove = '#[^A-Za-z0-9\/\.\-\_]+#'): string
{
    do {
        $path = rawurldecode($coded);
    } while ($max_decode-- > 0 && $path !== $coded && ($coded = $path));

    $max_decode                       ?: throw new DomainException('Path decoding loop detected', 400);
    (strlen($path) > $max_length)  && throw new DomainException('Path Exceeds Maximum Allowed', 400);

    $path = $rx_remove ? preg_replace($rx_remove, '', $path) : $path;       // removes non alphanum /.-_
    $path = preg_replace('#\.\.+#', '', $path);                             // remove serial dots
    $path = preg_replace('#(?:\./|/\.|/\./)#', '/', $path);                 // replace(/): /. ./ /./
    $path = preg_replace('#\/\/+#', '/', $path);                            // replace(/): //+, 
    $path = trim($path, '/');                                               // remove leading and trailing slashes

    return $path;
}

function io_draft(string $path, string $when_empty): array
{
    $candidates = [];
    $segments = (empty($path) || $path === '/') ? [$when_empty] : explode('/', trim($path, '/'));

    $relative_path = '';
    foreach ($segments as $depth => $seg) {
        $relative_path .= DIRECTORY_SEPARATOR . $seg;
        $args = array_slice($segments, $depth + 1, null, true); // get remaining segments as args
        $candidates[] = [$relative_path . DIRECTORY_SEPARATOR . $seg . '.php', $args];
        $candidates[] = [$relative_path . '.php', $args]; // direct match
    }

    krsort($candidates); // deep first, keep depth data
    return $candidates;
}

function io_probe($start, $quest, $__mission = []): array
{
    foreach ($quest[IO_PLAN] as $checkpoint)
        if ($yield = io_fetch($start . $checkpoint[0])) {

            $__mission[IO_FILE] = $checkpoint[0];
            $__mission[IO_ARGS] = $checkpoint[1];

            if (is_callable($yield)) {
                $__mission[IO_FUNC] = $yield;
                $__mission[IO_LOAD] = $yield($quest, ...($checkpoint[1]));
            } else {
                $__mission[IO_LOAD] = $yield;
            }

            return $__mission;
        }
    return $__mission;
}

function io_fetch(string $file)
{
    ob_start();
    $callable   = @include $file;
    $content    = trim(ob_get_clean()); // trim helps return ?: null (no opinion, significant whitespaces are in tags)
    return is_callable($callable) ? $callable : ($content ?: null);
}

function io_state($quest, $__state = 0): int
{
    foreach ($quest as $step => $v) $__state |= $step;
    return $__state;
}
