# Error Handling in BADHAT

Native PHP error mechanisms. No middleware, no wrappers.

Three layers routed through one installer:

- `set_error_handler()` for notices/warnings
- `set_exception_handler()` for uncaught exceptions  
- `register_shutdown_function()` for fatal errors

---

## Constants

```php
const EH_ERROR     = 1;   // set_error_handler
const EH_EXCEPTION = 2;   // set_exception_handler
const EH_SHUTDOWN  = 4;   // register_shutdown_function
const EH_SUPPRESS  = 8;   // suppress error propagation

const EH_OSD = 16;        // output to screen (echo)
const EH_LOG = 32;        // output to error_log

const EH_HANDLE_ALL = EH_ERROR | EH_EXCEPTION | EH_SHUTDOWN;
```

---

## Functions

### osd

```php
function osd(int $behave, $message): void
```

Output dispatcher. Routes message based on flags.

```php
osd(EH_LOG, 'to error log only');
osd(EH_OSD, 'to screen only');
osd(EH_LOG | EH_OSD, 'to both');
```

### badhat_install_error_handlers

```php
function badhat_install_error_handlers(int $behave = EH_HANDLE_ALL, ?string $request_id = null): string
```

Installs error handlers. Returns request ID.

```php
// Full installation with logging
$req_id = badhat_install_error_handlers(EH_HANDLE_ALL | EH_LOG);

// Development: screen output
$req_id = badhat_install_error_handlers(EH_HANDLE_ALL | EH_OSD);

// Both outputs
$req_id = badhat_install_error_handlers(EH_HANDLE_ALL | EH_LOG | EH_OSD);

// Custom request ID
$req_id = badhat_install_error_handlers(EH_HANDLE_ALL | EH_LOG, 'abc123');

// Selective handlers
badhat_install_error_handlers(EH_EXCEPTION | EH_SHUTDOWN | EH_LOG);
```

---

## Log Format

All handlers use consistent format:

```
[req=<id>] <Type> (<code>) <message> in <file>:<line>
```

Examples:
```
[req=a1b2c3d4] Error (errno=2) Missing file in /app/route/users.php:42
[req=a1b2c3d4] Uncaught (RuntimeException) DB failed in /app/db.php:15
[req=a1b2c3d4] Shutdown (type=1) Memory exhausted in /app/render.php:200
```

---

## Fatal Exit

On uncaught exception or fatal error, logs context then exits:

```
[req=a1b2c3d4] EXEC:0.0234 MEM:2097152 URI:/users REMOTE:192.168.1.1 AGENT:Mozilla/5.0 METHOD:GET #GET:2 #POST:0 #SESSION:3 #COOKIES:1 #FILES:0
```

Then:
- Cleans output buffer
- Sets HTTP 500
- Exits with code 1

---

## Handler Behavior

### Error Handler (EH_ERROR)

```php
// Logs error, optionally suppresses propagation
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    osd($behave, sprintf($format, 'Error', "errno=$errno", ...));
    return (bool)(EH_SUPPRESS & $behave);
});
```

### Exception Handler (EH_EXCEPTION)

```php
// Logs exception + trace, triggers fatal exit
set_exception_handler(function(Throwable $e) {
    osd($behave, sprintf($format, 'Uncaught', get_class($e), ...));
    osd($behave, $prefix . $e->getTraceAsString());
    $fatal_exit();
});
```

### Shutdown Handler (EH_SHUTDOWN)

```php
// Catches E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR
register_shutdown_function(function() {
    $err = error_get_last();
    if (!$err || !($err['type'] & (E_ERROR | E_PARSE | ...)))
        return;
    osd($behave, sprintf($format, 'Shutdown', "type={$err['type']}", ...));
    $fatal_exit();
});
```

---

## Error Routing

| Level | Mechanism | When |
|-------|-----------|------|
| debug | `error_log()` | diagnostics |
| notice | `trigger_error(E_USER_NOTICE)` | unexpected but recoverable |
| warning | `trigger_error(E_USER_WARNING)` | deprecated, fallback used |
| error | `throw` (4xx) | client/application error |
| critical | `throw` (5xx) | server/logic error |
| emergency | `exit()` | shutdown, maintenance |

---

## Usage Examples

### Debug

```php
error_log("DEBUG: Cache miss key=$key");
```

### Notice

```php
trigger_error("Missing lang, using default", E_USER_NOTICE);
```

### Warning

```php
trigger_error("Using fallback endpoint", E_USER_WARNING);
```

### Error (4xx)

```php
throw new InvalidArgumentException("Invalid user ID", 400);
```

### Critical (5xx)

```php
throw new RuntimeException("Database connection failed", 500);
```

---

## Configuration Patterns

### Production

```php
badhat_install_error_handlers(EH_HANDLE_ALL | EH_LOG);
```

### Development

```php
badhat_install_error_handlers(EH_HANDLE_ALL | EH_OSD | EH_LOG);
```

### Suppress Errors (continue execution)

```php
badhat_install_error_handlers(EH_HANDLE_ALL | EH_SUPPRESS | EH_LOG);
```

---

## Design

Let exceptions bubble. No local try/catch.

```php
// Wrong
try {
    $result = qp($sql, $params)->fetch();
} catch (PDOException $e) {
    // breaks the pattern
}

// Right
$result = qp($sql, $params)->fetch();
// exception → handler → log → 500
```