<?php

declare(strict_types=1);

// this is query i need to write:
// $or = clause(CLAUSE_OR);
// $and = clause(CLAUSE_AND);

// SELECT * 
// FROM client 
// WHERE enabled_at > 1995-01-01 
// AND (status = 'active' OR category = 'archive') 
// AND (tag_id IN(3, 4) OR tag_id IS NULL)
/*

this is the solution i think is best:
<?php
$selected_ids = $_POST['relational_ids']; // example selected tag IDs
$filtered = array_intersect_key($_GET, array_flip(['status', 'category']));
$select = clause(CLAUSE_SELECT);
$and = clause(CLAUSE_AND);
$or = clause(CLAUSE_OR, '=');
$list = clause(CLAUSE_LIST, 'tag_id');

$sql = "SELECT id, name, status, category, tag_id, enabled_at, created_at AS created, updated_at AS updated";
$sql .= " FROM client WHERE ";
$sql .= $and(
    'enabled_at > 1995-01-01',
    $or($filtered, '='),
    $or('tag_id IN ' . $list($selected_ids), 'tag_id IS NULL')
);
*/

const CLAUSE_SELECT   = 1;
const CLAUSE_AND      = 8;
const CLAUSE_OR       = 16;
const CLAUSE_ORDER_BY = 64;
const CLAUSE_GROUP_BY = 128;
const CLAUSE_LIST     = 512;
const CLAUSE_SET      = 16384;

/**
 * Returns a SQL‐fragment closure for the given clause‐type.
 * Internally defines & caches all your clause‐builders and helper closures.
 */
function clause(int $type, $glue = '='): callable
{
    static $cache = [];
    static $calls = null;

    $q = fn(string $id): string => '`' . str_replace('`', '``', $id) . '`';
    $dir = fn(string $d): string => strtoupper($d) === 'DESC' ? 'DESC' : 'ASC';

    if ($calls === null) {
        $calls = [
            CLAUSE_SELECT => [
                fn($k, $v) => is_int($k) ? $q($v) : "{$q($v)} AS {$q($k)}",
                fn($parts) => 'SELECT ' . implode(', ', $parts)
            ],

            CLAUSE_ORDER_BY => [
                fn($k, $v) => "{$q($k)} {$dir((string)$v)}",
                fn($parts) => 'ORDER BY ' . implode(', ', $parts)
            ],

            CLAUSE_GROUP_BY => [
                fn($k) => $q($k),
                fn($parts) => 'GROUP BY ' . implode(', ', $parts)
            ],
            CLAUSE_SET => [
                fn($k, $v, $prefix) => "{$q($k)} = :{$prefix}_{$k}",
                fn($parts) => 'SET ' . implode(', ', $parts)
            ],

            CLAUSE_LIST => [
                fn($k, $v, $prefix) => ":{$prefix}_{$k}",
                fn($parts) => '(' . implode(', ', $parts) . ')'
            ],
            CLAUSE_AND => [
                fn($k, $v, $glue = '=') => is_int($k) ? $v : "`{$k}` {$glue} :{$k}",
                fn($parts) => '(' . implode(' AND ', $parts) . ')'
            ],
            CLAUSE_OR => [
                fn($k, $v, $glue = '=') => is_int($k) ? $v : "`{$k}` {$glue} :{$k}",
                // fn($k, $v, $glue = '=') => "{$q($k)} {$glue} {$v}",
                fn($parts) => '(' . implode(' OR ', $parts) . ')'
            ],

        ];
    }

    if (!isset($cache[$type])) {
        [$transform, $format] = $calls[$type] ?? [fn($k, $v) => "$k $v", fn($p) => implode(' ', $p)];
        $cache[$type] = function (...$parts) use ($transform, $format, $glue): string {
            if (count($parts) === 1 && is_array($parts[0])) {
                $src = $parts[0];
                $parts = [];
                foreach ($src as $k => $v) {
                    $parts[] = $transform($k, $v, $glue);
                }
            }
            return $format($parts);
        };
    }

    return $cache[$type];
}
