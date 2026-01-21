<?php

namespace bad\db;                                               // lightweight PDO helpers with explicit failure semantics

function db(?\PDO $pdo = null, int $behave = 0): \PDO
{// get/set the shared PDO instance
    static $cache = null;                                       // request-scoped PDO cache

    ($pdo !== null) && ($cache = $pdo);                         // initialize / replace cached connection
    
    return $cache                                               ?? throw new \BadFunctionCallException('Call db(PDO) first', 400);
}

function qp(string $query, ?array $params = null, array $prep_options = [], ?\PDO $pdo = null): \PDOStatement
{// query/prepare/process (params=[], then prepare-only)
    $pdo ??= db();                                              // resolve PDO lazily from cache
    if ($params === null) return $pdo->query($query)            ?: throw _PDOErrorInfoException($pdo, 'PDO::query', 500);
    $stm = $pdo->prepare($query, $prep_options)                 ?: throw _PDOErrorInfoException($pdo, 'PDO::prepare', 500);
    if ($params) $stm->execute($params)                         || throw _PDOErrorInfoException($stm, 'PDO::execute', 500);
    return $stm;
}// return prepared (possibly executed) statement

function trans(callable $transaction, ?\PDO $pdo = null)
{// execute callable inside a single atomic transaction
    $pdo ??= db();                                              // resolve PDO lazily from cache
    $res = null;                                                // capture callable result for return
    $pdo->inTransaction()                                       && throw new \LogicException('PDO does not support real nested transactions', 400);
    try {                                                       // outer try/catch to manage transaction atomicity
        $pdo->beginTransaction()                                || throw _PDOErrorInfoException($pdo, 'PDO::beginTransaction', 500);

        try {
            $res = $transaction($pdo);
        } catch (\Throwable $t) {
            throw _PDOErrorInfoException($pdo, 'transaction callable', 400, $t);
        }
        $pdo->commit()                                          || throw _PDOErrorInfoException($pdo, 'PDO::commit', 500);
    } catch (\Throwable $t) {
        $pdo->inTransaction() && ($pdo->rollBack()              || throw _PDOErrorInfoException($pdo, 'PDO::rollback', 500, $t));
        throw $t;                                               // rethrow original error after rollback
    }
    return $res;
}// return the callable result after successful commit

function _PDOErrorInfoException(\PDO|\PDOStatement $source, $action, $exception_code, ?\Throwable $chain = null): \RuntimeException
{// creates rich, chainable runtime exception
    $error = $source->errorInfo() ?: ['NO_STATE', 'NO_CODE', 'errorInfo(): empty'];
    return new \RuntimeException("[STATE={$error[0]}, CODE={$error[1]}] {$action}() failed ({$error[2]})", $exception_code, $chain);
}// returns \RuntimeException with PDO error info embedded in message
