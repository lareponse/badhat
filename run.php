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

function loop($file_paths, $args = [], int $behave = 0, ?\Closure $ob = null): array
{
    $seed = $args;
    $loot = $seed;

    foreach ($file_paths as $file) {

        $in   = (RELOOT & $behave) ? $loot : $seed;
        $loot = $in;

        $fault = null;

        $include_ob_guard = $ob ? $ob(\bad\ob\CLEAN) : null;

        (BUFFER & $behave) && \ob_start();

        try {
            $loot[INC_RETURN] = include $file;
        } catch (\Throwable $t) {
            $fault = new \Exception(__FUNCTION__ . ":include:$file", 0xBADC0DE, $t);
        } finally {
            if (BUFFER & $behave)
                $loot[INC_BUFFER] = (string)\ob_get_clean();

            if ($include_ob_guard) {
                try { $include_ob_guard(); }
                catch (\Throwable $t) {
                    $ob_fault = new \Exception(__FUNCTION__ . ":ob:include:$file", 0xBADC0DE, $t);
                    $fault = $fault ? new \Exception(__FUNCTION__ . ":include+ob:$file", 0xBADC0DE, $fault) : $ob_fault;
                }
            }
        }

        if (INVOKE & $behave) {

            $invoke_ob_guard = $ob ? $ob(\bad\ob\CLEAN) : null;

            try {
                $loot[INC_RETURN] = boot((string)$file, $loot[INC_RETURN] ?? null, $loot, $behave, $fault);
            } finally {
                if ($invoke_ob_guard) {
                    try { $invoke_ob_guard(); }
                    catch (\Throwable $t) {
                        $ob_fault = new \Exception(__FUNCTION__ . ":ob:invoke:$file", 0xBADC0DE, $t);
                        $fault = $fault ? new \Exception(__FUNCTION__ . ":invoke+ob:$file", 0xBADC0DE, $fault) : $ob_fault;
                    }
                }
            }
        }

        if ($fault !== null && !(FAULT_AHEAD & $behave)) throw $fault;
    }

    return $loot;
}

function boot(string $file, $callable, array $args, int $behave, ?\Throwable &$fault = null)
{
    if ($fault !== null && !(RESCUE_CALL & $behave)) return $callable;

    $ok = ($callable instanceof \Closure)
       || ((ANYCALL & $behave) && \is_callable($callable));

    if (!$ok) return $callable;

    $call_args = $args;

    if (($behave & ABSORB) === ABSORB)
        $call_args[] = (string)($args[INC_BUFFER] ?? '');

    try {
        return (SPREAD & $behave) ? $callable(...$call_args) : $callable($call_args);
    } catch (\Throwable $t) {
        $fault = new \Exception(__FUNCTION__ . ":invoke:$file", 0xBADC0DE, $t);
        return $callable;
    }
}
