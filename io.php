<?php

const IO_NEST = 1;      // Flexible routing: try file + file/file patterns
const IO_DEEP = 2;      // Deep-first seek
const IO_ROOT = 4;      // Root-first seek

const IO_RETURN = 16;   // Value of the included file return statement
const IO_BUFFER = 32;   // Value of the included file output buffer
const IO_INVOKE = 64;   // Call fn(args) and store return value
const IO_ABSORB = 128;  // Call fn(buffer, args) and store return value

function io_map(string $base_dir, string $uri_path, int $behave = 0): ?array
{
  if ($path = io_look($base_dir, $uri_path, $behave))
    return [$path];

  if ($behave & (IO_DEEP | IO_ROOT))
    if ($path_and_args = io_seek($base_dir, $uri_path, $behave))
      return $path_and_args;

  return null;
}

function io_run(string $file_path, array $io_args, int $behave = 0, string $ext = '.html'): array
{
  $loot = [];

  ob_start();
  $loot[IO_RETURN] = include $file_path;
  $loot[IO_BUFFER] = ob_get_clean();

  $behave & IO_INVOKE && ($loot[IO_INVOKE] = $loot[IO_RETURN]($io_args));
  $behave & IO_ABSORB && ($loot[IO_ABSORB] = $loot[IO_RETURN]($loot[IO_BUFFER] ?: '', $io_args));

  if (!$loot[IO_BUFFER] && is_file($file_path = str_replace('.php', $ext, $file_path))) {
    ob_start();
    include $file_path;
    $loot[IO_BUFFER] = ob_get_clean();
  }

  return $loot;
}

// no trailing / for $base or $candidate
// no . for $extension
// return: ? full path to an -existing- file
function io_look(string $base_dir, string $candidate, int $behave = 0): ?string
{
  // Construct the base path (without extension)
  $path = $base_dir . DIRECTORY_SEPARATOR . $candidate;

  if (is_file($base_path = $path . '.php'))
    return $base_path;

  if ($behave & IO_NEST && is_file($nested_path = $path . DIRECTORY_SEPARATOR . basename($candidate) . '.php'))
    return $nested_path;

  return null;
}

// return: array with filepath+args or null
function io_seek(string $base_dir, string $uri_path, int $behave = 0): ?array
{
  $slashes_positions = [];
  $slashes = 0;
  for ($pos = -1; ($pos = strpos($uri_path, '/', $pos + 1)) !== false; ++$slashes)
    $slashes_positions[] = $pos;

  $segments = $slashes + 1;

  $depth  = $behave & IO_ROOT ? 1 : $segments;
  $end    = $behave & IO_ROOT ? $segments + 1 : 0; // +1 ? off-by-one workaround for !==

  for ($step = $behave & IO_ROOT ? 1 : -1; $depth !== $end; $depth += $step) {
    $candidate = $depth <= $slashes
      ? substr($uri_path, 0, $slashes_positions[$depth - 1])
      : $uri_path;

    if ($path = io_look($base_dir, $candidate, $behave)) {
      $args = $depth > $slashes ? [] : explode('/', substr($uri_path, $slashes_positions[$depth - 1] + 1));
      return [$path, $args];
    }
  }
  return null;
}
