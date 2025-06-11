<?php

declare(strict_types=1);
  
/**
 * qb_create('articles', null, ['title' => 'Hello', 'content' => '...'])
 * sql: INSERT INTO articles (title, content) VALUES (:title_0, :content_0)
 * binds: [':title_0' => 'Hello', ':content_0' => '...']
 * 
 * qb_create('articles', ['title', 'content'], [...], [...])
 * sql: INSERT INTO articles (title, content) VALUES (:title_0, :content_0), (:title_1, :content_1)
 * binds: [':title_0' => '...', ':content_0' => '...', ':title_1' => '...', ':content_1' => '...']  
 */
function qb_create(string $table, ?array $fields, array ...$rows): array
{
    $rows ?: throw new InvalidArgumentException('Rows cannot be empty.');
    $fields = $fields ?: array_keys(array_replace_recursive(...$rows));

    $bindings = $placeholders = [];

    foreach ($rows as $i => $row) {
        $holders = [];

        foreach ($fields as $col) {
            $ph = ":{$col}_{$i}";
            $holders[] = $ph;
            $bindings[$ph] = $row[$col];
        }

        $placeholders[] = '(' . implode(',', $holders) . ')';
    }

    $sql = "INSERT INTO {$table} (" . implode(',', $fields) . ") VALUES " . implode(',', $placeholders);

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

// qb_update('articles', $SET_assoc, 'id = ?', [42])
// == qb_update('articles', $SET_assoc, ['id' => 42]);

// qb_update('articles', $SET_assoc, "status = 'draft' AND id = ?", [42]);
// == qb_update('articles', $SET_assoc, ['status' => 'draft', 'id' => 42]);
function qb_update(string $table, array $data, array|string $where = [], array $binds = []): array
{
    if (!$data) return ['', []];

    [$set_clause, $set_binds] = qb_op($data, '=', 'update');

    if (is_string($where)) {
        $where_clause = 'WHERE ' . $where;
        $where_binds = $binds;
    } elseif (is_array($where) && $where) {
        [$where_clause, $where_binds] = qb_where($where);
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
    $binds = [];

    foreach ($conds as $col => $val) {
        if (is_array($val)) {
            [$clause, $bind] = qb_in($col, $val, 'qbw_in');
        } else {
            [$clause, $bind] = qb_op([$col => $val], '=', 'qbw_op');
        }
        $where[] = $clause;
        $binds = array_merge($binds, $bind);
    }
    
    return ['WHERE ' . implode(" $connective ", $where), $binds];
}

// qb_in('tag_id', [3, 4])
// qb_in('status', ['published', 'draft'], 'allowed')
function qb_in(string $col, array $val, string $prefix = 'in'): array
{
    if (!$val) return ["1=0", []]; // or throw exception

    $bindings = $ph = [];
    foreach ($val as $i => $v) {
        $k = qb_placeholder($prefix, $col, $i);
        $bindings[$k] = $v;
        $ph[] = $k;
    }
    return ["$col IN(" . implode(',', $ph) . ")", $bindings];
}

// qb_op(['status' => 'published', 'user_id' => 5], '=')
// qb_op(['status' => 'published', 'user_id' => 5], '<>', 'sp')
function qb_op(array $data, string $default_op = '=', string $prefix = 'qbc'): array
{
    $clauses = $bindings = [];
    $place_holder_count = -1;
    foreach ($data as $col => $val) {

        if (preg_match('/^(.+)\s*(=|!=|<>|<|>|<=|>=|LIKE|NOT LIKE|IS|IS NOT)$/i', $col, $m)) {
            $col = $m[1];
            $op = $m[2];
        }
        else {
            $op = $default_op;
        }

        $k = qb_placeholder($prefix, $col, ++$place_holder_count);
        $bindings[$k] = $val;
        $clauses[] = $op ? "{$col} {$op} {$k}" : "{$col} {$k}";
    }
    return [implode(' AND ', $clauses), $binds];
}

function qb_placeholder(string $prefix, string $col, int $i): string
{
    return ":{$prefix}_{$col}_{$i}";
}
