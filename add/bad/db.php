<?php

function db(
    ?string $dsn = null,
    ?string $user = null,
    ?string $pass = null,
    ?array $options = null
): PDO {

    static $pdo;

    if ($pdo === null) {
        $dsn  = $dsn  ?? defined('DB_DSN')  ? DB_DSN : null;
        $user = $user ?? defined('DB_USER') ? DB_USER : null;
        $pass = $pass ?? defined('DB_PASS') ? DB_PASS : null;

        if(!isset($dsn, $user, $pass)) {
            throw new LogicException("500 Database Definition Disappear");
        }
        // resolve defaults
        $options = $options ?? [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        $pdo = new PDO($dsn, $user, $pass, $options);
    }

    return $pdo;
}

function db_state(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_create(string $table, array $data): PDOStatement
{
    $sql  = sprintf(
        "INSERT INTO $table (%s) VALUES (%s);",
        implode(',', array_keys($data)),
        implode(',', array_fill(0, count($data), '?'))
    );
    $bindings = array_values($data);
    return db_state($sql, $bindings);
}

function db_update(string $table, array $data, string $where, array $params = []): PDOStatement
{
    $sets = implode(',', array_map(fn($c) => "$c = ?", array_keys($data)));
    $sql  = "UPDATE $table SET $sets WHERE $where";
    $bindings = array_merge(array_values($data), $params);

    return db_state($sql, $bindings);
}

function db_transaction(callable $work)
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $result = $work();
        $result ? $pdo->commit() : $pdo->rollBack();
        return $result;
    } catch (\Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
