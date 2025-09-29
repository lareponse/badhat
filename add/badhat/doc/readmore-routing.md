# Performance Architecture: Routing Complexity Core

**Real-world applications:**
- Most requests hit a small subset of routes (80/20 rule)
- Frameworks preload and scan routes that are never used
- BADHAT only touches filesystem paths related to the current request

**Resource efficiency:**
- No memory wasted on unused route definitions
- No CPU cycles scanning irrelevant patterns
- Filesystem cache makes repeated calls nearly free

**Performance predictability:**
- BADHAT response time: depends on URI complexity
- Framework response time: depends on total application size + URI position in route list



## The Fundamental Architectural Difference

**BADHAT routing time depends only on URI depth, not total routes.**



### BADHAT: O(d) Filesystem Resolution
```php
// /api/users/alter/123 = 4 segments deep
// Performance: O(1) to O(2d) regardless of application size

URI depth = 1: /users                       → max  2 filesystem calls
URI depth = 4: /api/users/alter/123         → max  8 filesystem calls  
URI depth = 5: /api/v2/users/edit/123       → max 12 filesystem calls

// If you reach 5+ segments, it’s time to rethink 
```



### Framework: O(n×m) Route Table Scan
```php
// Must scan ALL routes regardless of URI complexity
$routes = [
    '/simple' => 'Controller@simple',           // Route #1
    '/api/users/alter/{id}' => 'UserController@alter',  // Route #500
    // ... 4,500 more routes
];

// To match /api/users/alter/123:
// Scan route #1, #2, #3... #500 until match found
// Performance degrades as application grows
```

## Scaling Behavior
### Framework Performance Degradation
```
100 routes:   /api/users/alter/123 → scan ~50 routes  → 1,200 regex ops
1,000 routes: /api/users/alter/123 → scan ~500 routes → 12,000 regex ops  
5,000 routes: /api/users/alter/123 → scan ~2,500 routes → 60,000 regex ops

Performance: O(n) where n = total application routes
```

### BADHAT Constant Performance  
```
100 routes:   /api/users/alter/123 → 3-8 filesystem calls
1,000 routes: /api/users/alter/123 → 3-8 filesystem calls
5,000 routes: /api/users/alter/123 → 3-8 filesystem calls

Performance: O(d) where d = URI depth (typically 2-6)
```
> **Architectural advantage:** performance scales with request complexity, not application complexity

## Memory Architecture Comparison
### Framework: Preloaded Route Table
```php
// Laravel RouteCollection in memory
class RouteCollection {
    protected $routes = [];          // 5,000 Route objects
    protected $allRoutes = [];       // Flat array for iteration  
    protected $nameList = [];        // Named route lookup
    protected $actionList = [];      // Controller lookup
}

// Memory usage: 5,000 routes × ~700 bytes = 3.5MB route table
// ALWAYS loaded, regardless of which route is needed
```

### BADHAT: No Route Table
```php
// Zero preloaded routes
// URI /api/users/alter/123 triggers:
$candidates = [
    'route/api/users/alter/123.php',      // Try exact match
    'route/api/users/alter.php',          // Try parent with args
    'route/api/users.php',                // Try grandparent  
    'route/api.php'                       // Try root
];

// Memory usage: ~200 bytes for string building
// Only attempts paths relevant to current URI
```
---
# Conclusion

### Framework: Front-loaded Cost
- **Memory:** route table permanently in RAM
- **Startup:** parse and compile all routes during bootstrap  
- **CPU:** O(n×m) scanning for every request
- **Scaling:** Linear degradation as routes increase

### BADHAT: Pay-per-use
- **Memory:** ~200 bytes temporary strings
- **Startup:** zero route preprocessing
- **CPU:** O(d) filesystem calls only for requested URI
- **Scaling:** Constant performance regardless of app size