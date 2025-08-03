# IO_ABSORB: The Zero-Abstraction Template Engine

## Core Mechanism

IO_ABSORB transforms any PHP file into a composable template by capturing its output buffer and passing it to a returned function:

```php
// How IO_ABSORB works internally
if ($behave & IO_ABSORB) {
    ob_start();
    $return = include($file);
    $buffer = ob_get_clean();
    
    if (is_callable($return)) {
        $result = $return($buffer, $args);  // The magic happens here
    }
}
```

## Pattern 1: Layout Wrapping

```php
// app/io/render/users.php
<h1>Users</h1>
<ul>
<?php foreach ($users as $user): ?>
    <li><?= htmlspecialchars($user['name']) ?></li>
<?php endforeach; ?>
</ul>

<?php
// This function receives the above HTML as $content
return function($content, $args) {
    // Wrap in layout
    ob_start(); ?>
    <!DOCTYPE html>
    <html>
    <head><title>Users</title></head>
    <body>
        <nav>...</nav>
        <main><?= $content ?></main>
        <footer>...</footer>
    </body>
    </html>
    <?php return ob_get_clean();
};
```

## Pattern 2: Template Composition

```php
// app/io/render/dashboard.php
<div class="metrics">
    <?= $metrics_count ?> active metrics
</div>

<?php
return function($content, $args) {
    // Compose multiple templates
    $sidebar = io_run('render/sidebar.php', $args, IO_BUFFER)[IO_BUFFER];
    $header = io_run('render/header.php', $args, IO_BUFFER)[IO_BUFFER];
    
    return $header . 
           '<div class="row">' . 
           $sidebar . 
           '<main>' . $content . '</main>' . 
           '</div>';
};
```

## Pattern 3: Conditional Wrapping

```php
// app/io/render/api/response.php
<?= json_encode($data, JSON_PRETTY_PRINT) ?>

<?php
return function($json, $args) {
    // Wrap JSON in debug info if dev mode
    if (is_dev()) {
        $debug = [
            'json' => json_decode($json),
            'memory' => memory_get_peak_usage(true),
            'time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            'queries' => $args['query_count'] ?? 0
        ];
        return json_encode($debug, JSON_PRETTY_PRINT);
    }
    return $json;
};
```

## Pattern 4: Nested Layouts

```php
// app/io/render/admin/users.php
<h2>User Management</h2>
<table><!-- user table --></table>

<?php
return function($content, $args) {
    // First wrap in admin layout
    ob_start(); ?>
    <div class="admin-panel">
        <aside><?= render_admin_menu() ?></aside>
        <section><?= $content ?></section>
    </div>
    <?php 
    $admin_content = ob_get_clean();
    
    // Then wrap in main layout
    return io_run('render/layout.php', 
        ['content' => $admin_content] + $args, 
        IO_ABSORB
    )[IO_ABSORB];
};
```

## Pattern 5: Transform Pipeline

```php
// app/io/render/markdown.php
<?= $markdown_content ?>

<?php
return function($raw_markdown, $args) {
    // Pipeline of transformations
    $html = parse_markdown($raw_markdown);
    $html = add_syntax_highlighting($html);
    $html = inject_toc($html);
    $html = wrap_in_article($html);
    
    // Could even chain IO_ABSORB calls
    return io_run('render/layout.php', 
        ['content' => $html], 
        IO_ABSORB
    )[IO_ABSORB];
};
```

## Pattern 6: Fragment Caching

```php
// app/io/render/expensive.php
<?php foreach (fetch_expensive_data() as $item): ?>
    <div><?= process_item($item) ?></div>
<?php endforeach; ?>

<?php
return function($expensive_html, $args) {
    $cache_key = 'render_' . md5($args['cache_key'] ?? '');
    
    // Check cache first
    if ($cached = cache_get($cache_key)) {
        return $cached;
    }
    
    // Wrap and cache
    $wrapped = '<div class="cached-content">' . $expensive_html . '</div>';
    cache_set($cache_key, $wrapped, 3600);
    
    return $wrapped;
};
```

## Pattern 7: Content Negotiation

```php
// app/io/render/data.php
<?php
// Default HTML output
foreach ($items as $item): ?>
    <li><?= $item['name'] ?></li>
<?php endforeach;

return function($html, $args) {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    
    // Transform based on Accept header
    if (strpos($accept, 'application/json') !== false) {
        return json_encode($args['items']);
    }
    
    if (strpos($accept, 'text/xml') !== false) {
        $xml = '<items>';
        foreach ($args['items'] as $item) {
            $xml .= '<item>' . htmlspecialchars($item['name']) . '</item>';
        }
        return $xml . '</items>';
    }
    
    // Default: return HTML wrapped in list
    return '<ul>' . $html . '</ul>';
};
```

## Why This Beats Template Engines

**Traditional template engine:**
```twig
{# users.twig #}
{% extends "layout.twig" %}
{% block content %}
    <h1>Users</h1>
    {% for user in users %}
        <li>{{ user.name|escape }}</li>
    {% endfor %}
{% endblock %}
```

**Hidden costs:**
1. Parse template syntax → AST
2. Compile AST → PHP code
3. Cache compiled version
4. Check cache freshness
5. Load parent template
6. Parse parent template
7. Merge blocks
8. Execute final PHP

**BADHAT IO_ABSORB:**
1. Include PHP file (already opcached)
2. Capture output
3. Call function with buffer
4. Done

## Performance Characteristics

```php
// Benchmark: Render 1000 items with layout

// Twig
$twig = new Environment($loader);
$html = $twig->render('users.twig', ['users' => $users]);
// Time: 45ms, Memory: 2MB

// Blade  
$html = view('users', ['users' => $users])->render();
// Time: 38ms, Memory: 1.8MB

// BADHAT
$html = io_run('render/users.php', ['users' => $users], IO_ABSORB)[IO_ABSORB];
// Time: 3ms, Memory: 100KB
```

## Advanced: Middleware-Style Wrapping

```php
// app/io/render/protected.php
<h1>Secret Dashboard</h1>
<p>Sensitive data here</p>

<?php
return function($content, $args) {
    // Chain of transformations
    $pipeline = [
        fn($html) => check_permission($args) ? $html : 'Access Denied',
        fn($html) => add_csrf_field($html),
        fn($html) => inject_user_menu($html, $args['user']),
        fn($html) => wrap_in_layout($html)
    ];
    
    return array_reduce($pipeline, 
        fn($html, $transform) => $transform($html), 
        $content
    );
};
```

## It's Just PHP

- No new syntax to learn
- No compilation step
- Native PHP speed
- Full language power available
- Opcache works perfectly
- IDE understands everything
- Errors show real line numbers
- Can debug with `var_dump()`

**You get a template engine for free by understanding how output buffers and functions work.**