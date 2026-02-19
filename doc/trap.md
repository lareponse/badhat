# bad\trap

Your logs say `PHP Fatal error: Uncaught TypeError`.
They don't say which request. They never do.

`bad\trap` tags every log line with a request ID and gives you one place to handle errors, exceptions, and fatal shutdowns.

---

## Claim the channel

```php
$install = require '/add/badhat/trap.php';
$restore = $install();
```

Done. Errors, exceptions, fatal shutdowns — all yours now.

Every log line carries a tag:

```
[req=1234-a1b2c3d4] ...
```

Grep works again.

---

## Choose what you catch

```php
$restore = $install(HND_ERR | HND_EXC);   // errors + exceptions, not shutdown
$restore = $install(HND_ALL);              // everything (default)
```

What you don't claim, PHP keeps.

---

## Choose what you see

```php
$restore = $install(HND_ALL | LOG_WITH_TRACE);
```

Without `LOG_WITH_TRACE`: one line per failure.
With it: one line, then a `FRAME` per stack level. Loud. Worth it in dev, think twice in prod.

---

## Choose what survives a fatal

When the process dies, you probably have open output buffers.

```php
$restore = $install(HND_ALL | FATAL_OB_CLEAN);    // prod: discard everything
$restore = $install(HND_ALL | FATAL_OB_FLUSH);    // dev: dump what you have
```

OB cleanup fires on exceptions and shutdowns only — a warning doesn't wipe your buffers.

No HTTP status. That's `bad\http`'s job.

---

## Let PHP talk too

```php
$restore = $install(HND_ERR | ALLOW_INTERNAL);
```

Your handler logs. PHP's handler also runs. Errors only — exceptions and shutdowns are terminal anyway.

---

## Name your request

```php
$restore = $install(HND_ALL, 'order-7741');
```

If you don't, trap generates `pid-dechex(hrtime)`.

---

## Restore

```php
$restore();              // restore everything you claimed
$restore(HND_ERR);       // give back errors, keep exceptions
```

Default restores all. Pass a mask to restore selectively.

Shutdown handlers stick — PHP doesn't offer a way back.

---

## What hits the log

Every line, same shape:

```
[req=ID] LABEL #CODE (file:line) [SOURCE MESSAGE]
```

| Label      | You see it when…                          |
| ---------- | ----------------------------------------- |
| `HND_ERR`  | a runtime error fires                     |
| `HND_EXC`  | an exception escapes                      |
| `HND_SHUT` | the process dies                          |
| `PEEK`     | exception or shutdown (diagnostics line)  |
| `FRAME`    | `LOG_WITH_TRACE` is set                   |

`PEEK` tells you what the process looked like at death: elapsed ms, peak KiB, SAPI, include count, PID, OB depth, where headers were sent. Timing comes from `REQUEST_TIME_FLOAT` — absent in CLI, shows `-1ms`.

Exception chains read left to right:

```
RuntimeException:query failed <- PDOException:SQLSTATE[42S02]
```

Messages are scrubbed (control chars → space) and capped at 4096 bytes.

---

## Profiles

```php
// prod: quiet death
$restore = $install(HND_ALL | FATAL_OB_CLEAN);

// dev: see everything
$restore = $install(HND_ALL | LOG_WITH_TRACE | FATAL_OB_FLUSH | ALLOW_INTERNAL);
```

---

## Reference

| Flag             | Value | What it does                              |
| ---------------- | ----: | ----------------------------------------- |
| `HND_ERR`        |     1 | Claim runtime errors                      |
| `HND_EXC`        |     2 | Claim uncaught exceptions                 |
| `HND_SHUT`       |     4 | Claim fatal shutdown                      |
| `HND_ALL`        |     7 | All three                                 |
| `LOG_WITH_TRACE` |     8 | Emit stack frames after the main line     |
| `ALLOW_INTERNAL` |    16 | Don't suppress PHP's own error handler    |
| `FATAL_OB_FLUSH` |    32 | Flush all OB on exception/shutdown        |
| `FATAL_OB_CLEAN` |    64 | Discard all OB on exception/shutdown      |

`peek()` and `logladdy()` live in `bad\trap\`. Namespace-public, not API. Don't call them unless you mean it.