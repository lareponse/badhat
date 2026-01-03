<?php
function db($param = null, array $options = [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]): PDO
{
    static $cache = null;

    if ($cache && $param === null)  return $cache;  // db() call after setup, most frequent
    !$param && ($param = '');                       // no cache, no param, switch to default

    if ($param instanceof PDO)      $cache = $param;
    elseif (is_string($param))      $cache = new PDO(
        $_SERVER['DB_DSN_'  . $param] ?: (getenv('DB_DSN_'  . $param) ?: throw new DomainException("db-empty-dsn-for($param)")),
        $_SERVER['DB_USER_' . $param] ?: (getenv('DB_USER_' . $param) ?: null),
        $_SERVER['DB_PASS_' . $param] ?: (getenv('DB_PASS_' . $param) ?: null),
        $options
    );

    return $cache                   ?? throw new LogicException('db-no-connection');
}

// $params: null > query(), [] > prepare only, [non-empty] > prepare + execute
function qp(string $query, ?array $params = null, array $prepare_options = [], ?string $suffix = null): PDOStatement|false
{
    return $params === null
        ? db($suffix)->query($query)
        : (($prep = db($suffix)->prepare($query, $prepare_options)) && $params && $prep->execute($params)
            ? $prep : $prep);
}

function dbt(callable $transaction, ?string $suffix = null)
{
    $pdo = db($suffix);
    try {
        $pdo->beginTransaction();
        $out = $transaction($pdo);
        $pdo->commit();
        $pdo->errorCode() !== '00000' && ($error = $pdo->errorInfo()) && throw new RuntimeException($error[0] . ':' . $error[2], $error[1]);
        return $out;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
