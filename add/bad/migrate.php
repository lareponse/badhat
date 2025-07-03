#!/usr/bin/env php
<?php

declare(strict_types=1);

// --- getopt & help
$opts = getopt('', ['help', 'path:', 'host::', 'name:', 'user:', 'step::']);
if (isset($opts['help'])) {
    fwrite(
        STDOUT,
        "Usage: php {$argv[0]} --path=DIR --name=DB --user=USER [--host=HOST] [--step=N]\n" .
            "  step>0: migrate | step<0: rollback | step=0: status\n"
    );
    exit;
}
foreach (['path', 'name', 'user'] as $o) {
    if (empty($opts[$o])) {
        fwrite(STDERR, "--{$o} is required\n");
        exit(1);
    }
}
$dir = rtrim($opts['path'], '/');
if (!is_dir($dir)) {
    fwrite(STDERR, "Directory not found: {$dir}\n");
    exit(1);
}

define('DIR',        $dir);
define('HISTORY',    DIR . '/.history.json');
define('JSON_FLAGS', JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

$host = $opts['host'] ?? '127.0.0.1';
$db   = $opts['name'];
$usr  = $opts['user'];

// --- prompt for password & connect
echo "Password: ";
exec('stty -echo');
$pwd = trim(fgets(STDIN));
exec('stty echo');
echo "\n";
try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$db};charset=utf8mb4",
        $usr,
        $pwd,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    fwrite(STDERR, "Connection failed: {$e->getMessage()}\n");
    exit(1);
}

// --- history helpers
function history(): array
{
    return is_file(HISTORY)
        ? (json_decode((string)file_get_contents(HISTORY), true) ?? [])
        : [];
}
function save_history(array $h): void
{
    file_put_contents(HISTORY, json_encode($h, JSON_FLAGS));
}

// --- collect migrations
$migs = [];
foreach (glob(DIR . '/*.up.sql') ?: [] as $up) {
    $name = basename($up, '.up.sql');
    $migs[$name] = [
        'up'   => $up,
        'down' => is_file($d = DIR . "/{$name}.down.sql") ? $d : null,
    ];
}

$hist = history();
$step = isset($opts['step']) && is_numeric($opts['step'])
    ? (int)$opts['step']
    : 0;

// --- status (step=0)
if ($step === 0) {
    printf("%-10s | %s\n", 'STATUS', 'MIGRATION');
    echo str_repeat('-', 10) . '-+-' . str_repeat('-', 30) . "\n";
    foreach ($migs as $name => $_) {
        $st = in_array($name, $hist, true) ? 'applied' : 'pending';
        printf("%-10s | %s\n", $st, $name);
    }
    exit;
}

// --- determine up/down & targets
$isUp    = $step > 0;
$absStep = abs($step);

if ($isUp) {
    $pending = array_diff(array_keys($migs), $hist);
    $targets = array_slice($pending, 0, $absStep);
} else {
    $targets = array_slice(array_reverse($hist), 0, $absStep);
}

// --- apply or rollback
foreach ($targets as $name) {
    echo $isUp
        ? "→ Applying {$name}… "
        : "← Rolling back {$name}… ";

    $file = $migs[$name][$isUp ? 'up' : 'down'];
    if (!$isUp && $file === null) {
        echo "✗ no down.sql\n";
        continue;
    }

    try {
        $pdo->beginTransaction();
        $pdo->exec(file_get_contents($file));

        $h = history();
        if ($isUp) {
            $h[] = $name;
        } else {
            if (($i = array_search($name, $h, true)) !== false) {
                array_splice($h, $i, 1);
            }
        }
        save_history($h);

        $pdo->commit();
        echo "✓\n";
    } catch (Throwable $e) {
        $pdo->rollBack();
        echo "✗ failed: {$e->getMessage()}\n";
        exit(1);
    }
}
