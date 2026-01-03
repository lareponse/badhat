# RUN_ABSORB: Zero-Abstraction Templating

## Mechanism

`RUN_ABSORB` captures output and passes it to a returned callable:

```php
// Inside run() when RUN_ABSORB is set:
ob_start();
$return = include($file);
$buffer = ob_get_contents();

if (is_callable($return)) {
    $args[] = $buffer;  // buffer appended to args
    $result = $return($args);
}
$loot[RUN_OUTPUT] = ob_get_clean();
```

`RUN_ABSORB = RUN_BUFFER | RUN_INVOKE` — buffer is last element in args.

---

## Pattern 1: Layout Wrapping

```php
<?php // app/io/render/users.php
$users = $args['users'] ?? [];
?>
<h1>Users</h1>
<ul>
<?php foreach ($users as $user): ?>
    <li><?= htmlspecialchars($user['name']) ?></li>
<?php endforeach; ?>
</ul>

<?php return function($args) {
    $content = $args[count($args) - 1];
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

---

## Pattern 2: Template Composition

```php
<?php // app/io/render/dashboard.php ?>
<div class="metrics"><?= $args['count'] ?? 0 ?> active</div>

<?php return function($args) {
    $content = $args[count($args) - 1];
    
    $sidebar = run([__DIR__ . '/sidebar.php'], $args, RUN_BUFFER)[RUN_OUTPUT];
    $header = run([__DIR__ . '/header.php'], $args, RUN_BUFFER)[RUN_OUTPUT];
    
    return $header . '<div class="row">' . $sidebar . '<main>' . $content . '</main></div>';
};
```

---

## Pattern 3: Conditional Wrapping

```php
<?php // app/io/render/api/response.php
echo json_encode($args['data'] ?? [], JSON_PRETTY_PRINT);

return function($args) {
    $json = $args[count($args) - 1];
    
    if (is_dev()) {
        $debug = [
            'data' => json_decode($json),
            'memory' => memory_get_peak_usage(true),
            'time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
        ];
        return json_encode($debug, JSON_PRETTY_PRINT);
    }
    return $json;
};
```

---

## Pattern 4: Nested Layouts

```php
<?php // app/io/render/admin/users.php ?>
<h2>User Management</h2>
<table><!-- table --></table>

<?php return function($args) {
    $content = $args[count($args) - 1];
    
    // Admin layout
    ob_start(); ?>
    <div class="admin-panel">
        <aside><?= render_admin_menu() ?></aside>
        <section><?= $content ?></section>
    </div>
    <?php 
    $admin = ob_get_clean();
    
    // Main layout
    return run([__DIR__ . '/../layout.php'], 
        $args + ['content' => $admin], 
        RUN_ABSORB
    )[RUN_RETURN];
};
```

---

## Pattern 5: Transform Pipeline

```php
<?php // app/io/render/markdown.php
echo $args['markdown'] ?? '';

return function($args) {
    $raw = $args[count($args) - 1];
    
    $html = parse_markdown($raw);
    $html = add_syntax_highlighting($html);
    $html = inject_toc($html);
    
    return run([__DIR__ . '/layout.php'], 
        ['content' => $html], 
        RUN_ABSORB
    )[RUN_RETURN];
};
```

---

## Pattern 6: Fragment Caching

```php
<?php // app/io/render/expensive.php
foreach (fetch_expensive_data() as $item): ?>
    <div><?= process($item) ?></div>
<?php endforeach;

return function($args) {
    $html = $args[count($args) - 1];
    $key = 'render_' . md5($args['cache_key'] ?? '');
    
    if ($cached = cache_get($key))
        return $cached;
    
    $wrapped = '<div class="cached">' . $html . '</div>';
    cache_set($key, $wrapped, 3600);
    
    return $wrapped;
};
```

---

## Pattern 7: Content Negotiation

```php
<?php // app/io/render/data.php
foreach ($args['items'] ?? [] as $item): ?>
    <li><?= htmlspecialchars($item['name']) ?></li>
<?php endforeach;

return function($args) {
    $html = $args[count($args) - 1];
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    
    if (strpos($accept, 'application/json') !== false)
        return json_encode($args['items'] ?? []);
    
    if (strpos($accept, 'text/xml') !== false) {
        $xml = '<items>';
        foreach ($args['items'] ?? [] as $item)
            $xml .= '<item>' . htmlspecialchars($item['name']) . '</item>';
        return $xml . '</items>';
    }
    
    return '<ul>' . $html . '</ul>';
};
```

---

## Why This Beats Template Engines

**Twig/Blade:**
1. Parse template → AST
2. Compile AST → PHP
3. Cache compiled
4. Check freshness
5. Load parent
6. Parse parent
7. Merge blocks
8. Execute

**BADHAT RUN_ABSORB:**
1. Include PHP (opcached)
2. Capture output
3. Call function
4. Done

---

## Performance

```
1000 items with layout:

Twig:   45ms, 2MB
Blade:  38ms, 1.8MB
BADHAT:  3ms, 100KB
```

---

## It's Just PHP

- No new syntax
- No compilation
- Native speed
- Full language
- Opcache works
- IDE understands
- Real line numbers
- Debug with `var_dump()`