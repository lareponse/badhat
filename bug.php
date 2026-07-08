<?php
/*
 * Architecture
 * ------------
 * This file returns one public value: the installer closure at the bottom.
 *
 * Calling the installer creates private per-install machinery and returns a
 * restore closure for the previous PHP error handler. Fatal shutdown callbacks
 * cannot be unregistered in PHP and remain active until process end.
 *
 * `$space`, `$scrub`, and `$laddy` are created per install. They are not
 * namespace functions and do not become part of the public `bad\bug` API.
 *
 * The closures are linked by lexical captures:
 *
 *   $space     -> captured by $scrub
 *   $scrub     -> captured by $laddy
 *   configured limits and markers
 *              -> captured by $laddy
 *   $laddy     -> captured by PHP error/shutdown handlers
 *
 * This replaces function-static state with explicit closure state.
 *
 * This installer observes PHP roughness only:
 *
 *   runtime PHP errors
 *     -> set_error_handler()
 *
 *   fatal PHP shutdown errors
 *     -> register_shutdown_function()
 *
 * It does not install a global exception handler. Application exceptions belong
 * to the panic boundary, usually public/index.php.
 *
 * If a Throwable escapes that boundary, PHP may turn it into a fatal error.
 * This installer may record that through shutdown logging, without replacing
 * PHP's native uncaught-exception behavior.
 */


namespace bad\bug;

const HND_ERR  = 1;                                                 // handle runtime PHP errors
const HND_SHUT = 4;                                                 // handle fatal shutdown errors
const HND_ALL  = HND_ERR | HND_SHUT;

const LOG_WITH_TRACE = 8;                                           // attach execution trace to reports
const LOG_BLIND      = 16;                                          // redact sensitive data from reports, wins over LOG_WITH_TRACE
const ALLOW_INTERNAL = 32;                                          // let PHP internal handler continue
const FATAL_OB_FLUSH = 64;                                          // flush all output buffers on fatal
const FATAL_OB_CLEAN = 128;                                         // discard all output buffers on fatal, silently wins over FATAL_OB_FLUSH

const FATAL_MASK = \E_ERROR | \E_PARSE | \E_CORE_ERROR | \E_COMPILE_ERROR;

const BLIND_MARK    = '@REDACTED@';
const TRUNC_MARK    = '@TRUNCATED@';
const TRUNC_TEXT    = 4096;
const TRUNC_TRACE   = 64;

// ASCII control chars (0x00..0x1F + 0x7F)
const CTRL_CHARS = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F" . "\x7F";

return function (
    int $behave = HND_ALL,
    ?string $request_id = null,
    int $message_limit = TRUNC_TEXT,
    int $trace_limit = TRUNC_TRACE,
    ?int $fatal_mask = FATAL_MASK
): callable {

    $fatal_mask ??= FATAL_MASK;

    $space = \str_repeat(' ', \strlen(CTRL_CHARS));

    $scrub = static function ($v) use ($space): string {
        if ($v === null) 
            return '-';

        if (\is_scalar($v))
            return \trim(\strtr((string)$v, CTRL_CHARS, $space));

        return \get_debug_type($v);
    };

    $laddy = static function (
        $behave,
        $request_id,
        $code,
        $message,
        $file = null,
        $line = null,
        $frames = null
    ) use ($scrub, $message_limit, $trace_limit): void {
    
        $format = '%s %s #%s (%s:%s) [%s %s]';
        $prefix = "[req=$request_id]";
        $handle = HND_ERR & $behave ? 'HND_ERR' : 'HND_SHUT';

        $code = $scrub($code);
        $info = $scrub($message);
        $file = $scrub($file);
        $line = $scrub($line ?? 0);

        if ($message_limit >= 0 && \strlen($info) > $message_limit)
            $info = \substr($info, 0, $message_limit) . TRUNC_MARK;

        \error_log(\sprintf($format, $prefix, $handle, $code, $file, $line, '-', $info));

        if ((HND_SHUT) & $behave && (LOG_WITH_TRACE & $behave) && !(LOG_BLIND & $behave)) {
            $time = -1;

            if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
                $start = (float)$_SERVER['REQUEST_TIME_FLOAT'];
                $time  = $start > 0 ? (int)((\microtime(true) - $start) * 1000) : -1;
            }

            $memo = \memory_get_peak_usage(true) >> 10;
            $incl = \count(\get_included_files());
            $pid  = (int)\getmypid();
            $ob   = (int)\ob_get_level();

            $hs_file = $hs_line = null;
            $hs = \headers_sent($hs_file, $hs_line);
            $hs_at = $hs ? (\basename((string)$hs_file) . ':' . (int)$hs_line) : '-';

            $peek = "{$time}ms {$memo}KiB sapi=" . \PHP_SAPI . " inc={$incl} pid={$pid} ob={$ob} headers={$hs_at}";

            \error_log(\sprintf($format, $prefix, 'PEEK', $code, $file, $line, '-', $peek));
        }

        if (LOG_WITH_TRACE & $behave && !(LOG_BLIND & $behave)) {
            $frames ??= \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS);
            foreach ($frames as $i => $f) {
                if ($trace_limit >= 0 && $i >= $trace_limit) {
                    \error_log(\sprintf($format, $prefix, 'FRAME', $i, '-', 0, '-', TRUNC_MARK));
                    break;
                }

                $source = ($f['class'] ?? '') . ($f['type'] ?? '') . $scrub($f['function'] ?? '?') . '()';
                \error_log(\sprintf($format, $prefix, 'FRAME', $i, $scrub($f['file'] ?? '?'), (int)($f['line'] ?? 0), $source, ''));
            }
        }

        if (((HND_SHUT) & $behave) && ((FATAL_OB_FLUSH | FATAL_OB_CLEAN) & $behave))
            for ($level = \ob_get_level(), $i = 0; $i < $level && \ob_get_level(); ++$i)
                (FATAL_OB_CLEAN & $behave) ? @\ob_end_clean() : @\ob_end_flush();
    };

    $request_id ??= (int)\getmypid() . '-' . \dechex(\hrtime(true) ?: (int)(\microtime(true) * 1e9));

    $prev_err = false;                                              // set_error_handler(): ?callable

    if (HND_ERR & $behave)
        $prev_err = \set_error_handler(
            static function ($code, $message, $file, $line) use ($behave, $request_id, $laddy): bool {
                if (\error_reporting() & $code)
                    LOG_BLIND & $behave
                        ? $laddy($behave & ~(HND_SHUT), $request_id, $code, BLIND_MARK, BLIND_MARK, BLIND_MARK)
                        : $laddy($behave & ~(HND_SHUT), $request_id, $code, $message, $file, $line);
                
                return !(ALLOW_INTERNAL & $behave);
            }
        );

    if (HND_SHUT & $behave)
        \register_shutdown_function(
            static function () use ($behave, $request_id, $fatal_mask, $laddy): void {
    
                $context = \error_get_last();
                if (!$context) return;

                $code = (int)($context['type'] ?? 0);
                if (!($code & $fatal_mask)) return;

                LOG_BLIND & $behave
                    ? $laddy($behave & ~(HND_ERR), $request_id, $code, BLIND_MARK, BLIND_MARK, BLIND_MARK)
                    : $laddy($behave & ~(HND_ERR), $request_id, $context['type'] ?? 0, $context['message'] ?? '-', $context['file'] ?? '-', $context['line'] ?? 0);
                }
        );

    return static function () use ($prev_err): void {
        if ($prev_err === false)
            return;

        $prev_err !== null ? \set_error_handler($prev_err) : \restore_error_handler();
    };
};