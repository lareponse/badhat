<?php

namespace bad\error;                                                // provides configurable, installable PHP error/exception/shutdown handlers with request-context logging, optional traces, and restoration

const HND_ERR  = 1;                                                 // handle runtime PHP errors
const HND_EXC  = 2;                                                 // handle uncaught exceptions
const HND_SHUT = 4;                                                 // handle fatal shutdown errors
const HND_ALL  = HND_ERR | HND_EXC | HND_SHUT;

const MSG_WITH_TRACE = 8;                                           // attach execution trace to reports
const ALLOW_INTERNAL = 16;                                          // let PHP internal handler continue
const FATAL_OB_FLUSH = 32;                                          // flush all output buffers on fatal
const FATAL_OB_CLEAN = 64;                                          // discard all output buffers on fatal
const FATAL_HTTP_500 = 128;                                         // force HTTP 500 on fatal conditions
const MONOTONIC_TIME = 256;

const CTRL_CHARS = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F". "\x7F";  // ASCII control chars (0x00..0x1F + 0x7F)

return function ($behave = HND_ALL, ?string $request_id = null): callable {
    $now = static fn(): float => (MONOTONIC_TIME & $behave) ? (float)(hrtime(true) ?: (microtime(true) * 1e9)) : (microtime(true) * 1e9);
    
    $trace = static function($frames){
        $out = '';
        foreach ($frames as $i => $f)
            $out .= ($out ? \PHP_EOL : '') . sprintf('#%d %s:%s %s%s%s()', $i, $f['file'] ?? '-', $f['line'] ?? '?', $f['class'] ?? '', $f['type'] ?? '', $f['function'] ?? '?');
        return $out;
    };

    $start_time = $now();  // start tick (ns-ish): hrtime(true) when nonzero, else microtime()*1e9 (float; not strictly monotonic)
    $log_prefix = '[req=' . ($request_id ?? (dechex((int)$start_time) . '-' . (int)getmypid())) . ']';                      // create prefix with request id

    $ctrl_space = str_repeat(' ', strlen(CTRL_CHARS));
    $ctrl_scrub = static fn(string $s): string => \trim(strtr($s, CTRL_CHARS, $ctrl_space));

    $fatal = static function ($src, $type, $msg, $file = '', $line = 0, $traces = '') use ($behave, $start_time, $now, $log_prefix, $ctrl_scrub): void {

        $time = (int)(($now() - $start_time) / 1e6);
        $memo = memory_get_peak_usage(true) >> 10;
        $incl = count(get_included_files());
        $pid = (int)getmypid();
        $ob = (int)ob_get_level();
        $at = $file ? " in $file:$line" : '';
        
        $hs_file = $hs_line = null;
        $hs = headers_sent($hs_file, $hs_line);
        $hs_at = $hs ? (basename($hs_file) . ':' . (int)$hs_line) : '-';
        
        $msg = $ctrl_scrub($msg);
        $ctx = $ctrl_scrub("{$time}ms {$memo}KiB sapi=" . PHP_SAPI . " inc={$incl} pid={$pid} ob={$ob} headers={$hs_at}");

        error_log("{$log_prefix} FATAL ({$src}:{$type}) {$msg}{$at} [{$ctx}]"); // log fatal error with context
        $traces && error_log("{$log_prefix} TRACE" . \PHP_EOL . $traces);                                                   // TRACE is multi-line by design

        (FATAL_HTTP_500 & $behave) && !$hs && http_response_code(500);                                       // set HTTP 500 if allowed and headers not yet sent

        if ((FATAL_OB_FLUSH | FATAL_OB_CLEAN) & $behave)                                                                // output buffering
            for ($max = ob_get_level(), $i = 0; $i < $max && ob_get_level(); ++$i)
                (FATAL_OB_FLUSH & $behave) ? @ob_end_flush() : @ob_end_clean();
    };

    $prev_err = $prev_exc = false;  // restore slot: false=not installed; null=installed w/ no previous; callable=previous (re-set it)


    (HND_ERR & $behave) && $prev_err = set_error_handler(static function ($code, $msg, $file, $line) use ($behave, $log_prefix, $ctrl_scrub, $trace): bool {
        if (!(error_reporting() & $code)) return false;                                                                 // respect current error_reporting level

        $msg = $ctrl_scrub($msg);
        error_log("$log_prefix ERR (errno=$code) $msg in $file:$line") ;                                 // single-line, scrubbed

        (MSG_WITH_TRACE & $behave) && error_log("$log_prefix TRACE" . \PHP_EOL . $trace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)));

        return !(ALLOW_INTERNAL & $behave);
    });

   (HND_EXC & $behave) && $prev_exc = set_exception_handler(static function (\Throwable $e) use ($behave, $fatal, $log_prefix, $ctrl_scrub, $trace): void {

        $msg = $e->getMessage();
        for ($c = $e->getPrevious(); $c; $c = $c->getPrevious())
            $msg .= ' <- ' . $c::class . ':' . $c->getMessage();

        $traces = (MSG_WITH_TRACE & $behave) ? $trace($e->getTrace()) : '';
        $fatal('exception', $e::class, $ctrl_scrub($msg), $e->getFile(), $e->getLine(), $traces);
    });

    (HND_SHUT & $behave) && register_shutdown_function(static function () use ($fatal): void {
        if (($err = error_get_last()) && ($err['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR)))
            $fatal('shutdown', "type={$err['type']}", $err['message'], $err['file'], (int)$err['line'], '');
    });

    return static function () use ($prev_err, $prev_exc): void {
        ($prev_err !== false) && ($prev_err !== null ? set_error_handler($prev_err) : restore_error_handler());
        ($prev_exc !== false) && ($prev_exc !== null ? set_exception_handler($prev_exc) : restore_exception_handler());
    };
};
