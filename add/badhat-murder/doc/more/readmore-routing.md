# Performance Architecture: Routing Complexity

## The Fundamental Difference

**BADHAT routing time depends only on URI depth, not total routes.**

### BADHAT: O(d) Filesystem Resolution

```php
// /api/users/edit/123 = 4 segments
// Performance: O(1) to O(2d) regardless of app size

URI depth = 1: /users                 → max 2 filesystem calls
URI depth = 4: /api/users/edit/123    → max 8 filesystem calls  
URI depth = 5: /api/v2/users/edit/123 → max 12 filesystem calls
```

### Framework: O(n×m) Route Table Scan

```php
$routes = [
    '/simple' => 'Controller@simple',           // #1
    '/api/users/edit/{id}' => 'UserController', // #500
    // ... 4,500 more
];

// Match /api/users/edit/123:
// Scan #1, #2, #3... #500 until match
```

---

## Scaling Behavior

### Framework Degradation

```
100 routes:   scan ~50   → 1,200 regex ops
1,000 routes: scan ~500  → 12,000 regex ops  
5,000 routes: scan ~2,500 → 60,000 regex ops

O(n) where n = total routes
```

### BADHAT Constant

```
100 routes:   3-8 filesystem calls
1,000 routes: 3-8 filesystem calls
5,000 routes: 3-8 filesystem calls

O(d) where d = URI depth (typically 2-6)
```

---

## Memory Architecture

### Framework: Preloaded Table

```php
class RouteCollection {
    protected $routes = [];      // 5,000 Route objects
    protected $allRoutes = [];   // flat array
    protected $nameList = [];    // named lookup
    protected $actionList = [];  // controller lookup
}

// 5,000 routes × ~700 bytes = 3.5MB
// ALWAYS loaded
```

### BADHAT: No Table

```php
// /api/users/edit/123 triggers:
$candidates = [
    'route/api/users/edit/123.php',  // exact
    'route/api/users/edit.php',      // parent
    'route/api/users.php',           // grandparent
    'route/api.php'                  // root
];

// ~200 bytes for strings
// Only paths relevant to current URI
```

---

## Real-World Applications

- Most requests hit small subset (80/20 rule)
- Frameworks preload routes never used
- BADHAT only touches paths for current request

---

## Resource Efficiency

- No memory wasted on unused definitions
- No CPU scanning irrelevant patterns
- Filesystem cache makes repeated calls free

---

## Predictability

```
BADHAT: response time = f(URI complexity)
Framework: response time = f(app size + URI position)
```


## Conclusion

### Framework: Front-loaded Cost

- Route table permanently in RAM
- Parse all routes at bootstrap
- O(n×m) scan every request
- Linear degradation as routes grow

### BADHAT: Pay-per-use

- ~200 bytes temporary strings
- Zero preprocessing
- O(d) calls for current URI only
- Constant regardless of app size