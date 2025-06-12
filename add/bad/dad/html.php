<?php
const TRAY_APPEND  = 0;  // put items at the back (default)
const TRAY_PREPEND = 1;  // put items at the front
function tray(?string $key = null, ?string $item = null, int $flags = TRAY_APPEND): array
{
    static $trays = [];

    // Inspect all trays
    if ($key === null) {
        return $trays;
    }

    // Add to a tray (using ternary for prepend)
    if ($item !== null) {
        $trays[$key] ??= [];
        ($flags & TRAY_PREPEND)
            ? array_unshift($trays[$key], $item)
            : $trays[$key][] = $item;
        return $trays[$key];
    }

    // Flush & return one tray
    $batch = $trays[$key] ?? [];
    unset($trays[$key]);
    return $batch;
}

function xss(?callable $formatter, ?string $inner = null, ...$attributes): array
{
    if (empty($formatter) || !is_callable($formatter))
        $escape = fn(mixed $v): string => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    foreach ($attributes as $name => $value) {
        if (is_array($value))
            $value = implode(' ', $value);
        $escaped = $escape($value);

        if (is_int($name))
            $attributes[] = $escaped;
        else
            $attributes[$name] = $escaped;
    }

    return [$escape($inner), ...$attributes];
}