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

// named handler so we can detect replacement via ob_get_status()['name']
function ob_fence(string $buf, int $phase = 0): string { return $buf; }

function ob_trim(int $min, string $where = ''): void
{
    for ($n = \ob_get_level(); $n > $min; --$n)
        (\ob_end_clean() !== false) || throw new \RuntimeException(__FUNCTION__ . ":ob_end_clean:FAIL:$where:level=$n:min=$min");
}

function ob_guard_begin(string $tag = ''): array
{
    $base = \ob_get_level();                                                                  // baseline before step
    \ob_start(__NAMESPACE__ . '\\ob_fence');                                                  // fence at base+1
    return ['base' => $base, 'tag' => $tag];
}

function ob_guard_end(array $tok, ?\Throwable &$fault, string $phase, string $file): string
{
    $base = (int)($tok['base'] ?? 0);
    $tag  = (string)($tok['tag']  ?? ($phase . ':' . $file));

    try {
        $lvl = \ob_get_level();

        if ($lvl <= $base) {                                                                  // fence popped (or lower)
            $fault = new \Exception(__FUNCTION__ . ":ob:BREACH:$phase:$file", 0xBADC0DE, $fault);
            return '';
        }

        if ($lvl > $base + 1) ob_trim($base + 1, "leak:$tag");                                // drop leaks above fence

        $st = \ob_get_status();
        if (($st['name'] ?? '') !== __NAMESPACE__ . '\\ob_fence') {                           // handler replaced
            $fault = new \Exception(__FUNCTION__ . ":ob:HOP:$phase:$file", 0xBADC0DE, $fault);
            (\ob_end_clean() !== false) || throw new \RuntimeException(__FUNCTION__ . ":ob_end_clean:FAIL:fence:$tag");
            return '';
        }

        return (string)\ob_get_clean();                                                       // harvest fence, close it
    } finally {
        ob_trim($base, "finally:$tag");                                                       // ALWAYS restore baseline
    }
}


function loop($file_paths, $args = [], $behave = 0): array
{
    $seed = $args;
    $loot = $seed;

    foreach ($file_paths as $file) {

        $in   = (RELOOT & $behave) ? $loot : $seed;
        $loot = $in;                                                                          // shared include space: $loot is consumed by include

        $fault = null;

        // ---- include ----------------------------------------------------
        $tok = null;
        (BUFFER & $behave) && ($tok = ob_guard_begin("include:$file"));

        try {
            $loot[INC_RETURN] = include $file;                                                // stays HERE: shared scope preserved
        } catch (\Throwable $t) {
            $fault = new \Exception(__FUNCTION__ . ":include:$file", 0xBADC0DE, $t);
        }

        (BUFFER & $behave) && ($loot[INC_BUFFER] = ob_guard_end($tok, $fault, 'include', (string)$file));

        // ---- invoke -----------------------------------------------------
        if (INVOKE & $behave) {
            // optional: guard invoke leaks without capturing (still uses same fence,
            // but you can decide to keep it or not — here I keep it symmetrical)
            $itok = null;
            (BUFFER & $behave) && ($itok = ob_guard_begin("invoke:$file"));

            try {
                $loot[INC_RETURN] = boot($file, $loot[INC_RETURN] ?? null, $loot, $behave, $fault);
            } finally {
                // we do NOT overwrite INC_BUFFER: include output keeps its meaning
                (BUFFER & $behave) && ob_guard_end($itok, $fault, 'invoke', (string)$file);
            }
        }

        if ($fault !== null && !(FAULT_AHEAD & $behave)) throw $fault;
    }

    return $loot;
}

function boot(string $file, $callable, array $args, int $behave, ?\Throwable &$fault = null)
{
    if ($fault !== null && !(RESCUE_CALL & $behave)) return $callable;                        // include fault vetoes invoke (unless rescued)

    $ok = ($callable instanceof \Closure)                                                     // default: Closure-only (deterministic)
       || ((ANYCALL & $behave) && \is_callable($callable));                                   // opt-in: accept any callable

    if (!$ok) return $callable;                                                               // not callable => no-op, keep original value

    $call_args = $args;                                                                       // callable input bag = this step bag

    if (($behave & ABSORB) === ABSORB)                                                        // ABSORB handshake requires BUFFER+INVOKE
        $call_args[] = (string)($args[INC_BUFFER] ?? '');                                     // append include capture only

    try {
        return (SPREAD & $behave) ? $callable(...$call_args) : $callable($call_args);         // invoke callable, return its result
    } catch (\Throwable $t) {
        $fault = new \Exception(__FUNCTION__ . ":invoke:$file", 0xBADC0DE, $t);               // normalize invoke fault, preserve chain
        return $callable;                                                                     // keep original callable in INC_RETURN on failure
    }
}
