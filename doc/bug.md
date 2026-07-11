# bad\bug

`bad\bug` installs request-scoped PHP error and fatal-shutdown logging.

It returns one public value: an installer closure. Calling that installer creates
private per-install state and returns a restore closure for the previous PHP
error handler.

It handles only two PHP surfaces:

- runtime PHP errors through `set_error_handler()`
- fatal shutdown errors through `register_shutdown_function()`

It does not install a global exception handler. If an uncaught `Throwable`
escapes the application boundary and PHP turns it into a fatal shutdown error,
`bad\bug` may log that shutdown record.

---

## Install

```php
use const bad\bug\HND_ALL;

$install = require '/add/badhat/bug.php';
$restore = $install(HND_ALL);
```

The default behavior is `HND_ALL`, which currently means runtime errors plus
fatal shutdown errors.

Every log line carries a request tag:

```text
[req=1234-a1b2c3d4] ...
```

If no request ID is supplied, the installer generates one from the process ID
and a high-resolution time value.

---

## Installer Signature

```php
$restore = $install(
    int $behave = HND_ALL,
    ?string $request_id = null,
    int $message_limit = 4096,
    int $trace_limit = 64,
    ?int $fatal_mask = FATAL_MASK
);
```

`$behave` is a bitmask of handler and logging flags.

`$request_id` is copied into each log line. Pass one when the surrounding
application already has a request or correlation ID.

`$message_limit` caps the scrubbed message. Use a negative value to disable
message truncation.

`$trace_limit` caps emitted stack frames when `LOG_WITH_TRACE` is enabled. Use a
negative value to disable frame truncation.

`$fatal_mask` decides which `error_get_last()` types count as fatal shutdown
records. `null` restores the default `FATAL_MASK`.

---

## Runtime Errors

```php
use const bad\bug\HND_ERR;

$restore = $install(HND_ERR);
```

When `HND_ERR` is set, `bad\bug` installs a PHP error handler. If the current
`error_reporting()` mask includes the error code, it logs one `HND_ERR` line.

The handler suppresses PHP's internal handler by default. Add
`ALLOW_INTERNAL` when PHP should continue with its own error handling after
`bad\bug` logs the record:

```php
use const bad\bug\ALLOW_INTERNAL;
use const bad\bug\HND_ERR;

$restore = $install(HND_ERR | ALLOW_INTERNAL);
```

---

## Fatal Shutdowns

```php
use const bad\bug\HND_SHUT;

$restore = $install(HND_SHUT);
```

When `HND_SHUT` is set, `bad\bug` registers a shutdown function. At shutdown it
reads `error_get_last()`, checks the error type against `$fatal_mask`, and logs
one `HND_SHUT` line for matching fatal records.

By default, the fatal mask is:

```php
E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR
```

Shutdown handlers cannot be unregistered in PHP. The restore closure can restore
the previous runtime error handler, but registered shutdown callbacks remain
active until process end.

---

## Output Buffers

Fatal shutdown logging can also drain output buffers:

```php
use const bad\bug\FATAL_OB_CLEAN;
use const bad\bug\FATAL_OB_FLUSH;
use const bad\bug\HND_ALL;

$restore = $install(HND_ALL | FATAL_OB_CLEAN); // discard buffered output
$restore = $install(HND_ALL | FATAL_OB_FLUSH); // flush buffered output
```

Buffer cleanup runs only from the fatal-shutdown path. It does not run for a
runtime warning or notice.

If both flags are set, `FATAL_OB_CLEAN` wins.

---

## Trace And Diagnostics

```php
use const bad\bug\HND_ALL;
use const bad\bug\LOG_WITH_TRACE;

$restore = $install(HND_ALL | LOG_WITH_TRACE);
```

Without `LOG_WITH_TRACE`, each logged error or fatal shutdown emits one main
line.

With `LOG_WITH_TRACE`, `bad\bug` emits `FRAME` lines after the main line. Fatal
shutdown records also emit one `PEEK` diagnostics line with elapsed time, peak
memory, SAPI, included file count, PID, output-buffer depth, and header-sent
location.

`LOG_BLIND` redacts the message, file, and line fields and suppresses trace and
diagnostic detail:

```php
use const bad\bug\HND_ALL;
use const bad\bug\LOG_BLIND;

$restore = $install(HND_ALL | LOG_BLIND);
```

---

## Log Format

Every line uses the same shape:

```text
[req=ID] LABEL #CODE (file:line) [SOURCE MESSAGE]
```

Current labels are:

| Label      | Meaning                                      |
| ---------- | -------------------------------------------- |
| `HND_ERR`  | runtime PHP error                            |
| `HND_SHUT` | fatal shutdown error                         |
| `PEEK`     | fatal-shutdown diagnostics with trace enabled |
| `FRAME`    | stack frame with trace enabled               |

Messages are scrubbed before logging. Control characters are replaced with
spaces, non-scalar values are represented by their debug type, and long messages
are capped by `$message_limit` with `@TRUNCATED@`.

When `LOG_BLIND` is set, sensitive fields are replaced with `@REDACTED@`.

---

## Restore

```php
$restore();
```

The restore closure takes no arguments. If this install replaced a previous PHP
error handler, it reinstalls that previous handler. If PHP had no previous error
handler, it calls `restore_error_handler()`.

If `HND_ERR` was not installed, restore is a no-op.

Shutdown callbacks cannot be restored or removed.

---

## Reference

| Flag             | Value | What it does                                      |
| ---------------- | ----: | ------------------------------------------------- |
| `HND_ERR`        |     1 | Handle runtime PHP errors                         |
| `HND_SHUT`       |     4 | Handle fatal shutdown errors                      |
| `HND_ALL`        |     5 | `HND_ERR \| HND_SHUT`                             |
| `LOG_WITH_TRACE` |     8 | Emit stack frames; add fatal diagnostics          |
| `LOG_BLIND`      |    16 | Redact fields and suppress trace diagnostics      |
| `ALLOW_INTERNAL` |    32 | Let PHP's internal runtime error handler continue |
| `FATAL_OB_FLUSH` |    64 | Flush output buffers on fatal shutdown            |
| `FATAL_OB_CLEAN` |   128 | Discard output buffers on fatal shutdown          |
