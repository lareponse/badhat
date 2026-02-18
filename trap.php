<?php

namespace bad\trap;                                                // provides configurable, installable PHP error/exception/shutdown handlers with request-context logging, optional traces, and restoration

const HND_ERR  = 1;                                                 // handle runtime PHP errors
const HND_EXC  = 2;                                                 // handle uncaught exceptions
const HND_SHUT = 4;                                                 // handle fatal shutdown errors
const HND_ALL  = HND_ERR | HND_EXC | HND_SHUT;

const MSG_WITH_TRACE = 8;                                           // attach execution trace to reports
const ALLOW_INTERNAL = 16;                                          // let PHP internal handler continue
const FATAL_OB_FLUSH = 32;                                          // flush all output buffers on fatal
const FATAL_OB_CLEAN = 64;                                          // discard all output buffers on fatal

const CTRL_CHARS = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F" . "\x7F";  // ASCII control chars (0x00..0x1F + 0x7F)

$start_time = \hrtime(true);  // start tick (ns-ish): hrtime(true) when nonzero, else microtime()*1e9 (float; not strictly monotonic)

$d_framer = static function($frames): string {
    $out = '';
    foreach ($frames as $i => $f)
        $out .= ($out ? \PHP_EOL : '') . \sprintf('#%d %s:%s %s%s%s()', $i, $f['file'] ?? '-', $f['line'] ?? '?', $f['class'] ?? '', $f['type'] ?? '', $f['function'] ?? '?');
    return $out;
};

$d_logger = static function ($request_id, $grepable, $message, $type = null, $file = null, $line = null, $context = null): string {
    $msg = "[req=$request_id] $grepable $message ($type";
    $file !== null && $msg .= "::$file";
    $line !== null && $msg .= ":$line";
    $msg .= ')';
    $context && $msg .= " [$context]";
    return $msg;
};

$ctrl_space = \str_repeat(' ', \strlen(CTRL_CHARS));
$d_scrubber = static fn(string $s): string => \trim(\strtr($s, CTRL_CHARS, $ctrl_space));

return function (int $behave = HND_ALL, ?string $request_id = null, ?callable $logger = null, ?callable $framer = null, ?callable $scrubber = null)
            use ($d_framer, $d_scrubber, $d_logger, $start_time): callable {

    $logger   ??= $d_logger;
    $framer   ??= $d_framer;
    $scrubber ??= $d_scrubber;

    $request_id ??= \dechex($start_time) . '-' . (int)\getmypid();

    $fatal = static function ($src, $type, $msg, $file = '', $line = 0, $traces = '')
                         use ($behave, $start_time, $request_id, $logger, $scrubber): void {

        $time = (int)((\hrtime(true) - $start_time) / 1e6);

        $msg = $scrubber((string)$msg);
        $ctx = $scrubber(peak($time));

        \error_log($logger($request_id, 'FATAL', $msg, $type, $file, $line, $ctx));
        $traces && \error_log($logger($request_id, 'TRACE', (string)$traces, $type, $file, $line));
        
        if ((FATAL_OB_FLUSH | FATAL_OB_CLEAN) & $behave)
            for ($max = \ob_get_level(), $i = 0; $i < $max && \ob_get_level(); ++$i)
                (FATAL_OB_CLEAN & $behave) ? @\ob_end_clean() : @\ob_end_flush();
    };

    (HND_ERR & $behave) && $prev_err = \set_error_handler(
        static function ($code, $msg, $file, $line)
                    use ($behave, $request_id, $scrubber, $logger, $framer): bool {

            if (!(\error_reporting() & $code)) return false;

            $msg = $scrubber((string)$msg);

            \error_log($logger($request_id, 'TRIGGER', $msg, $code, $file, $line));
            if (MSG_WITH_TRACE & $behave)
                \error_log($logger($request_id, 'TRACE', $framer(\debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS)), $code, $file, $line));

            return !(ALLOW_INTERNAL & $behave);
        }
    );

    (HND_EXC & $behave) && $prev_exc = \set_exception_handler(
        static function (\Throwable $e) 
                    use ($behave, $fatal, $scrubber, $framer): void {

            $msg = $e->getMessage();
            for ($c = $e->getPrevious(); $c; $c = $c->getPrevious())
                $msg .= ' <- ' . $c::class . ':' . $c->getMessage();
            $traces = (MSG_WITH_TRACE & $behave) ? $framer($e->getTrace()) : '';
            $fatal('THROW', $e::class, $scrubber($msg), $e->getFile(), $e->getLine(), $traces);
        }
    );

    (HND_SHUT & $behave) && \register_shutdown_function(
        static function () 
                    use ($fatal): void {
            $err = \error_get_last();
            if (!$err) return;

            $type = (int)($err['type'] ?? 0);
            if (($type & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR)))            // policy: only act on fatal shutdown types
                $fatal('SHUTDOWN', $type, (string)($err['message'] ?? ''), (string)($err['file'] ?? ''), (int)($err['line'] ?? 0), '');
        }
    );

    return static function () use ($prev_err, $prev_exc): void {
        ($prev_err !== false) && ($prev_err !== null ? \set_error_handler($prev_err) : \restore_error_handler());
        ($prev_exc !== false) && ($prev_exc !== null ? \set_exception_handler($prev_exc) : \restore_exception_handler());
    };
};

function peak($time=null, $file=null)
{
    $time ??= -1;
    $memo = \memory_get_peak_usage(true) >> 10;
    $incl = \count(\get_included_files());
    $pid  = (int)\getmypid();
    $ob   = (int)\ob_get_level();
    $at   = $file ? " in $file:$line" : '';

    $hs_file = $hs_line = null;
    $hs = \headers_sent($hs_file, $hs_line);
    $hs_at = $hs ? (\basename($hs_file) . ':' . (int)$hs_line) : '-';

    return "{$time}ms {$memo}KiB sapi=" . \PHP_SAPI . " inc={$incl} pid={$pid} ob={$ob} headers={$hs_at}";
}