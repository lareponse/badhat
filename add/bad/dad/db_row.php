<?php

declare(strict_types=1);

require_once 'add/bad/db.php';
require_once 'add/bad/dad/qb.php';

const ROW_ID     = 'id';

const ROW_LOAD   = 1;       // boat are where conditions, nulled
const ROW_EDIT   = 2;       // boat are values to set, nulled
const ROW_MORE   = 4;       // boat 
const ROW_FIELDS = 8;

const ROW_SET    = 16;
const ROW_GET    = 32;
const ROW_SAVE   = 64;

function row(PDO $pdo, string $table): callable
{
    return function (int $behave, array $boat = []) use ($pdo, $table) {

        static $row = [];

        if ($behave & ROW_LOAD) {
            $st = dbq($pdo, ...qb_read($table, $boat));
            $st->rowCount() === 1 || throw new BadFunctionCallException('db_row ROW_LOAD yields rowCount() !==1', 500);

            $row[ROW_LOAD] = $st->fetch(PDO::FETCH_ASSOC);
            $boat = null;
        }

        if ($behave & ROW_FIELDS && empty($row[ROW_FIELDS])) {
            $row[ROW_FIELDS] = $boat ?: row_schema($pdo, $table, $row);
            $boat = null;
        }

        if ($behave & ROW_SET && $boat)
            row_import($row, $boat, $behave);
        
        if ($behave & ROW_SAVE && $row[ROW_EDIT]) {
            $qb = $row[ROW_LOAD]
                ? qb_update($table, $row[ROW_EDIT], ...qb_where([ROW_ID => $row[ROW_LOAD][ROW_ID]]))
                : qb_create($table, $row[ROW_EDIT], array_keys($row[ROW_FIELDS]));
            $row[ROW_SAVE] = dbq($pdo, ...$qb);
        }

        if ($behave & ROW_GET){
            $export = row_export($row, $boat);
            return !isset($boat[0]) || isset($boat[1]) ? $export : $export[$boat[0]];
        }

        return $row;
    };
}

function row_import(array &$row, array $boat, int $behave=0): void
{
    foreach ($boat as $col => $value)
        // skip id and existing values
        if ($col === ROW_ID || isset($row[ROW_LOAD][$col]) && $row[ROW_LOAD][$col] === $value)
            continue;
        else if ($behave & ROW_EDIT || $row[ROW_FIELDS] && isset($row[ROW_FIELDS][$col]))
            $row[ROW_EDIT][$col] = $value;
        else
            $row[ROW_MORE][$col] = $value;
}

function row_export(array &$row, array $boat = []): array
{
    $fields = $boat ?: array_keys($row[ROW_FIELDS] ?? []);

    if (empty($fields))
        return array_merge($row[ROW_LOAD] ?? [], $row[ROW_EDIT] ?? []);

    $ret = [];
    foreach ($fields as $col)
        $ret[$col] = $row[ROW_EDIT][$col] ?? $row[ROW_LOAD][$col];

    return $ret;
}

function row_schema(PDO $pdo, string $table, array $row): array
{
    if ($row[ROW_FIELDS])
        return $row[ROW_FIELDS];

    if ($row[ROW_LOAD] && $row[ROW_LOAD] !== false)
        return ($row[ROW_FIELDS] = array_flip(array_keys($row[ROW_LOAD])));

    $fields = [];
    $fields_query = dbq($pdo, "SELECT * FROM `$table` LIMIT 1");
    if (!$fields_query->rowCount() && $fields_query->columnCount() > 0)
        for ($i = 0, $cnt = $fields_query->columnCount(); $i < $cnt; ++$i)
            $fields[] = $fields_query->getColumnMeta($i)['name'];

    return array_flip($fields);
}
