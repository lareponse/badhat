<?php

declare(strict_types=1);

// qb_create('articles', ['title' => 'My Article', 'content' => 'This is the content.']);
// qb_create('articles', ['title' => 'My Article', 'content' => 'This is the content.', 'permissions' => 0], ['title', 'content']);
function qb_create(string $table, array $data, array $fields = []): array
{
    // vd(1, __FUNCTION__, func_get_args());
    if ($fields && $data) {
        $fields = array_keys(array_intersect_key($data, array_flip($fields)));
    }
    $fields = $fields ?: array_keys($data);
    $bindings = $placeholders = [];
    $placeholders = [];
    foreach ($fields as $col) {
        $ph = ":qb_$col";
        $placeholders[] = $ph;
        $bindings[$ph] = $data[$col];
    }

    $sql = "INSERT INTO {$table} (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ')';

    return [$sql, $bindings];
}

// qb_read('articles', 'id', 42)
// qb_read('articles', ['status' => 'published', 'user_id' => 5, 'tag_id' => [3, 4]])
function qb_read($table, ...$args): array
{
    if (is_array($args[0]))
        $args = $args[0];
    elseif (is_string($args[0]) && is_scalar($args[1]))
        $args = [$args[0] => $args[1]];

    [$where, $params] = qb_where($args, 'AND');
    return ["SELECT * FROM {$table} {$where}", $params];
}

// qb_update('articles', $data_assoc, 'id = ?', [42])
// == qb_update('articles', $data_assoc, ['id' => 42]);

// qb_update('articles', $data_assoc, "status = 'draft' AND id = ?", [42]);
// == qb_update('articles', $data_assoc, ['status' => 'draft', 'id' => 42]);
function qb_update(string $table, array $data, string $where, array $where_binds = []): array
{
    if (!$data) return ['', []];

    [$set_clause, $set_binds] = qb_set($data);

    if ($where) {
        $where_clause = $where;
    } else {
        $where_clause = '';
        $where_binds = [];
    }
    $sql = "UPDATE {$table} SET {$set_clause}";
    if ($where_clause) $sql .= " {$where_clause}";

    return [$sql, $set_binds + $where_binds];
}

// qb_where(['status' => 'published', 'user_id' => 5, 'tag_id' => [3, 4]])
// qb_where(['status' => 'published', 'user_id' => 5, 'tag_id' => [3, 4]], 'OR')
function qb_where(array $conds, string $connective = 'AND'): array
{
    if (!$conds) return ['', []];

    $where = [];
    $qbw_bindings = [];

    foreach ($conds as $col => $val) {
        if (is_array($val)) {
            [$clause, $bind] = qb_in($col, $val, 'qbw_in');
        } else {
            [$clause, $bind] = qb_condition([$col => $val], '=', __FUNCTION__);
        }
        $where[] = $clause;
        $qbw_bindings = array_merge($qbw_bindings, $bind ?? []);
    }

    return ['WHERE ' . implode(" $connective ", $where), $qbw_bindings];
}

// qb_in('tag_id', [3, 4])
// qb_in('status', ['published', 'draft'], 'allowed')
function qb_in(string $col, array $val, string $prefix = 'in'): array
{
    if (!$val) return ["1=0", []]; // or throw exception

    $bindings = $ph = [];
    foreach ($val as $i => $v) {
        $k = __qb_placeholder($prefix, $col, $i);
        $bindings[$k] = $v;
        $ph[] = $k;
    }
    return ["$col IN(" . implode(',', $ph) . ")", $bindings];
}

function qb_condition(array $data, string $default_op = '=', $andor = 'AND'): array
{
    $cubi = __qb_op($data, $default_op, __FUNCTION__);
    return [implode(" $andor ", $cubi[0]), $cubi[1]];
}

function qb_set(array $data): array
{
    $cubi = __qb_op($data, '=', __FUNCTION__);
    return [implode(', ', $cubi[0]), $cubi[1]];
}

// __qb_op(['status' => 'published', 'user_id' => 5], '=')
// __qb_op(['status' => 'published', 'level<' => 5], '<>', 'sp')
function __qb_op(array $data, string $default_op = '=', string $prefix = 'qbc'): array
{
    $clauses = $bindings = [];
    $place_holder_count = -1;
    foreach ($data as $col => $val) {

        if (preg_match('/^(.+)\s*(=|!=|<>|<|>|<=|>=|LIKE|NOT LIKE|IS|IS NOT)$/i', $col, $m)) {
            $col = $m[1];
            $op = $m[2];
        } else {
            $op = $default_op;
        }

        $k = __qb_placeholder($prefix, $col, ++$place_holder_count);
        $bindings[$k] = $val;
        $clauses[] = $op ? "{$col} {$op} {$k}" : "{$col} {$k}";
    }
    return [$clauses, $bindings];
}

function __qb_placeholder(string $prefix, string $col, int $i): string
{
    return ":{$prefix}_{$col}_{$i}";
}
