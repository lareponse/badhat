<?php

namespace bad\error;

const HND_ERR  = 1;    // handle runtime PHP errors
const HND_EXC  = 2;    // handle uncaught exceptions
const HND_SHUT = 4;    // handle fatal shutdown errors
const HND_ALL  = HND_ERR | HND_EXC | HND_SHUT;

const MSG_WITH_TRACE = 8;    // attach execution trace to reports
const ALLOW_INTERNAL = 16;   // let PHP internal handler continue
const FATAL_OB_FLUSH = 32;   // flush all output buffers on fatal
const FATAL_OB_CLEAN = 64;   // discard all output buffers on fatal
const FATAL_HTTP_500 = 128;  // force HTTP 500 on fatal conditions

const CODE_ACE = 0xACE;
const CODE_BAD = 0xBAD;
const CODE_COD = 0xC0D;

return function (int $behave = HND_ALL, ?string $request_id = null): callable {
    $start_ns = hrtime(true);                                                                                           // capture monotonic start time (nanoseconds as int)

    $log_prefix = '[req=' . ($request_id ?? (dechex($start_ns) . '-' . (int)getmypid())) . ']';                                  // create prefix with request id

    $fatal = static function (string $src, string $type, string $msg, string $file = '', int $line = 0, string $trace = '') use ($behave, $start_ns, $log_prefix): void {
        $ctx = sprintf('%.2fms %dKiB %s %s @%s', ((hrtime(true) - $start_ns) / 1e6), memory_get_peak_usage(true) >> 10, $_SERVER['REQUEST_METHOD'] ?? 'CLI', $_SERVER['REQUEST_URI'] ?? '-', $_SERVER['REMOTE_ADDR'] ?? '-');

        error_log("$log_prefix FATAL ($src:$type) $msg" . ($file ? " in $file:$line" : '') . " [$ctx]");
        $trace && error_log("$log_prefix TRACE" . \PHP_EOL . $trace);

        ($behave & FATAL_HTTP_500) && !headers_sent() && http_response_code(500);
        if ($behave & (FATAL_OB_FLUSH | FATAL_OB_CLEAN))
            for ($max = ob_get_level(), $i = 0; $i < $max && ob_get_level(); ++$i)($behave & FATAL_OB_FLUSH) ? @ob_end_flush() : @ob_end_clean();
    };

    $prev_err = $prev_exc = null;   // previous handlers, restored on uninstall

    (HND_ERR & $behave) && $prev_err = set_error_handler(static function (int $code, string $msg, string $file, int $line) use ($behave, $log_prefix): bool {
        if (!(error_reporting() & $code)) return false;

        error_log("$log_prefix ERR (errno=$code) $msg in $file:$line");

        if (MSG_WITH_TRACE & $behave) {
            ob_start();
            debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $trace = ob_get_clean();
            ($trace !== '' && $trace !== false) && error_log("$log_prefix TRACE" . \PHP_EOL . $trace);
        }

        return !(ALLOW_INTERNAL & $behave);
    });

    (HND_EXC & $behave) && $prev_exc = set_exception_handler(static function (\Throwable $e) use ($behave, $fatal): void {
        $msg = $e->getMessage();
        for ($c = $e->getPrevious(); $c; $c = $c->getPrevious())
            $msg .= ' <- ' . $c::class . ':' . $c->getMessage();

        $trace = '';
        if (MSG_WITH_TRACE & $behave)
            foreach ($e->getTrace() as $i => $f)
                $trace .= ($trace ? \PHP_EOL : '') . sprintf('#%d %s:%s %s%s%s()', $i, $f['file'] ?? '-', $f['line'] ?? '?', $f['class'] ?? '', $f['type'] ?? '', $f['function'] ?? '?');

        $fatal('exception', $e::class, $msg, $e->getFile(), $e->getLine(), $trace);
        exit(1);
    });

    (HND_SHUT & $behave) && register_shutdown_function(static function () use ($fatal): void {
        if (($err = error_get_last()) && ($err['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR))) // PHP FATAL ERRORS
            $fatal('shutdown', "type={$err['type']}", $err['message'], $err['file'], (int)$err['line'], '');
    });

    return static function () use ($prev_err, $prev_exc): void {
        $prev_err !== null ? set_error_handler($prev_err) : restore_error_handler();
        $prev_exc !== null ? set_exception_handler($prev_exc) : restore_exception_handler();
    };
};
