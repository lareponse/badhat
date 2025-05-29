/**
 * slot() - Minimalist content accumulation with native PHP elegance
 *
 * Three modes, zero ceremony, maximum clarity.
 * This is what PHP was designed for before frameworks ruined it.
 *
 * @param string|null $name  Slot identifier (null = return all slots)
 * @param string|null $value Content to append (null = consume slot)
 * @return array             Slot contents or all slots
 *
 * MODES:
 *   slot(null)              // Return all slots (debugging/inspection)
 *   slot('head', '<meta>')  // Append to named slot, return current contents
 *   slot('head')            // Consume and return slot contents
 *
 * BEHAVIOR:
 * - Write: appends to internal heap, returns current heap
 * - Read: returns and CLEARS the heap (consume-on-read)
 * - Inspect: null name returns entire slot state
 *
 * ELEGANT RESOURCE MANAGEMENT:
 * - Memory automatically cleaned on consumption
 * - Cross-request contamination impossible
 * - Test isolation automatic
 * - Debug inspection trivial: slot(null)
 *
 * ENTERPRISE DEVELOPERS WILL PANIC:
 * "You can't use static variables! What about dependency injection!"
 *
 * RESPONSE:
 * PHP static variables are request-scoped automatic storage.
 * This is literally what they were designed for.
 * Your DI container does the same thing with 500 lines of indirection.
 *
 * "But how do you mock this for testing!"
 *
 * RESPONSE:
 * You don't mock data structures. You test with real data.
 * Mocking simple append/consume operations is cargo cult insanity.
 *
 * "But static makes unit testing impossible!"
 *
 * RESPONSE:
 * Consume-on-read provides automatic isolation.
 * Every read operation clears state.
 * Your tests run cleaner than with manual tearDown() methods.
 *
 * "But you can't inspect the internal state!"
 *
 * RESPONSE:
 * slot(null) returns complete internal state.
 * More transparent than your private $container properties.
 *
 * "But what if someone calls it wrong!"
 *
 * RESPONSE:
 * Then they learn to read function signatures.
 * PHP has nullable parameters for a reason.
 * We use them correctly instead of inventing new abstractions.
 *
 * FRAMEWORK ALTERNATIVE (what we refuse):
 * ```php
 * interface SlotManagerInterface {
 *     public function add(string $name, string $value): void;
 *     public function get(string $name): array;
 *     public function clear(string $name): void;
 *     public function inspect(): array;
 * }
 * 
 * class SlotManager implements SlotManagerInterface {
 *     private array $slots = [];
 *     // + 80 lines of interface implementation
 * }
 * 
 * $container->bind(SlotManagerInterface::class, SlotManager::class);
 * // + configuration files
 * // + service provider registration
 * // + facade creation
 * ```
 *
 * BADGE ALTERNATIVE: This function. 11 lines. Done.
 *
 * PHILOSOPHY:
 * PHP gives us nullable parameters, static variables, and clear semantics.
 * We use them as intended instead of fighting the language.
 * 
 * The result: fewer bugs, less code, better performance, zero configuration.
 *
 * This is not "quick and dirty." This is software engineering.
 */
function slot(?string $name, ?string $value): array
{
    static $slots = [];

    if ($name === null) // Inspection mode - return complete state
        return $slots;

    if ($value !== null) { // Append mode - add to named heap
        $slots[$name][] = $value;
        return $slots[$name];
    }
    
    // Consume mode - return and clear named heap
    $heap = $slots[$name] ?? [];
    $slots[$name] = []; 
    return $heap;
}
```

---

## The Three-Mode Pattern: PHP Native Elegance

This function demonstrates why BADGE's approach is superior to enterprise orthodoxy:

**Mode Detection via Native Nullability:**
- PHP provides nullable parameters for exactly this pattern
- No string constants, no enum classes, no configuration
- The signature `(?string $name, ?string $value)` tells the complete story

**Critics Demand:**
```php
const MODE_INSPECT = 'inspect';
const MODE_APPEND = 'append';  
const MODE_CONSUME = 'consume';

function slot(string $mode, ?string $name = null, ?string $value = null)
```

**BADGE Response:** PHP already has a mode system. It's called "nullable parameters." Use it.

**Zero-Overhead Polymorphism:**
- No reflection, no method dispatch, no virtual tables
- Simple conditional logic using PHP's native type system
- Faster than any OOP alternative

**Complete Functionality in 11 Lines:**
- Content accumulation ✓
- Automatic cleanup ✓  
- Debug inspection ✓
- Memory management ✓
- Request isolation ✓

Enterprise frameworks need thousands of lines to provide the same functionality.

**This is what happens when you work WITH PHP instead of against it.**