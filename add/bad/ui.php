<?php

function ui(array $quest): array
{
    $quest ?: error_response(400, 'Bad Request', 'quest is empty');
    
    if(is_callable($quest[0])) {
        return error_response(500, 'Internal Server Error', 'This is a simulated error');
    }
    $handler = $quest[0] ?? null;
    $file = $quest[1] ?? '';
    $args = array_slice($quest, 2);
    
    if (!$handler) {
        return error_response(404, 'Not Found');
    }
    
    try {
        $response = is_callable($handler) ? $handler(...$args) : $handler;
        
        if (is_array($response) && isset($response['status'])) {
            return content_negotiate($quest, $response);
        }
        
        return content_negotiate($quest, ['status' => 200, 'body' => $response ?? 'OK']);
        
    } catch (Throwable $e) {
        $code = $e->getCode() ?: 500;
        return error_response($code, $e->getMessage());
    }
}

function content_negotiate($quest, array $response): array
{
    $route_file = $quest[1] ?? '';
    $in = io_other(io());
    io(null); // Switch to render context
    
    $base_view = str_replace($in, io(), $route_file);
    $base_view = str_replace('.php', '', $base_view);
    
    // Determine preferred format
    $format = negotiate_format($base_view);
    $view_file = "$base_view.$format.php";
    
    $status = $response['status'] ?? 200;
    $headers = $response['headers'] ?? [];
    $body = $response['body'] ?? '';
    
    if (file_exists($view_file)) {
        $render_quest = [
            'execute' => [
                'payload' => is_array($body) ? $body : ['content' => $body]
            ]
        ];
        
        $content = render($render_quest, $view_file);
    } else {
        // Fallback based on format
        $content = $format === 'json' 
            ? json_encode(is_array($body) ? $body : ['content' => $body])
            : (is_string($body) ? $body : json_encode($body));
    }
    
    return http_response($status, $content, array_merge($headers, [
        'Content-Type' => "$format; charset=UTF-8"
    ]));
}

function negotiate_format(string $base_view): string
{
    $requested = $_GET['format'] ?? null;
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    
    // Scan for available render files
    $files = glob("$base_view.*.php");
    $formats = [];
    
    foreach ($files as $file) {
        if (preg_match('/\.([^.]+)\.php$/', $file, $matches)) {
            $formats[] = $matches[1];
        }
    }
    
    // Check explicit format request
    if ($requested && in_array($requested, $formats)) {
        return $requested;
    }
    
    // Match against Accept header
    foreach ($formats as $format) {
        if (str_contains($accept, $format)) {
            return $format;
        }
    }
    
    return $formats[0] ?? 'text/html';
}

function error_response(int $status, string $message, ?string $error_id = null): array
{
    $error_id = $error_id ?: base_convert(random_int(100000, 999999), 10, 36);
    
    $format = $_GET['format'] ?? 'html';
    
    if ($format === 'json' || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        return http_response($status, json_encode([
            'status' => $status,
            'error' => $message,
            'error_id' => $error_id,
            'meta' => ['timestamp' => time()]
        ]), ['Content-Type' => 'application/json; charset=UTF-8']);
    }
    
    $html = is_dev() 
        ? "<h1>$status Error</h1><p>$message</p><p>ID: $error_id</p>"
        : "<h1>Error $error_id</h1><p>Something went wrong.</p>";
        
    return http_response($status, $html, ['Content-Type' => 'text/html; charset=UTF-8']);
}

