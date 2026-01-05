
# `bad\error`

**Bitmask-driven error channel ownership with request tracing**

`bad/error.php` is a **bootstrap actuator**, not a library. It does **not** try to be polite.

If something breaks, you *know exactly where and why*.

Requiring the file returns an **installer closure**.
Calling that closure installs PHP error, exception, and shutdown handlers according to a bitmask and returns a **single restore handle**.

If you install it, **you own the error channel**.

---

## Design

It is designed to be **owned**, **predictable**, and **loud by default**.

There is **no public function** and **no persistent API**.

Configuration lives entirely in the installer signature.
Behavior is composed with **bitmasks**, not options arrays:

* choose handlers: `HND_ERR | HND_EXC | HND_SHUT`
* choose outputs: `LOG_ERR | OSD_ERR`
* decide PHP noise: `ERR_SUPPRESS_PHP`
* decide fatal buffer handling: `OB_FLUSH_FATAL`

No objects.
No config files.
No DI.
No lifecycle abstractions.

One require.
One call.
One channel.

---

### Request identity

Each installation binds a **request ID** (generated or provided).

All diagnostics emitted during the request are prefixed with:

```
[req=<id>]
```

This makes logs grep-able and correlatable without log frameworks or context objects.

---

### Fatal exit semantics

Fatal exit is **explicit** and **owned**:

* dumps execution time and peak memory
* dumps request shape (URI, method, globals count)
* optionally flushes or discards output buffers
* terminates execution (`exit(1)`)

Shutdown handler exists to catch what `set_error_handler` cannot:
parse errors, compile errors, hard stops.

---

## Usage

### Minimal

```php
$install = require __DIR__ . '/bad/error.php';
$restore = $install();
```

Installs all handlers, logs to `error_log`.

---

### Production (quiet, log only)

```php
$install = require __DIR__ . '/bad/error.php';
$restore = $install(SET_ALL | LOG_ERR | ERR_SUPPRESS_PHP);
```

PHP internal handler suppressed.
Execution continues unless fatal.

---

### Debug (screen output, flush buffers on crash)

```php
$install = require __DIR__ . '/bad/error.php';
$restore = $install(SET_ALL | OSD_ERR | OB_FLUSH_FATAL);
```

Errors are printed immediately.
Output buffers flushed on fatal.

---

### Custom request ID (distributed tracing)

```php
$install = require __DIR__ . '/bad/error.php';
$restore = $install(SET_ALL | LOG_ERR, $trace_id);
```

All diagnostics share the provided trace ID.

---

### Temporary handler swap

```php
$install = require __DIR__ . '/bad/error.php';

$restore = $install(HND_ERR | LOG_ERR);
// ... risky operation ...
$restore();
```

No globals to clean.
Restoration is explicit.

---

## Reference

### Constants

| Constant           | Value | Purpose                              |
| ------------------ | ----: | ------------------------------------ |
| `HND_ERR`          |     1 | Install `set_error_handler`          |
| `HND_EXC`          |     2 | Install `set_exception_handler`      |
| `HND_SHUT`         |     4 | Install `register_shutdown_function` |
| `SET_ALL`          |     7 | All handlers                         |
| `ERR_SUPPRESS_PHP` |     8 | Suppress PHP internal error output   |
| `LOG_ERR`          |    16 | Write to `error_log()`               |
| `OSD_ERR`          |    32 | Print to stdout/stderr               |
| `OB_FLUSH_FATAL`   |    64 | Flush output buffers on fatal        |
| `PHP_FATAL_ERRORS` |     — | Bitmask of fatal error types         |

---

### Installer signature

```php
(int $behave = SET_ALL | LOG_ERR, ?string $request_id = null) : callable
```

**Returns:**
A `callable` restore handle.
Calling it restores previous PHP handlers.

There is **no return structure** and **no retained state**.

---

### Internal channels (non-public)

#### `report(int $behave, string $message): void`

Conditional output channel:

* prints if `OSD_ERR`
* logs if `LOG_ERR`

#### `fatal_exit(int $behave, string $prefix, float $start): never`

Owned termination path:

* emits final execution summary
* handles output buffers
* exits with status `1`

---

## Log examples

### Error (`set_error_handler`)

```
[req=a1b2c3d4] Error (errno=2) Undefined variable $foo in /app/io/route/user.php:42
```

---

### Uncaught exception (`set_exception_handler`)

```
[req=a1b2c3d4] Uncaught (InvalidArgumentException) Bad Request in /app/lib/io.php:18
[req=a1b2c3d4] #0 /app/io/route/api.php(23): io_in('')
#1 /app/index.php(45): include('/app/io/route/...')
#2 {main}
[req=a1b2c3d4] EXEC:0.0034 MEM:2097152 URI:/api/user/99 REMOTE:192.168.1.50 AGENT:Mozilla/5.0 METHOD:POST #GET:1 #POST:3 #SESSION:2 #COOKIES:1 #FILES:0
```

---

### Fatal shutdown (`register_shutdown_function`)

```
[req=a1b2c3d4] Shutdown (type=4) syntax error, unexpected '}' in /app/io/route/broken.php:17
[req=a1b2c3d4] EXEC:0.0008 MEM:524288 URI:/broken REMOTE:127.0.0.1 AGENT:curl/7.68.0 METHOD:GET #GET:0 #POST:0 #SESSION:0 #COOKIES:0 #FILES:0
```

---

### Warning suppressed (`ERR_SUPPRESS_PHP`)

```
[req=a1b2c3d4] Error (errno=8) Undefined array key "missing" in /app/io/route/index.php:31
```

No PHP output.
Execution continues.

---

### Multiple errors, one request

```
[req=f7e8d9c0] Error (errno=8) Undefined array key "name" in /app/lib/auth.php:55
[req=f7e8d9c0] Error (errno=2) file_get_contents(): Filename cannot be empty in /app/lib/io.php:89
[req=f7e8d9c0] Uncaught (RuntimeException) include:/app/io/route/fail.php in /app/lib/io.php:47
[req=f7e8d9c0] #0 /app/index.php(32): io_run(Array, Array, 6)
#1 {main}
[req=f7e8d9c0] EXEC:0.0127 MEM:4194304 URI:/fail REMOTE:10.0.0.1 AGENT:- METHOD:GET #GET:2 #POST:0 #SESSION:5 #COOKIES:3 #FILES:0
```

One request ID.
One execution story.


