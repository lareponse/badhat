<?php
const RUN_BUFFER = 1;                                                                                                   // activate buffering
const RUN_INVOKE = 2;                                                                                                   // call fn(args) and store return in RUN_RETURN (if callable)
const RUN_ABSORB = 4 | RUN_BUFFER | RUN_INVOKE;                                                                         // call fn(args + output buffer) and store return in RUN_RETURN
const RUN_RESCUE = 8;                                                                                                   // proceed with invoke even if include threw
const RUN_ONWARD = 16;                                                                                                  // suppress throw, continue to next file

const RUN_CHAIN  = 256;                                                                                                 // chain loot results as args for next included file

const USERLAND_ERROR = 0xBAD;                                                                                           // 2989
const RUN_RETURN = -1;                                                                                                  // stores include return value
const RUN_OUTPUT = -2;                                                                                                  // stores include output buffer


function run(array $file_paths, array $io_args, int $behave = 0): array
{                                                                                                                       // execute resolved paths, return last result
    $loot = $io_args;                                                                                                   // init loot with input args

    foreach ($file_paths as $file_path) {                                                                               // iterate execution targets
        $args = (RUN_CHAIN & $behave) ? $loot : $io_args;                                                               // chain previous loot or use original

        $level = ob_get_level();                                                                                        // get current for later drain
        $fault = null;                                                                                                  // reset error state

        (RUN_BUFFER & $behave) && ob_start(null, 0, 0);                                                                 // raw output is buffered, no autoflush

        try {                                                                                                           // try to run file and capture optional return
            $loot[RUN_RETURN] = include $file_path;
        } catch (Throwable $t) {                                                                                        // wrap in badhat throwable with context
            $fault = new RuntimeException("include:$file_path", USERLAND_ERROR, $t);
        }

        if ($fault === null || (RUN_RESCUE & $behave)) {                                                                // proceed if clean include or rescue mode
            if ((RUN_INVOKE & $behave) && is_callable($loot[RUN_RETURN])) {
                (RUN_ABSORB & $behave) === RUN_ABSORB && ($args[] = ob_get_contents());                                 // append buffer to args if absorbing

                try {                                                                                                   // try to invoke (with args) callable and capture optional return 
                    $loot[RUN_RETURN] = $loot[RUN_RETURN]($args);
                } catch (Throwable $t) {                                                                                // wrap invocation failure
                    $fault = new RuntimeException("invoke:$file_path", USERLAND_ERROR, $t);
                }
            }
        }

        while (ob_get_level() > $level + 1) ob_end_clean();                                                             // drain orphans

        (RUN_BUFFER & $behave) && ($loot[RUN_OUTPUT] = ob_get_clean());                                                 // capture and clear buffer (from include and call)
        !(RUN_ONWARD & $behave) && $fault !== null && throw $fault;                                                     // throw unless onward mode
    }

    return $loot;                                                                                                       // return final loot state
}