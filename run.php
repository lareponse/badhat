<?php
const RUN_BUFFER = 1;                            // activate buffering
const RUN_INVOKE = 2;                            // Call fn(args) and store return in RUN_RETURN (if callable)
const RUN_ABSORB = 4 | RUN_BUFFER | RUN_INVOKE;  // Call fn(args + output buffer) and store return value RUN_RETURN
const RUN_RESCUE = 8
const RUN_ONWARD = 16;

const RUN_CHAIN  = 256;                          // Chain loot results as args for next included file

const USERLAND_ERROR = 0xBAD;                    // 2989
const RUN_RETURN = -1;                           // stores include return value
const RUN_OUTPUT = -2;                           // stores include output buffer


function run(array $file_paths, array $io_args, int $behave = 0): array
{ // executes one or more resolved execution paths and returns last execution result
    $loot = $io_args;

    foreach ($file_paths as $file_path) {
        $args = (RUN_CHAIN & $behave) ? $loot : $io_args;

        $level = ob_get_level();
        $fault = null;

        (RUN_BUFFER & $behave) && ob_start(null, 0, 0);                  // raw output is buffered, no autoflush

        try {
            $loot[RUN_RETURN] = include $file_path;
        } catch (Throwable $t) {
            $fault = new RuntimeException("include:$file_path", USERLAND_ERROR, $t);
        }

        if($fault == null || (RUN_RESCUE & $behave)){
            if ((RUN_INVOKE & $behave) && is_callable($loot[RUN_RETURN])) {
                (RUN_ABSORB & $behave) === RUN_ABSORB && ($args[] = ob_get_contents());

                try {
                    $loot[RUN_RETURN] = $loot[RUN_RETURN]($args);
                } catch (Throwable $t) {
                    $fault = new RuntimeException("invoke:$file_path", USERLAND_ERROR, $t);
                }
            }
        }
            
        while (ob_get_level() > $level + 1) ob_end_clean();             // drain orphans

        (RUN_BUFFER & $behave) && ($loot[RUN_OUTPUT] = ob_get_clean());
        !(RUN_ONWARD & $behave) && $fault !== null && throw $fault;
    }

    return $loot;
}