<?php

/**
 * ADDBAD Core Routing & Dispatch
 * Version: 1.2.5 (2025-05-12)
 */

declare(strict_types=1);

// Maximum URL segments used for routing (extras become handler args)
if (!defined('MAX_ROUTE_SEGMENTS')) {
    define('MAX_ROUTE_SEGMENTS', 3);
}

/**
 * @param string $route_root Absolute path to the routes directory
 * @return array ['handler' => string, 'args' => array, 'root' => string] or exits with status+message
 */
function route(string $route_root): array
{
    $path = $_SERVER['REQUEST_URI'] ?? 'home';
    $raw  = urldecode(parse_url($path, PHP_URL_PATH) ?? '');

    // Basic traversal check
    if (preg_match('#(\.{2}|[\/]\.)#', $raw)) {
        exit('403 Forbidden');
    }

    // Normalize and split segments
    $clean = preg_replace('#/+#', '/', trim($raw, '/'));
    $segs  = $clean === '' ? ['home'] : explode('/', $clean);

    // Whitelist each segment
    foreach ($segs as $seg) {
        if (!preg_match('/^[a-z0-9_\-]+$/', $seg)) {
            exit(sprintf('400 Invalid Segment /%s/', $seg));
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

    // Route missing (DEV_MODE only)
    if (getenv('DEV_MODE')) {
        return scaffold($segs, $route_root, $candidates);
    }

    exit('404 Not Found');
}

/**
 * Phase 2: Dispatch prepare hooks, handler, then conclude hooks
 *
 * @param array $info ['handler','args','root']
 * @return array or exits with status+message
 */
function handle(array $info): array
{
    // Early exit on scaffold or errors from route()
    if (isset($info['status'])) {
        exit($info['status'] . ' ' . ($info['body'] ?? ''));
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

        $prepFile = $curDir . '/prepare.php';
        if (file_exists($prepFile)) {
            $fn = silent_include($prepFile);
            if (!is_callable($fn)) {
                exit("500 Invalid prepare hook at $prepFile");
            }
            $res = $fn();
            if (is_array($res)) {
                exit(
                    ($res['status'] ?? 500) . ' ' .
                    ($res['body']   ?? ''));
            }
        }

        $concFile = $curDir . '/conclude.php';
        if (file_exists($concFile)) {
            $fn2 = silent_include($concFile);
            if (!is_callable($fn2)) {
                exit("500 Invalid conclude hook at $concFile");
            }
            $concludes[] = $fn2;
        }
    }

    // Handler
    $fn = silent_include($handler);
    if (!is_callable($fn)) {
        exit('500 Invalid route handler');
    }
    $res = $fn(...$args);
    if (!is_array($res)) {
        exit('500 Handler did not return response array');
    }

    // Run conclude hooks in reverse
    foreach (array_reverse($concludes) as $finish) {
        $res = $finish($res);
        if (!is_array($res)) {
            exit('500 Conclude hook did not return response array');
        }
    }

    return $res;
}

/**
 * Scaffold for missing routes (DEV_MODE only)
 */
function scaffold(array $parts, string $route_root, array $candidates): array
{
    $path       = implode('/', $parts);
    $limit      = min(count($parts), MAX_ROUTE_SEGMENTS);
    $routeSegs  = array_slice($parts, 0, $limit);
    $handlerArgs = array_slice($parts, $limit);
    $routePath  = implode('/', $routeSegs) ?: 'home';

    $body  = "<pre>Missing route: /$path\n\n";
    $body .= "Choose route file to create:\n";
    $body .= implode("\n", array_map(fn($f) => "  $f", $candidates)) . "\n\n";
    $body .= "And add code: \n\n";
    $body .= htmlspecialchars("<?php\n\nreturn function (...\$args) {\n\treturn ['status' => 200, 'body' => __FILE__];\n};");
    $body .= "\n\n";
    $body .= "Expected arguments with this query: function(" . htmlspecialchars(json_encode($handlerArgs)) . ")";
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

/**
 * Silently include a PHP file: suppress warnings and any output.
 *
 * @param string $file
 * @return mixed
 */
function silent_include(string $file)
{
    if (!is_readable($file)) {
        return null;
    }
    ob_start();
    /** @noinspection PhpIncludeInspection */
    $result = @include $file;
    ob_end_clean();
    return $result;
}


register_shutdown_function(function () {
    $output = ob_get_clean();
    // if it looks like "### Message...", use that as status + body
    if (preg_match('/^(\d{3})\s+(.+)/s', $output, $m)) {
        respond([
            'status' => (int) $m[1],
            'body'   => $m[2],
        ]);
    }
    // otherwise, just output raw (you could also choose to swallow it)
    else {
        echo $output;
    }
});

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // Ignore notices and warnings
    if (error_reporting() === 0 || in_array($errno, [E_NOTICE, E_WARNING], true)) {
        error_log(sprintf('%s in %s:%d', $errstr, $errfile, $errline));
        return;
    }
    // Handle fatal errors
    if (in_array($errno, [E_ERROR, E_PARSE], true)) {
        exit(sprintf('500 %s in %s:%d', $errstr, $errfile, $errline));
    }
}, E_ALL);

set_exception_handler(function ($e) {
    // Handle uncaught exceptions
    exit(sprintf('500 %s in %s:%d', $e->getMessage(), $e->getFile(), $e->getLine()));
});
