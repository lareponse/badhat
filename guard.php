<?php

const CSRF_KEY = '_csrf_token';

function csp_nonce(): string
{
    static $nonce = null;
    return $nonce ??= bin2hex(random_bytes(16));
}
