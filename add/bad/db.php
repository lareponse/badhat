<?php

function pdo(...$args)
{
    static $pdo;
    
    if (!$pdo && $defaults = [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]) {                                        // not connected yet
    
        [$dsn, $user, $pass, $options] = $args;         // expected to provide PDO constructor arguments 
        $pdo = new PDO($dsn ?: throw new LogicException('Empty DSN'), $user, $pass, is_array($options) ? ($options + $defaults) : $defaults);
    
    } else if (is_string($args[0])) { // just querying, signature pdo('query', [bindings] ?? [], ?PDO): PDOStatement

        [$sql, $bindings, $connection] = $args + ['', [], $pdo];
        return ($s = $connection->prepare($sql))->execute($bindings) ? $s : $s;

    } else { // a transaction, signature pdo(callable, ?PDO)
        
        [$callable, $connection] = $args + [null, $pdo];
        $connection->beginTransaction();
        try {
            $r = $callable();
            $connection->commit();
            return $r;  
        } catch (Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
        
    }

    return $pdo; // going native, return PDO instance, signature pdo(): PDO
}