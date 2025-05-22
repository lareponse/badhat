<?php

function auth_user_fetch(string $username): ?array
{
    return ['username' => $username];
}

function auth_user_store(array $user): bool
{
    return false; // not supported
}

function auth_token_store(array $token): bool
{
    return false; // not supported
}

function auth_token_fetch(string $token_id): ?array
{
    return null; // not used
}

function auth_token_revoke(string $token_id): bool
{
    return false;
}

function auth_user_active(): ?array
{
    $name = $_SERVER['HTTP_X_AUTH_USER']
        ?? $_SERVER['PHP_AUTH_USER']
        ?? $_SERVER['REMOTE_USER']
        ?? null;

    return $name ? ['username' => $name] : null;
}
