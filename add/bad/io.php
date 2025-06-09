<?php

declare(strict_types=1);

const IO_STATE = 0;     // State bitmask

const IO_SEEK =  1;     // Parity Phase flags:  - any ODD flag  is a seek step,
const IO_SEND =  2;     //                      - any EVEN flag is a send step  

const IO_FILE =  4;     // Steps flags:     - the found file, , a ?callable, and ?string (output buffer)
const IO_ARGS =  8;     //                  - an ?array (remainder of /url_segments/) for the callable
const IO_CALL = 16;     //                  - a ?callable to execute ?
const IO_LOAD = 32;     //                  - a ?string output buffer ?

const IO_SEEK_CALL = IO_SEEK | IO_CALL | IO_ARGS;
const IO_SEND_CALL = IO_SEND | IO_CALL | IO_LOAD;

// io calls might/should trigger an http_response, if not, return the quest array for end-dev
function io(?string $in_path = null, ?string $out_path = null): array
{
    $in_path  ?: $out_path  ?:  throw new BadFunctionCallException('No input or output path provided', 400);
    $candidates = io_paths('index');
    $candidates             ?:  throw new DomainException('No IO paths found', 404);

    if ($in_path && ($found = io_probe($in_path, $candidates))) {
        foreach ($found as $step => $value)
            io_quest(IO_SEEK | $step, $value);       // set the quest values

        if (is_callable($found[IO_CALL]))
            io_quest(IO_SEEK_CALL, $found[IO_CALL]($found[IO_ARGS]));
    }
    if ($out_path && ($found = io_probe($out_path, $candidates))) {
        foreach ($found as $step => $value) {
           io_quest(IO_SEND | $step, $value);       // set the quest values
        }

        if (is_callable($found[IO_CALL]))
            io_quest(IO_SEND_CALL, $found[IO_CALL]($found[IO_LOAD]));
    }

    return io_quest();
}

function io_quest(?int $flag = null, $value = null): mixed
{
    static $quest = [IO_STATE => 0];

    if (isset($flag, $value) && $flag !== IO_STATE) {   // quest update
        $quest[$flag] = $value;                         //  - sets the flag value
        $quest[IO_STATE] |= $flag;                      //  - updates the state flag
    } else if (isset($flag))
        return $quest[$flag] ?? null;

    return $quest;
}

function io_fetch(string $file)                 // returns null on failure, [null, null] on empty, or [?callable, ?output_buffer] on success
{
    ob_start();
    $call = @include $file;
    $echo = ob_get_clean() ?: null;

    if ($call === false)
        return null;

    if (!is_callable($call))
        $call = null;  // ensure $call is a callable, or null

    return [$call, $echo];
}

function io_probe(string $start, array $candidates): ?array
{
    foreach ($candidates as $path => $args) {
        $yield = io_fetch($start . $path);             // fetch the file

        if ($yield === null) continue;                 // skip if no callable or output buffer

        [$cl, $ob] = $yield;
        return [IO_FILE => $path, IO_ARGS => $args, IO_CALL => $cl, IO_LOAD => $ob];
    }
    return null;
}

function io_paths(string $default_url_path): array
{
    $url_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
    $url_path = io_clean($url_path) ?: $default_url_path ?: throw new DomainException('Empty URL path, empty default provided', 400);
    return io_draft($url_path);
}

function io_draft(string $url_path, $target=null): array
{
    $segments = explode('/', $url_path);
    $candidates = [];
    $relative_path = '';
    foreach ($segments as $depth => $seg) {
        $args = array_slice($segments, $depth + 1, null, true); // get remaining segments as args
        if(null === $target) {
            $relative_path .= DIRECTORY_SEPARATOR . $seg;
            $candidates[$relative_path . DIRECTORY_SEPARATOR . $seg . '.php'] = $args;
            $candidates[$relative_path . '.php'] = $args;       // direct match
        }
        else{
            $candidates[$relative_path . DIRECTORY_SEPARATOR . $target . '.php'] = $args;  // direct match
            $candidates[$relative_path . DIRECTORY_SEPARATOR . $seg . DIRECTORY_SEPARATOR . $target . '.php'] = $args;
            $relative_path .= DIRECTORY_SEPARATOR . $seg; // save the current relative path
        }
    }
    return array_reverse($candidates);  // deep first, so that the most specific match is first
}

function io_clean(string $coded, $max_length = 4096, $max_decode = 9, $rx_remove = '#[^A-Za-z0-9\/\.\-\_]+#'): string
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
    $path = trim($path, '/');                                               // trim leading and trailing slashes
    return $path;
}
