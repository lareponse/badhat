# BADHAT Project Examples

Real projects. Real constraints. Real solutions.

---

## Content Management (Blog Engine)

```php
// app/io/route/posts/edit.php
return function($args) {
    checkin() ?? http_out(302, null, ['Location' => ['/login']]);
    
    $id = $args[0] ?? throw new \InvalidArgumentException('Post ID required', 400);
    
    if ($_POST) {
        csrf(CSRF_CHECK) || http_out(403, 'Invalid token');
        qp("UPDATE posts SET title=?, body=? WHERE id=?", 
            [$_POST['title'], $_POST['body'], $id]);
        http_out(302, null, ['Location' => ["/posts/$id"]]);
    }
    
    return ['post' => qp("SELECT * FROM posts WHERE id = ?", [$id])->fetch()];
};
```

**Scale:** 10,000+ posts, multiple authors.

---

## E-commerce Backend

```php
// app/io/route/admin/orders.php
return function($args) {
    checkin() ?? http_out(302, null, ['Location' => ['/login']]);
    
    $status = $_GET['status'] ?? 'pending';
    $orders = qp("
        SELECT o.*, u.email, COUNT(oi.id) as items
        FROM orders o 
        JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.status = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ", [$status])->fetchAll();
    
    return compact('orders', 'status');
};
```

**Scale:** 100,000+ orders.

---

## JSON API

```php
// app/io/route/api/users.php  
return function($args) {
    $action = $args[0] ?? 'list';
    $id = $args[1] ?? null;
    
    $data = match($action) {
        'list' => qp("SELECT id, name FROM users")->fetchAll(),
        'get'  => qp("SELECT * FROM users WHERE id = ?", [$id])->fetch(),
        default => throw new \InvalidArgumentException('Unknown action', 400)
    };
    
    http_out(200, json_encode($data), ['Content-Type' => ['application/json']]);
};
```

**Scale:** 1M+ requests/day.

---

## Dashboard Metrics

```php
// app/io/route/dashboard/metrics.php
return function($args) {
    $range = $args[0] ?? '24h';
    $hours = $range === '24h' ? 24 : 168;
    
    return [
        'metrics' => qp("
            SELECT 
                DATE_FORMAT(timestamp, '%H:00') as hour,
                SUM(requests) as total,
                AVG(response_time) as avg_time
            FROM metrics 
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
            GROUP BY hour
        ", [$hours])->fetchAll(),
        'range' => $range
    ];
};
```

---

## File Upload

```php
// app/io/route/files/upload.php
return function($args) {
    checkin() ?? http_out(302, null, ['Location' => ['/login']]);
    
    if (!$_FILES) 
        return ['max_size' => ini_get('upload_max_filesize')];
    
    csrf(CSRF_CHECK) || http_out(403, 'Invalid token');
    
    $file = $_FILES['file'];
    $path = 'uploads/' . date('Y/m/') . uniqid() . '_' . basename($file['name']);
    
    is_dir(dirname($path)) || mkdir(dirname($path), 0755, true);
    move_uploaded_file($file['tmp_name'], $path);
    
    qp("INSERT INTO files (name, path, size, user_id) VALUES (?, ?, ?, ?)", 
        [$file['name'], $path, $file['size'], checkin()]);
    
    http_out(302, null, ['Location' => ['/files']]);
};
```

---

## IoT Data Collector

```php
// app/io/route/api/sensors.php
return function($args) {
    $device_id = $args[0] ?? http_out(400, '{"error":"Device ID required"}', 
        ['Content-Type' => ['application/json']]);
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = qp("INSERT INTO readings (device_id, type, value, ts) VALUES (?, ?, ?, ?)", []);
    
    $count = 0;
    foreach ($data['sensors'] as $s) {
        $stmt->execute([$device_id, $s['type'], $s['value'], $s['ts']]);
        ++$count;
    }
    
    http_out(200, json_encode(['ok' => true, 'count' => $count]), 
        ['Content-Type' => ['application/json']]);
};
```

**Scale:** 10,000+ devices, millions of readings/day.

---

## Multi-tenant SaaS

```php
// app/io/route/app/projects.php
return function($args) {
    $tenant = resolve_tenant($_SERVER['HTTP_HOST']);
    db('tenant_' . $tenant['id']);
    
    return [
        'projects' => qp("
            SELECT p.*, COUNT(t.id) as tasks
            FROM projects p
            LEFT JOIN tasks t ON p.id = t.project_id
            GROUP BY p.id
        ")->fetchAll(),
        'tenant' => $tenant
    ];
};
```

**Scale:** 1000+ tenants.

---

## Transaction Example

```php
// app/io/route/trading/execute.php
return function($args) {
    checkin() ?? http_out(302, null, ['Location' => ['/login']]);
    csrf(CSRF_CHECK) || http_out(403, 'Invalid token');
    
    $order = [
        'user' => checkin(),
        'symbol' => $_POST['symbol'],
        'qty' => (int)$_POST['quantity'],
        'price' => (float)$_POST['price'],
        'type' => $_POST['type']
    ];
    
    return trans(function($pdo) use ($order) {
        qp("INSERT INTO orders (user_id, symbol, qty, price, type) VALUES (?, ?, ?, ?, ?)",
            [$order['user'], $order['symbol'], $order['qty'], $order['price'], $order['type']]);
        
        return ['order_id' => $pdo->lastInsertId(), 'status' => 'pending'];
    });
};
```

---

## Scope Guidelines

**Sweet Spot:**
- 1-10 developers
- Performance-critical
- Direct SQL preferred
- Custom logic heavy

**Manageable:**
- Up to 50,000 LOC
- High-traffic read-heavy
- Integration-heavy

**Consider alternatives:**
- 100+ developers
- Complex authorization
- Enterprise compliance

**Wrong tool:**
- Marketing sites → static generators
- Simple CRUD → Rails/Laravel
- Distributed systems → proper frameworks