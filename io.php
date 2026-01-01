<?php

const IO_NEST       = 1;                            // Flexible routing: try file + file/file
const IO_DEEP       = 2;                            // Deep-first seek
const IO_ROOT       = 4;                            // Root-first seek

const IO_RET_ROOTLESS = 8;                          // io_in returns rootless path
const IO_RET_ABSOLUTE = 16;                         // io_in returns absolute path
const IO_RAW_AS_QUERY = 32;                         //
const IO_THROW_ON_NUL = 64;
const IO_THROW_ON_CTL = 128;
const IO_THROW_ON_SP  = 256;

const IO_HTTP_STRICT = IO_THROW_ON_NUL | IO_THROW_ON_SP | IO_THROW_ON_CTL;

const ASCII_CONTROL_CARACTERS = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x7F";
const HTTP_QUERY_AND_FRAGMENTS = "?#";

function io_in(string $raw, string $forbidden = '', int $behave = 0): string
{
    $path = $raw;

    if (IO_RAW_AS_QUERY & $behave) {
        $stop = strcspn($path, HTTP_QUERY_AND_FRAGMENTS);
        isset($path[$stop]) && ($path = substr($path, 0, $stop));
    }

    $forbidden !== '' && isset($path[strcspn($path, $forbidden)])                           && throw new InvalidArgumentException('Bad Request: forbidden characters', 400);
    (IO_THROW_ON_NUL & $behave) && strpos($path, "\0") !== false                            && throw new InvalidArgumentException('Bad Request: nul byte character', 400);
    (IO_THROW_ON_SP  & $behave) && strpos($path, ' ')  !== false                            && throw new InvalidArgumentException('Bad Request: space in path', 400); // RFC 9112
    (IO_THROW_ON_CTL & $behave) && isset($path[strcspn($path, ASCII_CONTROL_CARACTERS)])    && throw new InvalidArgumentException('Bad Request: control characters', 400);

    ((IO_RET_ROOTLESS | IO_RET_ABSOLUTE) & $behave) && ($path = ltrim($path, '/'));
    (IO_RET_ABSOLUTE & $behave)                     && ($path = '/'.$path);

    return $path;
}

function io_out(int $status, string $body, array $headers): void
{
    http_response_code($status);
    if($status >= 200 && $status < 500)  // IMF-fixdate format per RFC 9110
        header('Date: ' . gmdate('D, d M Y H:i:s') . ' GMT');

    foreach ($headers as $h => $v)
        foreach ((array)$v as $val)                 // RFC 6265 and 9110
            header("$h: $val", is_string($v));      // header() already detects CR/LF injection, drop them and logs a warning

    if ($status > 199 && $status !== 204 && $status !== 304) // rfc9112
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
