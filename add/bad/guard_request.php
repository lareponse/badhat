<?php

// firewall.php — BADGE-style unified request validator

function badge_firewall(array $checks, array $opt = []): array
{
    $exit = $opt['exit'] ?? true;
    $errors = [];
    $i = 0;

    foreach ($checks as $name => $fn) {
        $label = is_string($name) ? $name : 'check_' . $i++;
        $out = $fn();

        if ($out === true) continue;

        [$code, $msg] = is_array($out) ? $out : [400, "Blocked: $label"];
        $errors[] = compact('code', 'msg', 'label');

        if ($exit) badge_block($code, $msg);
    }

    return $errors ? ['ok' => false, 'errors' => $errors] : ['ok' => true];
}

function badge_block(int $code, string $msg): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['error' => $msg]);
    exit;
}

// Optional helpers (composable closures)

function require_token(): callable
{
    return fn() => preg_match('/^Bearer\s+\w+/i', $_SERVER['HTTP_AUTHORIZATION'] ?? '')
        ? true : [401, 'Missing or invalid bearer token'];
}

function require_session_user(): callable
{
    return function () {
        session_start();
        return !empty($_SESSION['user']) ? true : [401, 'No user in session'];
    };
}

function require_origin(array $allowed): callable
{
    return fn() => in_array($_SERVER['HTTP_ORIGIN'] ?? '', $allowed, true)
        ? true : [403, 'Forbidden origin'];
}

function require_content_type(string $type): callable
{
    return fn() => str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', $type)
        ? true : [415, 'Unsupported Content-Type'];
}

function require_csrf(string $key = 'csrf_token'): callable
{
    return function () use ($key) {
        session_start();
        $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $stored = $_SESSION[$key] ?? '';
        return $sent && $stored && hash_equals($stored, $sent)
            ? true : [403, 'Invalid CSRF token'];
    };
}

function limit_payload(int $max): callable
{
    return fn() => ($_SERVER['CONTENT_LENGTH'] ?? 0) <= $max
        ? true : [413, 'Payload too large'];
}

function handle_etag(string $etag): callable
{
    return function () use ($etag) {
        header("ETag: $etag");
        if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
            http_response_code(304);
            exit;
        }
        return true;
    };
}
