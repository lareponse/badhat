<?php

define('SITE_ROOT', dirname(__DIR__) . '/');

$_SLOTS = [];

// --- Request parsing
function parse_request() {
    return [
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
        'path'   => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
        'query'  => $_GET,
        'body'   => $_POST,
    ];
}

// --- Routing + dispatch
function dispatch($req) {
    $parts = array_values(array_filter(explode('/', trim($req['path'], '/'))));
    $ctr   = $parts[0] ?? 'home';
    $act   = $parts[1] ?? $ctr;
    $args  = array_slice($parts, 2);

    $file = SITE_ROOT . "app/controller/public/{$ctr}.php";
    if (!file_exists($file)) return ['status' => 404, 'headers' => [], 'body' => '404 Not Found'];
    include_once $file;

    if (!function_exists($act)) return ['status' => 404, 'headers' => [], 'body' => '404 Not Found'];

    $ref = new ReflectionFunction($act);
    $params = [];
    foreach ($ref->getParameters() as $i => $p) {
        if ($i === 0 && $p->getName() === 'req') $params[] = $req;
        elseif (isset($args[0])) $params[] = array_shift($args);
    }

    $out = call_user_func_array($act, $params);
    if (is_array($out) && isset($out['body'])) {
        return ['status' => $out['status'] ?? 200, 'headers' => $out['headers'] ?? [], 'body' => $out['body']];
    }

    return ['status' => 200, 'headers' => [], 'body' => $out];
}

// --- Response
function send_response($res) {
    http_response_code($res['status']);
    foreach ($res['headers'] as $h => $v) header("$h: $v");
    echo $res['body'];
}

// --- View rendering
function render($view, $data = [], $layout = 'layout') {
    extract($data);
    ob_start(); include SITE_ROOT . "app/view/{$view}.php"; $content = ob_get_clean();
    ob_start(); include SITE_ROOT . "app/view/{$layout}.php"; return ob_get_clean();
}

function partial($name, $data = []) {
    extract($data);
    include SITE_ROOT . "app/view/_{$name}.php";
}

// --- Slot system
function slot($name, $content = null) {
    global $_SLOTS;
    if ($content !== null) $_SLOTS[$name][] = $content;
    return $content === null && isset($_SLOTS[$name]) ? end($_SLOTS[$name]) : null;
}

function slots($name, $sep = "\n") {
    global $_SLOTS;
    return isset($_SLOTS[$name]) ? implode($sep, $_SLOTS[$name]) : '';
}
