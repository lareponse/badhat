# BADHAT Project Examples

Real projects. Real constraints. Real solutions.

---

## Content Management (Blog Engine)

**Route**: Fetch posts, handle CRUD
**Render**: Display lists, forms, admin panels

```php
// app/io/route/posts/edit.php
return function($args) {
    $id = $args[0] ?? throw new InvalidArgumentException('Post ID required');
    
    if ($_POST) {
        csrf_validate() || http_out(403, 'Invalid token');
        [$sql, $binds] = qb_update('posts', $_POST, ['id' => $id]);
        dbq(db(), $sql, $binds);
        header('Location: /posts/' . $id);
        exit;
    }
    
    $post = dbq(db(), "SELECT * FROM posts WHERE id = ?", [$id])->fetch();
    return compact('post') + ['csrf_token' => csrf_token()];
};
```

**Scale**: 10,000+ posts, multiple authors, comment system, media uploads.

---

## E-commerce Backend (Admin Panel)

**Route**: Order processing, inventory management  
**Render**: Dashboards, reports, bulk operations

```php
// app/io/route/admin/orders.php
return function($args) {
    auth() ?: http_out(401, 'Admin required');
    
    $status = $_GET['status'] ?? 'pending';
    $orders = dbq(db(), "
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

**Scale**: 100,000+ orders, real-time inventory, payment processing webhooks.

---

## API Gateway (Microservice Router)

**Route**: Service discovery, load balancing, auth
**Render**: JSON responses, error formatting

```php
// app/io/route/api/users.php  
return function($args) {
    $user = auth_http() ?: http_out(401, 'Token required');
    
    $method = $_SERVER['REQUEST_METHOD'];
    $data = $method === 'POST' ? json_decode(file_get_contents('php://input'), true) : $_GET;
    
    // Proxy to user service
    $response = http_proxy("http://user-service:8080/{$args[0]}", $method, $data);
    
    header('Content-Type: application/json');
    echo $response;
    exit;
};
```

**Scale**: 1M+ requests/day, service mesh integration, rate limiting.

---

## Real-time Dashboard (Analytics)

**Route**: Data aggregation, WebSocket handling
**Render**: Charts, tables, live updates

```php
// app/io/route/dashboard/metrics.php
return function($args) {
    $range = $args[0] ?? '24h';
    
    $metrics = dbq(db(), "
        SELECT 
            DATE_FORMAT(timestamp, '%H:00') as hour,
            SUM(requests) as total_requests,
            AVG(response_time) as avg_time
        FROM metrics 
        WHERE timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
        GROUP BY hour
        ORDER BY hour
    ", [$range === '24h' ? 24 : 168])->fetchAll();
    
    return [
        'metrics' => $metrics,
        'range' => $range,
        'last_update' => time()
    ];
};
```

**Scale**: Real-time data ingestion, millions of data points, WebSocket updates.

---

## File Management System

**Route**: Upload handling, directory operations
**Render**: File browsers, upload forms, thumbnails

```php
// app/io/route/files/upload.php
return function($args) {
    if (!$_FILES) return ['max_size' => ini_get('upload_max_filesize')];
    
    $file = $_FILES['file'];
    $path = 'uploads/' . date('Y/m/') . uniqid() . '_' . $file['name'];
    
    move_uploaded_file($file['tmp_name'], $path);
    
    dbq(db(), "INSERT INTO files (name, path, size, user_id) VALUES (?, ?, ?, ?)", [
        $file['name'], $path, $file['size'], session_id()
    ]);
    
    header('Location: /files');
    exit;
};
```

**Scale**: Terabytes of files, CDN integration, virus scanning, metadata extraction.

---

## IoT Data Collector

**Route**: Sensor data ingestion, device management
**Render**: Device status, alert dashboards

```php
// app/io/route/api/sensors.php
return function($args) {
    $device_id = $args[0] ?? http_out(400, 'Device ID required');
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Bulk insert sensor readings
    $readings = [];
    foreach ($data['sensors'] as $sensor) {
        $readings[] = [
            'device_id' => $device_id,
            'sensor_type' => $sensor['type'], 
            'value' => $sensor['value'],
            'timestamp' => $sensor['timestamp']
        ];
    }
    
    [$sql, $binds] = qb_create('sensor_readings', null, ...$readings);
    dbq(db(), $sql, $binds);
    
    echo json_encode(['status' => 'ok', 'count' => count($readings)]);
    exit;
};
```

**Scale**: 10,000+ devices, millions of readings/day, time-series optimization.

---

## Multi-tenant SaaS

**Route**: Tenant isolation, subscription handling
**Render**: Per-tenant customization, billing interfaces

```php
// app/io/route/app/projects.php
return function($args) {
    $tenant = resolve_tenant($_SERVER['HTTP_HOST']);
    db(tenant_db($tenant['id'])); // Switch database connection
    
    $projects = dbq(db(), "
        SELECT p.*, COUNT(t.id) as task_count
        FROM projects p
        LEFT JOIN tasks t ON p.id = t.project_id
        WHERE p.tenant_id = ?
        GROUP BY p.id
    ", [$tenant['id']])->fetchAll();
    
    return compact('projects', 'tenant');
};
```

**Scale**: 1000+ tenants, isolated data, custom domains, usage metering.

---

## Financial Trading System

**Route**: Order execution, market data processing
**Render**: Trading interfaces, P&L reports

```php
// app/io/route/trading/execute.php
return function($args) {
    $user = auth() ?: http_out(401, 'Login required');
    
    $order = [
        'user_id' => $user,
        'symbol' => $_POST['symbol'],
        'quantity' => (int)$_POST['quantity'],
        'price' => (float)$_POST['price'],
        'type' => $_POST['type']
    ];
    
    // Atomic transaction for order placement
    return db_transaction(db(), function() use ($order) {
        // Validate balance, create order, update positions
        [$sql, $binds] = qb_create('orders', null, $order);
        dbq(db(), $sql, $binds);
        
        return ['order_id' => db()->lastInsertId(), 'status' => 'pending'];
    });
};
```

**Scale**: High-frequency trading, microsecond latency, regulatory compliance.

---

## Project Scope Guidelines

**Sweet Spot (BADHAT excels)**:
- 1-10 developers
- Performance-critical applications
- Direct database access preferred
- Custom business logic heavy
- Rapid iteration required

**Manageable (BADHAT works)**:
- Up to 50,000 LOC application code
- Complex domains with simple tech requirements
- High-traffic read-heavy workloads
- Integration-heavy applications

**Possible but consider alternatives**:
- 100+ developers (convention becomes critical)
- Heavy third-party API integrations
- Complex authorization schemes
- Enterprise compliance requirements

**Wrong tool**:
- Marketing websites (use static generators)
- Simple CRUD apps (use Rails/Laravel)  
- Distributed systems (use proper frameworks)
- Teams unfamiliar with direct SQL

---

The constraint drives design. BADHAT works when constraints align with capabilities.