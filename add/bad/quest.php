<?php

declare(strict_types=1);

require 'add/bad/http.php';
require 'add/bad/io.php';


const QST_CORE =  0;     // State bitmask

const QST_PULL =  1;     // Parity Phase flags:  - any ODD flag  is a pull step,
const QST_PUSH =  2;     //                      - any EVEN flag is a push step  

const QST_FILE =  4;     // Steps flags:     - the found file, , a ?callable, and ?string (output buffer)
const QST_ARGS =  8;     //                  - an ?array (remainder of /url_segments/) for the callable
const QST_CALL = 16;     //                  - a ?callable to execute ?
const QST_ECHO = 32;     //                  - a ?string output buffer ?

const QST_STOP = E_USER_ERROR;   // 256
const QST_WARN = E_USER_WARNING; // 512
const QST_INFO = E_USER_NOTICE;  // 1024

const QST_PULL_INVOKE = QST_PULL | QST_CALL | QST_ARGS; // invoke the callable with args
const QST_PUSH_INVOKE = QST_PUSH | QST_CALL | QST_ECHO; // invoke the callable with output buffer

// io calls might/should trigger an http_response, if not, return the quest array for end-dev
function quest(string $path, ?string $way_in = null, ?string $way_out = null): array
{
    $way_in  ?: $way_out                        ?: throw new BadFunctionCallException('No input or output path provided', 400);
    
    $way_in  && realpath($way_in) !== $way_in   && throw new RuntimeException("Scouting path is not real [$way_in]", 500);
    $way_out && realpath($way_out) !== $way_out && throw new RuntimeException("Rendering path is not real [$way_out]", 500);
    
    $paths    = chart($path)                    ?: throw new RuntimeException('No candidates found', 404);
    $way_in  && phase(QST_PULL, $way_in,  $paths);
    $way_out && phase(QST_PUSH, $way_out, $paths);

    return track();
}

// phase function to handle the pull/push logic
// - scout the paths and track the steps
// - invoke the callable if found, with phase dependant
// - catch and track Throwable
function phase(int $mode, string $start, array $paths): void
{

    ($mode & (QST_PULL | QST_PUSH)) || throw new BadFunctionCallException('Invalid phase mode', 500);
    
    try {
        $steps = scout($start, $paths);
        foreach ($steps as $step => $gain)
            track($mode | $step, $gain);

        $invoke = $steps[QST_CALL] ?? null;
        if (is_callable($invoke)) {
            $invoke_parm = $mode === QST_PULL ? QST_ARGS : QST_ECHO;

            $gain = $invoke($steps[$invoke_parm] ?? null);
            $mode === QST_PULL ? track(QST_PULL_INVOKE, $gain) : track(QST_PUSH_INVOKE, $gain);
        }
    } catch (Throwable $t) {
        track($mode | QST_STOP, $t);
    }
}

// path finding logic
// - returns an array of candidates, with the file path as key and the args as value
function chart(string $url_path, ?string $target = null): array
{
    $segments = explode('/', trim($url_path, '/'));
    $candidates = [];

    foreach ($segments as $depth => $segment) {
        $args = array_slice($segments, $depth + 1);
        $path_prefix = implode(DIRECTORY_SEPARATOR, array_slice($segments, 0, $depth+1));
        if ($target === null) {
            $candidates[$path_prefix . DIRECTORY_SEPARATOR . $segment . '.php'] = $args;
            if ($depth > 0) {
                $candidates[$path_prefix . '.php'] = $args;
            }
        } else {
            $candidates[$path_prefix . DIRECTORY_SEPARATOR . $target . '.php'] = $args;
            $candidates[$path_prefix . DIRECTORY_SEPARATOR . $segment . DIRECTORY_SEPARATOR . $target . '.php'] = $args;
        }
    }

    return array_reverse($candidates);
}

function scout(string $start, array $candidates): array
{    
    foreach ($candidates as $path => $args) {
        $yield = io($start . $path);             // fetch the file
        if (!$yield[0] && !$yield[1]) continue;  // skip if no callable or output buffer
        return [QST_FILE => $path, QST_ARGS => $args, QST_ECHO => $yield[1], QST_CALL => $yield[0]];
    }
    return [];
}

function track(?int $step = null, $reward = null): mixed
{
    static $quest = [QST_CORE => 0];

    if (isset($step, $reward) && $step !== QST_CORE) {   // quest update
        $quest[$step] = $reward;                         //  - sets the step reward
        $quest[QST_CORE] |= $step;                       //  - updates the state step
    } else if (isset($step))
        return $quest[$step] ?? null;

    return $quest;
}
