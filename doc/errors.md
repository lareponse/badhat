# Error Handling in BADHAT

Native PHP error mechanisms. No middleware, no wrappers.

Three layers routed through one installer:

- `set_error_handler()` for notices/warnings
- `set_exception_handler()` for uncaught exceptions  
- `register_shutdown_function()` for fatal errors

---

## Constants

```php
const EH_ERROR     = 1;   // Install error handler
const EH_EXCEPTION = 2;   // Install exception handler
const EH_SHUTDOWN  = 4;   // Install shutdown handler
const EH_SUPPRESS  = 8;   // Suppress error propagation

const EH_HANDLE_ALL = EH_ERROR | EH_EXCEPTION | EH_SHUTDOWN;
```

---

## Installation

```php
function badhat_install_error_handlers(int $behave = EH_HANDLE_ALL, ?string $request_id = null): string
```

**Returns:** request ID (generated if not provided)

```php
// Bootstrap (index.php)
require 'add/badhat/error.php';
$req_id = badhat_install_error_handlers();

// Custom: only exception + shutdown
badhat_install_error_handlers(EH_EXCEPTION | EH_SHUTDOWN);

// Suppress errors from bubbling (return true from handler)
badhat_install_error_handlers(EH_HANDLE_ALL | EH_SUPPRESS);

// With known request ID
badhat_install_error_handlers(EH_HANDLE_ALL, 'abc123');
```

---

## Log Format

All handlers log with consistent format:

```
[req=<id>] <Type> (<code>) <message> in <file>:<line>
```

Example:
```
[req=a1b2c3d4] Error (errno=2) Missing file in /app/route/users.php:42
[req=a1b2c3d4] Uncaught (RuntimeException) DB connection failed in /app/db.php:15
[req=a1b2c3d4] Shutdown (type=1) Allowed memory exhausted in /app/render.php:200
```

---

## Fatal Exit

On uncaught exception or fatal error, logs execution context:

```
[req=a1b2c3d4] EXEC:0.0234 MEM:2097152 URI:/users REMOTE:192.168.1.1 AGENT:Mozilla/5.0 METHOD:GET GET:2 POST:0 SESSION:3 COOKIES:1 FILES:0
```

Then:
- Cleans output buffer
- Sets HTTP 500
- Exits with code 1

---

## Error Routing Rules

| Level      | Mechanism                | When                                    |
|------------|--------------------------|----------------------------------------|
| debug/info | `error_log()`            | Diagnostics, traces                    |
| notice     | `trigger_error(E_USER_NOTICE)` | Unexpected but recoverable       |
| warning    | `trigger_error(E_USER_WARNING)` | Deprecated, fallback used       |
| error      | `throw` (4xx)            | Client/application error               |
| critical   | `throw` (5xx)            | Server/logic error                     |
| emergency  | `exit()`                 | Shutdown, maintenance                  |

---

## Usage Examples

### Debug/Info

```php
error_log("DEBUG: Cache miss for key=$key");
error_log("INFO: User $id authenticated");
```

### Notice

```php
trigger_error("Missing preferred language, using default", E_USER_NOTICE);
trigger_error("Config file not found, using defaults", E_USER_NOTICE);
```

### Warning

```php
trigger_error("Using fallback API endpoint", E_USER_WARNING);
trigger_error("Slow query: {$ms}ms", E_USER_WARNING);
```

### Error (4xx)

```php
throw new InvalidArgumentException("Invalid user ID", 400);
throw new BadMethodCallException("POST required", 405);
```

### Critical (5xx)

```php
throw new DomainException("Missing required config", 500);
throw new RuntimeException("Database connection failed", 500);
throw new Error("Inconsistent state", 500);
```

### Emergency

```php
exit("Service Unavailable – Maintenance");
exit("Rate limit exceeded");
```

---

## No Try/Catch

BADHAT routes exceptions to the global handler. **Do not catch locally.**

```php
// Wrong
try {
    $result = qp($sql, $params)->fetch();
} catch (PDOException $e) {
    // local handling breaks the pattern
}

// Right: let it bubble
$result = qp($sql, $params)->fetch();
// Exception goes to set_exception_handler → logged → 500 response
```

---

## Selective Installation

```php
// Production: all handlers
badhat_install_error_handlers();

// Development: let errors show (suppress handler)
badhat_install_error_handlers(EH_EXCEPTION | EH_SHUTDOWN);

// API: exception + shutdown only, errors as JSON
badhat_install_error_handlers(EH_EXCEPTION | EH_SHUTDOWN);
set_error_handler(function($errno, $msg) {
    io_die(500, json_encode(['error' => $msg]));
});
```

---

## Handler Behavior

### Error Handler (EH_ERROR)

```php
set_error_handler(function(int $errno, string $errstr, string $errfile, int $errline) {
    error_log(sprintf($format, 'Error', "errno=$errno", $errstr, $errfile, $errline));
    return (bool)(EH_SUPPRESS & $behave);  // true = suppress, false = propagate
});
```

### Exception Handler (EH_EXCEPTION)

```php
set_exception_handler(function(Throwable $e) {
    error_log(sprintf($format, 'Uncaught', get_class($e), $e->getMessage(), ...));
    error_log($prefix . $e->getTraceAsString());
    fatal_exit();
});
```

### Shutdown Handler (EH_SHUTDOWN)

```php
register_shutdown_function(function() {
    $err = error_get_last();
    if (!$err || !($err['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR)))
        return;
    error_log(sprintf($format, 'Shutdown', "type={$err['type']}", ...));
    fatal_exit();
});
```

---

## Summary

- Install once at bootstrap
- Use native mechanisms (`trigger_error`, `throw`, `exit`)
- Never suppress, always log
- Let exceptions bubble
- Request ID traces entire lifecycle