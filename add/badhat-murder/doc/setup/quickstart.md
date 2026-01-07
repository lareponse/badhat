# BADHAT Project Setup

Complete tutorial for starting a BADHAT project.

## 1. Initialize Project

```bash
mkdir myproject && cd myproject
git init
mkdir -p app/io/{route,render} public
touch .gitignore
```

## 2. Add BADHAT Core via Subtree

```bash
git subtree add --prefix=add/badhat git@github.com:lareponse/BADHAT.git main --squash
```

## 3. Create Entry Point

```php
<?php
// public/index.php
set_include_path(__DIR__ . '/..' . PATH_SEPARATOR . get_include_path());

require 'add/badhat/error.php';
require 'add/badhat/io.php';
require 'add/badhat/run.php';
require 'add/badhat/http.php';
require 'add/badhat/db.php';

register(HND_ALL | MESSAGE_LOG);

$path = io_in($_SERVER['REQUEST_URI'], "\0", IO_PATH_ONLY | IO_ROOTLESS);

// Route
$route = io_map(__DIR__ . '/../app/io/route/', $path, '.php', IO_TAIL);
$loot = $route ? run($route, [], RUN_INVOKE) : [];

// Render
$render = io_map(__DIR__ . '/../app/io/render/', $path, '.php', IO_TAIL | IO_NEST);
$loot = $render ? run($render, $loot, RUN_ABSORB) : $loot;

// Output
isset($loot[RUN_RETURN]) && is_string($loot[RUN_RETURN])
    ? http_out(200, $loot[RUN_RETURN], ['Content-Type' => ['text/html; charset=utf-8']])
    : http_out(404, 'Not Found');
```

## 4. Environment Config

```bash
# .env (not tracked)
cat > .env << 'EOF'
DB_DSN_="sqlite:db.sqlite"
DB_USER_=""
DB_PASS_=""
EOF

# .gitignore
cat > .gitignore << 'EOF'
.env
*.sqlite
EOF
```

## 5. Sample Route

```php
<?php
// app/io/route/users.php
return function($args) {
    if ($_POST) {
        qp("INSERT INTO users (name) VALUES (?)", [$_POST['name']]);
        http_out(302, null, ['Location' => ['/users']]);
    }
    
    return ['users' => qp("SELECT * FROM users")->fetchAll()];
};
```

## 6. Sample Render

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

<form method="post">
    <input name="name" placeholder="Name" required>
    <button>Add</button>
</form>

<?php return function($args) {
    $content = $args[count($args) - 1];
    ob_start(); ?>
<!DOCTYPE html>
<html>
<head><title>Users</title></head>
<body><?= $content ?></body>
</html>
<?php return ob_get_clean();
};
```

## 7. Database Init

```php
<?php
// scripts/init-db.php
require __DIR__ . '/../add/badhat/db.php';

$_SERVER['DB_DSN_'] = 'sqlite:' . __DIR__ . '/../db.sqlite';

db()->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

echo "Database initialized.\n";
```

## 8. First Commit

```bash
git add .
git commit -m "Initial BADHAT project"
```

Structure:
```
myproject/
├── .gitignore
├── add/badhat/
│   ├── auth.php
│   ├── csrf.php
│   ├── db.php
│   ├── error.php
│   ├── http.php
│   ├── io.php
│   └── run.php
├── app/io/
│   ├── route/users.php
│   └── render/users.php
├── public/index.php
└── scripts/init-db.php
```

## 9. Test

```bash
php scripts/init-db.php
cd public && php -S localhost:8000
# → http://localhost:8000/users
```

## 10. Update BADHAT

```bash
git subtree pull --prefix=add/badhat git@github.com:lareponse/BADHAT.git main --squash
```

---

## Adding Auth + CSRF

### Update Entry Point

```php
<?php
// public/index.php
set_include_path(__DIR__ . '/..' . PATH_SEPARATOR . get_include_path());

require 'add/badhat/error.php';
require 'add/badhat/io.php';
require 'add/badhat/run.php';
require 'add/badhat/http.php';
require 'add/badhat/db.php';
require 'add/badhat/auth.php';
require 'add/badhat/csrf.php';

register(HND_ALL | MESSAGE_LOG);

session_start();
db();
csrf(CSRF_SETUP);

$stmt = qp("SELECT password FROM users WHERE username = ?", []);
checkin(AUTH_SETUP, 'username', $stmt);

// ... rest of routing ...
```

### Login Route

```php
<?php
// app/io/route/login.php
return function($args) {
    if ($_POST) {
        csrf(CSRF_CHECK) || http_out(403, 'Invalid token');
        $user = checkin(AUTH_ENTER, 'username', 'password');
        $user && http_out(302, null, ['Location' => ['/dashboard']]);
    }
    return ['error' => $_POST ? 'Invalid credentials' : null];
};
```

### Login Render

```php
<?php // app/io/render/login.php
$error = $args['error'] ?? null;
?>
<?php if ($error): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="post">
    <?= csrf(CSRF_INPUT) ?>
    <input name="username" placeholder="Username" required>
    <input name="password" type="password" placeholder="Password" required>
    <button>Login</button>
</form>

<?php return function($args) {
    $content = $args[count($args) - 1];
    return "<!DOCTYPE html><html><body>$content</body></html>";
};
```

### Protected Route

```php
<?php
// app/io/route/dashboard.php
return function($args) {
    checkin() ?? http_out(302, null, ['Location' => ['/login']]);
    return ['user' => checkin()];
};
```

### Logout Route

```php
<?php
// app/io/route/logout.php
return function($args) {
    checkin(AUTH_LEAVE);
    http_out(302, null, ['Location' => ['/']]);
};
```

### Users Table with Password

```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert test user (password: 'secret')
INSERT INTO users (username, password) VALUES (
    'admin',
    '$2y$12$YourHashedPasswordHere'
);
```

Generate hash:
```php
echo password_hash('secret', PASSWORD_DEFAULT);
```