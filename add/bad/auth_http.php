<?php
// auth_http.php
declare(strict_types=1);

// HTTP-HMAC AUTH
function auth_http(): ?string
{
    $user = $_SERVER['HTTP_X_AUTH_USER'] ?? '';
    $sig  = $_SERVER['HTTP_X_AUTH_SIG']  ?? '';
    if ($user === '' || $sig === '') {
        return null;
    }

    $secret = getenv('BADHAT_AUTH_HMAC_SECRET')
        ?: throw new DomainException('HMAC secret missing', 500);

    $expected = hash_hmac('sha256', $user, $secret);
    return hash_equals($expected, $sig) ? $user : null;
}
