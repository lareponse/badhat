<?php

/**
 * UI helper functions
 *
 * Embraces simple, flexible, and minimalistic composition for views and slots,
 * providing rendering and slot management without globals.
 */

/**
 * Push a value onto a named slot and return all values for that slot.
 * If no value is provided, returns current array of values (or empty array).
 *
 * @param string      $name  Slot name
 * @param string|null $value Value to push onto the slot (optional)
 * @return array<string>     Array of all values for this slot name
 */
function slot(string $name, ?string $value = null): array
{
    static $slots = [];
    if ($value !== null) {
        $slots[$name][] = $value;
    }
    return $slots[$name] ?? [];
}

/**
 * Render a view inside a layout.
 *
 * @param string $view   View filename (without .php)
 * @param array  $data   Variables to extract into view
 * @param string $layout Layout filename (without .php)
 * @return string        Rendered HTML
 */
function render(string $view, array $args = [], string $layout): void
{
    $view = str_replace('route', 'render', $view);
    ob_start();
    require $view;
    // Push rendered view onto 'content' slot
    slot('content', ob_get_clean());

    // Invoke layout, which should output slot('content')
    require $layout;
}
