# Error Handling in BADHAT

BADHAT uses PHP’s native error mechanisms to simulate `PSR` log levels, provide meaningful HTTP responses, and maintain full control without abstraction.

No middleware. No annotations. No wrappers. Just procedural handling, routed through three native layers:

* `trigger_error()` for notices and warnings
* `throw` for application errors
* `exit()` for shutdown conditions

## Rule Set

* Use `error_log()` for debug and info
* Use `trigger_error()` for notice and warning
* Use `throw` for error and critical (depending on the code)
* Use `exit()` for emergency
* Never suppress or silence errors
* Always log, always format, always route


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

## Log Level: `error`, code 4xx

Use `throw` for application-level errors that should abort current flow and be caught by `set_exception_handler()`.

```php
throw new InvalidArgumentException("Invalid query parameter", 400);
```

---

## Log Level: `critical`, code 5xx

Use for internal logic errors or corrupted application state.

```php
throw new DomainException("Missing required configuration", 500);
throw new RuntimeException("Unable to connect to database", 500);
throw new Error("Inconsistent session state", 500);
```

---

## Log Level: `emergency`

Use for system shutdown, maintenance mode, or overload conditions.
Always respond with a 503 Service Unavailable status.

```php
exit("Service Unavailable – Maintenance in progress");
exit("Application overloaded – Try again later");
```

## No Try/Catch

BADHAT does not use try/catch blocks. Exceptions must be allowed to bubble to the top, where they are caught and logged by the global `set_exception_handler()`.

---

## Fatal Error Handling

Fatal errors not caught by PHP (E\_ERROR, E\_PARSE, etc.) are handled in a `register_shutdown_function()` block.

```php
// Fatal error example
undefined_function();
```

If an error is fatal and headers are not yet sent, BADHAT will respond with:

```
500 FATAL <id>: <message>
```

---

