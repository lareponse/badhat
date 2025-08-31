<?php

declare(strict_types=1);

/**
 * db() { Get or set a SINGLE cached PDO connection }
 * 
 * Usage:
 *   db()                         get cached connection or create from default env vars (empty suffix)
 *   db(string)                   create connection from 'read' suffix env vars, cache and return it
 *   db(PDO)                      set the cached connection and return it
 *
 * Note: Only one connection cached. Each string suffix call replaces the cache.
 * Note: falsy values of $param are interpreted as empty suffix ''
 * Note: Yes, trailing underscore for default connection ENV variables (DB_DSN_, DB_USER_, DB_PASS_)
 * 
 * @throws DomainException          If required env var DB_DSN_${suffix} is missing
 * @throws LogicException           If $param is neither string nor PDO, and not cache hit
 * @throws PDOException             If PDO connection fails
 */
function db($param = null, array $param_options = [
    PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES      => false,
]): PDO
{
    static $cache = null;

    if ($cache && $param === null)  return $cache;  // db() call after setup, most frequent

    !$param && ($param = '');                       // no cache, no param, switch to default

    if ($param instanceof PDO)      $cache = $param;
    elseif (is_string($param))      $cache = pdo_env($param, $param_options);

    return $cache                   ?? throw new LogicException('db() requires a string suffix or PDO instance');
}

// $params: null > query(), [] > prepare only, [non-empty] > prepare + execute
function dbq(string $query, ?array $params = null, array $prepare_options = []): PDOStatement|false
{
    if($params === null)
        return db()->query($query);

    ($prepared = db()->prepare($query, $prepare_options)) && $params && $prepared->execute($params);
    return $prepared;
}

function db_transaction(PDO $pdo, callable $transaction): mixed
{
    $pdo->getAttribute(PDO::ATTR_ERRMODE) !== PDO::ERRMODE_EXCEPTION && throw new DomainException('db_transaction requires PDO::ERRMODE_EXCEPTION');

    try {
        $pdo->beginTransaction();
        $out = $transaction($pdo);
        $pdo->commit();
        return $out;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function pdo_env(string $env_suffix, ?array $options = null): PDO
{
    return new PDO(
        $_SERVER['DB_DSN_'  . $env_suffix] ?? (getenv('DB_DSN_'  . $env_suffix) ?: throw new DomainException("empty env(DB_DSN_$env_suffix)")),
        $_SERVER['DB_USER_' . $env_suffix] ?? (getenv('DB_USER_' . $env_suffix) ?: null),
        $_SERVER['DB_PASS_' . $env_suffix] ?? (getenv('DB_PASS_' . $env_suffix) ?: null),
        $options
    );
}