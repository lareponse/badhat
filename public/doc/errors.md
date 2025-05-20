# Error & Exception Handling in BADGE

This guide outlines the philosophy, practices, and PHP 8.4-compliant mechanisms for error handling in the BADGE framework, consistent with ADDBAD principles: no boilerplate, no abstraction, just raw control.


## PHP Compatibility Note

As of PHP 8.4, `trigger_error(..., E_USER_ERROR)` is deprecated. The engine now requires exceptions for fatal application errors. BADGE adapts accordingly, without sacrificing minimalism.


## Core Principles

* All errors must pass through one of three native gates:

  * `set_error_handler()` – Notices, warnings
  * `set_exception_handler()` – Exceptions and `Error`
  * `register_shutdown_function()` – Fatal errors (E\_ERROR, E\_PARSE, etc.)
* No custom classes, middlewares, or wrappers
* Logging is centralized, and tied to PSR log levels


## Setup

```php
require_once __DIR__ . '/add/bad/errors.php';
```

## Error Type to Log Level Mapping

| Log Level       | Description                          | PHP Mechanism                        |
|-----------------|--------------------------------------|--------------------------------------|
| `debug`         | dev-only context                     | `error_log()`                        |
| `info`          | Informational context                | `error_log()`                        |
| `notice`        | Non-breaking minor issues            | `trigger_error(..., E_USER_NOTICE)`  |
| `warning`       | Unexpected but recoverable problems  | `trigger_error(..., E_USER_WARNING)` |
| `error`         | Application-level crash, recoverable | `throw new Exception()`              |
| `critical`      | Internal crash, corrupted state      | `throw new Error()`                  |
| `emergency`     | Unavailable service                  | `exit('503 ...')`                    |


### Debug message

```php
error_log("DEBUG: Starting cache warm-up");
```

### Info message

```php
error_log("INFO: Cache warm-up completed");
```
### Notice-level

```php
trigger_error("User agent not detected", E_USER_NOTICE);
```

### Warning-level

```php
trigger_error("Deprecated API usage", E_USER_WARNING);
```

### Application Error

```php
throw new RuntimeException("Failed to load config");
```

### Misconfiguration (Logic Error)

```php
throw new LogicException("Missing DB credentials");
```

### System Crash

```php
throw new Error("Unrecoverable system failure");
```

### Emergency Shutdown

```php
exit("Service Unavailable – Maintenance in progress");
```

---

## Do Not

* Do not use `trigger_error(..., E_USER_ERROR)` – deprecated in PHP 8.4+
* Do not leave exceptions unhandled
* Do not log inline unless truly necessary
* Do not rely on try/catch for flow control

---

## Use with `respond()`

All `throw` paths must be routed to `respond()` via `set_exception_handler()` so the app exits cleanly and formats the output (HTML, JSON, etc.) based on the environment or route.

---

## File Placement

* `add/bad/errors.php` – Load in bootstrap
* `add/bad/respond.php` – Unified response output

---

## Final Rule of Thumb

Warn with `trigger_error()`.
Crash with `throw`.
Die with `exit()`.
Always log. Always route.

This guide ensures maximum clarity, compatibility, and runtime correctness — without a single line of abstraction theater.
