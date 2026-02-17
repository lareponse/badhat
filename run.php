<?php

namespace bad\run;

const BUFFER      = 1;                                              // capture output (per step) into INC_BUFFER
const INVOKE      = 2;                                              // invoke return value if callable (per step)
const ABSORB      = 4 | BUFFER | INVOKE;                            // append captured output as extra arg (requires BUFFER+INVOKE)
const SPREAD      = 8;                                              // invoke callable receives positional args (else: one bag arg)

const RESCUE_CALL = 16;                                             // try invoke even if include faulted
const ANYCALL     = 32;                                             // INVOKE accepts any callable (default: Closure-only)

const RELOOT      = 64;                                             // pipe: next step input = previous step loot (else: always seed args)
const FAULT_AHEAD = 128;                                            // pipe: keep going on fault (else: throw)

const INC_RETURN  = -1;                                             // loot slot: last return value (include or invoke)
const INC_BUFFER  = -2;                                             // loot slot: last captured output (string)


function loop($file_paths, $args = [], int $behave = 0, ?\Closure $seal = null): array
{
    $seed  = $args;
    $loot  = $seed;
    $seal = $seal ?? static function ($token = null) {return ($token === null) ? 0 : '';};                                    // default: no buffering

    foreach ($file_paths as $file) {

        $in   = (RELOOT & $behave) ? $loot : $seed;
        $loot = $in;

        $fault = null;

        $tok = $seal();                                            // begin

        try {
            $loot[INC_RETURN] = include $file;
        } catch (\Throwable $t) {
            $fault = new \Exception(__FUNCTION__ . ":include:$file", 0xBADC0DE, $t);
        } finally {
            try {
                if (BUFFER & $behave)
                    $loot[INC_BUFFER] = $seal($tok);               // end + store
                else
                    $seal($tok);                                   // end only, ignore capture
            } catch (\Throwable $t) {
                $ob_fault = new \Exception(__FUNCTION__ . ":seal:include:$file", 0xBADC0DE, $t);
                $fault = $fault ? new \Exception(__FUNCTION__ . ":include+seal:$file", 0xBADC0DE, $fault) : $ob_fault;
            }
        }

        if (INVOKE & $behave) {

            $itok = $seal();                                       // begin (invoke fence)

            try {
                $loot[INC_RETURN] = boot((string)$file, $loot[INC_RETURN] ?? null, $loot, $behave, $fault);
            } finally {
                try { $seal($itok); }                              // end
                catch (\Throwable $t) {
                    $ob_fault = new \Exception(__FUNCTION__ . ":seal:invoke:$file", 0xBADC0DE, $t);
                    $fault = $fault ? new \Exception(__FUNCTION__ . ":invoke+seal:$file", 0xBADC0DE, $fault) : $ob_fault;
                }
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
