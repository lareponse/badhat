<?php
namespace bad\db;

const VOID_CACHE = 1;

function db(?\PDO $pdo = null, int $behave = 0): \PDO
{
    static $cache = null;
    
    ($pdo !== null) && ($cache = $pdo);
    (VOID_CACHE & $behave) && ($cache = null);
    
    return $cache                                   ?? throw new \BadFunctionCallException('Call db(PDO) first', 0xBAD);
}

function qp(string $query, ?array $params = null, array $prep_options = [], ?\PDO $pdo = null): \PDOStatement
{
    $pdo ??= db();                                  // throws if no previous db(PDO) call was made
    if($params === null) return $pdo->query($query) ?: pdo_throw($pdo, 'PDO::query');
    
    $stm = $pdo->prepare($query, $prep_options)     ?: pdo_throw($pdo, 'PDO::prepare');
    if($params) $stm->execute($params)              || pdo_throw($stm, 'PDO::execute');
    return $stm;
}

function trans(callable $transaction, ?\PDO $pdo = null)
{
    $pdo ??= db();                                  // throws if no previous db(PDO) call was made
    $res = null;
    try {
        $pdo->beginTransaction()                    || pdo_throw($pdo, 'PDO::beginTransaction', 0xACE);
        try{
            $res = $transaction($pdo);
        }catch(\Throwable $t){
            throw new \RuntimeException('transaction callable', 0xC0D, $t);
        }
        $pdo->commit()                              || pdo_throw($pdo, 'PDO::commit');
        return $res;
    } catch (\Throwable $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack()                        || pdo_throw($pdo, 'PDO::rollback', 0xACE, $e);
        throw $e;
    }
}

function pdo_throw($errorInfoable, string $action, int $exception_code = 500, ?\Throwable $chain = null)
{
    $error = $errorInfoable->errorInfo() ?? ['?????', 0, 'unknown error, errorInfo is null'];
    throw new \RuntimeException("[STATE={$error[0]}, CODE={$error[1]}] $action failed ({$error[2]})", $exception_code, $chain); // 0: sql-state  1: driver-code  2: message
}