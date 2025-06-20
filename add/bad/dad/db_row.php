<?php

declare(strict_types=1);

require_once 'add/bad/db.php';
require_once 'add/bad/dad/qb.php';

const ROW_ID     = 'id';

const ROW_TABLE = 1;
const ROW_FIELD = 2;
const ROW_SAVED = 4;
const ROW_ALTER = 8;
const ROW_EXTRA = 16;

const ROW_IMPORT = 32;
const ROW_EXPORT = 64;
const ROW_RELOAD = 128;

const ROW_PERSIST = 256;


function row_innit(string $table): array
{
    return [ROW_TABLE => $table];
}

function row($row, int $behave = ROW_IMPORT, array $boat = [])
{
    if ($behave & ROW_RELOAD) {
        $st = dbq(db(), ...qb_read($row[ROW_TABLE], $boat));
        $st->rowCount() === 1 || throw new BadFunctionCallException('db_row ROW_RELOAD yields rowCount() !==1', 500);
        $row[ROW_SAVED] = $st->fetch(PDO::FETCH_ASSOC) ?: false;
    }

    if ($behave & ROW_FIELD && empty($row[ROW_FIELD])) {
        $row[ROW_FIELD] = $boat ?: row_schema($row);
        return $row;
    }

    if ($behave & ROW_IMPORT) {
        foreach ($boat as $col => $value)
            if($col === ROW_ID)
                continue;
            else if (!$row[ROW_SAVED] || !isset($row[ROW_SAVED][$col]) || $row[ROW_SAVED][$col] !== $value)
                if ($row[ROW_FIELD] && isset($row[ROW_FIELD][$col]))
                    $row[ROW_ALTER][$col] = $value;
                else
                    $row[ROW_EXTRA][$col] = $value;
        return $row;
    }

    if ($behave & ROW_PERSIST && $row[ROW_ALTER]) {
        vd($row);
        $row[ROW_PERSIST] = dbq(db(), ...($row[ROW_SAVED]
            ? qb_update($row[ROW_TABLE], $row[ROW_ALTER], ...qb_where([ROW_ID => $row[ROW_SAVED][ROW_ID]]))
            : (qb_create($row[ROW_TABLE], $row[ROW_ALTER], array_keys($row[ROW_FIELD])))));
        return $row;
    }

    if ($behave & ROW_EXPORT) {
        $ret = [];
        foreach ($boat as $col)
            $ret[$col] = $row[ROW_ALTER][$col] ?? $row[ROW_SAVED][$col];

        return $ret;
    }
    
    return $row;
}

function row_schema($row): array
{
    $pdo = db();
    $table = $row[ROW_TABLE];

    if ($row[ROW_FIELD])
        return $row[ROW_FIELD];

    if ($row[ROW_SAVED] && $row[ROW_SAVED] !== false)
        return $row[ROW_SAVED];

    $fields = [];
    $fields_query = dbq($pdo, "SELECT * FROM `$table` LIMIT 1");
    if (!$fields_query->rowCount() && $fields_query->columnCount() > 0)
        for ($i = 0, $cnt = $fields_query->columnCount(); $i < $cnt; ++$i)
            $fields[] = $fields_query->getColumnMeta($i)['name'];

    return array_flip($fields);
}
