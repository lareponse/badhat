#!/usr/bin/env php
<?php

declare(strict_types=1);

// -----------------------------------------------------------------------------
// PARSE NAMED OPTIONS
// -----------------------------------------------------------------------------
$options = getopt('', [
    'help',        // --help
    'path:',       // --path=path      (required)
    'host::',      // --host=host      (optional; default=127.0.0.1)
    'name:',       // --name=name      (required)
    'user:',       // --user=name      (required)
    'mode::',      // --mode=...       (optional; migrate|rollback|status; default=status)
    'step::',      // --step=N         (optional; rollback steps; default=1)
]);

// Show help
if (isset($options['help'])) {
    $prog = basename($argv[0]);
    echo <<<USAGE
    Usage:
      php {$prog} --path=PATH --name=NAME --user=USER [--host=HOST]
                   [--mode=migrate|rollback|status] [--steps=N]

    --path       (required) path to your migrations directory
    --name        (required) database name
    --user      (required) database username
    --host      (optional) DB host (default: 127.0.0.1)
    --mode    (optional) migrate, rollback, or status (default: status)
    --step     (optional) number of migrations to rollback (default: 1)

    Example:
      php {$prog} --path=./migrations --name=mydb --user=myuser --mode=migrate
    USAGE;
    exit;
}

// Validate required options
foreach (['path', 'name', 'user'] as $opt) {
    if (empty($options[$opt])) {
        fwrite(STDERR, "Error: --{$opt} is required. Use --help for usage.\n");
        exit(1);
    }
}

$migrationsDir = rtrim($options['path'], '/');
if (!is_dir($migrationsDir)) {
    fwrite(STDERR, "Error: directory '{$migrationsDir}' does not exist.\n");
    exit(1);
}
define('MIGRATIONS_DIR', $migrationsDir);
define('HISTORY_FILENAME', '.history.json');

$host   = $options['host'] ?? '127.0.0.1';
$dbName = $options['name'];
$user   = $options['user'];

// Always prompt for password
echo "Password: ";
exec('stty -echo');
$password = trim(fgets(STDIN));
exec('stty echo');
echo "\n";

// -----------------------------------------------------------------------------
// CONNECT
// -----------------------------------------------------------------------------
$dsn = "mysql:host={$host};dbname={$dbName};charset=utf8mb4";
try {
    $pdo = new PDO(
        $dsn,
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    fwrite(STDERR, "Connection failed: {$e->getMessage()}\n");
    exit(1);
}

// -----------------------------------------------------------------------------
// FILE‐BASED HISTORY FUNCTIONS
// -----------------------------------------------------------------------------
function historyPath(): string
{
    return MIGRATIONS_DIR . '/' . HISTORY_FILENAME;
}

function migration_history(): array
{
    $path = historyPath();
    if (!is_file($path)) {
        return [];
    }
    $arr = json_decode(file_get_contents($path), true);
    return is_array($arr) ? $arr : [];
}

function migration_record(string $name): void
{
    $hist = migration_history();
    $hist[] = $name;
    file_put_contents(
        historyPath(),
        json_encode($hist, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
}

function migration_forget(string $name): void
{
    $hist = migration_history();
    if (false !== ($i = array_search($name, $hist, true))) {
        array_splice($hist, $i, 1);
        file_put_contents(
            historyPath(),
            json_encode($hist, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}

// -----------------------------------------------------------------------------
// DISCOVER MIGRATION FILES
// -----------------------------------------------------------------------------
function loadMigrations(string $dir): array
{
    $ups = glob("$dir/*.up.sql", GLOB_NOSORT) ?: [];
    sort($ups, SORT_STRING);
    $out = [];
    foreach ($ups as $upSql) {
        $name    = basename($upSql, '.up.sql');
        $downSql = "{$dir}/{$name}.down.sql";
        $out[$name] = [
            'up'   => $upSql,
            'down' => is_file($downSql) ? $downSql : null,
        ];
    }
    return $out;
}

// -----------------------------------------------------------------------------
// COMMAND IMPLEMENTATIONS
// -----------------------------------------------------------------------------
function migrate(PDO $pdo): void
{
    $all     = loadMigrations(MIGRATIONS_DIR);
    $applied = migration_history();
    $pending = array_diff(array_keys($all), $applied);
    if (empty($pending)) {
        echo "No pending migrations.\n";
        return;
    }
    foreach ($pending as $name) {
        echo "→ Applying {$name}… ";
        $sql = file_get_contents($all[$name]['up']);
        $pdo->beginTransaction();
        try {
            $pdo->exec($sql);
            migration_record($name);
            $pdo->commit();
            echo "✓\n";
        } catch (Throwable $e) {
            $pdo->rollBack();
            echo "✗ failed: {$e->getMessage()}\n";
            exit(1);
        }
    }
}

function rollback(PDO $pdo, int $step): void
{
    $all     = loadMigrations(MIGRATIONS_DIR);
    $history = array_reverse(migration_history());
    $toUndo  = array_slice($history, 0, $step);
    if (empty($toUndo)) {
        echo "Nothing to rollback.\n";
        return;
    }
    foreach ($toUndo as $name) {
        if (!isset($all[$name]) || $all[$name]['down'] === null) {
            echo "Cannot rollback {$name} (no .down.sql)\n";
            continue;
        }
        echo "← Rolling back {$name}… ";
        $sql = file_get_contents($all[$name]['down']);
        try {
            $pdo->exec($sql);
            migration_forget($name);
            echo "✓\n";
        } catch (Throwable $e) {
            echo "✗ failed: {$e->getMessage()}\n";
            exit(1);
        }
    }
}

function status(): void
{
    $all  = loadMigrations(MIGRATIONS_DIR);
    $done = migration_history();
    printf("%-10s | %s\n", 'STATUS', 'MIGRATION');
    echo str_repeat('-', 10) . "-+-" . str_repeat('-', 30) . "\n";
    foreach ($all as $name => $_) {
        $state = in_array($name, $done, true) ? 'applied' : 'pending';
        printf("%-10s | %s\n", $state, $name);
    }
}

// -----------------------------------------------------------------------------
// DISPATCH BASED ON --mode & --step
// -----------------------------------------------------------------------------
$action = $options['mode'] ?? 'status';
$step  = isset($options['step']) && is_numeric($options['step'])
    ? (int)$options['step']
    : 1;

switch ($action) {
    case 'migrate':
        migrate($pdo);
        break;
    case 'rollback':
        rollback($pdo, $step);
        break;
    case 'status':
    default:
        status();
        break;
}
