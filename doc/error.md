# `bad\error`

One-file bootstrap that **takes over PHP's error channel for the request** and tags everything with a **request ID** (`[req=...]`).

`require` returns an **installer**. Calling it installs handlers (via bitmask) and returns a **restore** handle.

```php
$install = require __DIR__ . '/bad/error.php';
$restore = $install();     // default: all handlers + ERR_LOG
// ... run app ...
$restore();                // restore previous handlers
```

## What it catches

* **Errors** (`set_error_handler`): logs/prints and keeps going (unless PHP dies)
* **Uncaught exceptions**: logs/prints + trace + **hard exit**
* **Fatal shutdown**: catches parse/compile/core fatals at shutdown + **hard exit**

On fatal/uncaught it emits a one-line request summary (time/memory + request shape). HTTP 500 is opt-in via `FATAL_HTTP_500`.

## Flags (bitmask)

### Handler ownership (`HND_*`)

Think of `HND_*` as: **which PHP hook(s) you claim** for this request. Combine with `|`.

* `HND_ERR` (1) — install `set_error_handler` (non-fatal runtime errors)
* `HND_EXC` (2) — install `set_exception_handler` (uncaught exceptions)
* `HND_SHUT` (4) — install `register_shutdown_function` (fatal errors found at shutdown)
* `HND_ALL` (7) — shorthand for "all of the above"

Example mental model:

* "I only want to own errors" → `HND_ERR`
* "I only care about fatals" → `HND_SHUT`
* "Own everything" → `HND_ALL`

### Output / behavior

* `ALLOW_INTERNAL` (8) — let PHP's internal error handler run too (default: suppressed)
* `ERR_LOG` (16) — write to `error_log`
* `ERR_OSD` (32) — print to output (stdout/stderr)
* `FATAL_OB_FLUSH` (64) — flush output buffers on fatal (otherwise discard)
* `FATAL_HTTP_500` (128) — emit HTTP 500 on fatal/uncaught (if headers not sent)

## Footguns / antiframework decisions

* **You own the channel.** By default PHP's error output is suppressed. If you omit both `ERR_LOG` and `ERR_OSD`, you create *silence*.
* **`ERR_OSD` is not for prod.** It can leak paths/stack traces/request details to the client.
* **Buffer policy is sharp:**

  * `FATAL_OB_FLUSH` can leak partial output
  * default discards buffered output (cleaner, but less debug signal)
* **Untrusted request fields go into logs** (URI/agent). Don't assume logs are "safe text."
* **Restore is explicit and order-sensitive** (nested installs should be restored LIFO).
* **Not a library.** No config arrays, no objects, no DI, no hooks—just "install / restore".

## Log samples

### Error (non-fatal)

```
[req=a1b2c3d4] Error (errno=2) Undefined variable $foo in /app/io/route/user.php:42
```

### Warning/notice (PHP output suppressed by default)

```
[req=a1b2c3d4] Error (errno=8) Undefined array key "missing" in /app/io/route/index.php:31
```

*(no PHP native output; your app continues)*

### Uncaught exception (hard exit)

```
[req=a1b2c3d4] Uncaught (InvalidArgumentException) Bad Request in /app/lib/io.php:18
[req=a1b2c3d4] #0 /app/io/route/api.php(23): io_in('')
[req=a1b2c3d4] #1 /app/index.php(45): include('/app/io/route/...')
[req=a1b2c3d4] #2 {main}
[req=a1b2c3d4] EXEC:0.0034 MEM:2097152 URI:/api/user/99 REMOTE:192.168.1.50 AGENT:Mozilla/5.0 METHOD:POST #GET:1 #POST:3 #SESSION:2 #COOKIES:1 #FILES:0
```

### Fatal shutdown (parse/compile/core fatal)

```
[req=a1b2c3d4] Shutdown (type=4) syntax error, unexpected '}' in /app/io/route/broken.php:17
[req=a1b2c3d4] EXEC:0.0008 MEM:524288 URI:/broken REMOTE:127.0.0.1 AGENT:curl/7.68.0 METHOD:GET #GET:0 #POST:0 #SESSION:0 #COOKIES:0 #FILES:0
```

### Multiple issues, one request ID

```
[req=f7e8d9c0] Error (errno=8) Undefined array key "name" in /app/lib/auth.php:55
[req=f7e8d9c0] Error (errno=2) file_get_contents(): Filename cannot be empty in /app/lib/io.php:89
[req=f7e8d9c0] Uncaught (RuntimeException) include:/app/io/route/fail.php in /app/lib/io.php:47
[req=f7e8d9c0] #0 /app/index.php(32): io_run(Array, Array, 6)
[req=f7e8d9c0] #1 {main}
[req=f7e8d9c0] EXEC:0.0127 MEM:4194304 URI:/fail REMOTE:10.0.0.1 AGENT:- METHOD:GET #GET:2 #POST:0 #SESSION:5 #COOKIES:3 #FILES:0
```