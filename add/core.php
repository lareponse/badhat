<?php

/**
 * BADGE Core Routing & Dispatch
 */

declare(strict_types=1);

define('BADGE_MIME_BASE', '');
define('BADGE_MIME_DEFAULT', 'text/html');
define('BADGE_MIME_ACCEPT', 'application/vnd.BADGE+json, text/html, text/plain');

function resolve(): callable
{
    foreach (io_candidates('in') as $route) {
        if (!empty($route['handler']) && route_exists($route['handler'])) {
            $handler = summon($route['handler']);
            $hooks   = hooks($route['handler']);

            return function () use ($handler, $hooks, $route) {
                $response = [];
                // Prepare hooks
                foreach ($hooks['prepare'] as $prepare)
                    is_callable($prepare) ? $response = $prepare($response) : trigger_error("Invalid prepare hook in resolve(): " . json_encode($prepare), E_USER_NOTICE);

                $response = $handler($response, ...($route['args']));

                // Conclude hooks
                foreach ($hooks['conclude'] as $conclude)
                    is_callable($conclude) ? $response = $conclude($response) : trigger_error("Invalid conclude hook in resolve(): " . json_encode($conclude), E_USER_NOTICE);

                return $response;
            };
        }
    }

    return function () {
        if (is_dev() && !empty(io_candidates('in', true))) {
            ob_start(); {
                require_once 'add/bad/scaffold.php';
            }
            $scaffold = ob_get_clean();  // Scaffold response
            return response(404, $scaffold, ['Content-Type' => 'text/html; charset=UTF-8']);
        }
        vd(io_candidates('in', true));
        die('too late');
        return response(404, 'Not Found', ['Content-Type' => 'text/plain']);
    };
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

function io(?string $arg = null): array
{
    static $io = [];

    if (!$io) {
        $arg                                                ?: throw new BadFunctionCallException('IO Requires Real Route Root', 500);
        $d = glob(dirname($arg) . '/*', GLOB_ONLYDIR) ?: [];
        count($d) === 2                                     || throw new RuntimeException('One folder containing in (route) and out (render) files', 500);
        $in = realpath($arg)                                ?: throw new RuntimeException('Route Reality Rescinded', 500);
        $out = realpath($d[0] === $in ? $d[0] : $d[1])      ?: throw new RuntimeException('Render Reality Rescinded', 500);

        $io = [$in, $out];
    }

    return $io;
}

function io_candidates(string $in_or_out, bool $scaffold = false): array
{
    $candidates = [];
    $cur        = '';
    $in_or_out  = $in_or_out === 'in' ? io()[0] : io()[1];

    foreach (request()['segments'] as $depth => $seg) {
        $cur .= '/' . $seg;
        $args = array_slice(request()['segments'], $depth + 1);

        $possible = [
            $in_or_out . $cur . '.php',
            $in_or_out . $cur . DIRECTORY_SEPARATOR . 'index.php',
        ];

        foreach ($possible as $candidate)
            $candidates[] = handler($candidate, $args);
    }

    krsort($candidates);

    if ($scaffold)
        return $candidates;

    foreach ($candidates as $candidate)
        if (route_exists($candidate['handler']) || file_exists($candidate['handler']))
            return [$candidate];

    return [];
}

function route_exists(string $file): bool
{
    static $routes = null;

    if ($routes === null) {
        $cache_file = dirname(io()[0]) . '/routes.cache';
        $routes = file_exists($cache_file) ? file_get_contents($cache_file) : '';
    }

    return strpos($routes, $file) !== false || file_exists($file);
}

function hooks(string $handler): array
{
    $base = rtrim(io()[0], '/');
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
        'conclude' => array_reverse(array_filter($after))
    ];
}

function request(?callable $uri_cleaner = null): array
{
    static $request;

    if ($request === null) {

        // Rate limit by IP
        // if (function_exists('check_rate_limit') && !check_rate_limit($_SERVER['REMOTE_ADDR'])) {
        //     trigger_error('429 Too Many Requests: Rate limit exceeded', E_USER_WARNING);
        //     return [
        //         'status' => 429,
        //         'body' => render(['error' => 'Too many requests, try again later.'])
        //     ];
        // }

        // CSRF check
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && function_exists('csrf') && !csrf($_POST['csrf_token'] ?? '')) {
            trigger_error('403 Forbidden: Invalid CSRF token', E_USER_WARNING);
            return response(403, render(['error' => 'Invalid CSRF token.']));
        }

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



function request_mime(?string $http_accept, ?string $requested_format): string
{
    return strpos("$requested_format$http_accept", 'application/vnd.BADGE+json') !== false ? 'application/vnd.BADGE+json' : 'text/html';
}
