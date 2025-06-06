<?php

// Init flags
const IO_PATH         = 1;
const IO_MAP          = 2;

// Phase flags
const IO_IN           = 4;
const IO_OUT          = 8;
const IO_HTTP         = 16;
// Operation flags  
const IO_FILE         = 32;
const IO_ARGS         = 64;
const IO_FUNC         = 128;
const IO_LOAD         = 256;

// Terminal flag


function io(string $route, string $render, $when_empty = 'index')
{
    $quest[IO_PATH] = io_path();
    $quest[IO_MAP]  = io_map($quest[IO_PATH], $when_empty);

    if (!isset($quest[IO_MAP])) return $quest;

    foreach (io_run($route, $quest[IO_MAP]) as $flag => $value) $quest[IO_IN | $flag] = $value;
    $quest[IO_IN] = true; // IN is done, even if io_run was empty

    if (io_state($quest) & IO_HTTP) return $quest;

    foreach (io_run($render, $quest[IO_MAP]) as $flag => $value) $quest[IO_OUT | $flag] = $value;
    $quest[IO_OUT] = true; // IN is done, even if io_run was empty

    return $quest;
}

function io_map(string $path, string $when_empty): array
{
    $map = [];
    $segments = (empty($path) || $path === '/') ? [$when_empty] : explode('/', trim($path, '/'));

    $relative_path = '';
    foreach ($segments as $depth => $seg) {
        $relative_path .= DIRECTORY_SEPARATOR . $seg;
        $args = array_slice($segments, $depth + 1, null, true); // get remaining segments as args
        $map[] = [$relative_path . DIRECTORY_SEPARATOR . $seg . '.php', $args];
        $map[] = [$relative_path . '.php', $args]; // direct match
    }

    krsort($map); // deep first, keep depth data
    return $map;
}

function io_state($quest, $__state = 0): int
{
    foreach ($quest as $step => $v) $__state |= $step;
    return $__state;
}

function io_run($start, $map): array
{
    $result = [];
    foreach ($map as $checkpoint)
        if ($closure_or_content = io_dig($start . $checkpoint[0])) {
            
            $result[IO_FILE] = $checkpoint[0];
            $result[IO_ARGS] = $checkpoint[1];

            if (is_callable($closure_or_content)) {
                $result[IO_FUNC] = $closure_or_content;
                $result[IO_LOAD] = $closure_or_content(...($checkpoint[1]));
            } else {
                $result[IO_LOAD] = $closure_or_content;
            }

            return $result;
        }

    return $result;
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
