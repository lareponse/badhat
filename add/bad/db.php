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
function db(mixed $arg = null, string $profile = ''): ?PDO
{
    static $store = [];

    if ($arg instanceof PDO) {
        $store[$profile] = $arg;
        return $store[$profile];
    }

    if (is_string($arg)) {
        $profile = $arg;
    } 

    if (isset($store[$profile])) {
        $existing = $store[$profile];
        try {
            $existing->query('SELECT 1'); // ping
            return $existing;
        } catch (PDOException $e) {
            unset($store[$profile]);
        }
    }

    // 4) Read environment variables for this profile.
    $dsn   = getenv('DB_DSN_' . $profile)  ?: throw new DomainException(
        "No DSN defined. SetEnv DB_DSN_$profile"
    );

    $user = getenv('DB_USER_' . $profile) ?: null;
    $pass = getenv('DB_PASS_' . $profile) ?: null;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        $store[$profile] = $pdo;
        return $store[$profile];
    } catch (PDOException $e) {
        throw new RuntimeException(sprintf("PDO Failed For Profile '%s : %s'", 'DEFAULT', $e->getMessage()), 500);
    }

    return null;
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
