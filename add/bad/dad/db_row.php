<?php

declare(strict_types=1);

require_once 'add/bad/db.php';

const ROW_ID     = 'id';

const ROW_LOAD   = 1;       // boat are where conditions, nulled
const ROW_EDIT   = 2;       // boat are values to set, nulled
const ROW_MORE   = 4;       // boat 

const ROW_SET    = 16;
const ROW_GET    = 32;
const ROW_SAVE   = 64;
const ROW_ERROR = 128; // errors from row_save
const ROW_SCHEMA = 8;


function row(PDO $pdo, string $table): callable
{
    return function (int $behave, array $boat = []) use ($pdo, $table) {

        static $row = [];

        if ($behave & ROW_LOAD) {
            $boat && ($row[ROW_LOAD] = row_load($pdo, $table, $boat));
            return $row[ROW_LOAD] ?? null;
        }

        if ($behave & ROW_SCHEMA) {
            $boat                                   && ($row[ROW_SCHEMA] = $boat);                                      // payload is always set
            !$row[ROW_SCHEMA] && $row[ROW_LOAD]     && ($row[ROW_SCHEMA] = array_flip(array_keys($row[ROW_LOAD])));     // skip schema query if we have row_load (updates)
            !$row[ROW_SCHEMA]                       && ($row[ROW_SCHEMA] = row_schema($pdo, $table, $row));             // cant skip it for inserts

            return $row[ROW_SCHEMA];
        }

        if ($behave & ROW_SET && $boat)
            return row_import($row, $boat, $behave);

        if ($behave & ROW_GET) {
            $export = row_export($row, $boat, $behave);
            return !isset($boat[0]) || isset($boat[1]) ? $export : $export[$boat[0]];
        }

        if ($behave & ROW_SAVE && $row[ROW_EDIT]) {
            $save = row_save($pdo, $table, $row);

            if (!$save || !$save instanceof PDOStatement || $save->errorCode() !== PDO::ERR_NONE) {
                $row[ROW_ERROR] = $save ? $save->errorInfo() : 'Unknown error';
            } else {
                $row[ROW_EDIT] = [];
                $row[ROW_SAVE] = $save;
            }

            return $save;
        }

        if ($behave & ROW_ERROR)
            return $row[ROW_ERROR] ?? null;


        return $row;
    };
}

function row_save(PDO $pdo, string $table, array $row): ?PDOStatement
{
    if (!$row[ROW_EDIT] || !$table || !$pdo) return null; // nothing to save

    $qb = $row[ROW_LOAD]
        ? qb_update($table, $row[ROW_EDIT], $row[ROW_LOAD][ROW_ID])
        : qb_insert($table, $row[ROW_EDIT], array_keys($row[ROW_SCHEMA]));

    return ($stmt = $pdo->prepare($qb[0])) && $stmt->execute($qb[1]) ? $stmt : null;
}

function row_load(PDO $pdo, string $table, array $boat): ?array
{
    $qb = qb_select($table, $boat);
    $stmt = $pdo->prepare($qb[0]);
    if (!$stmt || !$stmt->execute($qb[1]) || $stmt->rowCount() !== 1)
        return null;

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function row_schema(PDO $pdo, string $table): array
{
    $fields = [];
    $fields_query = dbq($pdo, "SELECT * FROM `$table` LIMIT 1");
    $cnt = $fields_query->columnCount();
    for ($i = 0; $i < $cnt; ++$i) {
        $m = $fields_query->getColumnMeta($i);
        $fields[$m['name']] = $m['pdo_type'] ?? true;
    }

    return $fields;
}

function row_import(array &$row, array $boat, int $behave = 0): void
{
    foreach ($boat as $col => $value) {
        if ($col === ROW_ID || isset($row[ROW_LOAD][$col]) && $row[ROW_LOAD][$col] === $value)
            continue; // skip id or existing identical values

        // force edit      or if we have fields restriction and applicable ?
        $add_to_edit = $behave & ROW_EDIT || $row[ROW_SCHEMA] && isset($row[ROW_SCHEMA][$col]);
        $row[$add_to_edit ? ROW_EDIT : ROW_MORE][$col] = $value;
    }
}

function row_export(array $row, array $fieldters = [], int $behave = 0): array
{
    if ($behave & ROW_LOAD) return $row[ROW_LOAD] ?? [];
    if ($behave & ROW_EDIT) return $row[ROW_EDIT] ?? [];
    if ($behave & ROW_MORE) return $row[ROW_MORE] ?? [];

    $fieldters = $fieldters ?: array_keys($row[ROW_SCHEMA] ?? []);

    if (empty($fieldters))
        return array_merge($row[ROW_LOAD] ?? [], $row[ROW_EDIT] ?? []);

    $ret = [];
    foreach ($fieldters as $col)
        $ret[$col] = $row[ROW_EDIT][$col] ?? $row[ROW_LOAD][$col];

    return $ret;
}

// qb_select('table', ['a-unique-column' => 'a-unique-value'])
// qb_select('article', ['slug' => 'my-article', 'deleted_at' => null])
function qb_select(string $table, array $data): array
{
    $named_bindings = $placeholders = [];

    foreach ($data as $col => $val) {
        if (null === $val)
            $placeholders[] = "`$col` IS NULL";
        else {
            $ph = ":row_qb_$col";
            $placeholders[] = "`$col` = $ph";
            $named_bindings[$ph] = $val;
        }
    }

    $placeholders   = implode(' AND ', $placeholders);

    return ["SELECT * FROM {$table} WHERE {$placeholders}", $named_bindings];
}

// qb_insert('article', ['title' => 'My Article', 'content' => 'This is the content.']);
function qb_insert(string $table, array $data): array
{
    if (!$table || !$data) return ['', []];

    $named_bindings = $placeholders = [];

    foreach ($data as $col => $val) {
        $ph = ":row_qb_$col";
        $named_bindings[$ph] = $val;
        $placeholders[] = $ph;
    }

    $fields         = implode('`,`', array_keys($data));
    $placeholders   = implode(',', $placeholders);

    return ["INSERT INTO {$table} (`$fields`) VALUES ($placeholders);", $named_bindings];
}

// qb_update('article', ['title' => 'Updated Title'], 42)
function qb_update(string $table, array $data, int $id): array
{
    if (!$table || !$data || $id <= 0) return ['', []];

    $named_bindings = $placeholders = [];

    foreach ($data as $col => $val) {
        $ph = ":row_qb_{$col}";
        $named_bindings[$ph] = $val;
        $placeholders[] = "`$col` = $ph";
    }

    $placeholders   = implode(', ', $placeholders);

    return ["UPDATE `$table` SET $placeholders WHERE `" . ROW_ID . "` = :qb_update_id;", $named_bindings + [':qb_update_id' => $id]];
}
