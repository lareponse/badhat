<?php

namespace bad\http\header;

const STATUS_BITS = 10;
const STATUS_MASK = (1 << STATUS_BITS) - 1;

const ADD   = 1   << STATUS_BITS;
const CSV   = 2   << STATUS_BITS;
const LOCK  = 4   << STATUS_BITS;
const DROP  = 8   << STATUS_BITS;       // ignores supplied status code
const RESET = 16  << STATUS_BITS;
const EMIT  = 64  << STATUS_BITS;
const CLEAR = 128 << STATUS_BITS;

const META_MASK = CSV | LOCK;


function headers_register_callback(int $behave = 0): bool
{
    return \header_register_callback(fn() => headers(EMIT | ($behave & ~STATUS_MASK)));
}

function headers(int $status_behave = 0, string $field = '', string $value = '')
{
    static $stage = [];
    static $last_status = 0;

    $status = $status_behave & STATUS_MASK;
    $behave = $status_behave & ~STATUS_MASK;

    if (RESET & $behave) {
        ($emit_status = $last_status)   && ($last_status = 0);      // if last_status was set, unset it
        ($emit_stage = $stage)          && ($stage = []);           // if stage is empty, dont create a new empty array

        return !(EMIT & $behave) || _emit($status ?: $emit_status, $emit_stage, (bool)(CLEAR & $behave));
    }

    if (DROP & $behave) {
        $key = \strtolower($field);
        if(!isset($stage[$key]) || (LOCK & ($stage[$key][2] ?? 0)))
            return false;
        \headers_sent() || \header_remove($stage[$key][0]);
        unset($stage[$key]);
        return true;
    }

    if ($field !== '' && __set($stage, $field, $value, $behave) && $status)
        $last_status = $status;                                                   // only set the status if adding header succeed

    ($field === '' && $status) && ($last_status = $status);                                    // or if the status was alone
    
    if (EMIT & $behave)
        return _emit($status ?: $last_status, $stage, (bool)(CLEAR & $behave));

    return $stage;
}

function __set(&$stage, $field, $value, $behave) 
{
        $key = \strtolower($field);
        if(LOCK & ($stage[$key][2] ?? 0))
            return false;
        
        $stage_value = $stage[$key][1] ?? null;

        if((ADD | CSV) & $behave && ($stage[$key] ?? false) && !\is_array($stage_value))
            return false;
        
        if ((ADD | CSV) & $behave || \is_array($stage_value)) {
            $stage[$key] ??= [0 => $field, 1 => [], 2 => ($behave & META_MASK)];
            $stage[$key][1] []= $value;
        }
        else
            $stage[$key] = [0 => $field, 1 => $value, 2 => ($behave & META_MASK)];

        if (LOCK & $behave)                                              // when all is done, set the lock
            $stage[$key][2] |= LOCK;
        
        return true;
}

function _emit(int $status, array $stage, bool $clear_first = false): bool
{
    if (\headers_sent()) return false;

    foreach ($stage as $ent){
        if($clear_first)                        \header_remove($ent[0]);

        if      (!\is_array($ent[1]))           \header($ent[0] . ': ' . $ent[1], true);
        elseif  (CSV & ($ent[2] ?? 0))          \header($ent[0] . ': ' . \implode(', ', $ent[1]), true);
        else    foreach ($ent[1] as $v)         \header($ent[0] . ': ' . $v, false);
    }
        
    if($status)                                 \http_response_code($status);

    return true;
}
