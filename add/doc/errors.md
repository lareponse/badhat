# Error Handling in BADGE

BADGE uses PHP’s native error mechanisms to simulate `PSR` log levels, provide meaningful HTTP responses, and maintain full control without abstraction.

No middleware. No annotations. No wrappers. Just procedural handling, routed through three native layers:

* `trigger_error()` for notices and warnings
* `throw` for application errors
* `exit()` for shutdown conditions

---

## Log Level: `debug`, `info`

Use for diagnostics, startup traces, instrumentation.

```php
error_log("DEBUG: Starting cron task");
error_log("INFO: User $id authenticated");
```

---

## Log Level: `notice`

Use when the system can continue, but something unexpected or missing occurred.

```php
trigger_error("Missing preferred language", E_USER_NOTICE);
trigger_error("No cache present, regenerating", E_USER_NOTICE);
```

---

## Log Level: `warning`

Use for deprecated usage, external service delays, recoverable system issues.

```php
trigger_error("Using fallback API credentials", E_USER_WARNING);
trigger_error("Slow response from mail server", E_USER_WARNING);
```

---

## Log Level: `error`

Use `throw` for application-level errors that should abort current flow and be caught by `set_exception_handler()`.

```php
throw new RuntimeException("500 Unable to connect to database", 500);
throw new InvalidArgumentException("400 Invalid query parameter");
```

---

## Log Level: `critical`

Use for internal logic errors or corrupted application state.

```php
throw new Error("Inconsistent session state", 500);
throw new LogicException("Missing required configuration", 500);
```

---

## Log Level: `emergency`

Use for system shutdown, maintenance mode, or overload conditions.

```php
exit("503 Service Unavailable – Maintenance in progress");
exit("503 Application overloaded – Try again later");
```

---

## HTTP Code Routing

Error and exception messages can begin with a status code prefix. If so, BADGE will extract and route them accordingly.

```php
throw new RuntimeException("404 User not found");
throw new Error("500 Unexpected token in response");
```

These will be parsed automatically and sent as structured HTTP responses.

---

## Logging Format

Each error, exception, or shutdown is logged in a consistent format:

```
UNCAUGHT RuntimeException a1b2c3d4: 404 User not found in /path/to/file.php:123
SHUTDOWN FATAL e4f5g6h7: [1] Segmentation fault in /path/core.php:99
```

Use `base_convert(mt_rand(), 10, 36)` to generate short error IDs.

---

## No Try/Catch

BADGE does not use try/catch blocks. Exceptions must be allowed to bubble to the top, where they are caught and logged by the global `set_exception_handler()`.

---

## Fatal Error Handling

Fatal errors not caught by PHP (E\_ERROR, E\_PARSE, etc.) are handled in a `register_shutdown_function()` block.

```php
// Fatal error example
undefined_function();
```

If an error is fatal and headers are not yet sent, BADGE will respond with:

```
500 FATAL <id>: <message>
```

---

## Final Rule Set

* Use `error_log()` for debug and info
* Use `trigger_error()` for notice and warning
* Use `throw` for error and critical
* Use `exit()` for emergency
* Never suppress or silence errors
* Always log, always format, always route

This is full-spectrum error control, without a single class or framework.
