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

    $base   = 0;                                                                              // buffer baseline (only meaningful if BUFFER)
    $keep   = 0;                                                                              // expected level (only meaningful if BUFFER)
    $cursor = 0;                                                                              // per-step cursor (only meaningful if BUFFER)

    if (BUFFER & $behave) {
        $base = \ob_get_level();
        \ob_start();
        $keep = $base + 1;

        $ob_trim = static function (int $max, string $where = ''): void {
            for ($n = \ob_get_level(); $n > $max; --$n)
                \ob_end_clean()
                    || throw new \RuntimeException(__FUNCTION__ . ":ob_end_clean:FAIL:$where:level=$n:max=$max");
        };

        $buf_mark = static function () use ($keep, $ob_trim): int {
            $ob_trim($keep, 'buf_mark');
            $len = \ob_get_length();
            return ($len === false) ? 0 : $len;
        };

        $buf_take = static function () use (&$cursor, $keep, $ob_trim): string {
            $ob_trim($keep, 'buf_take');

            $len = \ob_get_length();
            $len = ($len === false) ? 0 : $len;

            if ($len <= $cursor) {
                $cursor = $len;
                return '';
            }

            $buf = (string)\ob_get_contents();
            $out = (string)\substr($buf, $cursor);

            $cursor = $len;                                                                   // advance AFTER slicing
            return $out;
        };
    }

    try {
        foreach ($file_paths as $file) {
            $in   = (RELOOT & $behave) ? $loot : $seed;                                       // pick next step input (pipe policy)
            $loot = $in;                                                                      // IMPORTANT: included file consumes `$loot`

            $fault = null;                                                                    // per-step fault latch

            (BUFFER & $behave) && ($cursor = $buf_mark());

            try {
                $loot[INC_RETURN] = include $file;                                            // include runs file, stores its return value
            } catch (\Throwable $t) {
                $fault = new \Exception(__FUNCTION__ . ":include:$file", 0xBADC0DE, $t);      // normalize include fault, preserve chain
            }

            // IMPORTANT: capture per-step delta BEFORE invoke so ABSORB can use it.
            (BUFFER & $behave) && ($loot[INC_BUFFER] = $buf_take());

            if (INVOKE & $behave) {                                                           // optional invoke stage
                $loot[INC_RETURN] = boot($file, $loot[INC_RETURN] ?? null, $loot, $behave, $fault);
            }

            if ($fault !== null && !(FAULT_AHEAD & $behave)) throw $fault;                    // default: stop on fault
        }
    } finally {
        (BUFFER & $behave) && $ob_trim($base);                                                // close root capture (and any strays)
    }

    return $loot;                                                                             // final bag (args + INC_* slots)
}

function boot(string $file, $callable, array $args, int $behave, ?\Throwable &$fault = null)
{
    if ($fault !== null && !(RESCUE_CALL & $behave)) return $callable;                        // include fault vetoes invoke (unless rescued)

    $ok = ($callable instanceof \Closure)                                                     // default: Closure-only (deterministic)
       || ((ANYCALL & $behave) && \is_callable($callable));                                   // opt-in: accept any callable

    if (!$ok) return $callable;                                                               // not callable => no-op, keep original value

    $call_args = $args;                                                                       // callable input bag = this step bag
    (ABSORB & $behave) && ($call_args[] = (string)($args[INC_BUFFER] ?? ''));                 // append per-step delta (not whole transcript)

    try {
        return (SPREAD & $behave) ? $callable(...$call_args) : $callable($call_args);         // invoke callable, return its result
    } catch (\Throwable $t) {
        $fault = new \Exception(__FUNCTION__ . ":invoke:$file", 0xBADC0DE, $t);               // normalize invoke fault, preserve chain
        return $callable;                                                                     // keep original callable in INC_RETURN on failure
    }
}
