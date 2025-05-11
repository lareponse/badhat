<?php

declare(strict_types=1);
define('SITE_ROOT', __DIR__ . '/../');

/**
 * Phase 1: Find which file to run and what args to pass.
 * Returns either:
 *   - ['handler'=>string, 'args'=>array]
 *   - ['status'=>int, 'body'=>string] for 404 or scaffold
 */
function route(string $route_root): array
{
    $segs = [];

    if (isset($_SERVER['REQUEST_URI'])) {
        $_ = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

        if (preg_match('#(\.\.|[\\/]\.)#', $_)) {
            return ['status' => 403, 'body' => 'directory traversal detected'];
        }

        
        $_ = preg_replace('#/+#', '/', trim($_, '/')); //trim leading/trailing/consecutives slashes
        $segs = explode('/', $_);
    }

    $candidates = [];
    if (empty($segs)) {
        $segs = ['home'];
    } else {
        $cur = '';
        foreach ($segs as $depth => $seg) {

            if ($seg === '..' || $seg === '.') {
                return ['status' => 400, 'body' => 'Bad Request'];
            }
            $cur .= '/' . $seg;
            $candidates[$depth] = $route_root . $cur . '.php';
        }
        // try deepest first
        $candidates = array_reverse($candidates, true);
    }
    // build candidate files keyed by depth

    foreach ($candidates as $depth => $file) {
        if (file_exists($file)) {
            return [
                'handler' => $file,
                'args'    => array_slice($segs, $depth + 1),
            ];
        }
    }

    // missing route → scaffold or 404
    // if (getenv('DEV_MODE')) {
    scaffold($segs);
    // }
    return ['status' => 404, 'body' => 'Not Found'];
}

/**
 * Phase 2: Execute prepares, handler, then concludes.
 * Consumes the array returned by route().
 */
function handle(array $info): array
{
    // early response
    if (isset($info['status'])) {
        return $info;
    }

    $base    = SITE_ROOT . '/app/routes';
    $handler = $info['handler'];
    $args    = $info['args'];

    // derive segments/depth route_root handler path
    $rel     = substr($handler, strlen($base) + 1);         // "foo/bar/baz.php"
    $parts   = explode('/', dirname($rel));                 // ['foo','bar']
    $curDir  = $base;
    $concludes = [];

    // run prepare.php (and collect conclude.php) at each level
    foreach ($parts as $seg) {
        $curDir .= '/' . $seg;

        // prepare hook
        $fn = @include $curDir . '/prepare.php';
        if (is_callable($fn) && ($res = $fn())) {
            return $res;
        }

        // conclude hook → collect
        $fn2 = @include $curDir . '/conclude.php';
        if (is_callable($fn2)) {
            $concludes[] = $fn2;
        }
    }

    // invoke route handler
    $fn = require $handler;
    if (!is_callable($fn)) {
        return ['status' => 500, 'body' => 'Invalid route handler'];
    }
    $res = $fn(...$args);

    // run conclude hooks in reverse
    foreach (array_reverse($concludes) as $fin) {
        $res = $fin($res);
    }

    return $res;
}

/**
 * Send the final response.
 */
function respond(array $res): void
{
    http_response_code($res['status'] ?? 200);
    foreach ($res['headers'] ?? [] as $h => $v) {
        header("$h: $v");
    }
    echo $res['body'] ?? '';
}


function scaffold(array $parts): void
{
    $path = implode('/', $parts);
    $sig  = implode(', ', array_map(fn($n) => "\$arg$n", range(1, count($parts) - 1)));

    echo nl2br("Missing route: /$path\n\n");
    echo nl2br("Create file:\n  /routes/$path.php\n\n");
    echo nl2br("return function ($sig) {\n    return 'not implemented';\n};\n");
    exit;
}
