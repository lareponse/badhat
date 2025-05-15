<?php

/**
 * ADDBAD Core Routing & Dispatch
 * Version: 1.2.5 (2025-05-12)
 */

declare(strict_types=1);

set_error_handler(function (int $errno, string $errstr): bool {
    if ($errno === E_USER_ERROR)
        respond(
            preg_match('/^([1-5]\d{2})\s+(.*)$/s', $errstr, $m)
                ? response((int)$m[1], $m[2])
                : response(500, $errno . ': ' . $errstr)
        );

    return false; // let PHP (or another handler) deal with notices, warnings, etc.

    return false; // prevent PHP from executing its internal error handler
});

set_exception_handler(function ($e) {
    trigger_error(sprintf('500 Uncaught Exception: %s in %s:%d', $e->getMessage(), $e->getFile(), $e->getLine()), E_USER_ERROR);
});



/**
 * @param string $route_root Absolute path to the routes directory
 * @return array ['handler' => string, 'args' => array, 'root' => string] or trigger_error with status+message
 */
function route(string $route_root): array
{
    $path = clean_request_uri($_SERVER['REQUEST_URI']);
    $format = request_mime($_SERVER['HTTP_ACCEPT'] ?? null, $_GET['format'] ?? null);

    $segments  = $path === '' ? ['home'] : explode('/', $path);
    $candidates = route_candidates($route_root, $segments);

    foreach ($candidates as $depth => $file) {
        $args = array_slice($segments, $depth + 1);
        if (file_exists($file)) {
            $real = realpath($file) ?: '';
            if (strpos($real, realpath($route_root)) === 0) {
                return ['handler' => $real, 'args' => $args, 'root' => realpath($route_root)];
            }
        } else
            $candidates[$depth] = ['handler' => $file, 'args' => $args, 'root' => realpath($route_root)];
    }

    // Route missing (DEV_MODE only)
    if (getenv('DEV_MODE')) {
        return scaffold($path, $candidates);
    }

    return response(404, 'Not Found', ['Content-Type' => 'text/plain']);
}


function handle(array $info): array
{
    if (isset($info['status']))
        return $info;

    if (empty($info['handler']))
        trigger_error('500 Handler not found', E_USER_ERROR);

    // summon main handler first, if an error is triggered, we wont run the hooks
    $handler = summon($info['handler']);

    //collect all hooks along the path
    $hooks = hooks($info['root'], $info['handler']);
    vd($hooks);
    foreach ($hooks['prepare'] as $hook)
        $hook();

    $res = $handler(...$info['args']);

    foreach (array_reverse($hooks['conclude']) as $hook)
        $res = $hook($res);

    return $res;
}

function respond(array $http): void
{
    http_response_code($http['status'] ?? 200);

    foreach ($http['headers'] ?? [] as $h => $v) {
        header("$h: $v");
    }
    echo $http['body'] ?? '';
}


function summon(string $file): ?callable
{
    if (!is_readable($file)) return null;

    ob_start();
    $callable = @include $file;
    ob_end_clean();

    if (!is_callable($callable)) trigger_error("500 Invalid Callable in $file", E_USER_ERROR);

    return $callable;
}

function clean_request_uri(?string $path = null): string
{

    $path = $path ?? $_SERVER['REQUEST_URI'] ?: '';
    $path  = urldecode(parse_url($path, PHP_URL_PATH) ?? '');

    // Basic traversal check
    if (preg_match('#(\.{2}|[\/]\.)#', $path)) {
        trigger_error('403 Forbidden: Path Traversal', E_USER_ERROR);
    }

    // Normalize and split segments
    $path = preg_replace('#/+#', '/', trim($path, '/'));

    return $path;
}

function route_candidates($route_root, $segments): array
{
    $candidates = [];
    $cur        = '';

    // Whitelist each segment and build candidate list
    foreach ($segments as $seg) {
        if (!preg_match('/^[a-z0-9_\-]+$/', $seg)) {
            trigger_error('400 Bad Request: Invalid Segment ' . sprintf('/%s/', $seg), E_USER_ERROR);
        }
        $cur .= '/' . $seg;
        $candidates[] = $route_root . $cur . '.php';
    }

    krsort($candidates);

    return $candidates;
}

function hooks(string $base, string $handler): array
{
    $base = rtrim($base, '/');
    $before = $after = [];

    // Figure out the path segments under $base
    $rel   = substr($handler, strlen($base) + 1);
    $parts = explode('/', $rel);

    // Build a list of all directories to check, starting with $base
    // $before[$base] = summon($base . '/prepare.php');
    // $after[$base] = summon($base . '/conclude.php');
    array_unshift($parts, ''); // add empty string to the start of the array
    foreach ($parts as $seg) {
        $base .= '/' . $seg;
        $before[$base . '/prepare.php'] = summon($base . '/prepare.php');
        $after[$base . '/prepare.php'] = summon($base . '/conclude.php');
    }
    // var_dump("base: $base", $before, $after);
    return [
        'prepare'  => array_filter($before),
        'conclude' => array_filter($after)
    ];
}

function response(int $http_code, string $body, array $http_headers = []): array
{
    return [
        'status'  => $http_code,
        'body'    => $body,
        'headers' => $http_headers,
    ];
}

function request_mime(?string $http_accept, ?string $requested_format): string
{
    if ($requested_format === 'json')
        return 'application/vnd.addbad+json';

    if (!empty($http_accept)) {
        $accept = explode(',', $http_accept);
        foreach ($accept as $type) {
            if (strpos($type, 'application/vnd.addbad') !== false) {
                return 'application/vnd.addbad+json';
            }
        }
    }

    return 'text/html';
}
