<?php
const TYPE_FOREIGN_KEY = 1;
const TYPE_UNIQUE = 2;
const TYPE_CHECK = 4;
const TYPE_NOT_NULL = 8;
const TYPE_EXCLUSION = 16;
const TYPE_UNKNOWN = 32;


function parse_constraint_error(string $message, PDO $pdo): array
{
    return !function_exists($parser = 'parse_error_' . db_server($pdo))
        ? []
        : call_user_func($parser, $message) + ['raw_message' => $message];
}

function parse_error_mysql(string $message): array
{
    $result = [];

    if (preg_match('/^ERROR\s+(\d+)\s+\(([A-Z0-9]+)\):/', $message, $matches)) {
        $result['error_code'] = (int)$matches[1];
        $result['sqlstate'] = $matches[2];
    }
    if (preg_match("/Duplicate entry '(.+?)' for key '(.+?)'/", $message, $matches)) {
        $result['constraint_type'] = TYPE_UNIQUE;
        $result['constraint_name'] = $matches[2];
        $result['duplicate_value'] = $matches[1];
    } elseif (preg_match('/.+foreign key constraint fails \(`(.+?)`.`(.+?)`, CONSTRAINT `(.+?)`/', $message, $matches)) {
        $result['constraint_type'] = TYPE_FOREIGN_KEY;
        $result['constraint_name'] = $matches[3];
    } elseif (preg_match("/Check constraint '(.+?)' is violated/", $message, $matches)) {
        $result['constraint_type'] = TYPE_CHECK;
        $result['constraint_name'] = $matches[1];
    } elseif (preg_match("/Column '(.+?)' cannot be null/", $message, $matches)) {
        $result['constraint_type'] = TYPE_NOT_NULL;
        $result['column_name'] = $matches[1];
    }
    return $result;
}

function parse_error_mariadb(string $message): array
{
    $result = [];
    if (preg_match('/CONSTRAINT `(.+?)` failed for `(.+?)`.`(.+?)`/', $message, $matches)) {
        $result['constraint_type'] = TYPE_CHECK;
        $result['constraint_name'] = $matches[1];
    } else {
        $result = parse_error_mysql($message);
    }

    return $result;
}

function parse_error_pgsql(string $message): array
{
    $result = [];

    if (preg_match('/violates unique constraint "(.+?)"/', $message, $matches)) {
        $result['constraint_name'] = $matches[1];
        $result['constraint_type'] = TYPE_UNIQUE;
        if (preg_match('/Key \((.+?)\)=\((.+?)\)/', $message, $keyMatches))
            $result['duplicate_value'] = $keyMatches[2];
    } elseif (preg_match('/violates foreign key constraint "(.+?)"/', $message, $matches)) {
        $result['constraint_type'] = TYPE_FOREIGN_KEY;
        $result['constraint_name'] = $matches[1];
    } elseif (preg_match('/null value in column "(.+?)" violates not-null constraint/', $message, $matches)) {
        $result['constraint_type'] = TYPE_NOT_NULL;
        $result['column_name'] = $matches[1];
    } elseif (preg_match('/violates check constraint "(.+?)"/', $message, $matches)) {
        $result['constraint_type'] = TYPE_CHECK;
        $result['constraint_name'] = $matches[1];
    } elseif (preg_match('/violates exclusion constraint "(.+?)"/', $message, $matches)) {
        $result['constraint_type'] = TYPE_EXCLUSION;
        $result['constraint_name'] = $matches[1];
    }

    return $result;
}

function db_server(PDO $pdo): string
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'mysql' && stripos($pdo->getAttribute(PDO::ATTR_SERVER_VERSION), 'mariadb') !== false)
        return 'mariadb';

    return $driver;
}
