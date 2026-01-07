# BADHAT IO — Path Resolution

IO does one thing: resolve paths to files.

- No execution
- No output handling
- No HTTP concerns
- Just filesystem mapping

Execution belongs to `run()`. HTTP belongs to `http_out()`.

---

## Constants

```php
// io_in behavior
const IO_PATH_ONLY  = 1;              // strip ?query and #fragment
const IO_ROOTLESS   = 2;              // strip leading /
const IO_ABSOLUTE   = 4 | IO_ROOTLESS; // ensure leading /

// io_map, io_look, io_seek behavior
const IO_NEST       = 8;              // try path/basename pattern
const IO_TAIL       = 16;             // seek deepest first (intent-centric)
const IO_HEAD       = 32;             // seek shallowest first (entry-point-centric)
```

---

## Functions

### io_in

```php
function io_in(string $raw, string $forbidden = '', int $behave = 0): string
```

Normalizes a raw URI into a usable path string.

```php
// Basic usage
$path = io_in('/users/profile?tab=settings');           // '/users/profile?tab=settings'
$path = io_in('/users/profile?tab=settings', '', IO_PATH_ONLY);  // '/users/profile'
$path = io_in('/users/profile', '', IO_ROOTLESS);       // 'users/profile'
$path = io_in('users/profile', '', IO_ABSOLUTE);        // '/users/profile'

// Reject forbidden characters
$path = io_in($uri, "\0");                              // throws on null byte
$path = io_in($uri, "\0..");                            // throws on null or '..'
```

**Throws:** `InvalidArgumentException` (400) if forbidden chars found.

---

### io_map

```php
function io_map(string $base_dir, string $url_path, string $execution_suffix, int $behave = 0): ?array
```

Resolves a URL path to an executable file. Returns `[filepath, args]` or `null`.

**Note:** `$base_dir` must end with `DIRECTORY_SEPARATOR`.

```php
// Direct lookup (no flags)
$route = io_map('/app/route/', 'users', '.php');
// → ['/app/route/users.php', null] or null

// With nesting
$route = io_map('/app/route/', 'admin/users', '.php', IO_NEST);
// tries: admin/users.php, then admin/users/users.php

// With seeking (captures remaining segments as args)
$route = io_map('/app/route/', 'users/edit/42', '.php', IO_TAIL);
// tries: users/edit/42.php → users/edit.php → users.php
// returns: ['/app/route/users.php', ['edit', '42']]

$route = io_map('/app/route/', 'api/v2/users', '.php', IO_HEAD);
// tries: api.php → api/v2.php → api/v2/users.php
// returns first match with remaining segments
```

---

### io_look

```php
function io_look(string $base_dir, string $url_path, string $execution_suffix, int $behave = 0): ?string
```

Direct file lookup. No walking. Returns real path or `null`.

```php
$file = io_look('/app/route/', 'users', '.php');
// → '/app/route/users.php' or null

$file = io_look('/app/route/', 'admin', '.php', IO_NEST);
// tries: admin.php, then admin/admin.php
```

**Security:** validates resolved path stays within `$base_dir`.

---

### io_seek

```php
function io_seek(string $base_dir, string $url_path, string $execution_suffix, int $behave = 0): ?array
```

Walking lookup. Returns `[filepath, remaining_segments]` or `null`.

```php
// IO_TAIL: deepest first (default for intent-centric routing)
$route = io_seek('/app/route/', 'users/edit/42', '.php', IO_TAIL);
// walks: users/edit/42 → users/edit → users
// if users.php exists: ['/app/route/users.php', ['edit', '42']]

// IO_HEAD: shallowest first (for gateway patterns)
$route = io_seek('/app/route/', 'api/v2/users/42', '.php', IO_HEAD);
// walks: api → api/v2 → api/v2/users → api/v2/users/42
// if api.php exists: ['/app/route/api.php', ['v2', 'users', '42']]
```

---

## Patterns

### Basic File Router

```php
$path = io_in($_SERVER['REQUEST_URI'], "\0", IO_PATH_ONLY | IO_ROOTLESS);
$route = io_map('/app/route/', $path, '.php');

$route
    ? run($route, [])
    : http_out(404, 'Not Found');
```

### Parameterized Routes

```php
// /users/edit/42 → users.php receives ['edit', '42']
$route = io_map('/app/route/', $path, '.php', IO_TAIL);
[$file, $args] = $route ?? http_out(404, 'Not Found');
run([$file], $args ?? [], RUN_INVOKE);
```

### Gateway Pattern

```php
// /api/v2/users/42 → api.php receives ['v2', 'users', '42']
$route = io_map('/app/route/', $path, '.php', IO_HEAD);
```

### Nested Entry Points

```php
// /admin → admin/admin.php (if admin.php doesn't exist)
$route = io_map('/app/route/', $path, '.php', IO_NEST | IO_TAIL);
```
