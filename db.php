<?php
namespace bad\db;

const DB_DROP = 1;

function db(?\PDO $pdo = null, int $behave = 0): \PDO
{
    static $cache = null;
    ($behave & DB_DROP) && ($cache = null);
    $pdo !== null && ($cache = $pdo);
    return $cache                                   ?? throw new \BadFunctionCallException('Call db(PDO) first', 500);
}

function qp(string $query, ?array $params = null, array $prep_options = [], ?\PDO $pdo = null): \PDOStatement
{
    $pdo ??= db();                                  // throws if no previous db(PDO) call was made
    if($params === null) return $pdo->query($query) ?: throw new \RuntimeException('query failed', 500);
    $prepare = $pdo->prepare($query, $prep_options) ?: throw new \RuntimeException('prepare failed', 500);
    if($params) $prepare->execute($params)          || throw new \RuntimeException('execute failed', 500);
    return $prepare;
}

function dbt(callable $transaction, ?\PDO $pdo = null)
{
    $pdo ??= db();
    try {
        $pdo->beginTransaction()                    || throw new \RuntimeException('beginTransaction failed', 500);
        $out = $transaction($pdo);
        $pdo->commit()                              || throw new \RuntimeException('commit failed', 500);
        if($pdo->errorCode() !== '00000')           // if PDO not in exception mode
            ($error = $pdo->errorInfo())            && throw new \RuntimeException($error[0] . ':' . $error[2], $error[1]); // 0: sql-state  1: driver-code  2: message
        return $out;
    } catch (\Throwable $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack()                        || throw new \RuntimeException('rollback failed', 500, $e);
        throw $e;
    }
}