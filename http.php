<?php

const CSRF_KEY = '_csrf_token';


// Sanitize the inbound HTTP request URI
function http_no(int $max_decode = 9, ?callable $csrf = null, array $forbidden = ['..']): ?string
{
  if ($csrf && !empty($_POST) && $csrf($_POST) === false) {
    http_out(403, 'Invalid CSRF token.', ['Content-Type' => 'text/plain; charset=utf-8']);
  }

  $coded = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

  // Safe repeated URL decoding
  do {
    $uri_path = rawurldecode($coded);
  } while ($max_decode-- > 0 && $uri_path !== $coded && ($coded = $uri_path));

  if ($max_decode <= 0)
    return null;

  foreach($forbidden as $s)
    if (strpos($uri_path, $s) !== false)
      return null;

  return $uri_path;
}

// Normalize an inbound HTTP path and extract its representation suffix.
function http_in(string $safe_path, string $accept = 'html', string $default = 'index'): array
{
  while (strpos($safe_path, '//') !== false)
    $safe_path = str_replace('//', '/', $safe_path);

  $uri_path = trim($safe_path, '/');

  $last_slash = strrpos($uri_path, '/');
  $last_dot = strrpos($uri_path, '.');
  if ($last_dot !== false && $last_dot > ($last_slash === false ? 0 : $last_slash + 1)) {
    return [
      substr($uri_path, 0, $last_dot),
      substr($uri_path, $last_dot + 1)
    ];
  }

  return [$uri_path ?: $default, $accept];
}


// http response, side effect: exits
function http_out(int $status, string $body, array $headers = []): void
{
    http_response_code($status);
    foreach ($headers as $h => $v) header("$h: $v");
    echo $body;
    exit;
}


function csp_nonce(): string
{
    static $nonce = null;
    return $nonce ??= bin2hex(random_bytes(16));
}

function csrf_token(int $ttl = 3600): string
{
    $ttl || throw new InvalidArgumentException('CSRF token TTL must be a positive integer', 400);
    $now  = time();
    if (empty($_SESSION[CSRF_KEY]) || $now > $_SESSION[CSRF_KEY][1]) {
        $master_token       = bin2hex(random_bytes(32));
        $expires_at         = $now + $ttl;
        $_SESSION[CSRF_KEY] = [$master_token, $expires_at];
    }

    return $_SESSION[CSRF_KEY][0] ?? throw new RuntimeException('CSRF token cannot be initialized', 500);
}

function csrf_validate(?string $token = null): bool
{
    $_SESSION[CSRF_KEY]                             ?? throw new BadFunctionCallException('CSRF token not initialized', 403);
    $token = $token ?: ($_POST[CSRF_KEY] ?? '')     ?: throw new BadFunctionCallException('CSRF token is required', 400);

    [$master_token, $expires_at] = $_SESSION[CSRF_KEY];
    return time() <= $expires_at && hash_equals($master_token, $token);
}

function csrf_field(int $ttl = 3600): string
{
    return '<input type="hidden" name="' . CSRF_KEY . '" value="' . csrf_token($ttl) . '" />';
}
