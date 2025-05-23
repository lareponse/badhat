<?php

/**
 * BADGE Core Routing & Dispatch
 */

declare(strict_types=1);

function route(string $route_root): array
{
    // creates the request object
    request($route_root);

    $real_root = request()['route_root'];

    foreach (io_candidates($real_root) as $candidate) {
        if (strpos($candidate['handler'], $real_root) === 0 && file_exists($candidate['handler'])) {
            return $candidate;
        }
    }

    // Route missing (DEV_MODE only)
    if (defined('IS_DEV') && IS_DEV) {
        ob_start();
        @include __DIR__ . '/bad/scaffold.php';
        return response(202, ob_get_clean());
    }

    return response(404, 'Not Found', ['Content-Type' => 'text/plain']);
}

function handle(array $route): array
{
    if (isset($route['status'])) // already a response
        return $route;

    if (empty($route['handler']))
        trigger_error('500 Handler not found', E_USER_ERROR);

    // 1. summon main handler first, if an error is triggered, we wont run the hooks
    $handler = summon($route['handler']);

    // 2. collect all hooks along the path
    $hooks = hooks($route['handler']);

    // 3. run all prepare hooks
    foreach ($hooks['prepare'] as $hook)
        $hook();

    // 4. run the main handler
    $res = $handler(...($route['args']));

    // 5. run all conclude hooks
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

function io_candidates(string $in_or_out, bool $scaffold = false): array
{
    static $segments = null;

    if ($segments === null) {
        $segments = trim(request()['path'], '/') ?: 'home';
        $segments = explode('/', $segments);
        foreach ($segments as $seg)
            preg_match('/^[a-z0-9_\-]+$/', $seg) ?: trigger_error('400 Bad Request: Invalid Segment /' . $seg . '/', E_USER_ERROR);
    }

    $candidates = [];
    $cur        = '';

    // Whitelist each segment and build candidate list
    foreach ($segments as $depth => $seg) {

        $cur .= '/' . $seg;

        
        $args = array_slice($segments, $depth + 1); // remaining segments are args

        $possible = [
            $in_or_out . $cur . '.php',
            $in_or_out . $cur . DIRECTORY_SEPARATOR . $seg . '.php',
        ];
        foreach ($possible as $candidate) {

            if (strpos($candidate, $in_or_out) !== 0) // skip if the candidate is not in the same root
                continue;

            $candidates[] = handler($candidate, $args);
        }
    }

    krsort($candidates);

    if ($scaffold)
        return $candidates;

    foreach ($candidates as $candidate)
        if (strpos($candidate['handler'], $in_or_out) === 0  && file_exists($candidate['handler']))
            return [$candidate];
    
    return [];
}

function handler(string $path, array $args = []): array
{
    return ['handler' => $path, 'args' => $args];
}

function hooks(string $handler): array
{
    $base = rtrim(request()['route_root'], '/');
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

function response(int $http_code, string $body, array $http_headers = []): array
{
    return [
        'status'  => $http_code,
        'body'    => $body,
        'headers' => $http_headers,
    ];
}

function request(?string $route_root = null, ?callable $cleanor = null): array
{
    static $request = null;

    if ($request === null) {

        $route_root = $route_root                   ?: trigger_error('500 Request Requires Route Root', E_USER_ERROR);
        $route_root = realpath($route_root)         ?: trigger_error('500 Route Root Reality Report', E_USER_ERROR);
        $root = realpath($route_root . '/../../')   ?: trigger_error('500 Root Reality Report', E_USER_ERROR);

        $cleanor ??= function () {
            $_ = $_SERVER['REQUEST_URI']            ?: '';
            $_ = parse_url($_, PHP_URL_PATH)        ?: '';
            $_ = urldecode($_);
            !preg_match('#(\.{2}|[\/]\.)#', $_)      ?: trigger_error('403 Forbidden: Path Traversal', E_USER_ERROR);
            $_ = preg_replace('#/+#', '/', trim($_, '/'));

            return $_;
        };


        $path = $cleanor();

        $request = [
            'route_root'    => $route_root,
            'root'          => $root,
            'method'        => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'format'        => request_mime($_SERVER['HTTP_ACCEPT'] ?? null, $_GET['format'] ?? null),
            'path'          => '/' . $path
        ];
    }

    return $request;
}

function request_mime(?string $http_accept, ?string $requested_format): string
{
    if ($requested_format === 'json')
        return 'application/vnd.BADGE+json';

    if (!empty($http_accept)) {
        $accept = explode(',', $http_accept);
        foreach ($accept as $type) {
            if (strpos($type, 'application/vnd.BADGE') !== false) {
                return 'application/vnd.BADGE+json';
            }
        }
    }

    return 'text/html';
}
