# BADHAT Project Setup 

Complete tutorial for starting a BADHAT project with the **current core**.

---

## 1. Initialize Project

```bash
mkdir myproject && cd myproject
git init
mkdir -p app/io/{route,render} public scripts
touch .gitignore
```

---

## 2. Add BADHAT Core via Subtree

```bash
git subtree add --prefix=add/badhat git@github.com:lareponse/BADHAT.git main --squash
```

---

## 3. Create Entry Point (Canonical)

```php
<?php
// public/index.php — BADHAT Entry Point

set_include_path(__DIR__ . '/..' . PATH_SEPARATOR . get_include_path());

$install = require 'add/badhat/error.php';
require 'add/badhat/io.php';
require 'add/badhat/run.php';
require 'add/badhat/http.php';
require 'add/badhat/db.php';

use function bad\io\{path, seek, look};
use function bad\run\run;
use function bad\http\http_out;
use function bad\db\db;

use const bad\error\HND_ALL;
use const bad\io\{IO_URL, IO_NEST};
use const bad\run\{RUN_INVOKE, RUN_ABSORB, RUN_RETURN};

// --------------------------------------------------
// Bootstrap
// --------------------------------------------------

$install(HND_ALL);

// --------------------------------------------------
// DB bootstrap (REQUIRED before qp())
// --------------------------------------------------

$pdo = new PDO(
    getenv('DB_DSN_')  ?: 'sqlite:' . __DIR__ . '/../db.sqlite',
    getenv('DB_USER_') ?: null,
    getenv('DB_PASS_') ?: null,
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

db($pdo);

// --------------------------------------------------
// Normalize request path
// --------------------------------------------------

$key = path($_SERVER['REQUEST_URI'], "\0", IO_URL);
$io  = __DIR__ . '/../app/io';

// --------------------------------------------------
// Phase 1 — Route (logic)
// --------------------------------------------------

$loot = [];
$route = seek($io . '/route/', $key, '.php');

if ($route) {
    [$file, $args] = $route;
    $loot = run([$file], $args, RUN_INVOKE);
}

// --------------------------------------------------
// Phase 2 — Render (presentation)
// --------------------------------------------------

$render = look($io . '/render/', $key, '.php', IO_NEST);

if ($render) {
    $loot = run([$render], $loot, RUN_ABSORB);
}

// --------------------------------------------------
// Output
// --------------------------------------------------

isset($loot[RUN_RETURN]) && is_string($loot[RUN_RETURN])
    ? http_out(200, $loot[RUN_RETURN], [
        'Content-Type' => ['text/html; charset=utf-8']
      ])
    : http_out(404, 'Not Found');
```

---

## 4. Environment Config

```bash
# .env (not tracked)
cat > .env << 'EOF'
DB_DSN_=sqlite:db.sqlite
DB_USER_=
DB_PASS_=
EOF

# .gitignore
cat > .gitignore << 'EOF'
.env
*.sqlite
EOF
```

*(Load `.env` however you want: shell export, systemd, Apache, etc. BADHAT does not care.)*

---

## 5. Sample Route

```php
<?php
// app/io/route/users.php

use function bad\db\qp;
use function bad\http\http_out;

return function (array $args) {

    if ($_POST) {
        qp("INSERT INTO users (name) VALUES (?)", [$_POST['name']]);
        http_out(302, null, ['Location' => ['/users']]);
    }

    return [
        'users' => qp("SELECT * FROM users")->fetchAll()
    ];
};
```

---

## 6. Sample Render

```php
<?php
// app/io/render/users.php

$users = $args['users'] ?? [];
?>
<h1>Users</h1>

<ul>
<?php foreach ($users as $user): ?>
    <li><?= htmlspecialchars($user['name'], ENT_QUOTES) ?></li>
<?php endforeach; ?>
</ul>

<form method="post">
    <input name="name" placeholder="Name" required>
    <button>Add</button>
</form>

<?php
return function (array $args) {
    $content = $args[count($args) - 1];

    return "<!DOCTYPE html>
<html>
<head><title>Users</title></head>
<body>$content</body>
</html>";
};
```

---

## 7. Database Init Script

```php
<?php
// scripts/init-db.php

require __DIR__ . '/../add/badhat/db.php';

use function bad\db\db;

$pdo = new PDO('sqlite:' . __DIR__ . '/../db.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

db($pdo);

db()->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

echo \"Database initialized.\n\";
```

---

## 8. First Commit

```bash
git add .
git commit -m "Initial BADHAT project"
```

---

## 9. Project Structure

```
myproject/
├── .gitignore
├── .env
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
├── scripts/init-db.php
└── db.sqlite
```

---

## 10. Test

```bash
php scripts/init-db.php
cd public && php -S localhost:8000
# visit http://localhost:8000/users
```

---

## 11. Update BADHAT Core

```bash
git subtree pull --prefix=add/badhat git@github.com:lareponse/BADHAT.git main --squash
```

---

## Notes (Important, by design)

* `db($pdo)` **must be called once per request** before `qp()`
* Routing is **filesystem-driven**, not table-driven
* There is no hidden lifecycle
* If a file runs, it runs because *you included it*
* If something breaks, it breaks loudly
