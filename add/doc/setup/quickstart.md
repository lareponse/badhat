# BADHAT Quick Start

**Get running in 5 minutes**

---

## Setup

```bash
mkdir myapp && cd myapp
git clone https://github.com/lareponse/BADHAT.git add
mkdir -p app/{route,views,data}
```

**index.php:**
```php
<?php
require 'add/bad/io.php';
require 'add/bad/db.php';

// Database connection
[$dsn, $user, $pass] = require 'app/data/credentials.php';
db(new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]));

// Route processing
$quest = quest(__DIR__ . '/app/route');
http_out(deliver($quest));
```

---

## First Route

**app/route/home.php:**
```php
<?php
return function() {
    tray('main', '<h1>BADHAT Running</h1>');
    return ['status' => 200, 'body' => render_layout()];
};
```

**app/views/layout.php:**
```php
<!DOCTYPE html>
<html>
<head>
    <title>BADHAT App</title>
    <?= implode("\n", tray('head')) ?>
</head>
<body>
    <?= implode("\n", tray('main')) ?>
    <?= implode("\n", tray('scripts')) ?>
</body>
</html>
```

---

## URL Mapping

| URL | File | Args |
|-----|------|------|
| `/` | `app/route/home.php` | `[]` |
| `/users` | `app/route/users.php` | `[]` |
| `/users/42` | `app/route/users.php` | `['42']` |
| `/admin/users/edit/42` | `app/route/admin/users/edit.php` | `['42']` |

---

## Common Patterns

### Database Operations
```php
// app/route/users.php
<?php
return function($id = null) {
    if ($id) {
        $user = dbq(db(), "SELECT * FROM users WHERE id = ?", [$id])->fetch();
        if (!$user) trigger_error('404 User not found', E_USER_ERROR);
        
        tray('main', render('users/show', ['user' => $user]));
    } else {
        $users = dbq(db(), "SELECT * FROM users ORDER BY name")->fetchAll();
        tray('main', render('users', ['users' => $users]));
    }
    
    return ['status' => 200, 'body' => render_layout()];
};
```

### Form Handling
```php
// app/route/users/create.php
<?php
return function() {
    if ($_POST) {
        csrf_validate($_POST['csrf_token']);
        
        [$sql, $binds] = qb_create('users', null, $_POST);
        dbq(db(), $sql, $binds);
        
        header('Location: /users');
        exit;
    }
    
    tray('main', render('users/create'));
    return ['status' => 200, 'body' => render_layout()];
};
```

### Authentication Guard
```php
<?php
if (!auth()) {
    header('Location: /login');
    exit;
}
```

### API Endpoint
```php
// app/route/api/users.php
<?php
return function($id = null) {
    header('Content-Type: application/json');
    
    if ($id) {
        $user = dbq(db(), "SELECT * FROM users WHERE id = ?", [$id])->fetch();
        return ['status' => $user ? 200 : 404, 'body' => json_encode($user ?: ['error' => 'Not found'])];
    }
    
    $users = dbq(db(), "SELECT * FROM users")->fetchAll();
    return ['status' => 200, 'body' => json_encode($users)];
};
```

---

## Folder Structure Examples

### Simple Blog
```
app/
├── route/
│   ├── home.php
│   ├── post.php
│   └── archive.php
├── views/
├── functions/
└── data/
```

### SaaS Application
```
app/
├── route/
│   ├── tenant/
│   │   ├── dashboard.php
│   │   └── settings.php
│   ├── admin/
│   │   └── tenants.php
│   └── api/
│       └── webhooks.php
├── views/
│   ├── tenant/
│   └── admin/
├── functions/
│   ├── tenant.php
│   └── billing.php
└── data/
```

---

## Development Workflow

### 1. Add Route
Create `app/route/path.php` → handles `/path`

### 2. Add View (Optional)
Create `app/views/path.php` → use with `render('path', $data)`

### 3. Add Database
Use `dbq(db(), )` for queries, `db_transaction()` for transactions

### 4. Add Functions
Create `app/functions/feature.php` → require where needed

### 5. Test
Visit URL → BADHAT executes route function

---

## Essential Functions

```php
// Database
dbq(db(), $sql, $binds);                    // Execute query
db_transaction(db(),() { /* queries */ });    // Transaction

// Content
tray('main', $html);                  // Add to slot
render('template', $data);            // Render view
render_layout();                      // Complete page

// Auth
auth();                             // Current user
auth_post($user, $pass, $remember);   // Login
csrf_validate($token);                         // Validate CSRF

// Utilities
[$sql, $binds] = qb_create('table', null, $data);  // Query builder
trigger_error('404 Not found', E_USER_ERROR);      // HTTP errors
```

---

## Production Checklist

1. **Set database credentials** in environment variable
2. **Configure web server** document root to `/app/public/`
3. **Enable opcache** for performance
4. **Set auth secret** `BADHAT_AUTH_HMAC_SECRET` environment variable
5. **Remove debug flags** and error display
6. **Test routes** and database connections

---

## Common Issues

**Route not found:** Check file exists at `app/route/path.php`

**Database error:** Verify credentials in `app/data/credentials.php`

**CSRF failed:** Include `<?= csrf_field() ?>` in forms

**View not rendering:** Check `app/views/template.php` exists

**Performance slow:** Enable opcache, check query efficiency

---

That's it. URL → file → function → response.

No configuration files, no service containers, no magic.