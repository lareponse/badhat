<?php

namespace bad\run;

const RESULT  = 0;  // stores the return value of include
const BUFFER  = 1;  // activate and stores buffered output
const FAULTS  = 2;  // turns userland exceptions into stored "faults".
const SILENT  = 4;
const INVOKE  = 8;
const SPREAD  = 16;
const RELOOT  = 32; // On fault, re-include the same $path once. $loot[FAULTS] carries the previous fault so the file can inspect it
const OB_TRIM = 128;
const OB_SAME = 256;

function loot(array|string $paths, $args = [], int $behave = 0): array
{
    $single = \is_string($paths);
    $loot  = [];
    $path   = $single ? $paths : \reset($paths);

    \is_string($path)                                               || throw new \BadFunctionCallException('loot:path not a string');
    if ($single) $paths = [];
    
    $looted = false;
    $carry_faults = null;

    do {
        $loot = [];
        if (FAULTS & $behave) {
            $loot[FAULTS] = $carry_faults ?? [];
            $carry_faults = null;
        }
                
        $ob_guard = null;
        if((OB_SAME | OB_TRIM) & $behave) 
            $ob_guard = ob($behave);
        
        try 
        {
            ((BUFFER | SILENT) & $behave) && \ob_start();
            $loot[RESULT] = include $path;
        } 
        catch (\Throwable $fault) 
        {
            (FAULTS & $behave)                                      || throw $fault;
            $loot[FAULTS][] = $fault;
        } 
        finally 
        {
            $buffer = null;
            if ((BUFFER | SILENT) & $behave) {
                $buffer = (string)\ob_get_clean();
                (BUFFER & $behave) && ($loot[BUFFER] = $buffer);
            }
        }

        if ($ob_guard && !$ob_guard()) {
            $fault = new \RuntimeException("loot:ob:$path");
            (FAULTS & $behave)                                      || throw $fault;
            $loot[FAULTS][] = $fault;
        }

        if (INVOKE & $behave  && \is_callable($loot[RESULT] ?? null)) {
            try 
            {
                (SILENT & $behave) && \ob_start();

                $loot[RESULT] = (SPREAD & $behave && \is_array($args))
                              ? $loot[RESULT]($loot, ...$args)
                              : $loot[RESULT]($loot, $args);
            } 
            catch (\Throwable $fault) 
            {
                (FAULTS & $behave)                                  || throw $fault;
                $loot[FAULTS][] = $fault;
            } 
            finally 
            {
                (SILENT & $behave) && \ob_end_clean();
            }
        }

        if ((RELOOT & $behave) && !empty($loot[FAULTS]) && !$looted) {
            $carry_faults = $loot[FAULTS];
            $looted = true;
        } else {
            $looted = false;
            $path = \next($paths);
        }

    } while ($path !== false);

    return $loot;
}

function ob(int $behave = 0): \Closure
{
    $base = \ob_get_level();
    return static function () use ($behave, $base): bool {
        $level = \ob_get_level();
        if ($level > $base) {
            if (!(OB_TRIM & $behave))                               return false;
            for ($n = $level; $n > $base && $level > $base; --$n, $level = \ob_get_level())
                if (\ob_end_clean() === false)                      return false;
        }
        if ((OB_SAME & $behave) && $level !== $base)                return false;
        return true;
    };
}
