# ADDBAD : A technical manifesto for performance-first PHP development.

---

## The Refusal

ADDBAD refuses:
- Object-oriented ceremony and class inheritance
- Framework abstractions and middleware stacks  
- Template engines and dependency injection
- Array functions over native loops
- String-based programming over bitmasks
- Developer Experience over runtime performance

**Modern frameworks ship developer comfort at execution time**  
**ADDBAD optimizes for execution speed**

---

## Nine Technical Pillars

### 1. Bits and Booleans

A bit is not a single digit. A bit is a decision.
    Use integers constants for flags, not strings:
        - outperform strings for flag comparisons 
        - provide compile-time safety against typos
        - tell a story about the code's intent.

    PHP core uses bitmasks extensively (error_reporting, json_encode flags). 
    They're established patterns for performance-critical code.

A boolean variable is not a choice. It is a ternary state
    Boolean can be `true`, `false` or `null`
    Use three states for flags, not two:
        - Example: `approved: yes` (someone approved), `no` (someone revoked), `null` (pending).
        - This allows for more complex logic without additional variables.
        - Avoids confusion with simple true/false checks.
    That's three different states, not two.


### 2. Collections
Arrays for lists, loops over array functions:
```php
// This (fast)
foreach ($items as $item) { /* */ }

// Not this (slow)  
array_map(fn($item) => /* */, $items);
```

### 3. Blocks

**Structure**: No `switch`/`match`, minimal `if`/`elseif`/`else`

**Function**: 
- Function signatures are configuration
- No convenience wrappers (anti-pattern: `function e($v) { return htmlspecialchars($v); }`)
- Bundling requires 2+ native functions minimum

### 4. Files & Folders

File-based routing, directory structure as API

URLs are often very close to filesystem paths
Filesystem paths are reused as namespaces
Static routes are the third copy of the same information.

Apply SSOT and DRY principles, suddenly :
- No autoloaders, no route compilers
- Files are loaded directly, no magic
- Files are self-contained, no hidden dependencies
- Files are portable, no vendor lock-in

---

## Unified Principles

### 1. Eliminate Execution Overhead
Every abstraction costs CPU cycles. Micro-optimizations compound in high-traffic applications.
- Direct PDO over ORMs, it all boils down to a row.
- File includes over autoloaders  
- Native loops over array functions
- Bitmasks over string comparisons

### 2. Master Native PHP
Framework knowledge expires; language knowledge compounds.
- Read PHP manual, not framework docs
- Use built-in functions aggressively
- Understand opcache behavior
- Profile actual performance, not perceived

### 3. Build Portable Systems
- Plain PHP files (no vendor lock-in)
- Standard SQL (database agnostic)
- Filesystem routing (server agnostic)
- Environment variables (deployment agnostic)

### 4. Configure your server, not your framework
- Clean URLs via web server, not framework
- Use Apache/Nginx for routing, not framework
- Use PHP-FPM for process management, not framework
- Use environment variables for configuration, not framework settings

### 5. Explicit Over Convenient
Code should read like assembly instructions:
```php
// Explicit (good)
$users = qp("SELECT * FROM users WHERE active = 1")->fetchAll();

// Magic (bad) 
$users = User::where('active', true)->get();
```

### 6. Error-Driven Flow
Don't test ahead. Expect success, handle failure:
```php
// This (expect success)
$result = qp($sql, $params)->fetch();

// Not this (test ahead)
if (pdo_envion_valid()) { /* then query */ }
```

---

## Decision Matrix

| Scenario | Use BADHAT | Use Framework |
|----------|------------|---------------|
| High traffic (1000+ req/sec) | ✓ | ✗ |
| Small team (1-8 devs) | ✓ | ? |
| Direct SQL preferred | ✓ | ✗ |
| Performance critical | ✓ | ✗ |
| Large team (20+ devs) | ✗ | ✓ |
| Complex business rules | ? | ✓ |
| Rapid prototyping | ✗ | ✓ |

---

## Performance Targets

BADHAT succeeds when:
- Response times < 50ms consistently
- Memory usage < 2MB per request  
- Cold start time < 10ms
- Zero framework bootstrap overhead
- Bitmask operations in microseconds

Traditional metrics (developer velocity, code reuse) are secondary to runtime characteristics.

---

## Implementation Rules

**Loops over array functions**: Native `foreach` beats `array_map`/`array_filter`

**Bitmasks for flags**: Store multiple boolean states in single integers using bitwise operations

**Direct database access**: Raw PDO queries over ORM abstractions

**File-based routing**: Filesystem as URL mapping (no route compilation)

**Error handling**: Let exceptions bubble to global handlers

---

## Solution

BADHAT optimizes for **executable efficiency** over **syntactic convenience**:

- 400 lines of core vs. 50MB framework installs
- Microsecond routing vs. middleware stack overhead  
- Direct function calls vs. object method dispatch
- Compile-time constants vs. runtime string lookups

**The constraint drives the design.**
