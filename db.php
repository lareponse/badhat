<?php
namespace bad\db;

const VOID_CACHE = 1;

function db(?\PDO $pdo = null, int $behave = 0): \PDO
{
    static $cache = null;

    ($pdo !== null) && ($cache = $pdo);
    (VOID_CACHE & $behave) && ($cache = null);
    
    return $cache                                   ?? throw new \BadFunctionCallException('Call db(PDO) first', 400);
}
// prepare-only with params = []
function qp(string $query, ?array $params = null, array $prep_options = [], ?\PDO $pdo = null): \PDOStatement
{
    $pdo ??= db();                                  // throws if no previous db(PDO) call was made
    if($params === null) return $pdo->query($query) ?: pdo_throw($pdo, 'PDO::query', 500);
    
    $stm = $pdo->prepare($query, $prep_options)     ?: pdo_throw($pdo, 'PDO::prepare', 500);
    if($params) $stm->execute($params)              || pdo_throw($stm, 'PDO::execute', 500);
    return $stm;
}

function trans(callable $transaction, ?\PDO $pdo = null)
{
    $pdo ??= db();                                  // throws if no previous db(PDO) call was made
    try {
        $res = null;
        $pdo->beginTransaction()                    || pdo_throw($pdo, 'PDO::beginTransaction', 500);
        try{
            $res = $transaction($pdo);
        }catch(\Throwable $t){
            pdo_throw($pdo, 'transaction callable', 400, $t);
        }
        $pdo->commit()                              || pdo_throw($pdo, 'PDO::commit', 500);
        return $res;
    } catch (\Throwable $e) {
        $pdo->inTransaction() && ($pdo->rollBack()  || pdo_throw($pdo, 'PDO::rollback', 500, $e));
        throw $e;
    }
}

function pdo_throw($errorInfoable, string $action, int $exception_code, ?\Throwable $chain = null)
{
    $error = $errorInfoable->errorInfo() ?: ['?????', 0, 'EMPTY_ERROR_INFO']; // 0: sql-state  1: driver-code  2: message
    throw new \RuntimeException("[STATE={$error[0]}, CODE={$error[1]}] $action failed ({$error[2]})", $exception_code, $chain);
}