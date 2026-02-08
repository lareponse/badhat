<?php

namespace bad\run;

const BUFFER      = 1;                                                                        // capture output (per step) into INC_BUFFER
const INVOKE      = 2;                                                                        // invoke return value if callable (per step)
const ABSORB      = 4 | BUFFER | INVOKE;                                                      // append captured output as extra arg (requires BUFFER+INVOKE)
const SPREAD      = 8;                                                                        // invoke callable receives positional args (else: one bag arg)

const RESCUE_CALL = 16;                                                                       // try invoke even if include faulted
const ANYCALL     = 32;                                                                       // INVOKE accepts any callable (default: Closure-only)

const RELOOT      = 64;                                                                       // pipe: next step input = previous step loot (else: always seed args)
const FAULT_AHEAD = 128;                                                                      // pipe: keep going on fault (else: throw)

const INC_RETURN  = -1;                                                                       // loot slot: last return value (include or invoke)
const INC_BUFFER  = -2;                                                                       // loot slot: last captured output (string)

function loop($file_paths, $args = [], $behave = 0): array
{
    $seed = $args;                                                                            // immutable input unless RELOOT
    $loot = $seed;                                                                            // last produced bag

    foreach ($file_paths as $file) {
        $in = (RELOOT & $behave) ? $loot : $seed;                                             // pick next step input (pipe policy)

        $fault = null;                                                                        // per-step fault latch
        $loot  = loot($file, $in, $behave, $fault);                                           // execute one step

        if ($fault !== null && !(FAULT_AHEAD & $behave)) throw $fault;                        // default: stop on fault
    }

    return $loot;                                                                             // final bag (args + INC_* slots)
}

function loot(string $file, array $args, int $behave, ?\Throwable &$fault = null): array
{
    $fault = null;                                                                            // clear by default
    $loot  = $args;                                                                           // step output starts as input

    $base = \ob_get_level();                                                                  // snapshot buffer depth for cleanup/restore
    (BUFFER & $behave) && \ob_start();                                                        // start capture for this step (if enabled)

    try {
        $loot[INC_RETURN] = include $file;                                                    // include executes file, stores its return value
    } catch (\Throwable $t) {
        $fault = new \Exception(__FUNCTION__ . ":include:$file", 0xBADC0DE, $t);                // normalize include fault, preserve chain
    }

    if (INVOKE & $behave)                                                                     // optional invoke stage
        $loot[INC_RETURN] = boot($file, $loot[INC_RETURN] ?? null, $args, $behave, $fault);  // may update fault, may no-op

    $keep = $base + ((BUFFER & $behave) ? 1 : 0);                                             // expected level after step (baseline + our capture)
    for ($n = \ob_get_level(); $n > $keep; --$n) \ob_end_clean();                             // drop nested buffers opened inside file/callable

    (BUFFER & $behave)                                                                        // finalize capture for this step
        && ($loot[INC_BUFFER] = (\ob_get_level() > $base) ? (string)\ob_get_clean() : '');    // pop ours if still present, else empty

    return $loot;                                                                             // return updated bag (args + INC_* slots)
}

function boot(string $file, $callable, array $args, int $behave, ?\Throwable &$fault = null)
{
    if ($fault !== null && !(RESCUE_CALL & $behave)) return $callable;                        // include fault vetoes invoke (unless rescued)

    $ok = ($callable instanceof \Closure)                                                     // default: Closure-only (deterministic)
       || ((ANYCALL & $behave) && \is_callable($callable));                                   // opt-in: accept any callable

    if (!$ok) return $callable;                                                               // not callable => no-op, keep original value

    $call_args = $args;                                                                       // callable input bag = this step input bag
    (ABSORB & $behave) && ($call_args[] = (BUFFER & $behave) ? (string)\ob_get_contents() : ''); // append current capture (if any)

    try {
        return (SPREAD & $behave) ? $callable(...$call_args) : $callable($call_args);         // invoke callable, return its result
    } catch (\Throwable $t) {
        $fault = new \Exception(__FUNCTION__ . ":invoke:$file", 0xBADC0DE, $t);                 // normalize invoke fault, preserve chain
        return $callable;                                                                     // keep original callable in INC_RETURN on failure
    }
}
