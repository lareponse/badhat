<?php

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$dsn = $_SERVER['DB_DSN_' ] ?: (getenv('DB_DSN_' ) ?: throw new DomainException("db-empty-dsn"));
$usr = $_SERVER['DB_USER_'] ?: (getenv('DB_USER_') ?: null);
$pwd = $_SERVER['DB_PASS_'] ?: (getenv('DB_PASS_') ?: null);

return new \PDO($dsn, $usr, $pwd, $options);
