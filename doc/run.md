# bad\run

You've resolved a path to a file. Now what?

PHP's `include` is powerful but raw. It can *return* a value, but output streams immediately. Errors are messy. Chaining files means writing the same try/catch/buffer dance every time.

Buried in the manual, almost as an aside:

> Also, it's possible to return values from included files

That single fact flips the meaning of a "file". A file can be a producer.

Push one step further: a file can return a **closure**. Now the file defines behavior without adding names to the global namespace. No registry. No collisions. The call-site just receives a callable.

Closures also carry local context with `use()`. So a file can compute something once (paths, regexes, maps, formatters), capture it, and return a callable that remembers. No globals. Captured on purpose.

Then you hit the second truth: a PHP file can also **output**. So `include` has two results in practice: what the file returns, and what it prints.

`run()` names and controls those results:

- `INC_RETURN` is the returned value (from include or invoke)
- `INC_BUFFER` is captured output when you BUFFER

From there the moves are straightforward.

If the file returns a callable, you can INVOKE it.
If the file prints, you can BUFFER it.
If the file prints HTML *and* returns a callable, you get a two-phase template: buffer the skeleton, return a function that wraps it.
ABSORB is that handshake: output becomes input after include-time.

Finally, you want composition: run many files, keep args, pass loot forward.
That's RELOOT. And real pipelines need real failure rules:
FAULT_AHEAD for "secondary steps must not veto", and RESCUE_CALL for
"even if include failed, still run the callable we already have".

> `run()` executes files and collects what they produce—return values, output, or both.

---

## 1) First, you include

At its simplest, `run()` is just `include` with structure:

```php
use function bad\run\run;
use const bad\run\INC_RETURN;

$loot = run(['/app/boot.php']);
// $loot[INC_RETURN] = whatever boot.php returned
````

Pass arguments via the second parameter. They become the initial loot bag.
Because `include` shares scope, the included file sees a `$loot` variable already populated.

```php
// /app/route/user.php  (included)
$id = $loot[0];                          // positional arg
return ['id' => $id, 'name' => 'Ada'];   // producer file
```

```php
$loot = run(['/app/route/user.php'], ['42']);
// $loot[INC_RETURN] = ['id'=>'42','name'=>'Ada']
```

Default story: include a file, get back what it returned.

---

## 2) Then, you invoke

Most "route files" don't want to return data yet. They want to return a handler.
So the file returns a closure, often capturing some local context with `use()`.

```php
// /app/route/user.php
$query = 'SELECT * FROM users WHERE id = ?';      // computed once, captured

return function(array $bag) use ($query) {
    $id = $bag[1] ?? null;                        // original args by default
    return [$query, [$id]];                       // stand-in for "execute query"
};
```

Add `INVOKE`, and badhat calls the returned callable right away.

```php
use const bad\run\INVOKE;

$loot = run(['/app/route/user.php'], ['view', '42'], INVOKE);
// $loot[INC_RETURN] = [$query, ['42']]
```

By default, `INVOKE` only accepts `\Closure` returns. Add `CALLABLE` to accept any `is_callable()` value.

Story: files define handlers; `INVOKE` runs them.

---

## 3) Buffer when you need the output

PHP files can echo. Sometimes you want that output captured, not streamed.

```php
// /app/view/hello.php
?><h1>Hello, <?= htmlspecialchars($loot['name']) ?></h1><?php
```

```php
use const bad\run\BUFFER;
use const bad\run\INC_BUFFER;

$loot = run(['/app/view/hello.php'], ['name' => 'World'], BUFFER);

echo $loot[INC_BUFFER];  // "<h1>Hello, World</h1>"
```

Without `BUFFER`, output goes straight to the browser. With it, output lands in `INC_BUFFER`.

Story: sometimes you capture, you don't emit.

---

## 4) Absorb: when output feeds into the callable

Here's the two-phase template: the file prints markup *and* returns a wrapper.

```php
// /app/view/page.php
?><article><?= htmlspecialchars($loot['content']) ?></article><?php

$doctype = '<!doctype html>';                     // local context, captured

return function(array $bag) use ($doctype) {
    $body = end($bag);                            // last arg is buffered output
    return $doctype . "<html><body>$body</body></html>";
};
```

`ABSORB` captures the output, then passes it as the last argument to the invoked callable:

```php
use const bad\run\ABSORB;
use const bad\run\INC_RETURN;

$loot = run(['/app/view/page.php'], ['content' => 'Hello'], ABSORB);

echo $loot[INC_RETURN];
// <!doctype html><html><body><article>Hello</article></body></html>
```

`ABSORB` implies both `BUFFER` and `INVOKE`. It also appends the captured buffer to the callable's args.

Story: template outputs structure; wrapper receives it. One file, two phases.

---

## 5) Spread: positional invocation

By default, the callable receives one array argument. With `SPREAD`, the args are unpacked as positional parameters:

```php
// /app/route/user.php
return function(string $action, string $id) {
    return ['action' => $action, 'id' => $id];
};
```

```php
use const bad\run\{INVOKE, SPREAD};

$loot = run(['/app/route/user.php'], ['edit', '42'], INVOKE | SPREAD);
// callable receives ('edit', '42') instead of (['edit', '42'])
```

Story: when your handler has named parameters, spread the args.

---

## 6) Chain: pipelines across files

One file is simple. But what about auth → handler → renderer?

Without `RELOOT`, each invoked callable receives the original args.
With `RELOOT`, each invoked callable receives the *current loot bag* (including `INC_RETURN` from the previous step).

```php
// /app/mw/auth.php
$realm = 'members';                               // captured policy

return function(array $bag) use ($realm) {
    $token = $bag['token'] ?? null;               // comes from original args (first step)
    return $token ? ['user' => 'Ada', 'realm' => $realm] : null;
};
```

```php
// /app/route/home.php
use const bad\run\INC_RETURN;

return function(array $bag) {
    $user = $bag[INC_RETURN];                     // from auth.php
    return ['user' => $user, 'posts' => [1, 2, 3]];
};
```

```php
// /app/render/home.php
use const bad\run\INC_RETURN;

$layout = 'home';                                 // captured choice

return function(array $bag) use ($layout) {
    $model = $bag[INC_RETURN];                    // from home.php
    return "render:$layout:" . json_encode($model);
};
```

```php
use function bad\run\run;
use const bad\run\{INVOKE, RELOOT, INC_RETURN};

$loot = run(
    ['/app/mw/auth.php', '/app/route/home.php', '/app/render/home.php'],
    ['token' => 'ok'],
    INVOKE | RELOOT
);

echo $loot[INC_RETURN];
// render:home:{"user":{"user":"Ada","realm":"members"},"posts":[1,2,3]}
```

Story: middleware isn't magic. It's files returning values to the next file.

---

## 7) When things break

By default, `run()` throws on failure. The exception wraps the original:

```php
$loot = run(['/app/broken.php']);
// Exception("run:include:/app/broken.php", 0xBADC0DE, $originalThrowable)
```

### RESCUE_CALL: invoke anyway

If include fails, `INC_RETURN` might still contain a callable from a previous step (or from your initial args if you seeded it that way). `RESCUE_CALL` lets `run()` attempt the invoke phase anyway.

```php
use const bad\run\{INVOKE, RESCUE_CALL};

$loot = run(
    ['/app/noisy_include.php'],
    [],
    INVOKE | RESCUE_CALL
);
```

Rare. It exists for "salvage what we can" flows.

### FAULT_AHEAD: keep going

Multiple files, and you want to continue even if one fails (logging, metrics, optional sidecars):

```php
use const bad\run\FAULT_AHEAD;

$loot = run(['/app/a.php', '/app/logger.php', '/app/c.php'], [], FAULT_AHEAD);
```

Failures are suppressed and the pipeline continues.

Story: usually fail fast. Sometimes fail forward.

---

## Putting it together

```php
use function bad\map\{hook, seek};
use function bad\run\run;
use function bad\http\out;
use const bad\run\{BUFFER, INVOKE, INC_BUFFER};

$base = realpath(__DIR__ . '/routes') . '/';
$path = hook($base, $_SERVER['REQUEST_URI']);

[$file, $args] = seek($base, $path, '.php')
    ?? exit(out(404, 'Not Found'));

$loot = run([$file], $args, BUFFER | INVOKE);

exit(out(200, $loot[INC_BUFFER]));
```

Request to response: include → maybe invoke → maybe buffer.

---

## Reference

### Constants (behavior)

| Constant      | Value | Effect                                                                     |
| ------------- | ----- | -------------------------------------------------------------------------- |
| `BUFFER`      | 1     | Capture output to `INC_BUFFER` (per step)                                  |
| `INVOKE`      | 2     | Call returned callable (per step, if callable)                             |
| `ABSORB`      | 7     | `4 \| BUFFER \| INVOKE` — buffer + invoke + append buffer to callable args |
| `RELOOT`      | 8     | Invoke with the current loot bag (instead of original args)                |
| `SPREAD`      | 16    | Invoke callable with positional args (`...$args`) instead of one array     |
| `RESCUE_CALL` | 32    | Attempt invoke even if include failed                                      |
| `FAULT_AHEAD` | 64    | Suppress throws, continue to next file                                     |
| `CALLABLE`    | 128   | Accept any `is_callable()` return, not just `\Closure`                     |

### Constants (loot keys)

| Constant     | Value | Contains                            |
| ------------ | ----- | ----------------------------------- |
| `INC_RETURN` | -1    | Return value from include or invoke |
| `INC_BUFFER` | -2    | Captured output buffer (string)     |

### Throws

Failures wrap as `Exception` with code `0xBADC0DE`:

```
Exception("run:include:/path/to/file.php", 0xBADC0DE, $original)
Exception("run:invoke:/path/to/file.php", 0xBADC0DE, $original)
```

The message tells you which phase failed. The previous exception tells you why.

---

## Buffer cleanup

`run()` snapshots `ob_get_level()` before each file and tries to restore the expected level after the step. If a file opens extra output buffers and forgets to close them, `run()` discards deeper levels so your request doesn't leak buffer state across steps.