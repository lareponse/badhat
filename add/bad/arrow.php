<?php

/** ARROW --------------------------------->
 * 
 * ARRays
 * Four for state (load, schema, edit, more)
 *          ROW_LOAD   (1), 
 *          ROW_EDIT   (4), 
 *          ROW_MORE   (8)
 * 
 * ROW
 * Load one, save one, capture errors
 *          ROW_LOAD     (1),
 *          ROW_SAVE    (16),
 *          ROW_ERROR  (128)
 * 
 * Restrict
 * Auto sorts data into edit or more
 *          ROW_SCHEMA (2), 
 *          ROW_SET    (32),
 
 * Obtain data and structure
 *         ROW_GET    (64)
 * 
 * Write SQL
 *   - function qb_select(string $table, array $data): array
 *   - function qb_insert(string $table, array $data): array
 *   - function qb_update(string $table, array $data, int $id): array
 */

declare(strict_types=1);

const ROW_ID     = 'id';

const ROW_LOAD   = 1;
const ROW_SCHEMA = 2;
const ROW_EDIT   = 4;
const ROW_MORE   = 8;
const ROW_SAVE   = 16;
const ROW_ERROR  = 128;
const ROW_SET    = 32;
const ROW_GET    = 64;


// $full = $entity(ROW_LOAD | ROW_GET, ['id' => 123]);
// $entity(ROW_SET | ROW_SAVE, ['name' => 'New Name']);
// $entity(ROW_GET, ['name', 'email']);
// $entity(ROW_SET | ROW_SCHEMA); // set schema
// $entity(ROW_GET | ROW_SCHEMA); // get schema
// ensure we know the column list, safe set values, extract the merged result
// $preview = $entity(ROW_SCHEMA | ROW_SET | ROW_GET, ['name' => 'Acme', 'type' => 'widget']);
// $entity(ROW_SET | ROW_SCHEMA | ROW_SAVE, ['name' => 'New Name', 'relational-goop' => 'goop']);
function row(PDO $pdo, string $table): callable
{
    return function (int $behave, array $boat = []) use ($pdo, $table) {
        static $row = [];

        try {
            // LOAD ROW
            $behave & ROW_LOAD
                && empty($row[ROW_LOAD]) && $boat                                       // can only load once
                && ($db_io = row_load($pdo, $table, $boat)) && is_array($db_io)         // need PK or UK in assoc
                && ($row[ROW_LOAD] = $db_io)                                            // load row from DB
                && ($row[ROW_SCHEMA] = array_flip(array_keys($db_io)))                  // set schema from loaded row
                && ($boat = null);

            // SET ROW
            $behave === (ROW_SET | ROW_SCHEMA)
                && ($row[ROW_SCHEMA] = ($boat ?: select_schema($pdo, $table)))
                && $boat && ($boat = null);                                             // falsify boat if we had one

            $behave & ROW_SET && $boat && row_set($row, $boat, $behave) && ($boat = null);

            // SAVE ROW
            $behave & ROW_SAVE && $row[ROW_EDIT]
                && ($row[ROW_SAVE] = row_save($pdo, $table, $row))
                && ($row[ROW_EDIT] = []);

            if ($behave & ROW_GET) {

                if ($behave & ROW_SCHEMA)   return $row[ROW_SCHEMA] ?? null;
                if ($behave & ROW_ERROR)    return $row[ROW_ERROR] ?? null;

                $export = row_get($row, $boat, $behave);
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

function row_save(PDO $pdo, string $table, array $row): PDOStatement
{
    empty($row[ROW_EDIT])               && throw new DomainException('no_alterations');

    [$sql, $bindings] = $row[ROW_LOAD]
        ? qb_update($table, $row[ROW_EDIT], $row[ROW_LOAD][ROW_ID])
        : qb_insert($table, $row[ROW_EDIT], array_keys($row[ROW_SCHEMA]));

    $prepared = $pdo->prepare($sql);
    $prepared                           || throw new DomainException($sql, PDO::PARAM_EVT_EXEC_PRE);
    $prepared->execute($bindings)       || throw new DomainException(json_encode($prepared->errorInfo()), PDO::PARAM_EVT_EXEC_POST);
    return $prepared;
}

function row_load(PDO $pdo, string $table, array $data): array
{
    empty($data)                        && throw new DomainException('no_assoc_data');

    [$sql, $bindings] = qb_select($table, $data);

    $prepared = $pdo->prepare($sql);
    $prepared                           || throw new DomainException($sql, PDO::PARAM_EVT_EXEC_PRE);
    $prepared->execute($bindings)       || throw new DomainException(json_encode($prepared->errorInfo()), PDO::PARAM_EVT_EXEC_POST);
    $prepared->rowCount() === 1         || throw new DomainException("cardinality of $sql is " . $prepared->rowCount());

    return $prepared->fetch(PDO::FETCH_ASSOC) ?: throw new DomainException("Failed to fetch row for $sql");
}

function row_set(array &$row, array $data, int $behave = 0): bool
{
    $add_to_edit = null;
    foreach ($data as $col => $value) {
        if ($col === ROW_ID || ($row[ROW_LOAD] && array_key_exists($col, $row[ROW_LOAD]) && $row[ROW_LOAD][$col] === $value))
            continue;

        $add_to_edit = $behave & ROW_EDIT || !empty($row[ROW_SCHEMA]) && isset($row[ROW_SCHEMA][$col]);
        $row[$add_to_edit ? ROW_EDIT : ROW_MORE][$col] = $value;
    }
    return $add_to_edit !== null;
}

function row_get(array $row, ?array $data = [], int $behave = 0): ?array
{
    if ($behave & (ROW_LOAD | ROW_EDIT | ROW_MORE)) {
        $parts = [];
        $behave & ROW_LOAD && ($parts[] = $row[ROW_LOAD] ?? []);
        $behave & ROW_EDIT && ($parts[] = $row[ROW_EDIT] ?? []);
        $behave & ROW_MORE && ($parts[] = $row[ROW_MORE] ?? []);
        return $parts ? array_merge(...$parts) : [];
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
    $query || throw new DomainException("Failed to query schema for table `$table`");
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
