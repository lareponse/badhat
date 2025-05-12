<?php

/**
 * ADDBAD Core Routing & Dispatch
 * Version: 1.2.0 (2025-05-12)
 */

declare(strict_types=1);

// Maximum URL segments used for routing (extras become handler args)
if (!defined('MAX_ROUTE_SEGMENTS')) {
    define('MAX_ROUTE_SEGMENTS', 3);
}

/**
 * Phase 1: Resolve handler file and args
 *
 * @param string $route_root Absolute path to the routes directory
 * @return array ['handler' => string, 'args' => array, 'root' => string] or ['status'=>int,'body'=>string]
 */
function route(string $route_root): array
{
    // Extract and clean path
    $path = $_SERVER['REQUEST_URI'] ?? '';
    $raw  = urldecode(parse_url($path, PHP_URL_PATH) ?? '');

    if (preg_match('#(\.{2}|[\\/]\.)#', $raw)) {
        return ['status' => 403, 'body' => 'directory traversal'];
    }

    $clean = preg_replace('#/+#', '/', trim($raw, '/'));
    $segs  = $clean === '' ? ['home'] : explode('/', $clean);

    // Whitelist each segment
    foreach ($segs as $seg) {
        if (!preg_match('/^[a-z0-9_\-]+$/', $seg)) {
            return ['status' => 400, 'body' => 'Bad Request'];
        }
    }

    // Build candidate list (deepest-first) using up to MAX_ROUTE_SEGMENTS
    $limit      = min(count($segs), MAX_ROUTE_SEGMENTS);
    $candidates = [];
    $cur        = '';

    for ($i = 0; $i < $limit; $i++) {
        $cur .= '/' . $segs[$i];
        $candidates[$i] = $route_root . $cur . '.php';
    }

    krsort($candidates);

    // Find matching handler
    foreach ($candidates as $depth => $file) {
        if (file_exists($file)) {
            $real = realpath($file) ?: '';
            if (strpos($real, realpath($route_root)) === 0) {
                $args = array_slice($segs, $depth + 1);
                return ['handler' => $real, 'args' => $args, 'root' => realpath($route_root)];
            }
        }
    }

    // Route missing
    if (getenv('DEV_MODE')) {
        return scaffold($segs, $route_root);
    }
    return ['status' => 404, 'body' => 'Not Found'];
}

/**
 * Phase 2: Dispatch prepare hooks, handler, then conclude hooks
 *
 * @param array $info ['handler','args','root']
 * @return array
 */
function handle(array $info): array
{
    if (isset($info['status'])) {
        return $info;
    }

    $base     = $info['root'];
    $handler  = $info['handler'];
    $args     = $info['args'];

    // Determine hook directories relative to base
    $rel       = substr($handler, strlen($base) + 1);
    $parts     = explode('/', dirname($rel));
    $curDir    = $base;
    $concludes = [];

    // Prepare and collect conclude hooks
    foreach ($parts as $seg) {
        $curDir .= '/' . $seg;

        $_ = $curDir . '/prepare.php';
        if (file_exists($_)) {
            ob_start();
            $fn = include $_;
            ob_end_clean();
            if (is_callable($fn) && $res = $fn()) {
                return $res;
            }
        }

        $_ = $curDir . '/conclude.php';
        if (file_exists($_)) {
            ob_start();
            $fn2 = include $_;
            ob_end_clean();
            if (is_callable($fn2)) {
                $concludes[] = $fn2;
            }
        }
    }

    // Handler
    $fn = require $handler;
    if (!is_callable($fn)) {
        return ['status' => 500, 'body' => 'Invalid route handler'];
    }
    $res = $fn(...$args);

    // Run conclude hooks in reverse
    foreach (array_reverse($concludes) as $finish) {
        $res = $finish($res);
    }

    return $res;
}

/**
 * Scaffold for missing routes (DEV_MODE only)
 */
function scaffold(array $parts, string $route_root): array
{
    $path       = implode('/', $parts);
    $limit      = min(count($parts), MAX_ROUTE_SEGMENTS);
    $routeSegs  = array_slice($parts, 0, $limit);
    $handlerArgs = array_slice($parts, $limit);
    $routePath  = implode('/', $routeSegs) ?: 'home';

    $body  = "<pre>Missing route: /$path\n\n";
    $body .= "Create route file: $route_root/$routePath.php\n\n";
    $body .= htmlspecialchars("<?php
return function (...\$args) {
    return ['status' => 200, 'body' => __FILE__];
};");
    $body .= "\n\n";
    $body .= "Expected argument values: function(" . htmlspecialchars(json_encode($handlerArgs)) . ")";
    $body .= "</pre>";

    return ['status' => 404, 'body' => $body];
}

/**
 * Send response with security headers
 */
function respond(array $res): void
{
    http_response_code($res['status'] ?? 200);
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header("Content-Security-Policy: default-src 'self'");

    foreach ($res['headers'] ?? [] as $h => $v) {
        header("$h: $v");
    }
    echo $res['body'] ?? '';
}
