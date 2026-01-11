# `bad\error`

One-file bootstrap that **takes over PHP’s error channel for the request** and tags everything with a **request ID** (`[req=...]`).

`require` returns an **installer**. Calling it installs handlers (via bitmask) and returns a **restore** handle.

```php
$install = require __DIR__ . '/bad/error.php';
$restore = $install();     // default: all handlers
// ... run app ...
$restore();                // restore previous handlers
```

## What it catches

* **Errors** (`set_error_handler`): logged and execution continues (unless PHP dies)
* **Uncaught exceptions**: logged + optional trace + **hard exit**
* **Fatal shutdown**: catches parse/compile/core fatals at shutdown + **hard exit**

On fatal / uncaught exception it emits a **one-line request summary**
(time, peak memory, request method/URI/remote).

HTTP 500 is **opt-in** via `FATAL_HTTP_500`.

---

## Flags (bitmask)

### Handler ownership (`HND_*`)

Which PHP hook(s) you claim for this request. Combine with `|`.

| Flag       | Value | PHP hook                     | Catches                       |
| ---------- | ----- | ---------------------------- | ----------------------------- |
| `HND_ERR`  | 1     | `set_error_handler`          | non-fatal runtime errors      |
| `HND_EXC`  | 2     | `set_exception_handler`      | uncaught exceptions           |
| `HND_SHUT` | 4     | `register_shutdown_function` | parse / compile / core fatals |
| `HND_ALL`  | 7     | all above                    | everything                    |

---

### Message & fatal behavior

| Flag             | Value | Effect                            |
| ---------------- | ----- | --------------------------------- |
| `MSG_WITH_TRACE` | 8     | attach stack trace                |
| `ALLOW_INTERNAL` | 16    | let PHP native handler run too    |
| `FATAL_OB_FLUSH` | 32    | flush output buffers on fatal     |
| `FATAL_OB_CLEAN` | 64    | discard output buffers on fatal   |
| `FATAL_HTTP_500` | 128   | emit HTTP 500 on fatal / uncaught |

**Notes**

* Logging always goes through `error_log()`
* There is **no on-screen output mode** in this version
* Trace emission is explicit and flag-controlled

---

## Environment profiles

### Prod

```php
$restore = $install(HND_ALL | FATAL_HTTP_500 | FATAL_OB_CLEAN);
```

* error_log only
* clean output (buffers discarded)
* HTTP 500 on fatal
* no stack traces

### Dev

```php
$restore = $install(HND_ALL | MSG_WITH_TRACE | FATAL_OB_FLUSH | ALLOW_INTERNAL );
```

* traces included
* PHP native handler also runs
* partial output preserved for inspection

### Toggle via env

```php
$behave = HND_ALL;

getenv('DEV')
    ? $behave |= MSG_WITH_TRACE | FATAL_OB_FLUSH | ALLOW_INTERNAL
    : $behave |= FATAL_HTTP_500 | FATAL_OB_CLEAN;

$restore = $install($behave);
```

Behavior is **baked at install time** — no conditionals inside handlers.
Request ID tags all log lines for correlation.
Restore callable cleanly re-installs previous handlers (LIFO-safe).

`ALLOW_INTERNAL` lets PHP’s native formatting coexist with custom logging.
Useful in dev; usually omitted in prod.

---

## Footguns / antiframework decisions

* **You own the channel.** By default, PHP error output is suppressed.
* **No OSD mode.** If it’s visible, it’s because PHP itself prints it.
* **Traces are expensive.** Use `MSG_WITH_TRACE` deliberately.
* **Buffer policy is sharp:**

  * `FATAL_OB_FLUSH` may leak partial output
  * `FATAL_OB_CLEAN` is safer and quieter
* **Untrusted request data goes into logs** (URI, method, remote).
* **Restore is explicit and order-sensitive.**
* **Not a library.** No config arrays, no objects, no hooks — just *install / restore*.

---

## Log samples (updated)

### Runtime error (non-fatal)

```
[req=1a2b3c] ERR (errno=2) Undefined variable $foo in /app/io/route/user.php:42
```

*(execution continues)*

---

### Uncaught exception (hard exit)

Without `MSG_WITH_TRACE`:

```
[req=1a2b3c] FATAL (exception:InvalidArgumentException) Bad Request in /app/lib/io.php:18 [3.41ms 2048KiB POST /api/user @192.168.1.50]
```

With `MSG_WITH_TRACE`:

```
[req=1a2b3c] FATAL (exception:InvalidArgumentException) Bad Request in /app/lib/io.php:18 [3.41ms 2048KiB POST /api/user @192.168.1.50]
[req=1a2b3c] TRACE
#0 /app/io/route/api.php:23 io_in()
#1 /app/index.php:45 include()
#2 {main}
```

---

### Chained exceptions

```
[req=deadbe] FATAL (exception:RuntimeException) include:/app/io/route/fail.php <- PDOException:SQLSTATE[23000]: Integrity constraint violation in /app/lib/run.php:27 [12.7ms 4096KiB GET /fail @10.0.0.1]
```

---

### Fatal shutdown (parse / compile / core)

```
[req=deadbe] FATAL (shutdown:type=4) syntax error, unexpected '}' in /app/io/route/broken.php:17 [0.81ms 512KiB GET /broken @127.0.0.1]
```

---

### Multiple issues, one request

```
[req=f00baa] ERR (errno=8) Undefined array key "name" in /app/lib/auth.php:55
[req=f00baa] ERR (errno=2) file_get_contents(): Filename cannot be empty in /app/lib/io.php:89
[req=f00baa] FATAL (exception:RuntimeException) include:/app/io/route/fail.php in /app/lib/run.php:47 [12.7ms 4096KiB GET /fail @-]
```