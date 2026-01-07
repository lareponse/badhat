<?php
namespace bad\run;

const RUN_BUFFER = 1;
const RUN_INVOKE = 2;
const RUN_ABSORB = 4 | RUN_BUFFER | RUN_INVOKE;
const RUN_RESCUE = 8;
const RUN_ONWARD = 16;

const RUN_CHAIN  = 256;

const RUN_RETURN = -1;
const RUN_OUTPUT = -2;

function run(array $file_paths, array $args = [], int $behave = 0): array
{
    $loot = $args;

    foreach ($file_paths as $file) {
        $call_args = (RUN_CHAIN & $behave) ? $loot : $args;
        $level = ob_get_level();
        $fault = null;

        (RUN_BUFFER & $behave) && ob_start(null, 0, 0);

        try {
            $loot[RUN_RETURN] = include $file;
        } catch (\Throwable $t) {
            $fault = new \RuntimeException("include:$file", 0xC0D, $t);
        }

        if ($fault === null || (RUN_RESCUE & $behave)) {
            if ((RUN_INVOKE & $behave) && is_callable($loot[RUN_RETURN])) {
                (RUN_ABSORB & $behave) === RUN_ABSORB && ($call_args[] = ob_get_contents());

                try {
                    $loot[RUN_RETURN] = $loot[RUN_RETURN]($call_args);
                } catch (\Throwable $t) {
                    $fault = new \RuntimeException("invoke:$file", 0xC0D, $t);
                }
            }
        }

        while (ob_get_level() > $level + 1) ob_end_clean();     // clean nested buffers from include, keep ours

        (RUN_BUFFER & $behave) && ($loot[RUN_OUTPUT] = ob_get_clean());
        $fault !== null && !(RUN_ONWARD & $behave) && throw $fault;
    }

    return $loot;
}