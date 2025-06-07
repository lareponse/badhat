<?php

declare(strict_types=1);

const IO_SEEK =  0;     // Phase flags, makes even numbers SEEK phase operations, 
const IO_SEND =  1;

const IO_FILE =  2;     // Operation flags
const IO_ARGS =  4;
const IO_CALL =  8;
const IO_LOAD = 16;

const IO_PLAN = 32;

const IO_SEEK_CALL = IO_SEEK | IO_CALL | IO_ARGS;
const IO_SEND_CALL = IO_SEND | IO_CALL | IO_SEEK;

// io calls might/should trigger an http_response
// if not, return the quest array for end-dev
function io(string $route, string $render, $when_empty = 'index')
{
    $paths = io_clean(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '', $when_empty);
    $plans = io_draft($paths);

    $quest = io_probe(IO_SEEK, $route, $plans, $quest ?? []);
    is_callable(($cl = $quest[IO_SEEK | IO_CALL] ?? null))
        && ($quest[IO_SEEK_CALL] = ($cl($quest[IO_SEEK | IO_ARGS] ?? [])));


    if (!$quest[IO_SEEK_CALL]['status']) { // quest is ready for HTTP response
        $quest = io_probe(IO_SEND, $render, $plans, $quest ?? []);
        is_callable(($cl = $quest[IO_SEND | IO_CALL] ?? null))
            && ($quest[IO_SEND_CALL] = ($cl($quest[IO_SEEK_CALL])));
    }

    return $quest;
}

function io_draft(array $segments): array
{
    $candidates = [];
    $relative_path = '';
    foreach ($segments as $depth => $seg) {
        $relative_path .= DIRECTORY_SEPARATOR . $seg;

        $args = array_slice($segments, $depth + 1, null, true); // get remaining segments as args
        $candidates[$relative_path . DIRECTORY_SEPARATOR . $seg . '.php'] = $args;
        $candidates[$relative_path . '.php'] = $args; // direct match
    }

    return array_reverse($candidates);              // deep first, so that the most specific match is first
}

function io_probe($PHASE, $start, $plans, $quest = []): array
{
    foreach ($plans as $path => $args) {
        [$cl, $ob] = io_fetch($start . $path);      // fetch the file

        if (!$cl && !$ob) continue;                 // skip if no callable or output buffer

        $quest[$PHASE | IO_FILE] = $path;
        $quest[$PHASE | IO_ARGS] = $args;
        $quest[$PHASE | IO_CALL] = $cl;
        $quest[$PHASE | IO_LOAD] = $ob;
        break;
    }
    return $quest;
}

function io_fetch(string $file)     // returns [?callable, ?string] from $file
{
    return ob_start() ? [@include $file, ob_get_clean()] : [@include $file, null];
}

function io_clean(string $coded, $when_empty, $max_length = 4096, $max_decode = 9, $rx_remove = '#[^A-Za-z0-9\/\.\-\_]+#'): array
{
    do {
        $path = rawurldecode($coded);
    } while ($max_decode-- > 0 && $path !== $coded && ($coded = $path));

    $max_decode                     ?: throw new DomainException('Path decoding loop detected', 400);
    (strlen($path) > $max_length)   && throw new DomainException('Path Exceeds Maximum Allowed', 400);

    $path = $rx_remove ? preg_replace($rx_remove, '', $path) : $path;       // removes non alphanum /.-_
    $path = preg_replace('#\.\.+#', '', $path);                             // remove serial dots
    $path = preg_replace('#(?:\./|/\.|/\./)#', '/', $path);                 // replace(/): /. ./ /./
    $path = preg_replace('#\/\/+#', '/', $path);                            // replace(/): //+, 
    $path = trim($path, '/') ?: $when_empty;                                // trim leading and trailing slashes
    return explode('/', $path);
}

function io_state($quest, $__state = 0): int
{
    foreach ($quest as $step => $v) $__state |= $step;
    return $__state;
}
