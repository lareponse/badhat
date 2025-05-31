<?php

declare(strict_types=1);
  
/**
 * qb_create('articles', null, ['title' => 'Hello', 'content' => '...'])
 * sql: INSERT INTO articles (title, content) VALUES (:title_0, :content_0)
 * binds: [':title_0' => 'Hello', ':content_0' => '...']
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

    [$set_clause, $set_binds] = qb_compass($data, '=', 'update');

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


// ajouter un log ou une option 'allow_delete' contrôlée
function qb_delete(string $table, array $where): array
{
    error_log("Delete attempted on {$table} — denied by ADDBAD convention.");
    throw new BadMethodCallException("Automated deletes are forbidden. Use manual SQL.");
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

// qb_limit(10)
// qb_limit(10, 20)
function qb_limit(int $limit, int $offset = 0): array
{
    return $offset > 0
        ? ["LIMIT :limit OFFSET :offset", [':limit' => $limit, ':offset' => $offset]]
        : ["LIMIT :limit", [':limit' => $limit]];
}

// qb_in('tag_id', [3, 4])
// qb_in('status', ['published', 'draft'], 'allowed')
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

// qb_compass(['status' => 'published', 'user_id' => 5], '=')
// qb_compass(['status' => 'published', 'user_id' => 5], '<>', 'sp')
function qb_compass(array $data, string $op = '=', string $prefix = 'qbc'): array
{
    $clauses = $binds = [];
    foreach ($data as $col => $val) {
        $k = ":{$prefix}_{$col}";
        $binds[$k] = $val;
        $clauses[] = $op ? "{$col} {$op} {$k}" : "{$col} {$k}";
    }
    return [implode(' AND ', $clauses), $binds];
}
