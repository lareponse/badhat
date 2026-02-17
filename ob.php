<?php

namespace bad\ob;

const CLEAN = 1;                                                    // Discard all buffers above fence (don't throw on HOP)

function guard(int $behave = 0): \Closure
{
    $stack = [];

    return static function ($fence = null) use (&$stack, $behave): \Closure {

        $base  = \ob_get_level();
        $depth = \array_push($stack, true);                         // claim slot

        return static function () use (&$stack, $behave, $fence, $base, $depth): int {

            $current = \count($stack);
            $current > 0                                            || throw new \LogicException('ob:EMPTY_STACK');
            $depth === $current                                     || throw new \LogicException("ob:OUT_OF_ORDER:expected=$current:got=$depth");

            $level  = \ob_get_level();
            $target = ($fence !== null) ? $base + 1 : $base;

            if ($level > $target) {
                (CLEAN & $behave)                                   || throw new \UnexpectedValueException("ob:LEAK:base=$base:level=$level", 0xBADC0DE);
                while (($level = \ob_get_level()) > $target) {
                    @\ob_end_clean()                                || throw new \RuntimeException('ob:ob_end_clean');
                    (\ob_get_level() < $level)                      || throw new \RuntimeException('ob:CLEAN_STUCK');
                }
            }

            if ($fence !== null) {
                (\ob_get_level() === $base + 1)                     || throw new \UnexpectedValueException("ob:FENCE_MISSING:base=$base:level=" . \ob_get_level(), 0xBADC0DE);
                $name = \ob_get_status()['name'] ?? null;
                ($name === $fence)                                  || throw new \UnexpectedValueException("ob:WRONG_FENCE:expected=$fence:found=$name", 0xBADC0DE);
            } else {
                (\ob_get_level() === $base)                         || throw new \UnexpectedValueException("ob:BREACH:base=$base:level=" . \ob_get_level(), 0xBADC0DE);
            }

            \array_pop($stack);
            return $base;
        };
    };
}