# bad\run

You mapped a request to a file.
Now you need to execute that file — without turning your call-site into a ritual of `include`, output buffers, and exception plumbing.

`bad\run` is three verbs:

- **loop()** — run many files in order
- **loot()** — run one step (include + optional buffer + optional invoke)
- **boot()** — invoke what the file returned

A file can do two things:

1. **return** a value (data, a closure, a string, anything)
2. **print** output (echo / templates)

`bad\run` collects both into a single bag called **loot**.

- `INC_RETURN` is the last return value (from include or invoke)
- `INC_BUFFER` is the last captured output (when you BUFFER)

---

## 1) loop(): the call-site primitive

```php
use function bad\run\loop;
use const bad\run\{INVOKE, INC_RETURN};

$loot = loop([
    __DIR__ . '/mw/auth.php',
    __DIR__ . '/routes/home.php',
], ['token' => 'ok'], INVOKE);

$result = $loot[INC_RETURN] ?? null;
````

`loop()` returns the final loot bag.
By default, each step receives the original `$args` you passed.

If you want a real pipeline, add `RELOOT`: each next step receives the previous step’s loot bag.

```php
use const bad\run\{INVOKE, RELOOT, INC_RETURN};

$loot = loop([
    __DIR__ . '/mw/auth.php',
    __DIR__ . '/routes/home.php',
    __DIR__ . '/views/home.php',
], ['token' => 'ok'], INVOKE | RELOOT);

echo (string)($loot[INC_RETURN] ?? '');
```

---

## 2) loot(): one step (include + maybe invoke + maybe buffer)

`loot()` is what `loop()` does internally, exposed for the rare case where you want to run a single file and still get a fault latch.

```php
use function bad\run\loot;
use const bad\run\{BUFFER, INVOKE, INC_BUFFER, INC_RETURN};

$fault = null;
$loot  = loot(__DIR__ . '/views/hello.php', ['name' => 'World'], BUFFER, $fault);

if ($fault) throw $fault;

echo $loot[INC_BUFFER];
```

### Include-time input

The included file sees a `$loot` variable (the input bag).
That’s the contract: files consume the bag, return something, and/or print.

Example file:

```php
// views/hello.php
?><h1>Hello, <?= htmlspecialchars($loot['name'] ?? '...') ?></h1><?php
```

---

## 3) boot(): invoke what the file returned

Most “handler files” return a closure.
With `INVOKE`, `bad\run` invokes the returned value if it’s acceptable.

Default policy is strict: **Closure-only**.
If you want “any callable”, add `ANYCALL`.

```php
use function bad\run\loop;
use const bad\run\{INVOKE, ANYCALL, INC_RETURN};

$loot = loop([__DIR__ . '/routes/users.php'], ['42'], INVOKE);
// invokes only if routes/users.php returned a \Closure

$loot = loop([__DIR__ . '/routes/users.php'], ['42'], INVOKE | ANYCALL);
// invokes if it returned anything is_callable()
```

### Callable input shape

By default, the callable receives **one argument**: the bag (array).

```php
// routes/users.php
return function(array $bag) {
    $id = $bag[0] ?? null;
    return "user:$id";
};
```

If you want positional parameters, add `SPREAD`.

```php
// routes/users.php
return function(string $id) {
    return "user:$id";
};
```

```php
use const bad\run\{INVOKE, SPREAD, INC_RETURN};

$loot = loop([__DIR__ . '/routes/users.php'], ['42'], INVOKE | SPREAD);
echo $loot[INC_RETURN];
```

---

## 4) Buffering: capture output when you need it

Without `BUFFER`, output streams immediately.
With it, output is captured per step into `INC_BUFFER`.

```php
use function bad\run\loop;
use const bad\run\{BUFFER, INC_BUFFER};

$loot = loop([__DIR__ . '/views/hello.php'], ['name' => 'World'], BUFFER);
echo $loot[INC_BUFFER];
```

### ABSORB: when output feeds the callable

`ABSORB` is a handshake: buffer output, then invoke, and append the current capture to the callable’s input.

```php
// views/page.php
?><article><?= htmlspecialchars($loot['content'] ?? '') ?></article><?php

return function(array $bag) {
    $html = $bag[array_key_last($bag)] ?? '';
    return "<!doctype html><html><body>$html</body></html>";
};
```

```php
use function bad\run\loop;
use const bad\run\{ABSORB, INC_RETURN};

$loot = loop([__DIR__ . '/views/page.php'], ['content' => 'Hello'], ABSORB);
echo $loot[INC_RETURN];
```

---

## 5) Fault rules (when things break)

A step can fault on include or invoke.
`loop()` stops on the first fault and throws.

If you want “continue anyway”, add `FAULT_AHEAD`.

```php
use const bad\run\{INVOKE, FAULT_AHEAD};

$loot = loop([
    __DIR__ . '/a.php',
    __DIR__ . '/optional_metrics.php',
    __DIR__ . '/b.php',
], [], INVOKE | FAULT_AHEAD);
```

If include faulted, invoke is normally vetoed.
If you want “try invoke anyway”, add `RESCUE_CALL`.

---

## Reference

### Behavior flags

| Constant      | Value | Meaning                                                    |
| ------------- | ----: | ---------------------------------------------------------- |
| `BUFFER`      |   `1` | capture output per step into `INC_BUFFER`                  |
| `INVOKE`      |   `2` | invoke returned value per step (if acceptable)             |
| `ABSORB`      |   `7` | buffer + invoke + append current capture to callable input |
| `SPREAD`      |   `8` | invoke callable with positional args (`...$args`)          |
| `RESCUE_CALL` |  `16` | try invoke even if include faulted                         |
| `ANYCALL`     |  `32` | accept any `is_callable()` (default: `\Closure` only)      |
| `RELOOT`      |  `64` | pipe: next step input = previous step loot                 |
| `FAULT_AHEAD` | `128` | pipe: keep going on fault (else: throw)                    |

### Loot keys

| Constant     | Value | Contains                              |
| ------------ | ----: | ------------------------------------- |
| `INC_RETURN` |  `-1` | last return value (include or invoke) |
| `INC_BUFFER` |  `-2` | last captured output (string)         |

### Fault shape

When a fault occurs, `bad\run` normalizes it as `Exception` with code `0xBADC0DE`.
The message tells you the phase and the file:

* `loot:include:/path/to/file.php`
* `boot:invoke:/path/to/file.php`

The original throwable is kept as the previous exception.

---

## Buffer cleanup

Each step snapshots `ob_get_level()` and cleans up buffers opened inside the file/callable.
If a step leaks nested buffers, they’re dropped so the rest of the request doesn’t inherit broken buffer state.
