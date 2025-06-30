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

        $behave & ROW_LOAD && $boat && ($row[ROW_LOAD] = row_load($pdo, $table, $boat)) && $boat = null; // reset boat

        if ($behave & ROW_SCHEMA) {
            $boat                                   && ($row[ROW_SCHEMA] = $boat) && $boat = null;                                      // payload is always set
            !$row[ROW_SCHEMA] && $row[ROW_LOAD]     && ($row[ROW_SCHEMA] = array_flip(array_keys($row[ROW_LOAD])));     // skip schema query if we have row_load (updates)
            !$row[ROW_SCHEMA]                       && ($row[ROW_SCHEMA] = select_schema($pdo, $table));             // cant skip it for inserts
        }

        if ($behave & ROW_SET){
            $behave & ROW_SCHEMA && !isset($row[ROW_SCHEMA]) && ($row[ROW_SCHEMA] = select_schema($pdo, $table)); // ensure schema is set
            $boat && row_set($row, $boat, $behave) && $boat = null;
        }

        if ($behave & ROW_SAVE && $row[ROW_EDIT]) {
            $save = row_save($pdo, $table, $row);

            if (!$save || !$save instanceof PDOStatement || $save->errorCode() !== PDO::ERR_NONE) {
                $row[ROW_ERROR] = $save ? $save->errorInfo() : 'Unknown error';
            } else {
                $row[ROW_EDIT] = [];
                $row[ROW_SAVE] = $save;
            }
        }

        if ($behave & ROW_GET) {
            $export = row_get($row, $boat, $behave);
            return isset($boat[0]) && !isset($boat[1]) ? $export[$boat[0]] : $export;
        }

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

function row_load(PDO $pdo, string $table, array $data): ?array
{
    $qb = qb_select($table, $data);
    $stmt = $pdo->prepare($qb[0]);
    if (!$stmt || !$stmt->execute($qb[1]) || $stmt->rowCount() !== 1)
        return null;

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function row_set(array &$row, array $data, int $behave = 0): bool
{
    $behave & ROW_SCHEMA && ($row[ROW_SCHEMA] = select_schema($row[ROW_LOAD] ?? null, $data)); // ensure schema is set
    $add_to_edit = null;
    foreach ($data as $col => $value) {
        if ($col === ROW_ID || isset($row[ROW_LOAD][$col]) && $row[ROW_LOAD][$col] === $value)
            continue; // skip id or existing identical values

        // force edit      or if we have fields restriction and applicable ?
        $add_to_edit = $behave & ROW_EDIT || $row[ROW_SCHEMA] && isset($row[ROW_SCHEMA][$col]);
        $row[$add_to_edit ? ROW_EDIT : ROW_MORE][$col] = $value;
    }

    return $add_to_edit !== null;
}

// accepted ROW_GET combination: 
//      ROW_SCHEMA, 
//      ROW_ERROR, 
//      (ROW_LOAD | ROW_EDIT | ROW_MORE)
// no extra behavior, fieldters, explicitly set 
//      to null,    cancels all fields filtering, 
//      to [],      fallbacks to schema then empty
function row_get(array $row, ?array $fieldters = [], int $behave = 0): ?array
{
    if($behave & ROW_SCHEMA)
        return $row[ROW_SCHEMA] ?? null;

    if ($behave & ROW_ERROR)
        return $row[ROW_ERROR] ?? null;

    if ($behave & (ROW_LOAD | ROW_EDIT | ROW_MORE)){
        $_ = [];
        $behave & ROW_LOAD && ($_ += $row[ROW_LOAD] ?? []);
        $behave & ROW_EDIT && ($_ += $row[ROW_EDIT] ?? []);
        $behave & ROW_MORE && ($_ += $row[ROW_MORE] ?? []);
        return $_;
    }
    
    $fieldters = $fieldters ?: array_keys($row[ROW_SCHEMA] ?? []);

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
    $fields_query = $pdo->query("SELECT * FROM `$table` LIMIT 1");
    for ($i = $fields_query->columnCount() - 1; $i >= 0; --$i) {
        $m = $fields_query->getColumnMeta($i);
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
