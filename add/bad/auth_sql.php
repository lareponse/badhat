<?php

// Ensure required db helpers are available
if (!function_exists('dbq') || !function_exists('db_transaction')) {
    @include __DIR__ . '/../db.php';

    if (!function_exists('dbq') || !function_exists('db_transaction')) {
        throw new Exception('Cannot use auth_sql without dbq() and db_transaction()');
    }
}

// -- USERS --

function auth_user_fetch(string $username): ?array
{
    return dbq("SELECT * FROM users WHERE username = ?", [$username])->fetch(PDO::FETCH_ASSOC) ?: null;
}

function auth_user_store(array $user): bool
{
    return db_transaction(function () use ($user) {
        $stmt = dbq("UPDATE users SET password = ? WHERE username = ?", [
            $user['password'],
            $user['username']
        ]);

        if ($stmt->rowCount() === 0) {
            dbq("INSERT INTO users (username, password) VALUES (?, ?)", [
                $user['username'],
                $user['password']
            ]);
        }

        return true;
    });
}

function auth_user_active(): ?array
{
    $token_id = $_COOKIE['auth'] ?? '';
    if (!$token_id) return null;

    $token = auth_token_fetch($token_id);
    if (!$token || $token['expires_at'] < time()) return null;

    return dbq("SELECT * FROM users WHERE id = ?", [$token['user_id']])->fetch(PDO::FETCH_ASSOC) ?: null;
}

// -- TOKENS --

function auth_token_fetch(string $token_id): ?array
{
    return dbq(
        "SELECT * FROM tokens WHERE token = ? AND expires_at > ?",
        [$token_id, time()]
    )->fetch(PDO::FETCH_ASSOC) ?: null;
}

function auth_token_store(array $token): bool
{
    return db_transaction(function () use ($token) {
        $stmt = dbq("UPDATE tokens SET user_id = ?, expires_at = ? WHERE token = ?", [
            $token['user_id'],
            $token['expires_at'],
            $token['token']
        ]);

        if ($stmt->rowCount() === 0) {
            dbq("INSERT INTO tokens (token, user_id, expires_at) VALUES (?, ?, ?)", [
                $token['token'],
                $token['user_id'],
                $token['expires_at']
            ]);
        }

        return true;
    });
}

function auth_token_revoke(string $token_id): bool
{
    return dbq("DELETE FROM tokens WHERE token = ?", [$token_id])->rowCount() > 0;
}
