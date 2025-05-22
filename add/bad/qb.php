<?php

declare(strict_types=1);


function qb_create(string $table, array ...$rows): array
{
    $cols = array_keys($rows[0]);
    $bindings = $placeholders = [];
    foreach ($rows as $i => $row) {
        $holders = [];
        foreach ($cols as $col) {
            $holders[] = $ph = ":{$col}_{$i}";
            $bindings[$ph] = $row[$col];
        }
        $placeholders[] = '(' . implode(',', $holders) . ')';
    }
    return ["INSERT INTO {$table}(" . implode(',', $cols) . ") VALUES " . implode(',', $placeholders), $bindings];
}

function qb_where(array $conds, string $connective = 'AND'): array
{
    if (!$conds) return ['', []];

    $where = [];
    $binds = [];

    foreach ($conds as $col => $val) {
        if (is_array($val)) {
            [$clause, $bind] = qb_in($col, $val, 'where');
        } elseif (preg_match('/^(.+)\s+(=|!=|<>|<|>|<=|>=|LIKE|NOT LIKE|IS|IS NOT)$/i', $col, $m)) {
            [$clause, $bind] = qb_compass([$m[1] => $val], $m[2], 'where');
        } else {
            [$clause, $bind] = qb_compass([$col => $val], '=', 'where');
        }
        $where[] = $clause;
        $binds = array_merge($binds, $bind);
    }

    return ['WHERE ' . implode(" $connective ", $where), $binds];
}

function qb_in(string $col, array $val, string $prefix = 'in'): array
{
    if (!$val) return ["1=0", []]; // or throw exception

    $b = $ph = [];
    foreach ($val as $i => $v) {
        $k = ":{$prefix}_{$col}_{$i}";
        $b[$k] = $v;
        $ph[] = $k;
    }
    return ["$col IN(" . implode(',', $ph) . ")", $b];
}

function qb_compass(array $data, string $op = '=', string $prefix = 'comp'): array
{
    $clauses = $binds = [];
    foreach ($data as $col => $val) {
        $k = ":{$prefix}_{$col}";
        $binds[$k] = $val;
        $clauses[] = $op ? "{$col} {$op} {$k}" : "{$col} {$k}";
    }
    return [implode(' AND ', $clauses), $binds];
}


function qb_limit(int $limit, int $offset = 0): array
{
    return $offset > 0
        ? ["LIMIT :limit OFFSET :offset", [':limit' => $limit, ':offset' => $offset]]
        : ["LIMIT :limit", [':limit' => $limit]];
}
