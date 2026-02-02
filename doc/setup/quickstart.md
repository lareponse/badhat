# BADHAT Project Setup

Complete tutorial for starting a BADHAT project with the **current core**, pulled in as a **squashed subtree** with a **shallow fetch**.

---

## 1. Initialize Project

```bash
mkdir myproject && cd myproject
git init
mkdir -p app/io/{route,render} public
touch .gitignore
```

---

## 2. Add BADHAT Core via Subtree (shallow + squashed)

**Recommended**: shallow-fetch the tip of `main`, then add subtree from `FETCH_HEAD`.

```bash
git fetch --depth=1 git@github.com:lareponse/BADHAT.git main
git subtree add --prefix=add/badhat FETCH_HEAD --squash
```

Why this method:

* `--squash` keeps BADHAT as **one commit** in *your* history.
* `--depth=1` keeps the fetch **small** (only the tip snapshot, minimal history objects).

---

## 3. Create Entry Point (Canonical)

```php
<?php
// public/index.php — BADHAT Entry Point

set_include_path(__DIR__ . '/..' . PATH_SEPARATOR . get_include_path());

$install = require 'add/badhat/error.php';
require 'add/badhat/map.php';
require 'add/badhat/run.php';
require 'add/badhat/http.php';
require 'add/badhat/pdo.php';

use function bad\map\{hook, seek, look};
use function bad\http\{headers, out};
use function bad\run\run;
use function bad\pdo\db;

use const bad\error\HND_ALL;
use const bad\map\REBASE;
use const bad\run\{INVOKE, ABSORB, INC_RETURN};
use const bad\http\SET;

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

$io   = __DIR__ . '/../app/io';
$base = realpath($io . '/route') . '/';
$key  = hook($base, $_SERVER['REQUEST_URI'], "\0");

// --------------------------------------------------
// Phase 1 — Route (logic)
// --------------------------------------------------

$loot = [];
$route = seek($base, $key, '.php');

if ($route) {
    [$file, $args] = $route;
    $loot = run([$file], $args, INVOKE);
}

// --------------------------------------------------
// Phase 2 — Render (presentation)
// --------------------------------------------------

$render = look($io . '/render/', $key, '.php', REBASE);

if ($render) {
    $loot = run([$render], $loot, ABSORB);
}

// --------------------------------------------------
// Output
// --------------------------------------------------

if (isset($loot[INC_RETURN]) && is_string($loot[INC_RETURN])) {
    headers(SET, 'Content-Type', 'text/html; charset=utf-8');
    exit(out(200, $loot[INC_RETURN]));
}

exit(out(404, 'Not Found'));
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

use function bad\pdo\qp;
use function bad\http\{headers, out};
use const bad\http\SET;

return function (array $args) {

    if ($_POST) {
        qp("INSERT INTO users (name) VALUES (?)", [$_POST['name']]);
        headers(SET, 'Location', '/users');
        exit(out(302));
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

## 7. First Commit

```bash
git add .
git commit -m "Initial BADHAT project"
```

---

## 8. Project Structure

```
myproject/
├── .gitignore
├── .env
├── add/badhat/
│   ├── auth.php
│   ├── csrf.php
│   ├── pdo.php
│   ├── error.php
│   ├── http.php
│   ├── map.php
│   └── run.php
├── app/io/
│   ├── route/users.php
│   └── render/users.php
└── public/index.php
```

---

## 9. Test

```bash
cd public
php -S localhost:8000
```

Visit `http://localhost:8000/users`

---

## 10. Update BADHAT Core (shallow + squashed)

Same approach: shallow-fetch latest `main`, then subtree pull from `FETCH_HEAD`.

```bash
git fetch --depth=1 git@github.com:lareponse/BADHAT.git main
git subtree pull --prefix=add/badhat FETCH_HEAD --squash
```

---

## Notes 

* `db($pdo)` **must be called once per request** before `qp()`
* Routing is **filesystem-driven**, not table-driven
* There is no hidden lifecycle
* If a file runs, it runs because *you included it*
* If something breaks, it breaks loudly