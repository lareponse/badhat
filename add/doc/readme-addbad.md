# BADHAT Philosophy

**For architects and technical leads evaluating BADHAT**

---

## The Refusal

BADHAT is not a framework. It's a **refusal**.

A refusal of:
- Custom classes and object-oriented ceremony
- Namespaces and autoloading overhead
- Routing tables and middleware stacks
- Template engines and abstraction layers
- Configuration files and dependency injection

**Modern frameworks optimize for developer comfort**  
**BADHAT optimizes for application speed**

This is not retro. This is not a learning exercise.  
This is **first-to-last-bit-performance web development**.

---

## Core Philosophy: Code with Clear Purpose

Software should deliver precise functionality with minimal overhead. Every line, dependency, and abstraction must justify its place in the system.

BADHAT doesn't scale by adding abstractions.  
It scales by composition of simple, measurable parts.

---

## Six Principles

### 1. Eliminate Unnecessary Bloat

**Observation:** Many projects ship hundreds of megabytes of dependencies—often eclipsing the core application.

**Principle:** Keep deployments compact.

**Practice:**
- Audit all libraries: remove unused modules and transitive dependencies
- Prefer small, single-purpose packages over large monoliths  
- Bundle only what you use; defer or lazy-load non-critical components

**Example:** A typical Laravel installation includes 200+ packages totaling 50-80MB before writing any application code. BADHAT core is 400 lines across 8 files.

### 2. Master Your Tools, Not Just Your Framework

**Observation:** Developers frequently rely on complex toolchains without grasping the underlying language or runtime.

**Principle:** Tools should amplify expertise, not replace it.

**Practice:**
- Invest time in core language and platform documentation
- Read source or spec when behavior is unclear
- Use frameworks judiciously—understand their lifecycle, conventions, and trade-offs

**Example:** Many PHP developers cannot write efficient PDO queries because they've only used ORMs. BADHAT treats SQL as a first-class language.

### 3. Build for Your Users, Not Vendor Lock-In

**Observation:** Many abstractions exist to bind you into a vendor's ecosystem.

**Principle:** Architect for portability and user value.

**Practice:**
- Pin requirements to open standards or well-maintained OSS
- Layer vendor-specific code behind clear interfaces
- Reevaluate every third-party integration: is it chosen for technical fit or marketing promise?

**Example:** BADHAT uses plain PDO (standard), plain PHP files (portable), and filesystem routing (no vendor-specific concepts).

### 4. Focus on Five Core Primitives

Most applications can be built from these building blocks:

1. **Input** – Data ingestion or request handling
2. **Output** – Rendering or emitting results  
3. **State** – In-memory or persisted state management
4. **Data Storage** – Database or filesystem interactions
5. **Logic** – Core decision-making and business rules

*Advanced features* (queues, caching layers, ORMs, service meshes) belong only when their benefits clearly outweigh added complexity.

**Example:** Before adding Redis for caching, ask: "Does our database query time justify the complexity of cache invalidation logic?"

### 5. Favor Clarity Over Convenience

**Observation:** High-level abstractions can obscure execution flow and complicate debugging.

**Principle:** Code should read like a roadmap.

**Practice:**
- Prefer explicit control flow over "magic"
- Break complex operations into small, well-named functions
- Document side-effects and invariants at module boundaries

**Example:** 
```php
// Magic (convenient but unclear)
User::where('active', true)->with('posts')->get();

// Explicit (clear execution path)
$users = dbq(db(), "SELECT * FROM users WHERE active = 1")->fetchAll();
$posts = dbq(db(), "SELECT * FROM posts WHERE user_id IN (" . implode(',', $user_ids) . ")")->fetchAll();
```

### 6. Automate with Intent

**Observation:** It's easy to automate every repeatable task—and end up maintaining scripts that serve developers more than clients.

**Principle:** Every automation must deliver measurable client value.

**Practice:**
1. **Map to user outcomes:** Link each automated step to a customer-facing improvement (speed, reliability, clarity)
2. **Keep it minimal:** Build small, well-documented scripts instead of sprawling pipelines
3. **Review and retire:** Quarterly audit automations; deprecate any whose maintenance cost exceeds their benefit

**Example:** Automated testing that catches user-facing bugs delivers client value. Automated code formatting primarily serves developer convenience.

---

## Decision Framework: When to Choose BADHAT

### Choose BADHAT When:

**Performance is Critical**
- High-traffic applications (1000+ req/sec)
- Real-time or low-latency requirements
- Resource-constrained environments
- Cost optimization is important (fewer servers needed)

**Control is Valued**
- You want to understand every line that executes
- Debugging needs to be straightforward
- No "magic" or hidden behavior is acceptable
- Direct access to request/response/database layers is required

**Team Characteristics**
- Small, experienced teams (2-8 developers)
- Strong PHP fundamentals knowledge
- Comfortable with procedural programming
- Values simplicity over convention

**Application Types**
- APIs and microservices
- Admin dashboards and internal tools
- Content management systems
- Real-time applications
- High-performance web applications

### Choose Frameworks When:

**Convention Over Performance**
- Large teams requiring strict coding standards
- Rapid prototyping where speed-to-market matters most
- Heavy business logic requiring complex abstractions
- Teams with mixed experience levels

**Ecosystem Dependencies**
- Need for extensive third-party integrations
- Complex authentication/authorization requirements
- Requirement for specific framework-dependent packages
- Long-term maintenance by varying team compositions

**Application Complexity**
- Enterprise applications with complex business rules
- Applications requiring heavy ORM relationships
- Multi-tenant SaaS with complex pergoals
- Applications where framework conventions reduce cognitive load

### Hybrid Approach

Many successful applications use BADHAT for performance-critical paths and frameworks for complex business logic:

```php
// Hot path: Direct BADHAT for API endpoints
// /api/* routes use BADHAT for maximum performance

// Admin interface: Framework for complex forms/validation
// /admin/* routes can use Laravel/Symfony for developer productivity
```

---

## Philosophy in Practice

### From Principle to Implementation

**Principle:** Eliminate Bloat  
**Implementation:** File-based routing eliminates route compilation and registration overhead

**Principle:** Master Your Tools  
**Implementation:** Direct PDO usage instead of ORM abstraction

**Principle:** User Value Over Vendor Lock-in  
**Implementation:** Plain PHP files, standard SQL, no framework-specific concepts

**Principle:** Five Core Primitives  
**Implementation:** Each BADHAT function maps directly to Input, Output, State, Storage, or Logic

**Principle:** Clarity Over Convenience  
**Implementation:** Explicit function calls, no magic methods, clear execution path

**Principle:** Intentional Automation  
**Implementation:** Minimal build process, no complex deployment pipelines unless justified

### Measuring Success

BADHAT's philosophy succeeds when:
- Response times are consistently under 50ms
- Memory usage stays under 2MB per request
- Debugging takes minutes, not hours
- New developers understand the codebase in days, not weeks
- Infrastructure costs scale linearly with traffic

Traditional framework success metrics (developer velocity, code reuse, convention adherence) are secondary to these runtime characteristics.

---

## Common Objections and Responses

**"This looks like PHP from 2005"**  
Modern doesn't always mean better. BADHAT uses modern PHP features (7.4+) but avoids abstractions that add overhead without proportional benefit.

**"What about code reuse?"**  
Function composition provides reuse without inheritance complexity. Small, focused functions are easier to reuse than large, abstract classes.

**"How do you maintain consistency across team members?"**  
File structure and function naming conventions provide consistency. Code reviews focus on performance and clarity rather than framework compliance.

**"What about testing?"**  
BADHAT includes a minimal testing framework. Procedural code is often easier to test than complex object hierarchies.

**"Doesn't this create vendor lock-in to BADHAT?"**  
BADHAT is 400 lines of readable PHP. It's easier to understand and modify than most framework documentation.

---

## Conclusion

BADHAT's philosophy prioritizes:
1. **Runtime performance** over developer convenience
2. **Explicit behavior** over magical abstractions  
3. **Minimal dependencies** over rich ecosystems
4. **Direct control** over framework conventions
5. **Measurable outcomes** over subjective preferences

This philosophy produces applications that are faster, cheaper to operate, and easier to debug—at the cost of some developer conveniences that modern frameworks provide.

The question for each team: **Is the performance gain worth the convenience trade-off?**

For high-traffic applications, resource-constrained environments, or teams that value control, the answer is often yes.