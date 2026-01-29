<?php

namespace bad\pdo;                                                   // lightweight PDO helpers with explicit failure semantics

function db(?\PDO $pdo = null): \PDO
{// get/set the shared PDO instance
    static $cache = null;                                           // request-scoped PDO cache
    ($pdo !== null) && ($cache = $pdo);                             // initialize / replace cached connection
    return $cache                                                   ?? throw new \BadFunctionCallException(__FUNCTION__.':NO_CACHED_PDO');
}

function qp(string $query, ?array $params = null, array $prep_options = [], ?\PDO $pdo = null): \PDOStatement
{// query/prepare/process (params=[], then prepare-only)
    $pdo ??= db();                                                  // resolve PDO lazily from cache
    if ($params === null) return $pdo->query($query)                ?: throw _ErrorInfoException($pdo, 'query');
    $stm = $pdo->prepare($query, $prep_options)                     ?: throw _ErrorInfoException($pdo, 'prepare');
    if ($params) $stm->execute($params)                             || throw _ErrorInfoException($stm, 'execute');
    return $stm;
}// return prepared (possibly executed) statement

function trans(callable $transaction, ?\PDO $pdo = null)
{// execute callable inside a single atomic transaction
    $pdo ??= db();                                                  // resolve PDO lazily from cache
    !$pdo->inTransaction()                                          || throw new \LogicException('PDO does not support real nested transactions');
    $res = null;                                                    // capture callable result for return
    try {                                                           // outer try/catch to manage transaction atomicity
        $pdo->beginTransaction()                                    || throw _ErrorInfoException($pdo, 'beginTransaction');
        try {
            $res = $transaction($pdo);
        } catch (\Throwable $t) {
            throw _ErrorInfoException($pdo, 'transaction callable', 0xC0D, $t);
        }
        $pdo->commit()                                              || throw _ErrorInfoException($pdo, 'commit');
    } catch (\Throwable $t) {
        $pdo->inTransaction() && ($pdo->rollBack()                  || throw _ErrorInfoException($pdo, 'rollback',$t));
        throw $t;                                                   // rethrow original error after rollback
    }
    return $res;
}// return the callable result after successful commit

function _ErrorInfoException(\PDO|\PDOStatement $source, $action, ?\Throwable $chain = null): \RuntimeException
{// creates rich, chainable runtime exception
    $error = $source->errorInfo() ?: ['NO_STATE', 'NO_CODE', 'errorInfo(): empty'];
    return new \RuntimeException("[STATE={$error[0]}, CODE={$error[1]}] PDO::{$action}() failed ({$error[2]})", 0xC0D, $chain);
}// returns \RuntimeException with PDO error info embedded in message