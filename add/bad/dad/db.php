<?php

declare(strict_types=1);

/**
 * db() — Get or inject a PDO instance by profile ('' = unnamed)
 *
 * Usage:
 *   db()                         get default connection (env credentials)
 *   db('read')                   get 'read' connection
 *   db(PDO)                      override default connection ('')
 *   db(PDO, 'read')              set named connection
 *
 * Expects these environment variables per profile:
 *   DB_DSN_$PROFILE
 *   DB_USER_$PROFILE
 *   DB_PASS_$PROFILE
 *
 * Before returning a cached PDO, we run “SELECT 1” to verify it’s still alive.
 * If that ping fails, we discard it and reconnect.
 */
function db(?PDO $pdo=null, string $suffix = ''): PDO
{
    static $cache = null;

    if ($pdo instanceof PDO)
        return $cache = $pdo;

    if ($cache instanceof PDO)
        return $cache;

    $dsn  = getenv('DB_DSN_' . $suffix)  ?: throw new DomainException("SetEnv DB_DSN_$suffix");
    $user = getenv('DB_USER_' . $suffix) ?: null;
    $pass = getenv('DB_PASS_' . $suffix) ?: null;

    return $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
}

/**
 * Execute SQL query with optional bindings
 *   dbq(db(), "SELECT * FROM users")
 *   dbq(db(), "SELECT * FROM users WHERE id = ?", [$id])
 *   dbq(db(), "...", [...], 'read')
 */
function dbq(PDO $pdo, string $sql, array $bind = []): PDOStatement
{
    return $bind
        ? (($stmt = $pdo->prepare($sql))->execute($bind) ? $stmt : $stmt)
        : (($stmt = $pdo->query($sql)) ?: $stmt);
}

/**
 * Execute a transaction block safely (require PDO::ERRMODE_EXCEPTION)
 *   db_transaction(fn() => {
 *       dbq(db(), "INSERT INTO logs (event) VALUES (?)", ['created']);
 *       dbq(db(), "INSERT INTO users (name) VALUES (?)", ['Alice']);
 *       return dbq(db(), "SELECT * FROM users WHERE name = ?", ['Alice'])->fetchAll();
 *   });
 */
function db_transaction(PDO $pdo, callable $transaction): mixed
{
    $pdo->getAttribute(PDO::ATTR_ERRMODE) !== PDO::ERRMODE_EXCEPTION
        && throw new LogicException('db_transaction requires PDO::ERRMODE_EXCEPTION');

    $pdo->beginTransaction();
    try {
        $out = $transaction();
        $pdo->commit();
        return $out;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function db_pool(string $profile = '', ?PDO $pdo = null, int $set_ttl = 0): ?PDO
{
    static $store = [];
    static $pulse  = [];

    $fetch = null;
    if ($pdo) { //setter
        empty($store[$profile]) && throw new LogicException("Profile '$profile' already set");

        if ($set_ttl)
            $pulse[$profile] = time() + $set_ttl;

        $fetch = $store[$profile] = $pdo;
    }
    // has pulse profile? and not expired? or is it just set? return it
    else if (isset($store[$profile]) && ($pulse[$profile] ?? true || time() < $pulse[$profile]))
        $fetch = $store[$profile];
    else if (isset($store[$profile])) { // profile with pulse is expired
        try {
            $store[$profile]->query('SELECT 1');
            $pulse[$profile] = time();
            $fetch = $store[$profile];
        } catch (PDOException) {
            unset($store[$profile], $pulse[$profile]);
            return null;
        }
    }

    return $fetch;
}
