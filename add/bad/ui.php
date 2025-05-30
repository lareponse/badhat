<?php

/**
 * UI helper functions
 *
 * Embraces simple, flexible, and minimalistic composition for views and slots,
 * providing rendering and slot management without globals.
 * 
 * Design philosophy:
 * - Separation of concerns between routes and views
 * - Implicit routing based on directory structure
 * - Composition over inheritance for layouts
 * - Stateful slots without global pollution
 */

/**
 * Render a view file inside a layout.
 *
 * Process:
 * 1. Maps route file to corresponding view
 * 2. Captures view output into 'main' slot
 * 3. Searches for layout up directory tree
 * 4. Renders layout with view content or falls back to raw view
 *
 * @param  array  $vars       Variables to extract into the view scope
 * @param  string $routeFile  Current executing file (default: caller)
 * @param  string $layoutName Layout filename to search for
 * @return string             Rendered HTML output
 */
function render(array $data = [], string $routeFile = __FILE__, string $layoutName = 'layout.php'): string
{
    // Convert route handler path to view template path
    $viewFile = _ui_mirror($routeFile);

    if (!is_file($viewFile)) {
        trigger_error("404 View not found: '{$viewFile}'", E_USER_NOTICE);
        return '';
    }

    ob_start();
    @include $viewFile;
    $content = ob_get_clean();
    // Store view content in 'main' slot for layout to access
    slot('main', $content);

    // Search for layout file, traversing up directory tree
    $layoutFile = _ui_ascend(dirname($viewFile), $layoutName);
    if ($layoutFile && is_file($layoutFile)) {
        ob_start();
        @include $layoutFile;
        return ob_get_clean();
    }

    // No layout found, return raw view content
    return $content;
}


// Collects HTML fragments (e.g., <meta>, <link>, <script>) into named slots.
//
//   slot('name', 'value')   Push a value into the named slot and return the slot.
//   slot('name')            Flush and return the named slot.
//   slot(null)              Flush and return all slots.
//   slot()                  Return all slots without flushing (debug-like mode).
function slot(?string $index = null, ?string $value = null): array
{
    static $slots = [];

    // Case 4: slot() → Return all slots without flushing
    if (func_num_args() === 0)
        return $slots;

    // Case 3: slot(null) → return all slots and flush
    if ($index === null) {
        $all = $slots;
        $slots = [];
        return $all;
    }

    // Case 1: slot('name', 'value') → Push a value into the named slot and return it
    if ($value !== null) {
        $slots[$index][] = $value;
        return $slots[$index];
    }

    // Case 2: slot('name') → Flush and return the named slot
    $out = $slots[$index] ?? [];
    unset($slots[$index]);
    return $out;
}

function html(string $tag, ?string $inner = null, array $attributes = [], $formatter = null): string
{
    // Default to HTML escaping for security
    $formatter ??= fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    // Handle non-callable formatters by converting to no-op function
    $formatter = is_callable($formatter) ? $formatter : fn($v) => $v;

    // Build attribute string with proper escaping
    $attrs = '';
    foreach ($attributes as $name => $value) {
        // Handle array values (like classes) by joining with spaces
        $attr = $formatter(is_array($value) ? implode(' ', $value) : (string)$value);

        // Support both named attributes and boolean/valueless attributes (integer keys)
        $attrs .= ' ' . (is_int($name) ? $attr : "$name=\"$attr\"");
    }

    // Generate self-closing or regular tag based on inner content
    return "<{$tag}{$attrs}" . ($inner === null ? '/>' : sprintf('>%s</%s>', $formatter($inner), $tag));
}

function _ui_ascend(string $dir, string $layoutFile): ?string
{
    // Check if layout is an absolute path
    if (is_file($layoutFile)) {
        return $layoutFile;
    }


    $appDir = (dirname(io('i')));
    $current = rtrim($dir, '/');
    // Traverse upward through directory tree
    do {
        $candidate = $current . '/' . $layoutFile;
        if (is_file($candidate))
            return $candidate;

        $current = dirname($current);
    } while ($current !== $appDir);

    return null;
}

function _ui_mirror(): ?string
{
    $io = dirname(io('i'));
    $io = new FilesystemIterator($io, FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_PATHNAME);

    if ($io->current() === io('i'))
        $io->next();

    $view = array_shift(io_candidates(io('o')));
    return $view['handler'] ?? null;
}


