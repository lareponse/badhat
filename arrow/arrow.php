<?php

declare(strict_types=1);

const ROW_TABLE  = -2;
const ROW_UNIQUE = -1;

const ROW_LOAD   = 1;
const ROW_SCHEMA = 2;
const ROW_EDIT   = 4;
const ROW_MORE   = 8;

const ROW_SAVE   = 16;
const ROW_SET    = 32;
const ROW_GET    = 64;
const ROW_ERROR  = 128;

const ROW_RESET  = 256;

const ROW_CREATE = ROW_SCHEMA | ROW_SET | ROW_SAVE;
const ROW_UPDATE = ROW_LOAD | ROW_SET | ROW_SAVE; // update row context, save to DB, return row

function row(PDO $pdo, string $table, string $unique = 'id'): callable
{
    empty($table)                       && throw new InvalidArgumentException(__FUNCTION__ . ':no_table');

    $row = [ROW_TABLE => $table, ROW_UNIQUE => $unique]; // each row() call creates a new row context to use in the closure
    return function (int $behave, array $boat = []) use ($pdo, &$row) {
        try {
            // RESET -- first thing to do if requested
            $behave & ROW_RESET
                && ($row = [ROW_TABLE => $row[ROW_TABLE], ROW_UNIQUE => $row[ROW_UNIQUE]]) // reset row context
                && ($behave &= ~ROW_RESET);

            $behave & (ROW_UPDATE | ROW_CREATE) && ($setter = $boat);
            $behave & ROW_UPDATE && ($boat = [$row[ROW_UNIQUE] => $boat[$row[ROW_UNIQUE]]]);
            $behave & ROW_CREATE && ($boat = null);

            // LOAD -- needs boat of PK/UK
            $behave & ROW_LOAD
                && empty($row[ROW_LOAD]) && $boat                                       // can only load once
                && ($db_io = row_load($pdo, $row[ROW_TABLE], $boat)) && is_array($db_io)         // need PK or UK in assoc
                && ($row[ROW_LOAD] = $db_io)                                            // load row from DB
                && ($row[ROW_SCHEMA] = array_flip(array_keys($db_io)))                  // set schema from loaded row
                && ($boat = null);

            // SET -- needs boat of data to set
            $behave === (ROW_SET | ROW_SCHEMA)
                && ($row[ROW_SCHEMA] = ($boat ?: select_schema($pdo, $row[ROW_TABLE])))
                && $boat && ($boat = null);                                             // falsify boat if we had one

            // put the boat back
            $behave & ROW_SET && ($boat || $setter) && row_set($row, $setter ?? $boat, $behave) && ($boat = null);

            // SAVE --no boat
            $behave & ROW_SAVE && !empty($row[ROW_EDIT])
                && ($row[ROW_SAVE] = row_save($pdo, $row));

            // GET  -- boat optional, last thing to do
            if ($behave & ROW_GET) {

                if ($behave & ROW_SCHEMA)   return $row[ROW_SCHEMA] ?? null;
                if ($behave & ROW_ERROR)    return $row[ROW_ERROR] ?? null;

                $export = row_get($row, $boat, $behave);                                // boat acts as fieldter, if any
                return $boat && isset($boat[0]) && !isset($boat[1])
                    ? $export[$boat[0]]                                                 // we had a boat with a single field, return that value
                    : $export;
            }
        } catch (Throwable $t) {
            $row[ROW_ERROR] = $t;
        }
        return $row;
    };
}

function row_save(PDO $pdo, array $row): PDOStatement
{
    empty($row[ROW_EDIT])               && throw new InvalidArgumentException(__FUNCTION__ . ':no_alterations', 100);

    $unique_value = $row[ROW_LOAD][$row[ROW_UNIQUE]] ?? null;
    [$sql, $bindings] = $unique_value 
        ? qb_update($row[ROW_TABLE], $row[ROW_EDIT], $row[ROW_UNIQUE], (string)$unique_value)
        : qb_insert($row[ROW_TABLE], $row[ROW_EDIT]);
        
    return row_run($pdo, $sql, $bindings);
}

function row_load(PDO $pdo, string $table, array $data): array
{
    empty($data)                        && throw new InvalidArgumentException(__FUNCTION__ . ':no_filters');

    $prepared = row_run($pdo, ...qb_select($table, $data));
    $prepared->rowCount() === 1         || throw new LogicException('cardinality of ' . $prepared->rowCount() . ' for  ' . $table);

    return $prepared->fetch(PDO::FETCH_ASSOC) ?: throw new RuntimeException("Failed to fetch row for $table");
}

function row_set(array &$row, array $data, int $behave = 0): bool
{
    $add_to_edit = null;
    foreach ($data as $col => $value) {
        if ($col === $row[ROW_UNIQUE] || ($row[ROW_LOAD] && array_key_exists($col, $row[ROW_LOAD]) && $row[ROW_LOAD][$col] === $value))
            continue;

        $add_to_edit = $behave & ROW_EDIT || !empty($row[ROW_SCHEMA]) && isset($row[ROW_SCHEMA][$col]);
        $row[$add_to_edit ? ROW_EDIT : ROW_MORE][$col] = $value;
    }
    return $add_to_edit !== null;
}

function row_get(array $row, ?array $data = [], int $behave = 0): ?array
{
    if ($behave & (ROW_LOAD | ROW_EDIT | ROW_MORE)) {
        $result = [];
        ($behave & ROW_MORE) && isset($row[ROW_MORE]) && ($result += $row[ROW_MORE]);
        ($behave & ROW_EDIT) && isset($row[ROW_EDIT]) && ($result += $row[ROW_EDIT]);
        ($behave & ROW_LOAD) && isset($row[ROW_LOAD]) && ($result += $row[ROW_LOAD]);
        return $result;
    }

    $fieldters = $data ?: array_keys($row[ROW_SCHEMA] ?? []);

    if (empty($fieldters))
        return array_merge($row[ROW_LOAD] ?? [], $row[ROW_EDIT] ?? []);

    $ret = [];
    foreach ($fieldters as $col)
        $ret[$col] = $row[ROW_EDIT][$col] ?? $row[ROW_LOAD][$col];

    return $ret;
}

function select_schema(PDO $pdo, string $table): array
{
    $fields = [];
    $query = $pdo->query("SELECT * FROM `$table` LIMIT 1");
    $query || throw new RuntimeException("Failed to query schema for table `$table`");
    for ($i = $query->columnCount() - 1; $i >= 0; --$i) {
        $m = $query->getColumnMeta($i);
        $fields[$m['name']] = $m['pdo_type'] ?? true;
    }
    return $fields;
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
    (!$table || !$data) && throw new BadFunctionCallException(__FUNCTION__ . ':empty_params');

    $named_bindings = $placeholders = $fields = [];
    foreach ($data as $col => $val) {
        $ph = ":row_qb_$col";
        $named_bindings[$ph] = $val;
        $placeholders[] = $ph;
        $fields[] = "`$col`";
    }

    $fields         = implode(',', $fields);
    $placeholders   = implode(',', $placeholders);

    return ["INSERT INTO {$table} ($fields) VALUES ($placeholders);", $named_bindings];
}

// qb_update('article', ['title' => 'Updated Title'], 'id', 42)
function qb_update(string $table, array $data, string $unique_key, string $unique_value): array
{
    (!$table || !$data || !$unique_key || $unique_value === '') && throw new BadFunctionCallException(__FUNCTION__ . ':empty_params');

    $named_bindings = $placeholders = [];

    foreach ($data as $col => $val) {
        $ph = ":row_qb_{$col}";
        $named_bindings[$ph] = $val;
        $placeholders[] = "`$col` = $ph";
    }

    $placeholders   = implode(', ', $placeholders);

    return ["UPDATE `$table` SET $placeholders WHERE `$unique_key` = :qb_update_uk;", $named_bindings + [':qb_update_uk' => $unique_value]];
}

function row_run(PDO $pdo, string $sql, array $bindings): PDOStatement
{
    $prepared = $pdo->prepare($sql);
    $prepared                           || throw new RuntimeException('PDO::prepare failed : ' . json_encode($pdo->errorInfo()));
    $prepared->execute($bindings)       || throw new RuntimeException('PDO::execute failed : ' . json_encode($prepared->errorInfo()));
    return $prepared;
}
