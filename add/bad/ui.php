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
function render(array $vars = [], string $routeFile = __FILE__, string $layoutName = 'layout.php'): string
{
    // Convert route handler path to view template path
    $viewFile = _ui_mirror($routeFile);

    if (! is_file($viewFile)) {
        trigger_error("404 View not found: {$viewFile}", E_USER_ERROR);
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

/**
 * Manages named content slots using static storage
 * 
 * Provides dual functionality:
 * - As setter: stores content in named slot when value provided
 * - As getter: retrieves all accumulated values for slot when called without value
 *
 * Using static variable ensures slot persistence across function calls
 * while avoiding global state pollution
 *
 * @param string      $name  Slot identifier
 * @param string|null $value Content to append to slot (optional)
 * @return array<string>     All values stored in requested slot
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
 * Generates HTML elements with attribute safety
 *
 * Features:
 * - Self-closing tag support (when $inner is null)
 * - Optional attribute escaping via formatter
 * - Support for array attributes (converted to space-delimited strings)
 * - Integer keys treated as valueless attributes
 *
 * @param string        $tag        HTML element name
 * @param string|null   $inner      Element content (null for self-closing tags)
 * @param array         $attributes Element attributes as name=>value pairs
 * @param callable|null $formatter  Optional escaping function (defaults to htmlspecialchars)
 * @return string                   Complete HTML element
 */
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

/**
 * Locates layout file by traversing directory hierarchy
 *
 * Search algorithm:
 * 1. Check absolute path
 * 2. Check relative to view directory
 * 3. Traverse up directories until app root is reached
 *
 * This enables both:
 * - Component-specific layouts in deeper directories
 * - Fallback to parent/global layouts when not overridden
 *
 * @param  string      $dir        Starting directory path
 * @param  string      $layoutFile Layout filename
 * @return string|null             Full path to found layout or null if none exists
 */
function _ui_ascend(string $dir, string $layoutFile): ?string
{
    // Check if layout is an absolute path
    if (is_file($layoutFile)) {
        return $layoutFile;
    }

    // Check if layout exists in the view directory
    if (is_file($dir . '/' . $layoutFile)) {
        return $dir . '/' . $layoutFile;
    }

    $appDir = request()['root'];
    $current = rtrim($dir, '/');

    // Traverse upward through directory tree
    while ($current !== $appDir) {
        $candidate = $current . '/' . $layoutFile;
        if (is_file($candidate))
            return $candidate;

        $current = dirname($current);
    }

    return null;
}

/**
 * Maps route handler to corresponding view file
 * 
 * Convention-over-configuration approach:
 * - Assumes paired directory structure (routes + views)
 * - Determines view path by finding complementary directory
 * - Preserves path hierarchy from route to view
 *
 * Design choice: Uses file system structure rather than explicit mapping
 * to eliminate config maintenance and enforce consistency
 *
 * @param string $routeFile  Absolute path to executing route handler
 * @param string $format     Content type (reserved for content negotiation)
 * @return string            Absolute path to the matching view template
 */
function _ui_mirror(string $routeFile): string
{
    // Access application root directory
    $appDir = dirname(request()['root']);

    // Sort directories in descending order to prioritize structured directories
    foreach (array_diff(scandir($appDir, SCANDIR_SORT_DESCENDING), ['.', '..']) as $viewFolder) {
        $fullpath = $appDir . '/' . $viewFolder;

        // Identify view directory by excluding the directory containing the route file
        // Assumes routes and views are in separate top-level directories
        if (strpos($routeFile, $fullpath) === false) {
            // Mirror request path to maintain parallel structure between routes and views
            return $fullpath . request()['path'] . '.php';
        }
    }

    // Return empty string if structural assumptions fail
    return '';
}