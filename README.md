# BADHAT

```
Bits As Decision
HTTP As Terminal
```


> **A request is just a path that becomes a file that becomes execution.**

**BADHAT** is a minimalist PHP **execution engine** that treats HTTP like a terminal:
files decide, files execute, files emit output.

No controllers.

No routers.

No middleware stacks.

No framework lock-in.

Just ~200 lines of PHP that add **almost no overhead beyond PHP itself**, suitable for a large class of **direct, file-driven web applications**.



# PHP structure without the theology

PHP had a simple premise: URL maps to file, file runs, output goes to browser. Done. A contact form was one file. A blog was five.

Then PHP got ambitious. Or rather, PHP developers got insecure.

## The legitimacy problem

For years, PHP was "not a real language." Java had enterprise credibility. So PHP borrowed Java's patterns: MVC, front controllers, dependency injection containers, service providers, interface segregation, repository abstractions. Symfony explicitly modeled itself on Spring.

The patterns worked. PHP got respect. And somewhere along the way, the patterns became mandatory.

A junior developer today learns Laravel before learning PHP. They inherit a universe where a page request boots 400 files, passes through 14 middleware layers, resolves dependencies from a container, dispatches to a controller that calls a service that calls a repository that runs one query — and finally returns an "immutable response object" that says hello.

Nobody questions this. Questioning it would be unprofessional.

## The theology

Every religion has doctrines. PHP framework culture has these:

**Separation of Concerns.** Models, Views, Controllers — each in sacred directories. Even when your model is one query. Even when your view is ten lines. The separation is the virtue, not the outcome.

**Dependency Injection.** Never instantiate directly. Everything through containers. Your app has one database connection, used everywhere, unchanged for years. Doesn't matter. Inject it. What if you need to swap it? You won't. But what if?

**Don't Repeat Yourself.** Three lines appear twice? Extract a helper. Now a trait. Now a service class. Now you're reading four files to follow one action. But at least you didn't repeat yourself.

**Testability.** Entire architectures warped around unit tests that never get written. Interfaces for classes with one implementation. Mocks for things that will never change. The tests are theoretical. The indirection is permanent.

These aren't wrong. They solve real problems — at scale, with large teams, over long maintenance windows. The theology is applying them unconditionally. The belief that ceremony is correctness. That structure is virtue, independent of context.

A 5-page marketing site doesn't have the problems these patterns solve. It has the problems these patterns create.

## What PHP actually was

Early PHP had no opinions. A URL was a file path. The file executed top to bottom. `include` was your composition mechanism — pull in a header, pull in a database connection, pull in a footer. Global state everywhere because scope wasn't the problem. Shipping was the problem.

No "business logic" as a concept. A script would authenticate, query, loop, and echo HTML in fifty lines. It worked. It shipped. It was unmaintainable past a certain size — and most projects never hit that size.

The modern stack solves maintainability at scale. The cost is maintainability at small scale. A solo developer on a simple project now needs framework knowledge, directory conventions, configuration files, and a mental model of the request lifecycle before writing line one.

The old model was chaotic. The new model is bureaucratic. Both fail, differently.

## badhat

badhat is a bet that there's space between chaos and bureaucracy.

The filesystem is the router. You request `/admin/users`, badhat looks for `admin/users.php` or walks back to `admin.php` with `['users']` as arguments. No routing table. No YAML. No annotations. Files are files.

Scripts are scripts. They run. They can return a callable — badhat will invoke it. They can echo output — badhat can buffer it. They can do both. badhat doesn't care.

Pipelines are explicit. You want a boot script, a handler, and a renderer? Build the array, pass it to `run`. You want one file that does everything? Pass one file. Your architecture is your decision.

DRY is applied once, at the infrastructure level. Path resolution, include logic, output capture — written once in badhat, used everywhere in your app. Your app code repeats whatever it wants.

Separation of Concerns exists where it matters. Mapping is separate from execution. Execution is separate from output. Beyond that, organize however you think.

No base controllers. No service providers. No config cascades. No interface contracts. No middleware stacks. No opinions on directory names.

## The actual code

```php
headers(H_SET, 'Content-Type', 'text/html');

[$handler, $segments] = seek($base, $uri, '.php') 
    ?? exit(out(404, 'Not Found'));

$loot = run([$handler], $segments, BUFFER | INVOKE);

exit(out(200, $loot[INC_BUFFER]));

```

Four lines. Request to response. Add a boot script and a renderer if you want. Or don't.

## Who this is for

Developers who remember what PHP was for.

Developers tired of fighting frameworks to do simple things.

Developers who can organize their own directories.

Developers shipping alone or in small teams, without the governance problems that enterprise patterns solve.

Developers who understand that professionalism is solving the problem, not performing the ceremony.

## Who this is not for

Teams that need enforced conventions because they can't agree otherwise.

Projects that genuinely operate at scale, with long maintenance horizons and rotating staff.

Developers who find comfort in structure they didn't choose.

Anyone who thinks this essay is an attack on Laravel. It isn't. Laravel is good at what it is. What it is isn't what everyone needs.

## The point

PHP got complicated because PHP developers wanted respect. The respect came. The complexity stayed.

badhat is simple because simple is enough for most things. It provides structure — file resolution, execution pipelines, output handling — without doctrine about how you use it.

Your app. Your files. Your problem.

badhat just runs them.

---

## License

MIT.
No warranty.
Use at your own risk.