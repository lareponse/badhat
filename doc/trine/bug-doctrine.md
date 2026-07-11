# BADHAT BUG DOCTRINE

# Core

1. Return when the request is wrong.
2. Trigger when PHP should complain.
3. Throw when the system is wrong.
4. Emit only at the HTTP edge.

Record escaped failures and diagnostics through `bad\bug`.

## Bug?

```text
15:45 Relay #70 Panel F (moth) in relay. First actual case of bug being found.
                                                          - September 9, 1947
```

A bug is not a bad user outcome.

A bug is something inside the system that prevented the machine from keeping its promise.

* A bad login is not a bug.
* An invalid form is not a bug.
* A missing public page is not a bug.
* An expired CSRF token is not a bug.

Those are expected outcomes the system must detect and handle.

They belong to:

```php
return
```

**A bug is system-side wrongness.**

# PHP

BADHAT does not invent new failure mechanics.

It assigns doctrine to ordinary PHP mechanics.

```text
return
    Expected application decision.
    The request may be wrong.
    The system is still working.

error_log()
    Log only.
    No BADHAT control-flow meaning.

trigger_error()
    Intentional PHP diagnostic.
    PHP should complain.
    The program is not necessarily broken.

throw
    System failure.
    A promise required by the application or framework is broken.

exit
    Terminal script end.
    Reserved for edges and final boundaries.
```

## Grammar

PHP bug tokens, concepts, constructs, sorted by roughness.

|                   |      Kind | BADHAT meaning               |
| ----------------- | --------: | ---------------------------- |
| Engine warnings   |    engine | PHP diagnostic               |
| `error_log()`     |  function | Log only                     |
| `trigger_error()` |  function | Enter PHP diagnostic channel |
| Engine errors     |    engine | PHP failure                  |
| `throw`           | construct | Broken system promise        |
| `exit`            | construct | End script                   |
| `die`             | construct | Alias of `exit`              |

# BADHAT

Need a PHP diagnostic?

* `trigger_error(...)`
* `bad\bug` can record the PHP diagnostic channel when configured

Need only a log line?

* `error_log(...)`
* it just logs
* it is not handled as a BADHAT decision

Did PHP itself fail?

* let `Error`, `TypeError`, `ParseError`, and related failures escape
* `bad\bug` can record uncaught throwables and fatal shutdowns when configured

Did a BADHAT system promise fail?

* `throw`
* see the house rules for exception types

## Architecture of failure

### app code

* `return` expected outcomes explicitly
* may `trigger_error()` PHP diagnostics
* `throw` only when something is broken
* does not call `exit`

Application code may catch what it can actually handle.

If it does not catch the failure, the failure reaches the panic boundary.

That boundary is the only place where escaped application failure becomes HTTP.

### public/index.php

* catches with fine-grained precedence
* maps failure to HTTP status
* chooses JSON, admin HTML, or public error page
* passes the final response to `bad\http`

### bad\bug

All escaped system failures can be recorded by `bad\bug` when configured.

It records PHP-side events:

```text
diagnostics
uncaught throwables
fatal shutdowns
```

It does not decide what the user sees.

It does not recover the application.

It does not turn bad requests into bugs.

It may be told to clean or flush buffers.

Expected request outcomes are not automatically failures to log.

A bad login, a missing public route, an invalid form field, or an invalid CSRF token may be observed or audited when useful, but they are not system failures.

# BADHAT Exception House Style

A thrown exception means a promise the system depends on has been broken.

Now ask:

```text
Who owned the broken promise?
```

There are only two owners.

| Broken promise owner                                                                       | Family             |
| ------------------------------------------------------------------------------------------ | ------------------ |
| BADHAT's own code, trusted call, declaration, schema, DSL, contract, or invariant          | `LogicException`   |
| Runtime world: filesystem, database, environment, external system, loaded data, or storage | `RuntimeException` |

This is the central split.

```text
LogicException
    BADHAT asked itself to do something wrong.

RuntimeException
    The runtime world failed to provide something BADHAT needed.
```

The trusted/loaded distinction matters.

```text
Trusted value is wrong
    → LogicException family

Loaded value is wrong
    → RuntimeException family
```

A bad value from config, storage, database, filesystem, request cache, generated file, or external system is runtime wrongness, even if it looks like a domain, range, or length problem.

Everything else is outside normal developer signaling:

```text
Error
    PHP engine or type-system failure.
    Do not intentionally throw for application signaling.

ErrorException
    Only relevant when deliberately converting PHP errors into exceptions.
    Not normal BADHAT application signaling.

Exception
    Too vague.
    Do not intentionally throw raw Exception.
```

## The real exception question

Once BADHAT has decided to `throw`, the question is not:

```text
Which PHP exception class do I remember?
```

The question is:

```text
Who owned the broken promise?
```

Then choose the sharper class only if it says the failure more clearly.

## Logic side

```text
Internal promise broken
    → LogicException family
```

| Shape of the internal break                                                            | Class                      |
| -------------------------------------------------------------------------------------- | -------------------------- |
| The requested operation cannot exist: bad mode, flags, target, or argument combination | `BadFunctionCallException` |
| The operation is coherent, but one trusted argument violates the function contract     | `InvalidArgumentException` |
| A trusted value violates a formal domain, grammar, rule set, or DSL                    | `DomainException`          |
| A trusted value is outside a finite allowed set or range                               | `OutOfRangeException`      |
| A trusted value violates an exact length contract                                      | `LengthException`          |
| Internal rule broken, but no sharper subtype helps                                     | `LogicException`           |

Useful distinction:

```text
BadFunctionCallException
    The sentence is impossible.

InvalidArgumentException
    The sentence is valid, but one word is wrong.
```

## Runtime side

```text
Runtime promise broken
    → RuntimeException family
```

| Shape of the runtime break                                                  | Class                                        |
| --------------------------------------------------------------------------- | -------------------------------------------- |
| PDO, driver, connection, query execution, or database availability failed   | `PDOException`, usually thrown by PDO itself |
| Runtime data exists but its shape, type, meaning, or storage state is wrong | `UnexpectedValueException`                   |
| Generic runtime, resource, environment, file, or external promise failed    | `RuntimeException`                           |

Useful distinction:

```text
RuntimeException
    The world did not provide the thing.

UnexpectedValueException
    The world provided the thing, but the thing is wrong.
```

Examples:

```text
Required config file missing
    → RuntimeException

Credentials file exists but does not contain the expected keys
    → UnexpectedValueException

Database connection failed inside PDO
    → PDOException
```
