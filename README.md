# BADDAD

**Bits Are Data, Dont Add Decoration**

~400 lines of PHP that refuses frameworks, embraces speed.

```php
// Traditional framework approach
composer require framework/everything  // 50MB, 800 files, 2-5 seconds boot
$app = new Framework\Application();
$app->register(new ServiceProvider());

// BADDAD approach  
require 'add/bad/io.php';              // 4 files, <1MB, 1-2ms boot
$quest = quest(__DIR__ . '/app/route');
http(deliver($quest));
```

**Performance**: 10x faster, 20x less memory, 200x fewer files than modern frameworks.

---

## Quick Start (5 minutes)

```bash
git clone https://github.com/lareponse/BADDAD.git add
mkdir -p app/{route,views,data}
```

**Entry point** (`index.php`):
```php
<?php
require 'add/bad/io.php';
require 'add/bad/dad/db.php';

db(new PDO('sqlite:app.db'));
$quest = quest(__DIR__ . '/app/route');
http(deliver($quest));
```

**First route** (`app/route/home.php`):
```php
<?php
return function() {
    $users = dbq(db(), "SELECT name FROM users LIMIT 3")->fetchAll();
    
    tray('main', '<h1>Users</h1>');
    foreach ($users as $user) {
        tray('main', '<p>' . htmlspecialchars($user['name']) . '</p>');
    }
    
    return ['status' => 200, 'body' => render_layout()];
};
```

**Layout** (`app/views/layout.php`):
```php
<!DOCTYPE html>
<html><head><title>BADDAD</title></head>
<body>
    <?= implode("\n", tray('main')) ?>
</body></html>
```

Browse to `http://localhost/` and you're running.

---

## Core Concepts

### File-Based Routing
```
URL                 File                    Arguments
/users/edit/42  →  route/users/edit.php  →  ['42']
/admin/dashboard →  route/admin/dashboard.php
```

### Bitwise State Management
```php
const QST_PULL = 1;   // 001
const QST_PUSH = 2;   // 010  
const QST_CALL = 4;   // 100

$flags = QST_PULL | QST_PUSH;  // 011
if ($flags & QST_CALL) { /* 3x faster than array checks */ }
```

### Procedural Everything
```php
// Not this
$user = $userRepository->findByEmail($email);
$user->updateLastLogin();
$entityManager->persist($user);

// This
$user = dbq(db(), "SELECT * FROM users WHERE email = ?", [$email])->fetch();
dbq(db(), "UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
```

### SQL as First-Class
```php
// Direct PDO for complex queries
$stats = dbq(db(), "
    SELECT DATE(created_at) as date, COUNT(*) as orders
    FROM orders 
    WHERE created_at >= ? 
    GROUP BY DATE(created_at)
", [$start_date])->fetchAll();

// Query builders for repetitive operations
[$sql, $binds] = qb_create('users', ['name' => 'John', 'email' => 'john@example.com']);
dbq(db(), $sql, $binds);
```

---

## Essential Functions

```php
// Database
db(new PDO($dsn, $user, $pass));           // Connect once
$user = dbq(db(), $sql, $binds)->fetch();        // Query with bindings
[$sql, $binds] = qb_update('users', $data, ['id' => 42]);  // Query builder

// Routing  
$quest = quest('/path/to/routes');            // Route resolution
return ['status' => 200, 'body' => $html]; // Route response

// Templates
tray('head', '<link rel="stylesheet" href="/app.css">');   // Accumulate content
tray('main', '<h1>Page Title</h1>');
$content = implode("\n", tray('main'));    // Consume and clear

// HTML
html('div', 'Content', 'class' => 'box');  // <div class="box">Content</div>
csrf_field();                              // CSRF protection
```

---

## When to Use BADDAD

**Perfect for:**
- High-performance APIs
- Admin dashboards  
- Content management systems
- Real-time applications
- Teams that value control over convenience

**Consider frameworks for:**
- Large teams requiring strict conventions
- Applications with complex business rules requiring heavy abstraction
- Projects where developer onboarding speed matters more than runtime performance

---

## File Structure

```
myapp/
├── add/                    # BADDAD core (~400 lines)
├── app/
│   ├── route/             # URL handlers
│   │   ├── api/
│   │   ├── admin/
│   │   └── home.php
│   ├── views/             # Templates  
│   └── data/              # Config, credentials
└── index.php              # Entry point
```

---

## Documentation

- **[Philosophy](./add/doc/readme-addbad.md)** - Where ADDBAD came from
- **[Manifest](./add/doc/readme-baddad.md)** - What BADDAD stands for
- **[Comparison](./add/doc/readme-comparison.md)** - Code examples vs modern frameworks
- **[Guide](./add/doc/readme-guide.md)** - Complete API reference, advanced patterns  

---

## License

MIT