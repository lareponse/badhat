<?php

declare(strict_types=1);

function db(?string $dsn = null, ?string $u = null, ?string $p = null, ?array $o = null): PDO
{
    static $pdo;
    return $pdo ??= new PDO($dsn ?: throw new LogicException("No DSN"), $u, $p, ($o ?: []) + [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

function dbq(string $q, array $b = []): PDOStatement
{
    $s = db()->prepare($q);
    $s->execute($b);
    return $s;
}

function db_transaction(callable $f)
{
    $db = db();
    $db->beginTransaction();
    try {
        $r = $f();
        $db->commit();
        return $r;
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

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

function qb_read(string $t, array $c = [], array $s = ['*']): array
{
    $s = implode(', ', $s ?: ['*']);
    [$w, $bind] = qb_where($c);
    return ["SELECT {$s} FROM {$t} WHERE {$w}", $bind];
}

function qb_update(string $t, array $set, string $w, array $b = []): array
{
    [$set_clause, $bind] = qb_compass($set, '=', 'update');
    return ["UPDATE $t SET $set_clause WHERE $w", array_merge($bind, $b)];
}

function qb_where(array $conds, string $connective = 'AND', string $empty_default = '1=1'): array
{
    if (!$conds) return [$empty_default, []];

    $where = [];
    $binds = [];

    foreach ($conds as $col => $val) {
        if (is_array($val)) {
            [$clause, $bind] = qb_in($col, $val, 'where');
        } else {
            [$clause, $bind] = qb_compass([$col => $val], '=', 'where');
        }
        $where[] = $clause;
        $binds = array_merge($binds, $bind);
    }

    return [implode(" $connective ", $where), $binds];
}

function qb_in(string $col, array $val, string $prefix = 'in'): array
{
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
        $clauses[] = "{$col} {$op} {$k}";
    }
    return [implode(' AND ', $clauses), $binds];
}
