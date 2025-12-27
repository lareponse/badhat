# BADHAT IO — One-Page Guide

BADHAT IO turns **paths into execution**.

It is a small set of execution **primitives** whose **flags** let the filesystem **express architectures** normally imposed by frameworks.

There are no controllers, no routing tables, no required architecture.
Files run. Folders organize. Flags decide how execution flows.

---

## The Rule

> **Find a file. Run it. Decide what to do with what comes out.**


---

## The Primitives

**`io_in`** exists to turn an inbound request into intent.
It normalizes the path and separates what is structural from what is incidental.
Nothing is executed here.

> It has no flags.

---

**`io_map`** exists to decide what can run.
It maps intent to filesystem reality.
It does not execute, it only resolves.
If nothing resolves, execution does not proceed.

By combining deep-first or root-first resolution, it can express either intent-centric or entry-point-centric systems using the same directory tree.

> IO_NEST, IO_DEEP, IO_ROOT
---

**`io_run`** exists to execute.
It takes one or more resolved files and runs them in order.
This is where output, return values, invocation, chaining, and transformation happen.

With buffering enabled, output becomes a value.
With invocation enabled, files become behaviors.
With absorb enabled, output feeds behavior.
With chaining enabled, execution becomes a pipeline.

The same primitive can act as a renderer, a dispatcher, middleware, or a transformer — depending only on flags.

> IO_BUFFER, IO_INVOKE, IO_ABSORB, IO_CHAIN

---

**`io_look`** exists to answer a single question:
“Does this path correspond to an executable file?”
It performs no walking, no guessing, no fallback.

With nesting enabled, a directory can define its own local entry point without any central routing logic.

> IO_NEST

---

**`io_seek`** exists to relax exactness.
It walks a path until something executable appears, and returns what remains.
This is how intent survives missing specificity.

When walking deep-first, specificity wins.
When walking root-first, authority wins.

The same filesystem can therefore describe resources or commands without changing structure.

> IO_DEEP, IO_ROOT and IO_NEST, through io_look calls


---

**`io_die`** exists to stop everything.
It emits a response and terminates execution.
There is no return path.

It is the only absolute boundary in the system.

---

## Flags 

Flags do not introduce new concepts.
They **change what the same primitives are capable of expressing**.

Through their combination, the filesystem can behave as:

* a static site
* a parameterized application
* an API surface
* a data pipeline
* a rendering stack
* a middleware chain

No roles are declared.
No architecture is enforced.
Everything emerges from composition.


---

## Basic Setup

```php
[$path] = io_in($_SERVER['REQUEST_URI']);
$route  = io_map('/app/pages', $path);

$route
    ? io_run($route, [])
    : io_die(404, 'Not Found');
```

```
/about   → /app/pages/about.php
/contact → /app/pages/contact.php
```

Rename a file, the URL changes.

---

## Intent-First Paths (recommended)

BADHAT works best when paths read **left → right**:

```
/users/profile/42
/users/edit/42
/api/users/get/42
```

Intent first, data last.

---

## Parameters via Path Segments

```php
$route = io_map('/app/pages', 'users/profile/42', 'php', IO_DEEP);
$loot  = io_run($route, [], IO_INVOKE);
```

```php
// users.php
return function(array $args) {
    [$action, $id] = $args;
    return "User $id, action $action";
};
```

No placeholders.
Segments are just arguments.

---

## Data → Render (Two-Phase)

```php
$route  = io_map('/app/route', 'users/profile/42', 'php', IO_DEEP);
$loot   = io_run($route, [], IO_INVOKE);

$render = io_map('/app/render', 'users', 'php');
$loot   = io_run($render, $loot, IO_ABSORB);

io_die(200, $loot[IO_RETURN]);
```

* First file returns data
* Second file turns data into output

No enforced roles.

---

## Output Capture (Templates Without Engines)

```php
$route = io_map('/app/pages', 'home');
$loot  = io_run($route, [], IO_BUFFER);

io_die(200, $loot[IO_OUTPUT]);
```

If a file echoes, BADHAT can capture it.

---

## Layout via Output Absorb

```php
// page.php
<h1><?= $title ?></h1>

<?php
return fn($args) =>
    "<html><body>{$args[0]}</body></html>";
```

```php
$loot = io_run(['/app/page.php'], ['title' => 'Home'], IO_ABSORB);
echo $loot[IO_RETURN];
```

Output becomes input.

---

## Chaining Files

```php
$files = [
    '/app/mw/auth.php',
    '/app/route/users.php',
    '/app/render/users.php',
];

$loot = io_run($files, [], IO_INVOKE | IO_CHAIN);
io_die(200, $loot[IO_RETURN]);
```

Each file receives the previous result.
Order is explicit.

---

## JSON API Example

```php
$route = io_map('/app/api', 'users/get/42', 'php', IO_DEEP);
$loot  = io_run($route, [], IO_INVOKE);

io_die(
    200,
    json_encode($loot[IO_RETURN]),
    ['Content-Type' => 'application/json']
);
```
