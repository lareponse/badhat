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
    if ($params === null) return $pdo->query($query)                ?: throw new \RuntimeException(_ErrorInfoMessage($pdo, 'query'));
    $stm = $pdo->prepare($query, $prep_options)                     ?: throw new \RuntimeException(_ErrorInfoMessage($pdo, 'prepare'));
    if ($params) $stm->execute($params)                             || throw new \RuntimeException(_ErrorInfoMessage($stm, 'execute'));
    return $stm;
}// return prepared (possibly executed) statement

function trans(callable $transaction, ?\PDO $pdo = null)
{// execute callable inside a single atomic transaction
    $pdo ??= db();                                                  // resolve PDO lazily from cache
    !$pdo->inTransaction()                                          || throw new \LogicException('PDO does not support real nested transactions');
    $res = null;                                                    // capture callable result for return
    try {                                                           // outer try/catch to manage transaction atomicity
        $pdo->beginTransaction()                                    || throw new \RuntimeException(_ErrorInfoMessage($pdo, 'beginTransaction'));
        try {
            $res = $transaction($pdo);
        } catch (\Throwable $t) {
            throw new \RuntimeException(_ErrorInfoMessage($pdo, 'callable', $t), 0xBADC0D);
        }
        $pdo->commit()                                              || throw new \RuntimeException(_ErrorInfoMessage($pdo, 'commit'));
    } catch (\Throwable $t) {
        $pdo->inTransaction() && ($pdo->rollBack()                  || throw new \RuntimeException(_ErrorInfoMessage($pdo, 'rollback', $t)));
        throw $t;                                                   // rethrow original error after rollback
    }
    return $res;
}// return the callable result after successful commit

function _ErrorInfoMessage(\PDO|\PDOStatement $source, string $action, ?\Throwable $t = null): string
{
    $err  = $source->errorInfo() ?? [];
    $base = $source::class . "::{$action} [" . ($err[0] ?? '?').':' .($err[1] ?? '?').']';
    $t && ($base .= ' ' . $t::class);
    
    $log = $base . ': ' . ($err[2] ?? '?');                          // may be sensitive
    $t && $log .= ' | ' . $t::class . '#' . (string)$t->getCode() . ': ' . $t->getMessage();

    trigger_error(str_replace(["\r", "\n"], ' ', $log), E_USER_WARNING);
    return $base;
}// returns sanitized base; log driver detail + throwable detail (may include sensitive info)
