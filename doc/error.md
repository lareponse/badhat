# bad\error

PHP fails in three ways:

- **errors** (warnings, notices): noisy, but execution continues
- **exceptions**: execution stops
- **fatals**: execution stops hard

None of them tell you *which request* caused the problem.

So your logs look like this:

```

PHP Fatal error: Uncaught TypeError: ...

````

Which request? Which user? Good luck.

> `bad\error` takes over PHP’s error channel, tags everything with a request ID,
> and gives you one place to decide what happens when things break.

---

## 1) Install

```php
$install = require __DIR__ . '/bad/error.php';
$restore = $install();
````

That’s it.

From this point on:

* runtime errors go through your handler
* uncaught exceptions go through your handler
* fatal shutdowns go through your handler

Every log line is tagged:

```
[req=1a2b3c4d-1234] ...
```

One request. One ID. Grep works again.

---

## 2) Request ID

```php
use const bad\error\HND_ALL;

// custom ID (used as-is)
$restore = $install(HND_ALL, 'my-request-id');
```

If you don’t pass one, `bad\error` generates:

```
dechex(start_time) . '-' . getmypid()
```

Default time source is microtime-based.
Add `MONOTONIC_TIME` if you want `hrtime()` when available.

---

## 3) What to catch

The first argument is a bitmask of **handler flags**.

```php
use const bad\error\{HND_ERR, HND_EXC, HND_SHUT, HND_ALL};

$restore = $install(HND_ERR | HND_EXC); // errors + exceptions
$restore = $install(HND_ALL);           // everything (default)
```

| Flag       | Catches               |
| ---------- | --------------------- |
| `HND_ERR`  | Runtime errors        |
| `HND_EXC`  | Uncaught exceptions   |
| `HND_SHUT` | Fatal shutdown errors |
| `HND_ALL`  | All of the above      |

**Story:**
Claim the hooks you want. Leave the rest alone.

---

## 4) How much to log

By default, logs are one line per failure.

Add traces when you need them:

```php
use const bad\error\MSG_WITH_TRACE;

$restore = $install(HND_ALL | MSG_WITH_TRACE);
```

Example:

```
[req=1a2b3c4d-1234] FATAL (exception:InvalidArgumentException) Bad input in /app/api.php:18
[req=1a2b3c4d-1234] TRACE
#0 /app/api.php:23 validate()
#1 /app/index.php:45 include()
```

Traces are expensive.
Use them in dev. Think twice in prod.

---

## 5) What happens to output on fatal

When execution dies, you probably have buffered output.

Choose what to do with it:

```php
use const bad\error\{FATAL_OB_FLUSH, FATAL_OB_CLEAN};

// Dev: show what you have
$restore = $install(HND_ALL | FATAL_OB_FLUSH);

// Prod: discard everything
$restore = $install(HND_ALL | FATAL_OB_CLEAN);
```

If you want the client to know something went wrong:

```php
use const bad\error\FATAL_HTTP_500;

$restore = $install(HND_ALL | FATAL_HTTP_500 | FATAL_OB_CLEAN);
```

**Story:**
Prod hides the mess. Dev shows you everything.

---

## 6) Let PHP talk too (optional)

Sometimes you want your logs **and** PHP’s native output:

```php
use const bad\error\ALLOW_INTERNAL;

$restore = $install(HND_ERR | ALLOW_INTERNAL);
```

Your handler logs. PHP’s handler runs too.

Runtime errors only.

---

## 7) Restore

The installer returns a restore callable:

```php
$restore = $install();
// ...
$restore();
```

* error + exception handlers are restored
* shutdown handlers **cannot** be removed

If you nest installs, restore in reverse order.

---

## Typical profiles

### Production

```php
$restore = $install(HND_ALL | FATAL_HTTP_500 | FATAL_OB_CLEAN);
```

Clean failure. No traces. Proper status code.

### Development

```php
$restore = $install(
    HND_ALL |
    MSG_WITH_TRACE |
    FATAL_OB_FLUSH |
    ALLOW_INTERNAL
);
```

See everything. Logs + output + traces.

---

## Sharp edges

* **You own the channel.** Nothing is shown unless you allow it.
* **Traces are loud.** They double log volume.
* **Shutdown sticks.** Once installed, it stays for the request lifetime.
* **Restore is on you.** Especially in tests.

---

## Reference

### Handler flags

| Flag       | Value |
| ---------- | ----: |
| `HND_ERR`  |     1 |
| `HND_EXC`  |     2 |
| `HND_SHUT` |     4 |
| `HND_ALL`  |     7 |

### Behavior flags

| Flag             | Value |
| ---------------- | ----: |
| `MSG_WITH_TRACE` |     8 |
| `ALLOW_INTERNAL` |    16 |
| `FATAL_OB_FLUSH` |    32 |
| `FATAL_OB_CLEAN` |    64 |
| `FATAL_HTTP_500` |   128 |
| `MONOTONIC_TIME` |   256 |
