<?php

// io-quest-states.php

const IO_STATE        = 0;

// ── Top‐level 'quest' keys
const IO_PATH         = 1;
const IO_MAP          = 2;

// ── 'route' sub‐array keys (run occurs before file)
const IO_IN_RUN       = 4;
const IO_IN_FILE      = 8;
const IO_IN_ARGS      = 16;
const IO_IN_FUNC      = 32;
const IO_IN_LOAD      = 64;
const IO_IN_HTTP      = 128;

// ── 'render' sub‐array keys (run occurs before file)
const IO_OUT_RUN      = 256;
const IO_OUT_FILE     = 512;
const IO_OUT_ARGS     = 1024;
const IO_OUT_FUNC     = 2048;
const IO_OUT_LOAD     = 4096;
const IO_OUT_HTTP     = 8192;

// ── Composite (macro) constants ────────────────────────────────────────────────
const IO_IS_INIT         = IO_PATH | IO_MAP;
const IO_HAS_CONTENT     = IO_IN_LOAD | IO_OUT_LOAD;


function io(string $route, string $render, $when_empty = 'index')
{
    $quest = [IO_STATE => IO_STATE];

    ($quest[IO_PATH]      = io_path())                                            && $quest[IO_STATE] |= IO_PATH;
    ($quest[IO_MAP]       = io_map($quest[IO_PATH], $when_empty))                 && $quest[IO_STATE] |= IO_MAP;

    if (!($quest[IO_STATE] & IO_IS_INIT))                                         return $quest;

    ($quest = io_run($route, $quest) + $quest)                                    && $quest[IO_STATE] |= IO_IN_RUN; 
    
    if ($quest[IO_STATE] & IO_IN_HTTP)                                            return $quest;

    ($quest = io_run($render, $quest) + $quest)                                   && $quest[IO_STATE] |= IO_OUT_RUN;

    return $quest;
}

function io_map(string $path, string $when_empty): array
{
    $map = [];
    $segments = (empty($path) || $path === '/') ? [$when_empty] : explode('/', trim($path, '/'));

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

function io_run($start, $quest): ?array
{
    // ask quest state to determine if we are running a route or render, using & IO_IN and IO_OUT
    [$IO___FILE, $IO___ARGS, $IO___FUNC,  $IO___LOAD] = ($quest[IO_STATE] & IO_IN_RUN)
        ? [IO_IN_FILE,  IO_IN_ARGS,  IO_IN_FUNC,  IO_IN_LOAD]
        : [IO_OUT_FILE, IO_OUT_ARGS, IO_OUT_FUNC, IO_OUT_LOAD];

    foreach ($quest[IO_MAP] as $checkpoint)
        if ($closure_or_content = io_dig($start . $checkpoint[0] ?? '')) {
            ($quest[$IO___FILE] = $checkpoint[0] ?? null)                               && $quest[IO_STATE] |= $IO___FILE;
            ($quest[$IO___ARGS] = $checkpoint[1] ?? null)                               && $quest[IO_STATE] |= $IO___ARGS;
            if (is_callable($closure_or_content)) {
                ($quest[$IO___FUNC] = $closure_or_content)                              && $quest[IO_STATE] |= $IO___FUNC; // store closure
                ($quest[$IO___LOAD] = $closure_or_content(...$quest[$IO___ARGS]))       && $quest[IO_STATE] |= $IO___LOAD; // execute closure with args
            } else ($quest[$IO___LOAD] = $closure_or_content)                           && $quest[IO_STATE] |= $IO___LOAD; // store content


            return $quest; // return the first found closure or content
        }

    return $quest;
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
