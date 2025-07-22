<?php

declare(strict_types=1);

const CLAUSE_SELECT   =    1;
const CLAUSE_WHERE    =    2;
const CLAUSE_GROUP_BY =   64;
const CLAUSE_ORDER_BY =   32;
const CLAUSE_SET      =  128;
const CLAUSE_VALUES   =  512;

const OP_AND      =    4;
const OP_OR       =    8;
const OP_IN       =   16;
const PH_LIST     = 1024;
const SINGLETON   = 2048; // NEW : used to indicate a single value, not reusable
const VALUES_LIST = CLAUSE_VALUES | PH_LIST;

function statement(...$args)
{
    $sql = [];
    $bindings = [];

    foreach ($args as $arg) {
        if (empty($arg)) continue;

        if (is_array($arg) && isset($arg[0])) {
            $sql []= $arg[0];
            isset($arg[1]) && is_array($arg[1]) && ($bindings = array_merge($bindings, $arg[1]));
        } else
            $sql []= $arg;
    }
    return [implode(' ',$sql), $bindings];
}

function clause(int $type, string $glue = ''): callable
{
    static $formats;

    if (!$formats) {
        $formats = [
            CLAUSE_SELECT               => ['SELECT ',   ', ',    ''],
            CLAUSE_WHERE                => ['WHERE ',    ' AND ', ''],
            CLAUSE_WHERE | OP_OR        => ['WHERE ',    ' OR ',  ''],
            OP_AND                      => ['(',         ' AND ', ')'],
            OP_OR                       => ['(',         ' OR ',  ')'],
            CLAUSE_VALUES               => ['VALUES (',  ',',     ')'],
            CLAUSE_SET                  => ['SET ',      ',',     ''],
            CLAUSE_ORDER_BY             => ['ORDER BY ', ',',     ''],
            CLAUSE_GROUP_BY             => ['GROUP BY ', ',',     ''],
        ];
    }
    
    $fmt        = $formats[$type] ?? ['', ' ', ''];
    $bindings   = [];
    $parts      = [];

    return function (...$args) use ($type, $glue, $fmt, &$bindings, &$parts) {
        [$pre, $delim, $suf] = $fmt;

        if ($type & (PH_LIST)) {
            vd($args);
            $params = [];
            foreach ($args as $i => $v) {
                $ph = ":{$glue}_in_{$i}";
                $params[] = $ph;
                $bindings[$ph] = $v;
            }
            $prefix = $type & OP_IN ? "IN" : "VALUES";
            $sql = "$prefix (" . implode(', ', $params) . ")";
            return [$sql, $bindings];
        }

        // browse the $or('raw sql', ['bind' => 'value'], 'raw sql', $and('raw sql', ['bind' => 'value'], 'raw sql'))
        foreach ($args as $arg) {
            if(is_string($arg)){
                $parts[] = $arg;
            } elseif (is_array($arg)){
                // 1. return of a closure
                if(count($args) === 2 && isset($arg['clausure_query']) && isset($arg['clausure_bindings'])) { // result of another closure
                    $parts += $arg['clausure_query'];
                    $bindings = array_merge($bindings, $arg['clausure_bindings']);
                }
                else{
                    foreach ($args[0] as $k => $v) {
                        if ($type & CLAUSE_SELECT) {
                            $frag = is_int($k) ? "$v" : "$v AS `$k`";
                        } elseif ($type & (OP_AND | OP_OR | CLAUSE_WHERE)) {
                            if (is_int($k)) {
                                $frag = $v;
                            } else {
                                $frag = "`$k` $glue :$k";
                                $bindings[$k] = $v;
                            }
                        } elseif ($type & CLAUSE_ORDER_BY) {
                            $frag = "`$k` " . (strtoupper($v) === 'DESC' ? 'DESC' : 'ASC');
                        } elseif ($type & CLAUSE_GROUP_BY) {
                            $frag = "`$k`";
                        } elseif ($type & (PH_LIST | CLAUSE_VALUES)) {
                            $frag = ":$k";
                            $bindings[$k] = $v;
                        } elseif ($type & CLAUSE_SET) {
                            $frag = "`$k` = :$k";
                            $bindings[$k] = $v;
                        } else {
                            $frag = "$k$glue$v";
                        }
                        $parts[] = $frag;
                    }
                }
            }
        } 
        /*
        else {
            // vd($args);
            foreach ($args as $arg) {
                if (is_array($arg) && isset($arg[0])) {
                    $parts[] = $arg[0];
                    isset($arg[1]) && is_array($arg[1]) && ($bindings = array_merge($bindings, $arg[1]));
                } elseif (is_string($arg)) {
                    $parts[] = $arg;
                }
            }
        }
        */

        return [$pre . implode($delim, $parts) . $suf, $bindings];
    };
}
