<?php

namespace bad\auth;

const DUMMY_HASH = '$2y$12$8NidQXAmttzUc23lTnUDAuC.JoxuJtdG0NQTjhh3Y7C442uVQ4FTy';

function checkin(?string $username = null, ?string $password = null, ?\PDOStatement $_update = null, ?\PDOStatement $_select = null): string
{
    static ?\PDOStatement $stm_update = null;
    static ?\PDOStatement $stm_select = null;

    if (isset($_update, $_select)){
        !isset($stm_update, $stm_select)                            || throw new \BadFunctionCallException('checkin:already initialized');
        $stm_update = $_update;
        $stm_select = $_select;
    }

    isset($stm_update, $stm_select)                                 || throw new \BadFunctionCallException('checkin:not initialized');
    (\session_status() === \PHP_SESSION_ACTIVE)                     || throw new \BadFunctionCallException('checkin:session not active');

    if (isset($username, $password)){
        $username !== '' && $password !== ''                        || throw new \BadFunctionCallException('checkin:empty credentials');
        $stm_select->execute([$username])                           || throw new \RuntimeException('checkin:select failed');
        $db_p = $stm_select->fetchColumn() ?: DUMMY_HASH;
        $stm_select->closeCursor();

        if (\password_verify($password, $db_p) && DUMMY_HASH !== $db_p){
            \session_regenerate_id(true)                            || throw new \RuntimeException('checkin:session_regenerate_id failed');
            $stm_update->execute([$username])                       || throw new \RuntimeException('checkin:update failed');
            $stm_update->closeCursor();
            $_SESSION[__NAMESPACE__][__FUNCTION__] = $username;
        }
    }

    return $_SESSION[__NAMESPACE__][__FUNCTION__] ?? '';
}

function checkout()
{
    (\session_status() === \PHP_SESSION_ACTIVE)                     || throw new \BadFunctionCallException('checkout:session not active');
    $_SESSION = [];

    if (\ini_get('session.use_cookies')) {
        $p = \session_get_cookie_params();
        \setcookie(\session_name(), '', [
            'expires'  => \time()-211121,
            'path'     => $p['path'] ?? '/',
            'domain'   => $p['domain'] ?? '',
            'secure'   => $p['secure'] ?? false,
            'httponly' => $p['httponly'] ?? true,
            'samesite' => $p['samesite'] ?? (\ini_get('session.cookie_samesite') ?: 'Lax'),
        ])                                                          || \trigger_error('checkout:cookie destroy failed', \E_USER_WARNING);
    }
    \session_destroy()                                              || throw new \RuntimeException('checkout:session_destroy failed');
}