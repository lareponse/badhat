<?php

/**
 * ADDBAD Core Routing & Dispatch
 * Version: 1.2.5 (2025-05-12)
 */

declare(strict_types=1);

/**
 * @param string $route_root Absolute path to the routes directory
 * @return array ['handler' => string, 'args' => array, 'root' => string] or trigger_error with status+message
 */
function route(string $route_root): array
{
    $path = $_SERVER['REQUEST_URI'] ?? 'home';
    $raw  = urldecode(parse_url($path, PHP_URL_PATH) ?? '');

    // Basic traversal check
    if (preg_match('#(\.{2}|[\/]\.)#', $raw)) {
        trigger_error('403 Forbidden: Path Traversal', E_USER_ERROR);
    }

    // Normalize and split segments
    $clean = preg_replace('#/+#', '/', trim($raw, '/'));
    $segs  = $clean === '' ? ['home'] : explode('/', $clean);
    $candidates = [];
    $cur        = '';

    // Whitelist each segment and build candidate list
    foreach ($segs as $seg) {
        if (!preg_match('/^[a-z0-9_\-]+$/', $seg)) {
            trigger_error('400 Bad Request: Invalid Segment ' . sprintf('/%s/', $seg), E_USER_ERROR);
        }

        $cur .= '/' . $seg;
        $candidates[] = $route_root . $cur . '.php';
    }

    // Find matching handler, deepth-first
    krsort($candidates);
    foreach ($candidates as $depth => $file) {
        if (file_exists($file)) {
            $real = realpath($file) ?: '';
            if (strpos($real, realpath($route_root)) === 0) {
                $args = array_slice($segs, $depth + 1);
                return ['handler' => $real, 'args' => $args, 'root' => realpath($route_root)];
            }
        } else $candidates[$depth] = ['handler' => $file, 'args' => array_slice($segs, $depth + 1), 'root' => realpath($route_root)];
    }

    // Route missing (DEV_MODE only)
    if (getenv('DEV_MODE')) {
        return scaffold($path, $route_root, $candidates);
    }

    trigger_error('404 Not Found', E_USER_ERROR);
}

/**
 * Phase 2: Dispatch prepare hooks, handler, then conclude hooks
 *
 * @param array $info ['handler','args','root']
 * @return array or exits with status+message
 */
function handle(array $info): array
{
    if(isset($info['status']))
        return $info;

    if (empty($info['handler']))
        trigger_error('500 Handler not found', E_USER_ERROR);

    // summon main handler first, if an error is triggered, we wont run the hooks
    $handler = summon($info['handler']);

    $hooks = hooks($info['root'], $info['handler']);

    foreach ($hooks['prepare'] as $hook)
        $hook();

    $res = $handler(...$info['args']);

    foreach (array_reverse($hooks['conclude']) as $hook)
        $res = $hook($res);

    return $res;
}

/**
 * Scaffold for missing routes (DEV_MODE only)
 */
function scaffold(string $path, string $route_root, array $candidates): array
{
    $body = "<h1>Missing route: $path</h1>\n\n";
    $body .= "Choose route file to create:\n";
    $body .= "<dl>";
    foreach ($candidates as $depth => $response) {
        $handler = $response['handler'];
        $handlerArgs = empty($response['args']) ? 'none' : implode(',', $response['args']);
        $templateCode = "<?php\nreturn function (...\$args) {\n\t// Expected arguments: function($handlerArgs)\n\treturn ['status' => 200, 'body' => __FILE__];\n};";
        $body .= "<dt><strong>$handler</strong></dt>";
        $body .= '<dd><pre>' . htmlspecialchars($templateCode) . '</pre></dd>';
    }
    $body .= "</dl>";


    return ['status' => 404, 'body' => $body];
}

function hooks(string $base, string $handler): array
{
    $ret = [
        'prepare'  => [],
        'conclude' => [],
    ];

    // Figure out the path segments under $base
    $rel   = substr($handler, strlen($base) + 1);
    $parts = explode('/', $rel);

    // Build a list of all directories to check, starting with $base
    $dirs = [$base];
    $cur  = $base;
    foreach ($parts as $seg) {
        $cur .= '/' . $seg;
        $dirs[] = $cur;
    }

    foreach ($dirs as $dir) {
        // Collect prepare hooks
        $prepFile = $dir . '/prepare.php';
        if (file_exists($prepFile)) {
            $ret['prepare'][] = summon($prepFile);
        }

        // Collect conclude hooks
        $concFile = $dir . '/conclude.php';
        if (file_exists($concFile)) {
            $ret['conclude'][] = summon($concFile);
        }
    }

    return $ret;
}

function response(int $http_code, string $body, array $http_headers = []): array
{
    return [
        'status'  => $http_code,
        'body'    => $body,
        'headers' => $http_headers,
    ];
}
/**
 * Send response with security headers
 */
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

set_error_handler(function (int $errno, string $errstr): bool {
    if ($errno !== E_USER_ERROR)
        return false; // let PHP (or another handler) deal with notices, warnings, etc.

    // expecting "$status $body…"
    preg_match('/^([1-5]\d{2})\s+(.*)$/s', $errstr, $m)
        ? respond(['status' => (int)$m[1], 'body' => $m[2]])
        : respond(['status' => 500, 'body' => $errno . ': ' . $errstr]);

    exit;
});

set_exception_handler(function ($e) {
    trigger_error(sprintf('500 Uncaught Exception: %s in %s:%d', $e->getMessage(), $e->getFile(), $e->getLine()), E_USER_ERROR);
});
