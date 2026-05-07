# BADHAT

```
Bits As Decision.
HTTP As Terminal.
```

Emergent programming for PHP.

Architecture that emerges from the language and the filesystem, not from a framework's ontology. The request is reality. Structure arises from execution, not doctrine. No registry, no lifecycle, no hive — just path → file → chain → bytes.

> BADHAT provides conditions. What grows is yours.

---

## What it is

BADHAT is not a framework. It is a climate.

It doesn't tell you what the application should be. It ensures that whatever you build remains in contact with reality: every transformation can be traced, every responsibility has an address, every effect has a cause you can point to.

**569 logical lines of production PHP** (737 total lines, ~23% comments/whitespace) across kernel + core modules. Turns PHP's implicit request flow into something you can control, audit, and depend on.

**Maps**, not routes. **Loops**, not controllers. **Bitmasks**, not config files.

> requires POSIX and PHP >= 8.0

---

## The physics

An organism doesn't start with a blueprint. It starts with a physics:

- **Boundaries** — request in, response out
- **Medium** — filesystem, PHP execution
- **Metabolism** — map → run → emit
- **Constraints** — flags, failure semantics, header rules, type safety, timing safety
- **Feedback** — loot, buffers, codified faults, request tracing

Nothing in BADHAT exists to *replace* something PHP already does. Everything exists to make what PHP already does *safer and more explicit*.

The filesystem is the router. Output buffering is the template engine. Bitmasks are the configuration language. PHP is the config file. `if` is the validation framework.

---

## What it does

```php
use function bad\map\{hook, seek};
use function bad\run\loot;
use function bad\http\out;

use const bad\run\{INVOKE, RESULT};
use const bad\http\QUIT;

$base = realpath(__DIR__ . '/routes') . '/';
$path = hook($_SERVER['REQUEST_URI'], "\0");

[$file, $args] = seek($base, $path, '.php')
    ?? exit(out(QUIT | 404, 'Not Found'));

$loot = loot([$file], $args, INVOKE);

exit(out(QUIT | 200, (string)($loot[RESULT] ?? '')));
```

Three core moves:

1. **hook** — extract a path from a URL, validate percent-encoding, reject forbidden bytes
2. **seek** — map that path to an executable file, return trailing segments as args
3. **loot** — include the file, optionally invoke a returned callable, capture output and faults

Edge cases handled:
- **hook**: percent-escape validation, URI scheme/authority parsing, query/fragment stripping, configurable char rejection
- **seek**: directional scan (forward or reverse), optional REBASE for nested patterns (path/name/name), symlink validation against base boundary
- **loot**: output buffering with nesting guards, exception capture + reloot-on-fault, callable invocation with optional argument spreading

---

## Kernel: two files doing heavy lifting

| File     | LOC | LLOC | Responsibility                                 |
|----------|-----|------|------------------------------------------------|
| `map.php`   | 89 | 70   | URL → path validation → file lookup + args      |
| `run.php`   | 117 | 97  | include + optional invoke + output capture + fault handling + buffer nesting guards |
| **Total kernel** | **206** | **167** | Core request→file→response mechanics |

Both use bitmask flags to configure behavior — one `int` tells you what the engine will do.

---

## Core modules: production-grade utilities

| Module      | LOC | LLOC | Purpose                                          |
| ----------- | --- | ---- | ------------------------------------------------ |
| `http.php`  | 122 | 92   | Stateful header staging + response emission with status codes, list-mode formatting, entry-level locking |
| `pdo.php`   | 73  | 61   | Query/prepare/execute wrapper with transaction support, error redaction (no query leaks) |
| `auth.php`  | 59  | 50   | Constant-time password verification with timing-attack protection, session regeneration, logout |
| `csrf.php`  | 48  | 36   | Token generation/validation with TTL, expiry cleanup, timing-safe comparison |
| `trap.php`  | 114 | 88   | Error/exception/shutdown handler with request ID tracking, optional traces, control-char sanitization, OB management |
| `rfc.php`   | 74  | 59   | RFC 9110/5234/3986 validators for field names, field values, URL paths with percent-encoding checks |
| `dev.php`   | 41  | 16   | Development utilities: var_dump with backtraces (vd, vdh) |
| **Total modules** | **531** | **402** | **Production + dev utilities** |
| **Grand total** | **737** | **569** | **Kernel + all modules** |

All modules throw exceptions on invalid input (or return them, configurable).

---

## Edge case handling

### URL parsing (`map.php:hook`)
- Percent-escape syntax validation (no silent corruption)
- URI scheme/authority detection before path extraction
- Query string and fragment removal before path operations
- Char-based rejection for forbidden bytes (configurable)

### Path resolution (`map.php:seek`)
- Symlink canonicalization: `realpath()` result must stay within base boundary (prevents escape)
- Bidirectional segment scanning: reverse (greedy) or forward (earliest match)
- Optional REBASE: path/name/name pattern for nested route hierarchies
- Returns file + remaining segments as arguments

### Execution (`run.php:loot`)
- **Output buffering guards**: nesting detection, optional trim-on-fault, optional OB state validation
- **Fault capture**: exceptions collected instead of thrown (configurable), can reloot same file with fault context
- **Callable invocation**: auto-detect if include returns callable, invoke with args (optional spreading)
- **Buffer management**: separate SILENT (capture, discard) vs BUFFER (capture, keep) flags

### Headers (`http.php`)
- **Status bits**: low 10 bits hold status code, high bits hold behavior flags (one `int`)
- **Entry-level mutation control**: per-header LOCK flag prevents further mutation
- **List-mode headers**: CSV/SSV formatting for multi-value headers (Set-Cookie, CSP)
- **Staged emission**: headers staged in-memory until EMIT flag, can reset + re-emit
- **Safe defaults**: Content-Length auto-set, status 0 = no-op, EMIT fails if headers already sent

### Authentication (`auth.php`)
- **Timing-attack resistance**: `password_verify()` against dummy hash even on user-not-found
- **Session safety**: mandatory `session_regenerate_id(true)` on login, full `$_SESSION = []` on logout
- **Optional update callback**: can fire last-login tracking without complicating the core
- **Strict checks**: empty credentials rejected, session must be active

### CSRF (`csrf.php`)
- **TTL as integer**: bitmask-friendly (low 28 bits = seconds), no string parsing
- **Expiry cleanup**: expired tokens deleted immediately, not cached
- **Timing-safe comparison**: `hash_equals()` not `===`
- **Key scoping**: multiple tokens per session, separate namespace

### Error handling (`trap.php`)
- **Request ID**: combines PID + nanosecond timestamp (hrtime) for unique tracing
- **Control-char sanitization**: error messages scrubbed of ASCII control chars (0x00-0x1F, 0x7F)
- **Trace logging**: optional stack traces, skips internal frames by request
- **OB management on fatal**: can flush or clean all buffers, prevents corrupted responses
- **PEEK metrics**: elapsed ms, peak memory, included file count, OB nesting level, headers-sent location

### Validation (`rfc.php`)
- **RFC-shaped validators**: field names (RFC 9110 token chars), field values (VCHAR + obs-text), paths (RFC 3986 + backslash ban)
- **Percent-encoding**: syntax validated (not decoded) to prevent homograph attacks
- **CTL filtering**: rejects control chars except HTAB (allowed via OWS in field values)
- **Configurable throw**: E_THROW flag throws on invalid, otherwise returns exception object

---

## Philosophy

**Emergent, not imposed.** Frameworks define a world and require you to inhabit it. BADHAT observes what PHP and HTTP already do and formalizes the minimum needed to work with that reality reliably.

**Maps resolve depth.** A single map can represent infinite routes with O(depth) resolution. Locality is preserved: longer matches win.

**Files produce.** A PHP file can return a value. Better: it can return a closure that captured intent.

**Closures capture intent.** A file computes once, returns a callable that remembers its context and dependencies.

**Bitmasks are the interface.** One `int` tells you what the engine will do, what happened, and what to expect. No silent defaults.

**Failure is explicit.** No silent `false`. Faults become exceptions with source context. Timing attacks are anticipated. Buffer corruption is detected.

---

## Quick taste

```php
// routes/api/users.php
use function bad\pdo\qp;
use function bad\http\{headers, out};
use const bad\http\{ONE, QUIT};

return function(array $bag) {
    headers(ONE, 'Content-Type', 'application/json; charset=utf-8');

    $rows = qp('SELECT id, name FROM users')->fetchAll();
    out(QUIT | 200, json_encode($rows));
};
```

Call-site:

```php
use function bad\run\loot;
use const bad\run\{INVOKE, RESULT};

$loot = loot([__DIR__ . '/routes/api/users.php'], [], INVOKE);
exit((string)($loot[RESULT] ?? ''));
```

---

## What BADHAT will never implement

Six concerns that belong to infrastructure, external tools, or standalone libraries — not to a request lifecycle toolkit:

1. **Infrastructure** — compression, TLS, rate limiting, static file serving, process management, log rotation. Your web server and OS handle this.
2. **Data abstraction** — ORM, cache wrappers, storage engine facades. Use PDO, APCu, phpredis directly.
3. **External I/O** — HTTP client, mail, queues, sockets. Use cURL, PHPMailer, AMQP directly.
4. **Rendering** — template engines, Markdown, PDF, image processing. Use PHP itself, league/commonmark, dompdf, GD directly.
5. **Tooling** — testing, linting, asset compilation, CLI scaffolding. Use PHPUnit, PHPStan, php-cs-fixer directly.
6. **Policy orchestration** — DI containers, event buses, middleware stacks, config DSLs, validation frameworks, global state stores. Call the constructor. Call the function. Write the `if`.

The rule: if a mature, standalone tool already does it, BADHAT will not re-skin it behind a `bad\` namespace.

## Install

```bash
git fetch --depth=1 git@github.com:lareponse/BADHAT.git main
git subtree add --prefix=add/badhat FETCH_HEAD --squash
```

---
    
## License

BADHAT LICENSE. No warranty. Ship it.
