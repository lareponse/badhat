<?php

/**
 * BADGE Core Routing & Dispatch
 */

declare(strict_types=1);
define('BADGE_MIME_BASE', 'application/vnd.BADGE');

function route($route_root): array
{
    request();
    io($route_root);
    
    // creates the request object
    $real_root = io('i');
    foreach (io_candidates($real_root) as $candidate) {
        if (strpos($candidate['handler'], $real_root) === 0 && file_exists($candidate['handler'])) {
            return $candidate;
        }
    }

    if (is_dev()) {
        ob_start();
        @include __DIR__ . '/bad/scaffold.php';
        return response(202, ob_get_clean());
    }

    return response(404, 'Not Found', ['Content-Type' => 'text/plain']);
}

function handle(array $route): array
{
    if (!empty($route['status'])) // already a response
        return $route;

    if (empty($route['handler'])) // no handler
        return response(404, 'Not Found', ['Content-Type' => 'text/plain']);
    // summon end point handler 
    $handler = summon($route['handler']);
    $hooks = hooks($route['handler']);
    // gather prepare/conclude hooks along the route tree

    // prepare > execute > conclude
    foreach ($hooks['prepare'] as $hook)
        $hook();

    $res = null;
    if ($handler)
        $res = $handler(...($route['args']));

    foreach (array_reverse($hooks['conclude']) as $hook)
        $res = $hook($res);

    if ($handler === null && !is_array($res)) {
        $static = render([], $route['handler']);

        if ($static) {
            $res = response(200, $static, ['Content-Type' => 'text/html']);
        } else {
            $res = response(500, 'Internal Server Error', ['Content-Type' => 'text/plain']);
        }
    }
    return $res ?? [];
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

    if (is_callable($callable))
        return $callable;

    trigger_error("Invalid Callable in $file", E_USER_NOTICE);
    return null;
}

/**
 * Replace file_exists() with strpos() in existing BADGE functions
 * 
 * Generate cache: find app/route -name '*.php' > routes.cache
 */

function route_exists(string $file): bool
{
    static $routes = null;

    if ($routes === null) {
        $cache_file = dirname(io('i')) . '/routes.cache';
        $routes = file_exists($cache_file) ? file_get_contents($cache_file) : '';
    }

    return strpos($routes, $file) !== false;
}

// Modify existing io_candidates() - replace file_exists() with route_exists()
function io_candidates(string $in_or_out, bool $scaffold = false): array
{
    static $segments = null;

    if ($segments === null) {
        $segments = trim(request()['path'], '/') ?: 'home';
        $segments = explode('/', $segments);
        foreach ($segments as $seg)
            preg_match('/^[a-zA-Z0-9_\-]+$/', $seg) ?: throw new DomainException('Bad Request: Invalid Segment /' . $seg . '/', 400);
    }

    $candidates = [];
    $cur        = '';

    foreach (request()['segments'] as $depth => $seg) {
        $cur .= '/' . $seg;
        $args = array_slice(request()['segments'], $depth + 1);

        $possible = [
            $in_or_out . $cur . '.php',
            $in_or_out . $cur . DIRECTORY_SEPARATOR . $seg . '.php',
        ];
        foreach ($possible as $candidate) {
            if (strpos($candidate, $in_or_out) !== 0)
                continue;

            $candidates[] = handler($candidate, $args);
        }
    }

    krsort($candidates);

    if ($scaffold)
        return $candidates;

    foreach ($candidates as $candidate)
        if (strpos($candidate['handler'], $in_or_out) === 0 && route_exists($candidate['handler']))
            return [$candidate];

    // Cache miss: probe filesystem
    foreach ($candidates as $candidate)
        if (strpos($candidate['handler'], $in_or_out) === 0 && file_exists($candidate['handler']))
            return [$candidate];

    return [];
}


function hooks(string $handler): array
{
    $base = rtrim(io('i'), '/');
    $before = $after = [];

    // Figure out the path segments under $base
    $rel   = substr($handler, strlen($base) + 1);
    $parts = explode('/', $rel);

    array_unshift($parts, ''); // add empty string to the start of the array
    foreach ($parts as $seg) {
        $base .= '/' . $seg;
        $before[$base . '/prepare.php'] = summon($base . '/prepare.php');
        $after[$base . '/conclude.php'] = summon($base . '/conclude.php');
    }
    return [
        'prepare'  => array_filter($before),
        'conclude' => array_filter($after)
    ];
}

function request(?callable $uri_cleaner = null): array
{
    static $request;

    if ($request === null) {
        $request = parse_url($_SERVER['REQUEST_URI'] ?? '/');

        $uri_cleaner ??= function (string $uri) {
            $uri = parse_url($uri, PHP_URL_PATH)        ?: '';
            $uri = urldecode($uri);
            !preg_match('#(\.{2}|[\/]\.)#', $uri)      ?: throw new DomainException('Forbidden: Path Traversal', 403);
            $uri = preg_replace('#/+#', '/', rtrim($uri, '/'));

            return $uri;
        };

        $request['path'] = $uri_cleaner($request['path']);

        $segments = explode('/', trim($request['path'], '/') ?: 'home');
        foreach ($segments as $seg)
            preg_match('/^[a-zA-Z0-9_\-]+$/', $seg) ?: throw new DomainException('Bad Request: Invalid Segment /' . $seg . '/', 400);
        $request['segments'] = $segments;

        $request['accept'] = request_mime($_SERVER['HTTP_ACCEPT'] ?? null, $_GET['format'] ?? null);
    }

    return $request;
}

function handler(string $path, array $args = []): array
{
    return ['handler' => $path, 'args' => $args];
}

function response(int $http_code, string $body, array $http_headers = []): array
{
    return [
        'status'  => $http_code,
        'body'    => $body,
        'headers' => $http_headers,
    ];
}

function io(string $arg): string
{
    static $io = [];

    if(!$io){
        $route_root = $arg ?:  throw new BadFunctionCallException('IO Requires Real Route Root', 500);

        $d = glob(dirname($route_root) . '/*', GLOB_ONLYDIR) ?: [];
        count($d) === 2                                     || throw new RuntimeException('One folder containing in (route) and out (render) files', 500);
        $in = realpath($route_root)                         ?: throw new RuntimeException('Route Reality Rescinded', 500);
        $out = realpath($d[0] === $in ? $d[1] : $d[0])      ?: throw new RuntimeException('Render Reality Rescinded', 500);
        $root = realpath(dirname(__DIR__))                  ?: throw new RuntimeException('Root Reality Rescinded', 500);

        $io = [
            'i' => $in,
            'o' => $out,
            '/' => $root,
        ];

        return '';
    }
    
    return $io[$arg] ?? throw new InvalidArgumentException("Invalid IO argument: $arg [i,o,/]", 500);
}


function request_mime(?string $http_accept, ?string $requested_format): string
{
    return strpos("$requested_format$http_accept", 'application/vnd.BADGE+json') !== false ? 'application/vnd.BADGE+json' : 'text/html';
}
