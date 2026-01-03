<?php

const IO_NEST       = 1;
const IO_DEEP       = 2;
const IO_ROOT       = 4;
const IO_ROOTLESS   = 8;
const IO_ABSOLUTE   = 16 | IO_ROOTLESS;
const IO_PATH_ONLY  = 32;

const IO_HDR_STRICT = 64;
const IO_HDR_SOFT   = 128;

const ASCII_CTL = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x7F";
const HTTP_PATH_UNSAFE = ' ' . ASCII_CTL;
const HTTP_TCHAR = '!#$%&\'*+-.^_`|~0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

function io_in(string $raw, string $forbidden = '', int $behave = 0): string
{
    $path = $raw;

    (IO_PATH_ONLY & $behave) && ($stop = strcspn($path, '?')) && isset($path[$stop]) && ($path = substr($path, 0, $stop));

    $forbidden !== '' && isset($path[strcspn($path, $forbidden)]) && throw new InvalidArgumentException('Bad Request', 400);

    (IO_ROOTLESS & $behave) && ($path = ltrim($path, '/'));
    (IO_ABSOLUTE & $behave) && ($path = '/' . $path);

    return $path;
}

function io_out(int $status, string $body, array $headers, int $behave = IO_HDR_SOFT): void
{
    http_response_code($status);
    ($status >= 200 && $status < 500) && !isset($headers['Date']) && header('Date: ' . gmdate('D, d M Y H:i:s') . ' GMT');

    foreach ($headers as $h => $v) {
        $h_invalid = !$h || isset($h[strspn($h, HTTP_TCHAR)]);

        foreach ((array)$v as $val) {
            if ($h_invalid || strpbrk($val, ASCII_CTL) !== false) {
                (IO_HDR_STRICT & $behave) && throw new InvalidArgumentException("header invalid: $h", 500);
                (IO_HDR_SOFT & $behave) && trigger_error("io_out: header rejected: $h", E_USER_WARNING);
                continue;
            }
            header("$h: $val", is_string($v));
        }
    }

    ($status > 199 && $status !== 204 && $status !== 304) && print $body;
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
