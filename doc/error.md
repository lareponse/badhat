# badhat\error

PHP has three failure modes: errors, exceptions, and fatals. Errors warn and continue. Exceptions and fatals kill the request. Each has its own handler. None of them tell you which request caused the problem.

You've seen the logs. A hundred requests per second, and somewhere in there:

```
PHP Fatal error: Uncaught TypeError: ...
```

Which request? Which user? Good luck.

> `bad\error` takes over PHP's error channel, tags everything with a request ID, and gives you one place to decide what happens when things break.

---

## 1) First, you install

The file returns an installer. Call it, and you own the error channel:

```php
$install = require __DIR__ . '/bad/error.php';
$restore = $install();
```

That's it. From this point forward:

- Runtime errors go through your handler
- Uncaught exceptions go through your handler  
- Fatal shutdowns go through your handler

Every log line gets a request ID: `[req=a1b2c3]`. When something breaks at 3am, you can grep.

**Request ID**
- If you pass `$request_id`, it is used as-is.
- Otherwise it is generated as: `dechex((int)($start * 10000) ^ getmypid())`

**Default story:**
"One request, one ID. Everything that goes wrong is tagged."

---

## 2) Then, you choose what to catch

Not every app wants every hook. The first argument is a bitmask:

```php
use const bad\error\{HND_ERR, HND_EXC, HND_SHUT, HND_ALL};

$restore = $install(HND_ERR | HND_EXC);  // errors + exceptions, not shutdown
$restore = $install(HND_ALL);             // everything (default)
```

| Flag | Catches |
|------|---------|
| `HND_ERR` | Runtime errors (warnings, notices, deprecations) |
| `HND_EXC` | Uncaught exceptions |
| `HND_SHUT` | Parse errors, compile errors, core fatals |
| `HND_ALL` | All of the above |

**Story:**
"Claim the hooks you want. Leave the rest alone."

---

## 3) Decide what gets logged

By default, you get one-line messages. Add `MSG_WITH_TRACE` for stack traces:

```php
use const bad\error\MSG_WITH_TRACE;

$restore = $install(HND_ALL | MSG_WITH_TRACE);
```

Without trace:
```
[req=1a2b3c] FATAL (exception:InvalidArgumentException) Bad input in /app/route/api.php:18 [3.4ms 2048KiB POST /api @192.168.1.50]
```

With trace:
```
[req=1a2b3c] FATAL (exception:InvalidArgumentException) Bad input in /app/route/api.php:18 [3.4ms 2048KiB POST /api @192.168.1.50]
[req=1a2b3c] TRACE
#0 /app/route/api.php:23 validate()
#1 /app/index.php:45 include()
```

Traces are expensive. Use them in dev. Think twice in prod.

**Story:**
"Logs should be greppable first, debuggable second."

---

## 4) Decide what happens to output

When a fatal hits, you probably have partial output buffered. Two choices:

```php
use const bad\error\{FATAL_OB_FLUSH, FATAL_OB_CLEAN};

// Dev: flush what you have (helps debugging)
$restore = $install(HND_ALL | FATAL_OB_FLUSH);

// Prod: discard everything (cleaner failure)
$restore = $install(HND_ALL | FATAL_OB_CLEAN);
```

And if you want the browser to know something went wrong:

```php
use const bad\error\FATAL_HTTP_500;

$restore = $install(HND_ALL | FATAL_HTTP_500 | FATAL_OB_CLEAN);
```

`FATAL_HTTP_500` sends the status code before the process dies. Without it, the client might get a 200 with garbage.

**Story:**
"Prod hides the mess. Dev shows you everything."

---

## 5) Let PHP's handler run too

Sometimes you want both: your logging *and* PHP's native output. Dev mode, usually:

```php
use const bad\error\ALLOW_INTERNAL;

$restore = $install(HND_ALL | MSG_WITH_TRACE | ALLOW_INTERNAL);
```

Your handler logs. PHP's handler prints. You see both.

**Story:**
"You own the channel, but you can share it."

---

## 6) Restore when you're done 

The installer returns a restore function. Call it to put **MOST** things back:

```php
$restore = $install(HND_ALL);
// ... app runs ...
$restore();  // previous handlers restored, except for shutdown
```

Order matters. If you nest installs, restore in reverse order (LIFO).

---

## Environment profiles

### Production

```php
$restore = $install(HND_ALL | FATAL_HTTP_500 | FATAL_OB_CLEAN);
```

- Logs only (no screen output)
- Clean failure (buffers discarded)
- HTTP 500 on fatal
- No stack traces in logs

### Development

```php
$restore = $install(HND_ALL | MSG_WITH_TRACE | FATAL_OB_FLUSH | ALLOW_INTERNAL);
```

- Stack traces in logs
- PHP's native output too
- Partial output preserved
- See everything

### Toggle via environment

```php
use const bad\error\{HND_ALL, MSG_WITH_TRACE, FATAL_OB_FLUSH, FATAL_OB_CLEAN, FATAL_HTTP_500, ALLOW_INTERNAL};

$behave = HND_ALL;

getenv('DEV')
    ? $behave |= MSG_WITH_TRACE | FATAL_OB_FLUSH | ALLOW_INTERNAL
    : $behave |= FATAL_HTTP_500 | FATAL_OB_CLEAN;

$restore = $install($behave);
```

Behavior is baked at install time. No runtime conditionals inside handlers.

---

## What the logs look like

### Runtime error (non-fatal)

```
[req=1a2b3c] ERR (errno=2) Undefined variable $foo in /app/route/user.php:42
```

Execution continues.

### Uncaught exception

```
[req=1a2b3c] FATAL (exception:InvalidArgumentException) Bad Request in /app/lib/map.php:18 [3.41ms 2048KiB POST /api/user @192.168.1.50]
```

Hard exit.

### Chained exceptions

```
[req=deadbe] FATAL (exception:RuntimeException) include:/app/route/fail.php <- PDOException:SQLSTATE[23000]: Integrity constraint violation in /app/lib/run.php:27 [12.7ms 4096KiB GET /fail @10.0.0.1]
```

The chain is visible. You see what caused what.

### Fatal shutdown

```
[req=deadbe] FATAL (shutdown:type=4) syntax error, unexpected '}' in /app/route/broken.php:17 [0.81ms 512KiB GET /broken @127.0.0.1]
```

Parse errors, compile errors—things that kill PHP before your code runs.

### Multiple issues, same request

```
[req=f00baa] ERR (errno=8) Undefined array key "name" in /app/lib/auth.php:55
[req=f00baa] ERR (errno=2) file_get_contents(): Filename cannot be empty in /app/lib/map.php:89
[req=f00baa] FATAL (exception:RuntimeException) include:/app/route/fail.php in /app/lib/run.php:47 [12.7ms 4096KiB GET /fail @-]
```

Same `req=` prefix. One grep finds all of it.

---

## Reference

### Handler flags

| Flag | Value | PHP hook |
|------|-------|----------|
| `HND_ERR` | 1 | `set_error_handler` |
| `HND_EXC` | 2 | `set_exception_handler` |
| `HND_SHUT` | 4 | `register_shutdown_function` |
| `HND_ALL` | 7 | All of the above |

### Behavior flags

| Flag | Value | Effect |
|------|-------|--------|
| `MSG_WITH_TRACE` | 8 | Include stack trace in logs |
| `ALLOW_INTERNAL` | 16 | Let PHP's native handler run too |
| `FATAL_OB_FLUSH` | 32 | Flush output buffers on fatal |
| `FATAL_OB_CLEAN` | 64 | Discard output buffers on fatal |
| `FATAL_HTTP_500` | 128 | Send HTTP 500 on fatal |

### Special codes

| Constant | Value | Meaning |
|----------|-------|---------|
| `CODE_ACE` | 0xACE | (reserved) |
| `CODE_BAD` | 0xBAD | (reserved) |
| `CODE_COD` | 0xC0D | Exception code for wrapped errors |

---

## Sharp edges

- **You own the channel.** PHP's default output is suppressed unless you opt in with `ALLOW_INTERNAL`.
- **No screen mode.** This logs to `error_log()`. If you want pretty errors in the browser, that's a different tool.
- **Request data hits logs.** URI, method, remote IP—all logged. Don't put secrets in URLs.
- **Restore is your job.** If you install, you restore. Especially in tests.