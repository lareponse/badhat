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
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]): PDO
{
    static $cache = null;

    if (!$param)
        $cache ??= db_connect('', $param_options); 

    else if ($param instanceof PDO)
        $cache = $param;

    else if (is_string($param)) 
        $cache = db_connect($param, $param_options);
    
    return $cache ?? throw new LogicException('db() requires a string suffix or PDO instance');
}

/**
 * Query Prepare (and execute with non empty binding params.
 *
 * Usage:
 *   qp(PDO, "SELECT * FROM operator WHERE id = :id", [':id' => $id]);
 *   qp(PDO, "SELECT * FROM events WHERE type = ?", ['click'], [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
 *   qp(PDO, "SELECT * FROM users", []); // works, but use db()->query()
 *   qp(PDO, "SELECT * FROM users", null); // prepares only, no execution
 */
function qp(PDO $pdo, string $query, ?array $params, array $prepareOptions = []): PDOStatement|false
{
    $_ = $pdo->prepare($query, $prepareOptions);
    $_ && $params!==null && $_->execute($params);
    return $_;
}

/**
 * @requires PDO::ERRMODE_EXCEPTION
 * @throws DomainException        If the PDO isn’t in ERRMODE_EXCEPTION
 * @throws Throwable              Any exception from inside your callable (after rollback)
 */
function db_transaction(PDO $pdo, callable $transaction): mixed
{
    $pdo->getAttribute(PDO::ATTR_ERRMODE) !== PDO::ERRMODE_EXCEPTION
        && throw new DomainException('db_transaction requires PDO::ERRMODE_EXCEPTION');

    $pdo->beginTransaction();
    try {
        $out = $transaction($pdo);
        $pdo->commit();
        return $out;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function db_connect($getenv_suffix = '', ?array $options = null): PDO
{
    $dsn  = getenv('DB_DSN_' . $getenv_suffix)  ?: throw new DomainException("empty getenv(DB_DSN_$getenv_suffix)");
    $user = getenv('DB_USER_' . $getenv_suffix) ?: null;
    $pass = getenv('DB_PASS_' . $getenv_suffix) ?: null;

    return new PDO($dsn, $user, $pass, $options);
}
