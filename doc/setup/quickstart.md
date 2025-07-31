# BADHAT Project Setup

Complete tutorial for starting a BADHAT project with `lareponse/arrow` integration as example.

## 1. Initialize Project

```bash
# Create and enter project directory
mkdir myproject && cd myproject
git init

# Create basic structure
mkdir -p app/io/{route,render} public
touch .gitignore
```

## 2. Add BADHAT Core via Subtree

```bash
# Add BADHAT core to add/ directory
git subtree add --prefix=add/badhat git@github.com:lareponse/BADHAT.git main --squash
```

## 3. Add Arrow Library via Subtree

```bash
# Add arrow to add/arrow/ directory  
git subtree add --prefix=add/arrow git@github.com:lareponse/arrow.git main --squash

# Should see arrow-specific files
```

## 4. Create Entry Point

```php
<?php
// public/index.php
set_include_path(__DIR__ . '/..' . PATH_SEPARATOR . get_include_path());


require 'add/badhat/build.php';
require 'add/badhat/error.php';
require 'add/badhat/io.php';
require 'add/badhat/db.php';
require 'add/badhat/auth.php';

require 'add/arrow/arrow.php';  // Load arrow library

$request = http_in();

// Phase 1: Route logic
$route = io_route('app/io/route', $request, 'index');
$data = io_fetch($route, [], IO_INVOKE);

// Phase 2: Render output
$render = io_route('app/io/render', $request, 'index');
$html = io_fetch($render, $data[IO_INVOKE] ?? [], IO_ABSORB);

http_out(200, $html[IO_ABSORB] ?? 'Not Found');
```

## 5. Create Environment Config

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
db.sqlite
EOF
```

## 6. Create Sample Route with Arrow

```php
<?php
// app/io/route/admin/users.php
return function($args) {
    // Use arrow for row operations
    $user = row(db(), 'users');
    
    if ($_POST) {
        $user(ROW_LOAD, ['id' => $_POST['id']]);
        $user(ROW_SET | ROW_SAVE, $_POST);
        header('Location: /users');
        exit;
    }
    
    // Load user list
    $users = db()->query("SELECT * FROM users")->fetchAll();
    return compact('users');
};
```

## 7. Create Sample Render

```php
// app/io/render/admin/users.php
<h1>Users</h1>
<ul>
<?php foreach ($users as $user): ?>
    <li><?= htmlspecialchars($user['name']) ?></li>
<?php endforeach; ?>
</ul>

<form method="post">
    <input name="name" placeholder="Name" required>
    <button>Add User</button>
</form>

<?php
return function ($this_html, $args = []) {
    [$ret, $get] = ob_ret_get('app/io/render/admin/layout.php', ($args ?? []) + ['main' => $this_html])
    return $get;
};
```

## 8. First Commit

```bash
# Add all files
git add .
git commit -m "Initial BADHAT project with arrow"

# Verify structure
tree -I '.git'
```

Expected structure:
```
myproject/
├── .gitignore
├── add/                     # BADHAT core (subtree)
│   ├── arrow/              # Arrow library (subtree)
│   ├── io.php
│   ├── db.php
│   └── ...
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
# Initialize database
php scripts/init-db.php

# Start PHP dev server
cd public && php -S localhost:8000

# Test in browser: http://localhost:8000/users
```

## 11. Update Workflow

```bash
# Update BADHAT core
git subtree pull --prefix=add git@github.com:lareponse/BADHAT.git main --squash

# Update arrow library  
git subtree pull --prefix=add/arrow git@github.com:lareponse/arrow.git main --squash

# Commit updates
git commit -m "Update BADHAT and arrow"
```

```bash
# Update BADHAT core
git subtree push --prefix=add badhat main

# Update arrow library  
git subtree push --prefix=add/arrow arrow main
```

## 12. Deployment

```bash
# Clone to production server
git clone https://github.com/you/myproject.git /var/www/app

# No submodule init required - subtrees include all files!
cd /var/www/app
composer install  # If you use any composer deps
php scripts/init-db.php

# Configure web server to point to public/
```

## Performance Notes

- **Opcache benefits**: All files are "real" - no symlink overhead
- **Single repository**: Zero deployment complexity  
- **Arrow integration**: CROW pattern for high-performance row operations
- **Direct file execution**: No framework bootstrap overhead

You now have a complete BADHAT project with Arrow integration, ready for high-performance web development.

