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
require 'add/badhat/db.php';
require 'add/badhat/auth.php';

badhat_install_error_handlers();

[$path, $accept] = io_in($_SERVER['REQUEST_URI']);

// Phase 1: Route
$route = io_map(__DIR__ . '/../app/io/route', $path, 'php', IO_DEEP);
$loot = $route ? io_run($route, [], IO_INVOKE) : [];

// Phase 2: Render
$render = io_map(__DIR__ . '/../app/io/render', $path, 'php', IO_DEEP | IO_NEST);
$loot = $render ? io_run($render, $loot, IO_ABSORB) : $loot;

io_die(
    isset($loot[IO_RETURN]) ? 200 : 404,
    $loot[IO_RETURN] ?? 'Not Found',
    ['Content-Type' => 'text/html; charset=utf-8']
);
```

## 4. Create Environment Config

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

## 5. Create Sample Route

```php
<?php
// app/io/route/users.php
return function($args) {
    if ($_POST) {
        qp("INSERT INTO users (name) VALUES (?)", [$_POST['name']]);
        header('Location: /users');
        exit;
    }
    
    $users = qp("SELECT * FROM users")->fetchAll();
    return compact('users') + ['csrf' => csrf_token()];
};
```

## 6. Create Sample Render

```php
<?php // app/io/render/users.php
$users = $args['users'] ?? [];
$csrf = $args['csrf'] ?? '';
?>
<h1>Users</h1>
<ul>
<?php foreach ($users as $user): ?>
    <li><?= htmlspecialchars($user['name']) ?></li>
<?php endforeach; ?>
</ul>

<form method="post">
    <input type="hidden" name="_csrf_token" value="<?= $csrf ?>">
    <input name="name" placeholder="Name" required>
    <button>Add User</button>
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

## 7. Create Database Init Script

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

Expected structure:
```
myproject/
├── .gitignore
├── add/
│   └── badhat/
│       ├── auth.php
│       ├── db.php
│       ├── error.php
│       └── io.php
├── app/
│   └── io/
│       ├── route/
│       │   └── users.php
│       └── render/
│           └── users.php
├── public/
│   └── index.php
└── scripts/
    └── init-db.php
```

## 9. Test Setup

```bash
php scripts/init-db.php
cd public && php -S localhost:8000
# → http://localhost:8000/users
```

## 10. Update BADHAT

```bash
git subtree pull --prefix=add/badhat git@github.com:lareponse/BADHAT.git main --squash
```

## 11. Deployment

```bash
git clone https://github.com/you/myproject.git /var/www/app
cd /var/www/app
php scripts/init-db.php
# Configure web server → public/
```

## Performance Notes

- **Opcache:** All files real, no symlinks
- **Single repo:** Zero deployment complexity
- **Direct execution:** No framework bootstrap
- **~200 bytes** routing memory

---

## Adding Auth

```php
<?php
// public/index.php (add after db.php require)
require 'add/badhat/auth.php';

// After badhat_install_error_handlers():
$stmt = qp("SELECT password FROM users WHERE name = ?", []);
checkin(AUTH_SETUP, 'username', $stmt);
```

```php
<?php
// app/io/route/login.php
return function($args) {
    if ($_POST) {
        $user = checkin(AUTH_ENTER, 'name', 'password');
        if ($user) {
            header('Location: /dashboard');
            exit;
        }
    }
    return ['error' => $_POST ? 'Invalid credentials' : null, 'csrf' => csrf_token()];
};
```

```php
<?php
// app/io/route/dashboard.php
return function($args) {
    checkin(AUTH_GUARD, '/login');
    return ['user' => checkin()];
};
```