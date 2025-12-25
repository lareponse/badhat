# BADHAT Project Examples

Real projects. Real constraints. Real solutions.

---

## Content Management (Blog Engine)

**Route:** Fetch posts, handle CRUD  
**Render:** Display lists, forms, admin panels

```php
// app/io/route/posts/edit.php
return function($args) {
    $id = $args[0] ?? throw new InvalidArgumentException('Post ID required', 400);
    
    if ($_POST) {
        csrf_validate() || io_die(403, 'Invalid token');
        qp("UPDATE posts SET title=?, body=? WHERE id=?", 
            [$_POST['title'], $_POST['body'], $id]);
        header('Location: /posts/' . $id);
        exit;
    }
    
    $post = qp("SELECT * FROM posts WHERE id = ?", [$id])->fetch();
    return compact('post') + ['csrf' => csrf_token()];
};
```

**Scale:** 10,000+ posts, multiple authors, comment system, media uploads.

---

## E-commerce Backend (Admin Panel)

**Route:** Order processing, inventory management  
**Render:** Dashboards, reports, bulk operations

```php
// app/io/route/admin/orders.php
return function($args) {
    checkin(AUTH_GUARD, '/login');
    
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

**Scale:** 100,000+ orders, real-time inventory, payment webhooks.

---

## API Gateway (Microservice Router)

**Route:** Service discovery, auth  
**Render:** JSON responses

```php
// app/io/route/api/users.php  
return function($args) {
    $user = auth_http() ?: io_die(401, json_encode(['error' => 'Unauthorized']), 
        ['Content-Type' => 'application/json']);
    
    $method = $_SERVER['REQUEST_METHOD'];
    $data = $method === 'POST' 
        ? json_decode(file_get_contents('php://input'), true) 
        : $_GET;
    
    // Proxy to user service
    $response = http_proxy("http://user-service:8080/{$args[0]}", $method, $data);
    
    io_die(200, $response, ['Content-Type' => 'application/json']);
};
```

**Scale:** 1M+ requests/day, service mesh integration, rate limiting.

---

## Real-time Dashboard (Analytics)

**Route:** Data aggregation  
**Render:** Charts, tables

```php
// app/io/route/dashboard/metrics.php
return function($args) {
    $range = $args[0] ?? '24h';
    $hours = $range === '24h' ? 24 : 168;
    
    $metrics = qp("
        SELECT 
            DATE_FORMAT(timestamp, '%H:00') as hour,
            SUM(requests) as total_requests,
            AVG(response_time) as avg_time
        FROM metrics 
        WHERE timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
        GROUP BY hour
        ORDER BY hour
    ", [$hours])->fetchAll();
    
    return [
        'metrics' => $metrics,
        'range' => $range,
        'last_update' => time()
    ];
};
```

**Scale:** Real-time ingestion, millions of data points.

---

## File Management System

**Route:** Upload handling  
**Render:** File browsers

```php
// app/io/route/files/upload.php
return function($args) {
    checkin(AUTH_GUARD, '/login');
    
    if (!$_FILES) 
        return ['max_size' => ini_get('upload_max_filesize')];
    
    $file = $_FILES['file'];
    $path = 'uploads/' . date('Y/m/') . uniqid() . '_' . $file['name'];
    
    move_uploaded_file($file['tmp_name'], $path);
    
    qp("INSERT INTO files (name, path, size, user_id) VALUES (?, ?, ?, ?)", 
        [$file['name'], $path, $file['size'], checkin()]);
    
    header('Location: /files');
    exit;
};
```

**Scale:** Terabytes of files, CDN integration.

---

## IoT Data Collector

**Route:** Sensor ingestion  
**Render:** Device dashboards

```php
// app/io/route/api/sensors.php
return function($args) {
    $device_id = $args[0] ?? io_die(400, '{"error":"Device ID required"}', 
        ['Content-Type' => 'application/json']);
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = qp("INSERT INTO sensor_readings (device_id, sensor_type, value, timestamp) VALUES (?, ?, ?, ?)", []);
    
    $count = 0;
    foreach ($data['sensors'] as $sensor) {
        $stmt->execute([$device_id, $sensor['type'], $sensor['value'], $sensor['timestamp']]);
        ++$count;
    }
    
    io_die(200, json_encode(['status' => 'ok', 'count' => $count]), 
        ['Content-Type' => 'application/json']);
};
```

**Scale:** 10,000+ devices, millions of readings/day.

---

## Multi-tenant SaaS

**Route:** Tenant isolation  
**Render:** Per-tenant customization

```php
// app/io/route/app/projects.php
return function($args) {
    $tenant = resolve_tenant($_SERVER['HTTP_HOST']);
    db('tenant_' . $tenant['id']);  // Switch connection
    
    $projects = qp("
        SELECT p.*, COUNT(t.id) as task_count
        FROM projects p
        LEFT JOIN tasks t ON p.id = t.project_id
        GROUP BY p.id
    ")->fetchAll();
    
    return compact('projects', 'tenant');
};
```

**Scale:** 1000+ tenants, isolated data, custom domains.

---

## Financial Trading System

**Route:** Order execution  
**Render:** Trading interfaces

```php
// app/io/route/trading/execute.php
return function($args) {
    checkin(AUTH_GUARD, '/login');
    $user = checkin();
    
    $order = [
        'user_id' => $user,
        'symbol' => $_POST['symbol'],
        'quantity' => (int)$_POST['quantity'],
        'price' => (float)$_POST['price'],
        'type' => $_POST['type']
    ];
    
    return dbt(function($pdo) use ($order) {
        qp("INSERT INTO orders (user_id, symbol, quantity, price, type) VALUES (?, ?, ?, ?, ?)",
            [$order['user_id'], $order['symbol'], $order['quantity'], $order['price'], $order['type']]);
        
        return ['order_id' => $pdo->lastInsertId(), 'status' => 'pending'];
    });
};
```

**Scale:** High-frequency trading, microsecond latency.

---

## Project Scope Guidelines

**Sweet Spot (BADHAT excels):**
- 1-10 developers
- Performance-critical
- Direct database access preferred
- Custom business logic heavy

**Manageable (BADHAT works):**
- Up to 50,000 LOC
- Complex domains, simple tech
- High-traffic read-heavy
- Integration-heavy

**Consider alternatives:**
- 100+ developers
- Heavy third-party APIs
- Complex authorization
- Enterprise compliance

**Wrong tool:**
- Marketing sites → static generators
- Simple CRUD → Rails/Laravel
- Distributed systems → proper frameworks
- Teams unfamiliar with SQL

---

The constraint drives design.