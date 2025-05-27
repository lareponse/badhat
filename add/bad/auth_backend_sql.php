<?php

// Ensure required db helpers are available
if (!function_exists('pdo')) {
    @include __DIR__ . '/../db.php';

    if (!function_exists('pdo')) {
        throw new Exception('Cannot use auth_sql without pdo()');
    }
}

// -- USERS --

function auth_user_fetch(string $username): ?array
{
    return pdo("SELECT * FROM users WHERE username = ?", [$username])->fetch(PDO::FETCH_ASSOC) ?: null;
}

function auth_user_store(array $user): bool
{

    return pdo(function () use ($user) {
        $stmt = pdo("UPDATE users SET password = ? WHERE username = ?", [
            $user['password'],
            $user['username']
        ]);

        if ($stmt->rowCount() === 0) {
            pdo("INSERT INTO users (username, password) VALUES (?, ?)", [
                $user['username'],
                $user['password']
            ]);
        }

        return true;
    });
}

function auth_user_active(): ?string
{
    $token['user_id'] = 1;
    // $token_id = $_COOKIE['auth'] ?? '';
    // if (!$token_id) return null;

    // $token = auth_token_fetch($token_id);
    // if (!$token || $token['expires_at'] < time()) return null;

    return pdo("SELECT username FROM users WHERE id = ?", [$token['user_id']])->fetch(PDO::FETCH_COLUMN) ?: null;
}

// -- TOKENS --
function auth_token_fetch(string $token_id): ?array
{
    return pdo(
        "SELECT * FROM tokens WHERE token = ? AND expires_at > ?",
        [$token_id, time()]
    )->fetch(PDO::FETCH_ASSOC) ?: null;
}

function auth_token_store(array $token): bool
{
    setcookie(
        'auth',
        $token['token'],
        [
            'expires'  => $token['expires_at'],
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            // 'secure' => true, // if you’re on HTTPS
        ]
    );
    return pdo(function () use ($token) {
        $stmt = pdo("UPDATE tokens SET user_id = ?, expires_at = ? WHERE token = ?", [
            $token['user_id'],
            $token['expires_at'],
            $token['token']
        ]);

        if ($stmt->rowCount() === 0) {
            pdo("INSERT INTO tokens (token, user_id, expires_at) VALUES (?, ?, ?)", [
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
    return pdo("DELETE FROM tokens WHERE token = ?", [$token_id])->rowCount() > 0;
}
