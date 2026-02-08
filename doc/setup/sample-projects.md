# BADHAT Project Examples

Real projects. Real constraints. Real solutions.

---

## Content Management (Blog Engine)

```php
<?php
// app/io/route/posts/edit.php

use function bad\auth\checkin;
use function bad\csrf\csrf;
use function bad\pdo\qp;
use function bad\http\{headers, out};
use const bad\csrf\CHECK;
use const bad\http\ONE;

return function($args) {
    if (!checkin()) {
        headers(ONE, 'Location', '/login');
        exit(out(302));
    }
    
    $id = $args[0] ?? throw new \InvalidArgumentException('Post ID required');
    
    if ($_POST) {
        csrf('_csrf', null, CHECK) || exit(out(403, 'Invalid token'));
        qp("UPDATE posts SET title=?, body=? WHERE id=?", 
            [$_POST['title'], $_POST['body'], $id]);
        headers(ONE, 'Location', "/posts/$id");
        exit(out(302));
    }
    
    return ['post' => qp("SELECT * FROM posts WHERE id = ?", [$id])->fetch()];
};
```

**Scale:** 10,000+ posts, multiple authors.

---

## E-commerce Backend

```php
<?php
// app/io/route/admin/orders.php

use function bad\auth\checkin;
use function bad\pdo\qp;
use function bad\http\{headers, out};
use const bad\http\ONE;

return function($args) {
    if (!checkin()) {
        headers(ONE, 'Location', '/login');
        exit(out(302));
    }
    
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
<?php
// app/io/route/api/users.php

use function bad\pdo\qp;
use function bad\http\{headers, out};
use const bad\http\ONE;

return function($args) {
    $action = $args[0] ?? 'list';
    $id = $args[1] ?? null;
    
    $data = match($action) {
        'list' => qp("SELECT id, name FROM users")->fetchAll(),
        'get'  => qp("SELECT * FROM users WHERE id = ?", [$id])->fetch(),
        default => throw new \InvalidArgumentException('Unknown action')
    };
    
    headers(ONE, 'Content-Type', 'application/json');
    exit(out(200, json_encode($data)));
};
```

**Scale:** 1M+ requests/day.

---

## Dashboard Metrics

```php
<?php
// app/io/route/dashboard/metrics.php

use function bad\pdo\qp;

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
<?php
// app/io/route/files/upload.php

use function bad\auth\checkin;
use function bad\csrf\csrf;
use function bad\pdo\qp;
use function bad\http\{headers, out};
use const bad\csrf\CHECK;
use const bad\http\ONE;

return function($args) {
    if (!checkin()) {
        headers(ONE, 'Location', '/login');
        exit(out(302));
    }
    
    if (!$_FILES) 
        return ['max_size' => ini_get('upload_max_filesize')];
    
    csrf('_csrf', null, CHECK) || exit(out(403, 'Invalid token'));
    
    $file = $_FILES['file'];
    $path = 'uploads/' . date('Y/m/') . uniqid() . '_' . basename($file['name']);
    
    is_dir(dirname($path)) || mkdir(dirname($path), 0755, true);
    move_uploaded_file($file['tmp_name'], $path);
    
    qp("INSERT INTO files (name, path, size, user_id) VALUES (?, ?, ?, ?)", 
        [$file['name'], $path, $file['size'], checkin()]);
    
    headers(ONE, 'Location', '/files');
    exit(out(302));
};
```

---

## IoT Data Collector

```php
<?php
// app/io/route/api/sensors.php

use function bad\pdo\qp;
use function bad\http\{headers, out};
use const bad\http\ONE;

return function($args) {
    headers(ONE, 'Content-Type', 'application/json');
    
    $device_id = $args[0] ?? exit(out(400, '{"error":"Device ID required"}'));
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = qp("INSERT INTO readings (device_id, type, value, ts) VALUES (?, ?, ?, ?)", []);
    
    $count = 0;
    foreach ($data['sensors'] as $s) {
        $stmt->execute([$device_id, $s['type'], $s['value'], $s['ts']]);
        ++$count;
    }
    
    exit(out(200, json_encode(['ok' => true, 'count' => $count])));
};
```

**Scale:** 10,000+ devices, millions of readings/day.

---

## Multi-tenant SaaS

```php
<?php
// app/io/route/app/projects.php

use function bad\pdo\{db, qp};

return function($args) {
    $tenant = resolve_tenant($_SERVER['HTTP_HOST']);
    
    // Switch to tenant database
    db(new \PDO(
        "mysql:host=localhost;dbname=tenant_{$tenant['id']};charset=utf8mb4",
        getenv('DB_USER'),
        getenv('DB_PASS'),
        [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
    ));
    
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
<?php
// app/io/route/trading/execute.php

use function bad\auth\checkin;
use function bad\csrf\csrf;
use function bad\pdo\{qp, trans};
use function bad\http\{headers, out};
use const bad\csrf\CHECK;
use const bad\http\ONE;

return function($args) {
    if (!checkin()) {
        headers(ONE, 'Location', '/login');
        exit(out(302));
    }
    
    csrf('_csrf', null, CHECK) || exit(out(403, 'Invalid token'));
    
    $order = [
        'user'   => checkin(),
        'symbol' => $_POST['symbol'],
        'qty'    => (int)$_POST['quantity'],
        'price'  => (float)$_POST['price'],
        'type'   => $_POST['type']
    ];
    
    return trans(function($pdo) use ($order) {
        qp("INSERT INTO orders (user_id, symbol, qty, price, type) VALUES (?, ?, ?, ?, ?)",
            [$order['user'], $order['symbol'], $order['qty'], $order['price'], $order['type']],
            [],
            $pdo);
        
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