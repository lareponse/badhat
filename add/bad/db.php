<?php

declare(strict_types=1);

function db(?string $dsn = null, ?string $u = null, ?string $p = null, ?array $o = null): PDO
{
    static $pdo;
    return $pdo ??= new PDO($dsn ?: throw new LogicException("No DSN"), $u, $p, ($o ?: []) + [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

function dbq(string $q, array $b = []): PDOStatement
{
    $s = db()->prepare($q);
    $s->execute($b);
    return $s;
}

function db_transaction(callable $f)
{
    $db = db();
    $db->beginTransaction();
    try {
        $r = $f();
        $db->commit();
        return $r;
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

