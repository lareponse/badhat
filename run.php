<?php

namespace bad\run;

const BUFFER = 1;                                                                              // capture output (per step) into INC_BUFFER
const INVOKE = 2;                                                                              // invoke return value if callable (per step)
const ABSORB = 4 | BUFFER | INVOKE;                                                            // pass captured output as extra arg (requires BUFFER+INVOKE)
const RELOOT = 8;                                                                              // chain: next step receives previous loot as args

const RESCUE_CALL = 16;                                                                        // keep going to invoke even if include failed
const PIPE_ONWARD = 32;                                                                        // keep going on fault (otherwise throw)

const INC_RETURN = -1;                                                                         // loot slot: last return value (include or invoke result)
const INC_BUFFER = -2;                                                                         // loot slot: last captured output (string)

function run($file_paths, $args = [], $behave = 0): array
{
    $loot = $args;                                                                             // payload / accumulator (may be chained)

    foreach ($file_paths as $file) {                                                           // pipeline: include (optional invoke) for each file
        $level = ob_get_level();                                                               // snapshot output-buffer depth for cleanup/restore
        $fault = null;                                                                         // capture failure to rethrow under policy

        (BUFFER & $behave) && ob_start();                                                      // start capture for this step (if enabled)

        try {
            $loot[INC_RETURN] = include $file;                                                 // execute file, store its return value
        } catch (\Throwable $t) {
            $fault = new \Exception("include:$file", 0xC0D, $t);                               // normalize include failure, preserve chain
        }

        if ($fault === null || (RESCUE_CALL & $behave)) {                                      // proceed if no fault, or rescue policy says "continue"
                
            if ((INVOKE & $behave) && is_callable($loot[INC_RETURN])) {                        // invoke only when enabled + callable

                (RELOOT & $behave) ?  ($call_args = $loot) : ($call_args = $args);             // args source: chained loot or original args
                (ABSORB & $behave) && ($call_args []= ob_get_contents());                      // append current captured output as extra arg

                try {
                    $loot[INC_RETURN] = $loot[INC_RETURN]($call_args);                         // invoke callable, store its return value
                } catch (\Throwable $t) {
                    $fault = new \Exception("invoke:$file", 0xC0D, $t);                        // normalize invoke failure, preserve chain
                }
            }

        }

        $keep = $level + ((BUFFER & $behave) ? 1 : 0);                                         // expected buffer level after this step
        for ($n = ob_get_level(); $n > $keep + 1; --$n)  @ob_end_clean();                      // leave baseline + our capture intact; drop only nested buffers

        (BUFFER & $behave)                                                                     // finalize capture for this step
            && ($loot[INC_BUFFER] = (ob_get_level() > $level) ? ob_get_clean() : '');          // pop ours if still present, else empty

        $fault === null || (PIPE_ONWARD & $behave) || throw $fault;                            // default: stop on fault unless ONWARD allows continuation
    }

    return $loot;                                                                              // final loot contains args + RUN_* slots
}