# BADDAD Testing Guide

**Testing the BADDAD way: No magic, just files and functions.**

## Philosophy

BADDAD testing follows the same principles as the framework itself:

- **No classes** - Pure functions for testing
- **No frameworks** - No PHPUnit, no complex setup
- **File-based** - Test organization mirrors app structure
- **Direct testing** - Test actual functions and routes
- **Simple requires** - Explicit includes, no autoloading
- **Built-in assert()** - Use PHP's native assertion function
- **Exception validation** - Test specific exception types and messages
- **Minimal core** - 15 lines of testing code vs 10,000+ in frameworks

## Quick Start


### 2. Create Testing Structure

```
test/
├── test.php               # Testing core (15 lines)
├── database/
│   ├── user_test.php      # Test user mapper
│   ├── article_test.php   # Test article mapper
│   └── qb_test.php        # Test query builders
├── routes/
│   ├── admin_test.php     # Test admin routes
│   └── auth_test.php      # Test authentication
├── helpers/
│   ├── validation_test.php # Test validation
│   └── ui_test.php        # Test UI helpers
└── run_all.php           # Run all tests
```


### 4. Write Your First Test

Create `test/database/user_test.php`:

```php
<?php

require_once __DIR__ . '/../test.php';
require_once __DIR__ . '/../../add/bad/dad/db.php';
require_once __DIR__ . '/../../mapper/user.php';

function setup_test_db(): void
{
    static $setup = false;
    if ($setup) return;
    
    // Use in-memory SQLite for fast tests
    db('sqlite::memory:');
    
    // Create test tables
    dbq("CREATE TABLE users (
        id INTEGER PRIMARY KEY,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        full_name TEXT NOT NULL,
        role TEXT DEFAULT 'user',
        status TEXT DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $setup = true;
}

test('user_create works with valid data', function() {
    setup_test_db();
    
    $user_data = [
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'password123',
        'full_name' => 'Test User'
    ];
    
    $user_id = user_create($user_data);
    
    assert(is_numeric($user_id));
    assert($user_id > 0);
});

test('user_verify works with correct credentials', function() {
    setup_test_db();
    
    $user_data = [
        'username' => 'verifytest',
        'email' => 'verify@example.com',
        'password' => 'testpass',
        'full_name' => 'Verify Test'
    ];
    
    user_create($user_data);
    
    $user = user_verify('verifytest', 'testpass');
    
    assert($user !== false);
    assert($user['username'] === 'verifytest');
});

test('user_create fails with duplicate username', function() {
    setup_test_db();
    
    $user_data = [
        'username' => 'duplicate',
        'email' => 'first@example.com',
        'password' => 'password123',
        'full_name' => 'First User'
    ];
    
    user_create($user_data);
    
    // Test that duplicate username throws PDOException
    assert_throws(function() use ($user_data) {
        $user_data['email'] = 'second@example.com';
        user_create($user_data);
    }, 'PDOException', 'UNIQUE constraint');
});

run_tests();
```

### 5. Run Tests

```bash
# Run specific test file
php test/database/user_test.php

# Output:
Running tests...

✓ user_create works with valid data
✓ user_verify works with correct credentials
✓ user_create fails with duplicate username

3 tests, 3 passed, 0 failed
```

## Writing Tests

### Testing Functions with Built-in assert()

```php
// test/database/article_test.php
require_once __DIR__ . '/../test.php';
require_once __DIR__ . '/../../mapper/article.php';

test('articles_get_published returns only published articles', function() {
    setup_test_db();
    
    // Create test data
    article_create([
        'title' => 'Published Article',
        'slug' => 'published-article',
        'content' => 'Content here',
        'status' => 'published',
        'user_id' => 1
    ]);
    
    article_create([
        'title' => 'Draft Article',
        'slug' => 'draft-article', 
        'content' => 'Draft content',
        'status' => 'draft',
        'user_id' => 1
    ]);
    
    $articles = articles_get_published();
    
    assert(count($articles) === 1);
    assert($articles[0]['title'] === 'Published Article');
    assert($articles[0]['status'] === 'published');
});
```

### Testing Errors and Exceptions

```php
test('user_create throws on duplicate username', function() {
    setup_test_db();
    
    $user_data = ['username' => 'duplicate', 'email' => 'test@example.com'];
    user_create($user_data);
    
    // Test specific exception type and message
    assert_throws(function() use ($user_data) {
        user_create($user_data);
    }, 'PDOException', 'UNIQUE constraint');
});

test('route handler throws 404 for missing article', function() {
    setup_test_db();
    
    // Test that accessing non-existent article throws specific error
    assert_throws(function() {
        $handler = require __DIR__ . '/../../app/io/route/admin/articles/alter.php';
        $handler(99999); // Non-existent ID
    }, 'Error', '404 Not Found');
});

test('validation throws InvalidArgumentException', function() {
    // Test specific exception type only
    assert_throws(function() {
        validate_email('invalid-email');
    }, 'InvalidArgumentException');
});

test('any exception is thrown', function() {
    // Test that any exception is thrown (no class specified)
    assert_throws(function() {
        trigger_error('Some error', E_USER_ERROR);
    });
});
```

### assert_throws() Usage Patterns

```php
// Test that any exception is thrown
assert_throws(function() {
    dangerous_operation();
});

// Test specific exception type
assert_throws(function() {
    invalid_function_call();
}, 'InvalidArgumentException');

// Test exception type and message
assert_throws(function() {
    user_create(['username' => 'duplicate']);
}, 'PDOException', 'UNIQUE constraint');

// Test message only (any exception type)
assert_throws(function() {
    trigger_error('Custom error', E_USER_ERROR);
}, '', 'Custom error');
```

### Testing Query Builders

```php
// test/database/qb_test.php
require_once __DIR__ . '/../test.php';
require_once __DIR__ . '/../../add/bad/qb.php';

test('qb_create generates correct SQL', function() {
    $data = ['name' => 'Test', 'email' => 'test@example.com'];
    
    [$sql, $params] = qb_create('users', $data);
    
    assert(strpos($sql, 'INSERT INTO users') !== false);
    assert(strpos($sql, '(name,email)') !== false);
    assert(strpos($sql, 'VALUES') !== false);
    assert(array_values($params) === ['Test', 'test@example.com']);
});

test('qb_where handles multiple conditions', function() {
    $conditions = ['status' => 'active', 'role' => 'admin'];
    
    [$where, $params] = qb_where($conditions);
    
    assert(strpos($where, 'WHERE') !== false);
    assert(strpos($where, 'status = ?') !== false);
    assert(strpos($where, 'role = ?') !== false);
    assert(strpos($where, 'AND') !== false);
    assert($params === ['active', 'admin']);
});

test('qb_where handles IN clause', function() {
    $conditions = ['status' => ['active', 'pending']];
    
    [$where, $params] = qb_where($conditions);
    
    assert(strpos($where, 'status IN(') !== false);
    assert(array_values($params) === ['active', 'pending']);
});
```

### Testing Route Handlers

```php
// test/routes/admin_test.php
require_once __DIR__ . '/../test.php';
require_once __DIR__ . '/../../add/core.php';

test('admin articles route resolves correctly', function() {
    $route_root = __DIR__ . '/../../app/io/route';
    
    // Mock request
    $_SERVER['REQUEST_URI'] = '/admin/articles';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    $route = route($route_root);
    
    assert(strpos($route['handler'], '/admin/articles') !== false);
    assert($route['args'] === []);
});

test('admin article edit route accepts ID parameter', function() {
    $route_root = __DIR__ . '/../../app/io/route';
    
    $_SERVER['REQUEST_URI'] = '/admin/articles/alter/42';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    $route = route($route_root);
    
    assert(strpos($route['handler'], '/admin/articles/alter') !== false);
    assert($route['args'] === ['42']);
});
```

### Testing Validation Functions

```php
// test/helpers/validation_test.php
require_once __DIR__ . '/../test.php';
require_once __DIR__ . '/../../helpers/validation.php';

test('validate_slug accepts valid slug', function() {
    $error = validate_slug('valid-slug-123', 'test_table');
    assert($error === null);
});

test('validate_slug rejects empty slug', function() {
    $error = validate_slug('', 'test_table');
    assert($error === 'Slug is required');
});

test('validate_slug rejects invalid characters', function() {
    $error = validate_slug('invalid_slug!', 'test_table');
    assert($error === 'Invalid slug format');
});
```

## Integration Testing

Test complete workflows by testing actual route handlers:

```php
// test/integration/article_admin_test.php
require_once __DIR__ . '/../test.php';

test('article creation workflow works end-to-end', function() {
    setup_test_db();
    
    // Create test user
    $user_id = user_create([
        'username' => 'admin',
        'email' => 'admin@test.com',
        'password' => 'admin123',
        'full_name' => 'Admin User'
    ]);
    
    // Mock POST data
    $_POST = [
        'title' => 'Test Article',
        'slug' => 'test-article',
        'content' => 'Test content here',
        'status' => 'draft'
    ];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    // Mock authentication
    $_SERVER['HTTP_X_AUTH_USER'] = 'admin';
    
    // Load and execute route handler
    $handler = require __DIR__ . '/../../app/io/route/admin/articles/alter.php';
    $result = $handler(); // No ID = create mode
    
    assert($result['status'] === 200);
    
    // Verify article was created
    $article = article_get_by('slug', 'test-article');
    assert($article !== false);
    assert($article['title'] === 'Test Article');
});
```

## Running All Tests

Create `test/run_all.php`:

```php
<?php

require_once __DIR__ . '/test.php';

// Include all test files
require_once __DIR__ . '/database/user_test.php';
require_once __DIR__ . '/database/article_test.php';
require_once __DIR__ . '/database/qb_test.php';
require_once __DIR__ . '/routes/admin_test.php';
require_once __DIR__ . '/helpers/validation_test.php';

// Run all tests
run_tests();
```

Run with:

```bash
php test/run_all.php
```

## Performance Testing

Test performance characteristics:

```php
// test/performance/db_test.php
test('qb_create performance vs manual SQL', function() {
    $data = ['name' => 'Test', 'email' => 'test@example.com'];
    
    // Test qb_create
    $start = microtime(true);
    for ($i = 0; $i < 1000; $i++) {
        [$sql, $params] = qb_create('users', $data);
    }
    $qb_time = microtime(true) - $start;
    
    // Test manual SQL
    $start = microtime(true);
    for ($i = 0; $i < 1000; $i++) {
        $sql = "INSERT INTO users (name, email) VALUES (?, ?)";
        $params = ['Test', 'test@example.com'];
    }
    $manual_time = microtime(true) - $start;
    
    // Should be no more than 2x slower
    assert($qb_time < ($manual_time * 2), 
        "qb_create too slow: {$qb_time}s vs {$manual_time}s");
});
```

## Best Practices

### 1. Use SQLite In-Memory for Database Tests

```php
function setup_test_db(): void
{
    static $setup = false;
    if ($setup) return;
    
    db('sqlite::memory:'); // Fast, isolated
    
    // Create tables...
    $setup = true;
}
```

### 2. Test Real Functions, Not Mocks

```php
// Good: Test actual function
$user_id = user_create($data);
assert($user_id > 0);

// Avoid: Mocking complexity
// $mock->expects($this->once())->method('create')...
```

### 3. Keep Tests Simple and Direct

```php
// Good: Clear and direct
test('user creation works', function() {
    $id = user_create(['username' => 'test']);
    assert($id > 0);
});

// Avoid: Complex setup/teardown
// class UserTest extends TestCase { ... }
```

### 4. Test Edge Cases

```php
test('handles empty input gracefully', function() {
    $result = validate_slug('', 'users');
    assert($result === 'Slug is required');
});

test('handles database constraint violations', function() {
    setup_test_db();
    user_create(['username' => 'duplicate']);
    
    // Test specific exception type and message
    assert_throws(function() {
        user_create(['username' => 'duplicate']);
    }, 'PDOException', 'UNIQUE constraint');
});

test('handles invalid argument types', function() {
    assert_throws(function() {
        calculate_age('not-a-date');
    }, 'InvalidArgumentException');
});

test('handles file not found errors', function() {
    assert_throws(function() {
        load_config('/nonexistent/file.php');
    }, 'RuntimeException', 'File not found');
});
```

### 5. Test Route Handlers End-to-End

```php
test('login route redirects on success', function() {
    setup_test_db();
    user_create(['username' => 'test', 'password' => 'pass']);
    
    $_POST = ['username' => 'test', 'password' => 'pass'];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    $handler = require __DIR__ . '/../../app/io/route/login.php';
    
    ob_start();
    $result = $handler();
    ob_end_clean();
    
    // Check for redirect header
    $headers = xdebug_get_headers() ?? [];
    $has_location = false;
    foreach ($headers as $header) {
        if (strpos($header, 'Location:') === 0) {
            $has_location = true;
            break;
        }
    }
    assert($has_location, 'Should redirect after login');
});
```

## Debugging Tests

### Add Debug Output

```php
test('debug failing test', function() {
    $result = some_function();
    
    // Add debug output
    echo "Debug: result = " . var_export($result, true) . "\n";
    
    assert($result === 'expected');
});
```

### Run Single Test

```php
// Comment out other tests, run single file
php test/database/user_test.php
```

### Use Error Reporting

```php
// Add to top of test files
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Advantages Over Framework Testing

| BADDAD Testing | PHPUnit/Other |
|---------------|---------------|
| 15 lines of code | 10,000+ lines |
| Built-in assert() + exception validation | Complex assertion classes |
| File-based | XML config |
| Direct testing | Mock complexity |
| Fast execution | Framework overhead |
| Easy debugging | Magic methods |
| No dependencies | Composer hell |

## Common Patterns

### Database Test Setup

```php
function setup_test_db(): void
{
    static $setup = false;
    if ($setup) return;
    
    db('sqlite::memory:');
    
    // Load schema
    $schema = file_get_contents(__DIR__ . '/../schema/test.sql');
    foreach (explode(';', $schema) as $statement) {
        if (trim($statement)) {
            dbq($statement);
        }
    }
    
    $setup = true;
}
```

### Cleanup Between Tests

```php
function cleanup_test_data(): void
{
    dbq("DELETE FROM users");
    dbq("DELETE FROM articles");
    dbq("DELETE FROM categories");
}

test('first test', function() {
    setup_test_db();
    cleanup_test_data();
    // ... test code
});
```

### Testing Database Constraints

```php
test('handles constraint violations properly', function() {
    setup_test_db();
    
    // Test UNIQUE constraint
    user_create(['username' => 'test', 'email' => 'test@example.com']);
    assert_throws(function() {
        user_create(['username' => 'test', 'email' => 'other@example.com']);
    }, 'PDOException', 'UNIQUE constraint');
    
    // Test NOT NULL constraint  
    assert_throws(function() {
        dbq("INSERT INTO users (username) VALUES (?)", ['incomplete']);
    }, 'PDOException', 'NOT NULL');
    
    // Test FOREIGN KEY constraint
    assert_throws(function() {
        article_create(['title' => 'Test', 'user_id' => 99999]);
    }, 'PDOException', 'FOREIGN KEY');
});
```

### Testing Route Error Handling

```php
test('routes handle errors appropriately', function() {
    // Test 404 errors
    assert_throws(function() {
        $handler = require __DIR__ . '/../../app/io/route/admin/articles/alter.php';
        $handler(99999); // Non-existent ID
    }, 'Error', '404 Not Found');
    
    // Test 401 Unauthorized (if auth is enabled)
    unset($_SERVER['HTTP_X_AUTH_USER']);
    assert_throws(function() {
        $handler = require __DIR__ . '/../../app/io/route/admin/articles/alter.php';
        $handler();
    }, 'Error', '401 Unauthorized');
    
    // Test 400 Bad Request
    assert_throws(function() {
        $_POST = ['invalid' => 'data'];
        $handler = require __DIR__ . '/../../app/io/route/admin/articles/alter.php';
        $handler();
    }, 'Error', '400 Bad Request');
});
```

### Testing File Operations

```php
test('file upload works', function() {
    $temp_file = tempnam(sys_get_temp_dir(), 'test');
    file_put_contents($temp_file, 'test content');
    
    // Mock $_FILES
    $_FILES['upload'] = [
        'name' => 'test.txt',
        'tmp_name' => $temp_file,
        'size' => filesize($temp_file),
        'error' => UPLOAD_ERR_OK
    ];
    
    $result = handle_file_upload();
    
    assert($result['success']);
    
    // Cleanup
    unlink($temp_file);
});
```