<?php
const IO_NEST   = 1;                            // Flexible routing: try file + file/file
const IO_DEEP   = 2;                           // Deep-first seek
const IO_ROOT   = 4;                           // Root-first seek

function io_in(string $raw): string
{
    $path = parse_url($raw, PHP_URL_PATH) ?: '';
    $path = rawurldecode($path);

    strpos($path, "\0") === false || throw new InvalidArgumentException('Bad Request', 400);    // Reject null byte explicitly

    return trim($path, '/');   // eats all trailing slashes
}

function io_out(int $status, string $body, array $headers): void
{
    http_response_code($status);
    foreach ($headers as $h => $v)
        header("$h: $v"); // header() already detects CR/LF injection, drop them and logs a warning
    echo $body;
}

function io_map(string $base_dir, string $url_path, string $execution_suffix, int $behave = 0): ?array
{ // resolves an execution path
    if ((IO_DEEP | IO_ROOT) & $behave)
        return io_seek($base_dir, $url_path, $execution_suffix, $behave);

    return ($look_up = io_look($base_dir, $url_path, $execution_suffix, $behave)) !== null
        ? [$look_up, null]
        : null;
} // an array: [string path, ?array args]



function io_look(string $base_dir, string $url_path, string $execution_suffix, int $behave = 0): ?string
{
    (!$base_dir || $base_dir[-1] !== DIRECTORY_SEPARATOR) && throw new InvalidArgumentException('base_dir must end with directory separator '. DIRECTORY_SEPARATOR); // for valid strpos security check

    $path = $base_dir . $url_path;
    $file = $path . $execution_suffix;

    if (!is_file($file) && (IO_NEST & $behave)) {
        $file = $path . DIRECTORY_SEPARATOR . basename($url_path) . $execution_suffix;
        is_file($file) || ($file = null);
    }

    return $file !== null && ($real = realpath($file)) && strpos($real, $base_dir) === 0
        ? $real
        : null;
} // returns a real, in-base direct execution path, or null

function io_seek(string $base_dir, string $url_path, string $execution_suffix, int $behave = 0): ?array
{ // resolves an execution path by segment walk (and remaining segments), or null
    $slashes_positions = [];
    $slashes = 0;
    for ($pos = -1; ($pos = strpos($url_path, '/', $pos + 1)) !== false; ++$slashes)
        $slashes_positions[] = $pos;

    $segments = $slashes + 1;

    $depth  = IO_ROOT & $behave ? 1 : $segments;
    $end    = IO_ROOT & $behave ? $segments + 1 : 0; // +1 ? off-by-one workaround for $depth !== $end

    for ($step = (IO_ROOT & $behave) ? 1 : -1; $depth !== $end; $depth += $step) {
        $candidate = $depth <= $slashes
            ? substr($url_path, 0, $slashes_positions[$depth - 1])
            : $url_path;

        if ($path = io_look($base_dir, $candidate, $execution_suffix, $behave)) {
            $args = $depth > $slashes ? [] : explode('/', substr($url_path, $slashes_positions[$depth - 1] + 1));
            return [$path, $args];
        }
    }
    return null;
}
