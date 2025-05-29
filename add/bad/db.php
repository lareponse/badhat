<?php

declare(strict_types=1);

/**
 * db() — Get or inject a PDO instance by profile ('' = unnamed)
 *
 * Usage:
 *   db()                         → get unnamed connection
 *   db('read')                   → get named profile
 *   db($pdo)                     → inject unnamed connection
 *   db($pdo, 'test')             → inject named profile
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

    $dsn  = getenv("DB_DSN_$profile")  ?: getenv("DB_DSN")  ?: throw new LogicException("Missing DB_DSN (or DB_DSN_$profile)");
    $user = getenv("DB_USER_$profile") ?: getenv("DB_USER") ?: null;
    $pass = getenv("DB_PASS_$profile") ?: getenv("DB_PASS") ?: null;

    return $map[$profile] = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
}


/**
 * dbq() — Execute SQL query with optional bindings
 *
 * Usage:
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
 * dbt() — Execute a transaction block safely
 *
 * Usage:
 *   dbt(fn() => {
 *       dbq("INSERT INTO logs (event) VALUES (?)", ['created']);
 *       dbq("INSERT INTO users (name) VALUES (?)", ['Alice']);
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


/**
 *   pdo()                         db()
 *   pdo("$sql", [bind])           dbq("$sql", [bind])
 *   pdo(callable)                 dbt(callable)
 */
function pdo(...$args): mixed
{
    return !$args ? db() : (is_callable($args[0]) ? dbt($args[0]) : dbq($args[0], $args[1] ?? [])); 
}
