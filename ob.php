<?php

namespace bad\ob;

const CLEAN = 1;  // Discard all buffers above fence (don't throw on HOP)

function _ob_fence(string $buf, int $phase = 0): string { return $buf; }

function seal(int $behave = 0): \Closure
{
    $want = __NAMESPACE__ . '\\_ob_fence';

    return static function () use ($behave, $want): \Closure {
        $base = \ob_get_level();
        \ob_start($want)                                            || throw new \RuntimeException('ob:ob_start');
        
        $unsealed = false;
        $cleanup = static function() use (&$unsealed, $base, $want) {
            if (!$unsealed && \ob_get_level() > $base)
                if ((\ob_get_status()['name'] ?? null) === $want) 
                    @\ob_end_clean();
        };
        \register_shutdown_function($cleanup);

        return static function () use (&$unsealed, $base, $behave, $want): string {
            !$unsealed                                              || throw new \LogicException('ob:ALREADY_UNSEALED');
            $unsealed = true;

            $level = \ob_get_level();
            ($level > $base)                                        || throw new \UnexpectedValueException("ob:BREACH:base=$base:level=$level", 0xBADC0DE);

            if ($level > $base + 1) {
                (CLEAN & $behave)                                   || throw new \UnexpectedValueException("ob:HOP:found=".(\ob_get_status()['name'] ?? null), 0xBADC0DE);
                while (($level = \ob_get_level()) > $base + 1) {
                    @\ob_end_clean();
                    (\ob_get_level() < $level)                      || throw new \RuntimeException('ob:CLEAN_STUCK');
                }
            }

            $name = \ob_get_status()['name'] ?? null;
            ($name === $want)                                       || throw new \UnexpectedValueException("ob:WRONG_FENCE:expected=$want:found=$name", 0xBADC0DE);

            ($buffer = \ob_get_clean()) !== false                   || throw new \RuntimeException('ob:ob_get_clean');
            (\ob_get_level() === $base)                             || throw new \UnexpectedValueException("ob:BREACH:base=$base:level=" . \ob_get_level(), 0xBADC0DE);

            return $buffer;
        };
    };
}
