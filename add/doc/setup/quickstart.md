# BADGE Development Workflow

## Quick Start (5 minutes)

### 1. Setup Structure
```bash
mkdir myapp && cd myapp
git clone https://github.com/lareponse/BADGE.git add
mkdir -p app/public/assets
cp add/doc/setup/* app/public/
mv app/public/index.base.php app/public/index.php
```

### 2. Create Entry Point
**app/public/index.php:**

**Critical:** `route(__DIR__ . '/../io/route')` defines your entire app architecture.

**Rule:** You must have a parent folder with exactly two children - one for routes, one for views. You can name them anything:

```php
// Option 1: parent "io", route child "route"
$route = route(__DIR__ . '/../io/route');
// Expects: app/io/route/ and app/io/views/

// Option 2: parent "web", route child "handlers"  
$route = route(__DIR__ . '/../web/handlers');
// Expects: app/web/handlers/ and app/web/views/

// Option 3: parent "src", route child "controllers"
$route = route(__DIR__ . '/../src/controllers');
// Expects: app/src/controllers/ and app/src/views/
```

You pass the path to the **route child**. BADGE infers the views folder is the sibling. The parent can only contain these two folders.

**URL mapping:** `/user/edit` → `app/io/route/user/edit.php` → `app/io/views/user/edit.php`

**app/data/credentials.php:**
```php
<?php
$host = getenv('DB_HOST') ?: 'localhost';
$name = getenv('DB_NAME') ?: 'myapp';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

return ["mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass];
```

**.htaccess:**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [QSA,L]

<IfModule mod_env.c>
SetEnv DEV_MODE true
</IfModule>
```

### 4. Create First Route
**app/io/route/home.php:**
```php
<?php
return function() {
    return [
        'status' => 200,
        'body' => render(['title' => 'Welcome to BADGE'])
    ];
};
```

**app/io/views/home.php:**
```php
<h1><?= $title ?></h1>
<p>Your BADGE app is running.</p>
```

**app/io/views/layout.php:**
```php
<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?? 'BADGE App' ?></title>
    <?= implode("\n", tray('head')) ?>
</head>
<body>
    <?= implode("\n", tray('main')) ?>
    <?= implode("\n", tray('scripts')) ?>
</body>
</html>
```

### 5. Test Setup
Visit `http://localhost/myapp/app/public/` or configure virtual host.

## Development Workflow

### Adding Routes
1. **Simple route:** Create `app/io/route/about.php`
2. **Nested route:** Create `app/io/route/blog/post.php`
3. **Dynamic route:** Create `app/io/route/user.php` (takes ID as arg)

### Database Operations
1. **Create mapper:** `app/mapper/user.php`
2. **Add functions:** `user_create()`, `user_get_by_id()`
3. **Use in routes:** `require_once '../mapper/user.php'`

### Adding Authentication
1. **Protect routes:** Add `app/io/route/admin/prepare.php`
2. **Check auth:** Use `whoami()`

### Development Mode Features
- **Missing routes:** Shows scaffold templates
- **Error display:** Full stack traces
- **No caching:** Immediate code changes

### File Organization Pattern
```
app/io/route/
├── admin/
│   ├── prepare.php     # Auth check for all /admin/*
│   ├── users.php       # /admin/users
│   └── posts/
│       ├── prepare.php # Additional checks for /admin/posts/*
│       └── edit.php    # /admin/posts/edit
├── api/
│   └── users.php       # /api/users
└── home.php            # /home or /
```

### Common Patterns

**Form handling:**
```php
if ($_POST) {
    if (!csrf($_POST['csrf_token'])) {
        trigger_error('403 Forbidden: Invalid CSRF', E_USER_ERROR);
    }
    // Process form
}
```

**API responses:**
```php
return [
    'status' => 200,
    'body' => json_encode($data),
    'headers' => ['Content-Type' => 'application/json']
];
```

**File uploads:**
```php
if ($_FILES['upload']['error'] === UPLOAD_ERR_OK) {
    $path = 'uploads/' . uniqid() . '.pdf';
    move_uploaded_file($_FILES['upload']['tmp_name'], $path);
}
```

## Production Deployment

1. **Disable dev mode:** Remove `DEV_MODE` env var
2. **Set auth secret:** Generate strong `BADGE_AUTH_HMAC_SECRET`
3. **Configure database:** Update credentials for production
4. **Set document root:** Point to `app/public/`
5. **Enable opcache:** PHP performance optimization

## Debugging Tips

- **Route issues:** Check DEV_MODE scaffold suggestions
- **DB problems:** Use `db()->errorInfo()` after queries
- **Auth failures:** Verify HMAC secret and headers
- **View errors:** Check variable extraction in templates