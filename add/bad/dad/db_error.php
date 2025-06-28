<?php
const DB_ERR_USER         = 1;
const DB_ERR_FOREIGN_KEY  = 32;
const DB_ERR_UNIQUE       = 2;
const DB_ERR_CHECK        = 4;
const DB_ERR_NOT_NULL     = 8;
const DB_ERR_EXCLUSION    = 16;

function mysql_parse_error(string $message): array
{
    $result = [];

    // map each constraint‐type constant to a single‐capture regex
    $rules = [
        DB_ERR_UNIQUE      => "/Duplicate entry '.*?' for key '(.+?)'/",
        DB_ERR_FOREIGN_KEY => "/foreign key constraint fails .*CONSTRAINT `(.+?)`/",
        DB_ERR_CHECK       => "/Check constraint '(.+?)' is violated/",
        DB_ERR_NOT_NULL    => "/Column '(.+?)' cannot be null/",
    ];

    foreach ($rules as $type => $pattern) {
        if (preg_match($pattern, $message, $matches)) {
            // just one capture per type, and no duplicate‐value
            $result[DB_ERR_USER] = $type === DB_ERR_NOT_NULL
                ? $matches[1] . '-not-null'
                : $matches[1];
            break;
        }
    }

    return $result;
}

function parse_error_mariadb(string $message): array
{
    $result = [];
    if (preg_match('/CONSTRAINT `(.+?)` failed for `(.+?)`.`(.+?)`/', $message, $matches)) {
        $result['constraint_type'] = DB_ERR_CHECK;
        $result['constraint_name'] = $matches[1];
    } else {
        $result = mysql_parse_error($message);
    }

    return $result;
}

function parse_error_pgsql(string $message): array
{
    $result = [];

    // map each constraint‐type constant to its regex
    $rules = [
        DB_ERR_UNIQUE       => '/violates unique constraint "(.+?)"/',
        DB_ERR_FOREIGN_KEY  => '/violates foreign key constraint "(.+?)"/',
        DB_ERR_NOT_NULL     => '/null value in column "(.+?)" violates not-null constraint/',
        DB_ERR_CHECK        => '/violates check constraint "(.+?)"/',
        DB_ERR_EXCLUSION    => '/violates exclusion constraint "(.+?)"/',
    ];

    foreach ($rules as $type => $pattern) {
        if (preg_match($pattern, $message, $matches)) {
            $result[$type] = $type === DB_ERR_NOT_NULL
                ? $matches[1] . '-not-null'
                : $matches[1];
            break; // stop after first match
        }
    }

    return $result;
}

function db_server(PDO $pdo): string
{
    return ($drv = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME)) === 'mysql'
        && stripos($pdo->getAttribute(PDO::ATTR_SERVER_VERSION), 'mariadb') !== false
        ? 'mariadb'
        : $drv;
}
