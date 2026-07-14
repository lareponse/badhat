# bad\form

Post-PHP form verification primitives.

> CSRF, honeypot, signed submission age, request shape and application-level rate limits. Network and PHP admission controls remain deployment configuration.

---

## Boundary

`bad\form` starts after PHP has accepted the request. Anything enforceable before PHP receives or processes a request belongs in the web server, reverse proxy, WAF, operating system or PHP configuration rather than this API.

Keep these outside the module:

- request-body, upload, input-variable and timeout limits
- HTTPS and supported-method enforcement
- trusted-proxy and canonical client-IP configuration
- coarse IP, connection and global endpoint flood limits
- secure session-cookie defaults
- production error suppression, logging bounds and rotation

The application still owns semantic validation, field-length policy, recipient selection, mail-header safety, output escaping and Post/Redirect/Get.

Three controls intentionally overlap:

| Control | Deployment boundary | `bad\form` or application boundary |
| --- | --- | --- |
| Request size | Reject oversized bodies before PHP | Enforce field-specific limits |
| Rate limiting | Stop coarse IP and global floods | Apply session-, form- and identity-specific limits |
| File uploads | Impose global upload limits | Reject unexpected `$_FILES` for the form |

---

## Requirements

An active PHP session and one stable site secret of at least 32 bytes.

```php
session_start();

$secret = $_ENV['BADHAT_FORM_SECRET'] ?? '';
strlen($secret) >= 32 || throw new RuntimeException('BADHAT_FORM_SECRET missing');
```

Generate a secret once, store it in deployment configuration and keep it stable across requests:

```bash
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Changing the secret invalidates outstanding form tokens. Purpose strings, expected field names, honeypot names and rate-limit keys are application-owned and limited to 255 bytes.

---

## Contact-form example

### Render

```php
use function bad\form\token;

$purpose = 'contact';
$form_token = token($purpose, $secret, 900); // expires after 15 minutes
```

```html
<form method="post" action="/contact">
    <input
        type="hidden"
        name="_form"
        value="<?= htmlspecialchars($form_token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
    >

    <div class="form-trap" aria-hidden="true">
        <label>Company <input name="company" tabindex="-1" autocomplete="off"></label>
    </div>

    <label>Name <input name="name" required maxlength="120"></label>
    <label>Email <input name="email" type="email" required maxlength="254"></label>
    <label>Message <textarea name="message" required maxlength="5000"></textarea></label>
    <button type="submit">Send</button>
</form>
```

Visually remove the honeypot from normal interaction with site CSS, but do not declare it as `type="hidden"`; basic bots often skip hidden inputs. Server-side verification remains authoritative.

### Verify

Every primitive returns the same three-slot result. `verify()` executes checks in order, passes the previous successful result to the next callable and stops at the first failure.

```php
use function bad\form\{
    result, verify, csrf, timing, honeypot, shape, session_rate
};
use const bad\form\{VALID, CODE, REJECT_UNKNOWN};

$purpose = 'contact';

$verification = verify(
    fn() => csrf($_POST['_form'] ?? null, $purpose, $secret),
    fn(array $csrf) => timing($csrf, 3),
    fn() => honeypot($_POST, 'company'),
    fn() => shape(
        $_POST,
        ['_form', 'company', 'name', 'email', 'message'],
        [],
        REJECT_UNKNOWN
    ),
    fn() => $_FILES === []
        ? result(true)
        : result(false, 'shape:files'),
    fn() => session_rate('contact:minute', 5, 60),
    fn() => session_rate('contact:hour', 20, 3600)
);

if (!$verification[VALID]) {
    error_log('contact form rejected: ' . $verification[CODE]);
    http_response_code(400);
    exit('Unable to submit the form.');
}
```

Do not expose detailed result codes as public error messages. They are intended for bounded internal diagnostics and application control flow.

After shape verification, apply normal application validation:

```php
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$message = trim($_POST['message']);

if ($name === '' || strlen($name) > 120) {
    // generic public failure
}

if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 254) {
    // generic public failure
}

if ($message === '' || strlen($message) > 5000) {
    // generic public failure
}
```

Field limits are application policy; `shape()` intentionally does not invent them.

---

## CSRF tokens

```php
token(string $purpose, string $secret, int $ttl = 900, ?int $now = null): string
csrf($token, string $purpose, string $secret, ?int $now = null): array
```

A token is bound to:

- the active PHP session
- the exact form purpose
- the stable site secret
- an issue time and expiration time

`csrf()` authenticates the issue time before `timing()` uses it. A token issued for `contact` cannot validate for `signup`.

Tokens are deliberately reusable until expiry. Rotating the PHP session ID invalidates outstanding tokens because the session ID is included only inside the HMAC input and never exposed in the token. This module does not implement token consumption or single-use semantics. The older `bad\csrf\csrf()` API remains separate and retains its existing session-stored, single-use-on-success behavior.

Expected failures return codes such as `csrf:missing`, `csrf:malformed`, `csrf:invalid`, `csrf:future` and `csrf:expired`. Configuration errors throw.

---

## Minimum submission time

```php
timing(array $csrf, int $minimum): array
```

`timing()` reads the authenticated age from a successful `csrf()` result. It returns `timing:too_fast` when the form was submitted before the minimum number of seconds elapsed.

This is a low-cost bot signal, not proof that a submission is human.

---

## Honeypot

```php
honeypot(array $input, string $field): array
```

The field must exist and be exactly the empty string. Missing, nested-array and populated values return distinct internal codes.

Use a site-owned field name and include it in the expected request shape.

---

## Request shape

```php
shape(
    array $input,
    array $required,
    array $optional = [],
    int $behave = 0
): array
```

`shape()` enforces:

- every required field name is present
- every submitted value is a string, not a nested array
- every submitted field name and value is valid UTF-8
- submitted values contain no null byte
- unknown fields are rejected when `REJECT_UNKNOWN` is set

Without `REJECT_UNKNOWN`, extra fields are allowed but still receive scalar, UTF-8 and null-byte checks.

Empty strings are valid shape. Required-value, type, range and length rules remain application validation.

---

## Application-level rate limits

### Session wrapper

```php
session_rate(string $key, int $limit, int $window, ?int $now = null): array
```

The key is application-owned. Different keys create independent fixed windows, so limits compose directly:

```php
$minute = session_rate('contact:minute', 5, 60);
$hour = session_rate('contact:hour', 20, 3600);
```

A blocked result uses `rate:limited` and includes bounded `limit`, `reset_at` and `retry_after` metadata.

Verify CSRF before charging a session limit. Otherwise, a cross-site request could consume a victim session's allowance without possessing a valid token.

### Storage-neutral primitive

```php
rate(
    array &$buckets,
    string $key,
    int $limit,
    int $window,
    ?int $now = null
): array
```

`rate()` mutates a caller-owned bucket array. This lets an application use the same primitive with session state or with state loaded from another store.

For cross-process or cross-session limits, the application must persist the array and perform the read-modify-write atomically. `rate()` does not disguise database, cache or locking policy.

Coarse IP and global flood protection should still reject traffic at the reverse proxy, web server or WAF before PHP runs.

---

## Structured results

```php
use const bad\form\{VALID, CODE, DATA};
```

Each verification result has this shape:

```php
[
    VALID => true|false,
    CODE  => 'stable:internal_code',
    DATA  => [],
]
```

`CODE` must be non-empty valid UTF-8 and is limited to 128 bytes. `DATA` contains bounded metadata only. The built-in primitives do not copy submitted field values or unknown field names into results.

Applications can compose their own checks with the same structure:

```php
use function bad\form\result;

$length = strlen($_POST['message']) <= 5000
    ? result(true)
    : result(false, 'field:length', ['field' => 'message']);
```

---

## Tests

```bash
php test/form.php
```

The test script covers token binding, expiry and reuse; authenticated timing; honeypot behavior; scalar/UTF-8/null-byte shape checks; short-circuit composition; and generic/session rate limits.
