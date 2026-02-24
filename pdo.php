<?php

namespace bad\pdo;

function db(?\PDO $pdo = null): \PDO
{// get/set the shared PDO instance
    static $cache = null;
    ($pdo !== null) && ($cache = $pdo);
    return $cache ?? throw new \BadFunctionCallException(__FUNCTION__.':NO_CACHED_PDO');
}

function qp(string $query, ?array $params = null, array $prep_options = [], ?\PDO $pdo = null): \PDOStatement
{// query/prepare/process (params=[], then prepare-only)
    $pdo ??= db();
    $stm = null;

    try {
        if ($params === null)
            return $pdo->query($query)                              ?: throw new \RuntimeException(redact_key($pdo, 'query'));

        $stm = $pdo->prepare($query, $prep_options)                 ?: throw new \RuntimeException(redact_key($pdo, 'prepare'));

        if ($params)
            $stm->execute($params)                                  || throw new \RuntimeException(redact_key($stm, 'execute'));

        return $stm;
    } catch (\RuntimeException $e) {
        throw $e;
    } catch (\Throwable $t) {
        $source = ($stm instanceof \PDOStatement) ? $stm : $pdo;
        $action = ($params === null) ? 'query' : ($stm ? 'execute' : 'prepare');
        throw new \RuntimeException(redact_key($source, $action, $t));
    }
}// return prepared (possibly executed) statement

function trans(callable $transaction, ?\PDO $pdo = null)
{// execute callable inside a single atomic transaction
    $pdo ??= db();

    if ($pdo->inTransaction())
        throw new \LogicException('nested transaction');

    $steps = 'beginTransaction';
    if ($pdo->beginTransaction())
    {
        $ex = null;
        try {
            $steps = 'transaction';
            $mixed = $transaction($pdo);
        } catch (\Throwable $t) {
            $ex = $t;
        }

        if ($ex === null)
            try{
                $steps = 'commit';
                if ($pdo->commit())
                    return $mixed;
            } catch (\Throwable $t) {
                $ex = $t;
            }

    }
    $pdo->inTransaction() && $pdo->rollBack();
    throw new \RuntimeException(redact_key($pdo, $steps, $ex), $steps === 'transaction' ? 0xBADC0DE : 0);
}// return the callable result after successful commit

function redact_key(\PDO|\PDOStatement $source, string $action, ?\Throwable $t = null): string
{
    $err  = $source->errorInfo() ?? [];
    $base = $source::class . "::{$action} [" . ($err[0] ?? '?') . ':' . ($err[1] ?? '?') . ']';
    $t && ($base .= ' ' . $t::class . '#' . (string)$t->getCode());
    return $base;
}