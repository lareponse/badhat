<?php

namespace bad\trap;                                                // provides configurable, installable PHP error/exception/shutdown handlers with request-context logging, optional traces, and restoration

const HND_ERR  = 1;                                                 // handle runtime PHP errors
const HND_EXC  = 2;                                                 // handle uncaught exceptions
const HND_SHUT = 4;                                                 // handle fatal shutdown errors
const HND_ALL  = HND_ERR | HND_EXC | HND_SHUT;

const LOG_WITH_TRACE = 8;                                           // attach execution trace to reports
const ALLOW_INTERNAL = 16;                                          // let PHP internal handler continue
const FATAL_OB_FLUSH = 32;                                          // flush all output buffers on fatal
const FATAL_OB_CLEAN = 64;                                          // discard all output buffers on fatal

const CTRL_CHARS = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F" . "\x7F";  // ASCII control chars (0x00..0x1F + 0x7F)

return function (int $behave = HND_ALL, ?string $request_id = null): callable {

    $request_id ??= (int)\getmypid() . '-' . \dechex(\hrtime(true) ?: (int)(\microtime(true) * 1e9));

    $prev_err = false;
    $prev_exc = false;

    (HND_ERR & $behave) && $prev_err = \set_error_handler(
        static function ($code, $message, $file, $line) use ($behave, $request_id): bool {
            if ((\error_reporting() & $code))
                logladdy($behave & ~ (HND_EXC | HND_SHUT), $request_id, $code, $message, $file, $line);
            return !(ALLOW_INTERNAL & $behave);
        }
    );

    (HND_EXC & $behave) && $prev_exc = \set_exception_handler(
        static function (\Throwable $e) use ($behave, $request_id): void {
            $message = $e::class . ':'.$e->getMessage();
            for ($c = $e->getPrevious(); $c; $c = $c->getPrevious())
                $message .= ' <- ' . $c::class . ':' . $c->getMessage();
            
            $frames = (LOG_WITH_TRACE & $behave) ? $e->getTrace() : [];
            logladdy($behave & ~ (HND_ERR | HND_SHUT), $request_id, $e->getCode(), $message, $e->getFile(), $e->getLine(), $frames);
        }
    );

    (HND_SHUT & $behave) && \register_shutdown_function(
        static function () use ($behave, $request_id): void {
            $context = \error_get_last();
            if (!$context) return;

            $code = (int)($context['type'] ?? 0);
            if (!($code & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR))) return;

            logladdy($behave & ~ (HND_ERR | HND_EXC), $request_id, $context['type'] ?? 0, $context['message'] ?? '-', $context['file'] ?? '-', $context['line'] ?? 0);
        }
    );

    return static function ($behave = HND_ALL) use ($prev_err, $prev_exc): void {
        (HND_ERR & $behave) && ($prev_err !== false) && ($prev_err !== null ? \set_error_handler($prev_err) : \restore_error_handler());
        (HND_EXC & $behave) && ($prev_exc !== false) && ($prev_exc !== null ? \set_exception_handler($prev_exc) : \restore_exception_handler());
    };
};

function peek()
{
    $time = -1;
    if(isset($_SERVER['REQUEST_TIME_FLOAT'])){
        $start = (float)($_SERVER['REQUEST_TIME_FLOAT'] ?? 0.0);
        $time  = $start > 0 ? (int)((\microtime(true) - $start) * 1000) : -1;
    }
    $memo  = \memory_get_peak_usage(true) >> 10;
    $incl  = \count(\get_included_files());
    $pid   = (int)\getmypid();
    $ob    = (int)\ob_get_level();

    $hs_file = $hs_line = null;
    $hs = \headers_sent($hs_file, $hs_line);
    $hs_at = $hs ? (\basename($hs_file) . ':' . (int)$hs_line) : '-';

    return "{$time}ms {$memo}KiB sapi=" . \PHP_SAPI . " inc={$incl} pid={$pid} ob={$ob} headers={$hs_at}";
}

function logladdy($behave, $request_id, $code, $message, $file = null, $line = null, $frames = null)
{
    static $space = null;
    static $scrub = null;
    
    $space ??= \str_repeat(' ', \strlen(CTRL_CHARS));
    $scrub ??= static fn(string $s): string => \trim(\strtr($s, CTRL_CHARS, $space));
    
    $code = (int)$code;
    $info = $scrub((string)$message);
    $file = $file ? $scrub((string)$file) : '-';
    $line = (int)($line ?? 0);

    if (\strlen($info) > 4096) $info = \substr($info, 0, 4096) . '@TRUNCATED@';
    
    $format = '%s %s #%d (%s:%d) [%s %s]';
    $prefix = "[req=$request_id]";
    $handle = HND_ERR & $behave ? 'HND_ERR' : (HND_EXC & $behave ? 'HND_EXC' : 'HND_SHUT');

    \error_log(\sprintf($format, $prefix, $handle, $code, $file, $line, '-', $info));

    if ((HND_SHUT | HND_EXC) & $behave)
        \error_log(\sprintf($format, $prefix, 'PEEK', $code, $file, $line, '-', peek()));

    if ($frames === null)
        $frames = (LOG_WITH_TRACE & $behave) ? \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS) : [];

    foreach ($frames as $i => $f){
        $source = ($f['class'] ?? '') . ($f['type'] ?? '') . $scrub($f['function'] ?? '?') . '()';
        \error_log(\sprintf($format, $prefix, 'FRAME', $i, $scrub($f['file'] ?? '?'), (int)($f['line'] ?? 0), $source, ''));
    }

    if (((HND_SHUT | HND_EXC) & $behave) && ((FATAL_OB_FLUSH | FATAL_OB_CLEAN) & $behave))
        for ($level = \ob_get_level(), $i = 0; $i < $level && \ob_get_level(); ++$i)
            (FATAL_OB_CLEAN & $behave) ? @\ob_end_clean() : @\ob_end_flush();
}