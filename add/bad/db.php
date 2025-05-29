<?php

declare(strict_types=1);

/**
 * db() — Get or inject a PDO instance by profile ('' = unnamed)
 *
 * Usage:
 *   db()                         get default connection
 *   db('read')                   get 'read' connection
 *   db($pdo)                     override getenv to set default connection
 *   db(new PDO('sqlite::memory:'), 'test')             → set named connection
 * 
 * 
 * expects environment variables:
 *   DB_DSN_$profile, DB_USER_$profile, DB_PASS_$profile
 *      Where profile is the name of the connection, or empty for the default (DB_DSN_, DB_USER_, DB_PASS_)
 */
function db(PDO|string $arg = '', ?string $profile = null): PDO
{
    static $map = [];

    // Setter mode
    if ($arg instanceof PDO) {
        $map[$profile ?? ''] = $arg;
        return $arg;
    }

    // Getter mode
    $profile = $arg ?? '';

    if (isset($map[$profile])) {
        return $map[$profile];
    }

    $dsn  = getenv("DB_DSN_$profile")  ?: throw new LogicException("Missing ENV: DB_DSN_$profile");
    $user = getenv("DB_USER_$profile") ?: null;
    $pass = getenv("DB_PASS_$profile") ?: null;

    return $map[$profile] = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
}


/**
 * Execute SQL query with optional bindings
 *   dbq("SELECT * FROM users")
 *   dbq("SELECT * FROM users WHERE id = ?", [$id])
 *   dbq("...", [...], 'read')
 */
function dbq(string $sql, array $bind = [], string $profile = ''): PDOStatement
{
    $pdo = db($profile);
    return $bind
        ? (($stmt = $pdo->prepare($sql))->execute($bind) ? $stmt : $stmt)
        : (($stmt = $pdo->query($sql)) ?: $stmt);
}

/**
 * Execute a transaction block safely
 *   dbt(fn() => {
 *       dbq("INSERT INTO logs (event) VALUES (?)", ['created']);
 *       dbq("INSERT INTO users (name) VALUES (?)", ['Alice']);
 *       return dbq("SELECT * FROM users WHERE name = ?", ['Alice'])->fetchAll();
 *   });
 */
function dbt(callable $fn, string $profile = ''): mixed
{
    $pdo = db($profile) ?: throw new LogicException("No PDO connection available for profile '$profile'");
    $pdo->beginTransaction();
    try {
        $out = $fn();
        $pdo->commit();
        return $out;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
