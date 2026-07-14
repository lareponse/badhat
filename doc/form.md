# bad\form

Application-aware form request checks.

> Signed form proof, honeypot, submission age, request shape, and composable rate limits. Deployment remains responsible for rejecting abusive requests before PHP runs.

---

## Boundary

`bad\form` starts after PHP has received the request. It does not replace web-server, reverse-proxy, WAF, or PHP configuration.

Keep these controls outside the module:

- maximum request-body size and PHP `post_max_size`
- request and slow-request timeouts
- rejection of unsupported HTTP methods at the route boundary
- HTTPS, trusted proxy, and client-IP configuration
- coarse IP, connection, and global flood limits
- strict cookie-only sessions, secure session cookies, and production error settings

The module applies information only the application reliably knows: form purpose, current session, expected fields, honeypot value, signed render time, and application-defined rate keys.

---

## Setup

An active PHP session and a stable application secret of at least 32 bytes are required for form proofs and the default session limiter.

```php
session_start();

$secret = $_ENV['BADHAT_FORM_SECRET'];
```

Generate the secret once and keep it outside the repository. Rotating it invalidates outstanding form proofs.

---

## Render a form

```php
use function bad\form\token;

$proof = token($secret, 'contact.send', 900); // valid for 15 minutes
```

The purpose is application-owned. Use one stable, narrow value per action, such as `contact.send`, `account.email.change`, or `invoice.pay`.

```html
<form method="post" action="/contact">
    <input type="hidden" name="_badhat" value="<?= htmlspecialchars($proof, ENT_QUOTES, 'UTF-8') ?>">

    <div class="form-trap" aria-hidden="true">
        <label for="website">Leave this field empty</label>
        <input id="website" name="website" type="text" value="" tabindex="-1" autocomplete="off">
    </div>

    <input name="name" required>
    <input name="email" type="email" required>
    <textarea name="message" required></textarea>
    <button>Send</button>
</form>
```

The proof contains signed issue and expiry timestamps plus a random nonce. Its MAC binds those values to the supplied purpose and the active session ID. The secret, purpose, and session ID are not placed in the token.

A proof is reusable within its lifetime. Successful verification does not consume it; single-use storage and explicit consumption are intentionally deferred.

---

## Verify a submission

```php
use function bad\form\{combine, honeypot, rate, shape, verify};
use const bad\form\REJECT_UNKNOWN;

$shape = shape(
    $_POST,
    ['_badhat', 'website', 'name', 'email', 'message'],
    [],
    REJECT_UNKNOWN
);

$checks = combine(
    $shape,
    verify($secret, 'contact.send', $_POST['_badhat'] ?? null, 2),
    honeypot($_POST, 'website'),
    rate('form:contact.send', 5, 60)
);

if (!$checks['ok']) {
    // Keep the public response generic. The internal result codes are for
    // application decisions, tests, and bounded security logging.
    http_response_code(400);
    exit('Unable to submit form');
}

// Field lengths, required content, email syntax, business rules, and output
// escaping remain application validation.
```

The minimum age in `verify(..., 2)` rejects a proof submitted less than two seconds after it was issued. This is a low-cost bot signal, not proof that a human submitted the form.

Evaluate or combine checks in the order that matches the endpoint. A rate limit may deliberately run even for malformed or invalid submissions so abuse still consumes capacity.

---

## Request shape

```php
shape(
    array $input,
    array $required,
    array $optional = [],
    int $behave = 0,
    ?array $files = null
): array
```

`shape()` checks only request structure:

- every required field is present
- allowed values are strings, not arrays or other types
- allowed values contain no null byte
- allowed values are valid UTF-8
- unknown fields are rejected when `REJECT_UNKNOWN` is set
- files are rejected by default

Optional fields may be absent, but are checked when present.

```php
use const bad\form\{ALLOW_FILES, REJECT_UNKNOWN};

$result = shape(
    $_POST,
    ['title'],
    ['description'],
    REJECT_UNKNOWN | ALLOW_FILES,
    $_FILES
);
```

`ALLOW_FILES` only delegates file validation to the application. It does not validate an upload. Without that flag, any entry in `$_FILES` produces `file.unexpected`.

`shape()` does not trim, normalize, escape, or impose application field lengths. It also does not validate email addresses, numbers, dates, choices, or business rules.

When unknown-field rejection is disabled, unknown values are ignored by this helper. Application code should still read only explicitly expected keys.

---

## Honeypot

```php
honeypot(array $input, string $field): array
```

The field must be present, scalar, and exactly empty. Missing, array-shaped, whitespace-filled, or otherwise filled values fail.

A honeypot is only a signal. Password managers, accessibility tools, and aggressive browser autofill can create false positives, so keep the public response generic and combine it with stronger checks.

---

## CSRF proof and submission age

```php
token(
    string $secret,
    string $purpose,
    int $ttl = 900,
    ?int $_now = null
): string

verify(
    string $secret,
    string $purpose,
    mixed $proof,
    int $minimum = 0,
    ?int $_now = null
): array
```

`verify()` checks, in order:

1. token shape and encoding
2. HMAC integrity
3. application purpose
4. active session binding
5. issue and expiry timestamps
6. minimum submission age

`$_now` is a deterministic clock hook for tests. Production calls should omit it.

Changing the purpose, session ID, secret, timestamps, nonce, or MAC invalidates the proof. A session ID regeneration therefore invalidates proofs issued before the regeneration.

---

## Rate limiting

```php
rate(
    string $key,
    int $limit,
    int $window,
    ?callable $hit = null,
    int $cost = 1,
    ?int $_now = null
): array
```

Without `$hit`, counters live in the active session:

```php
$session = rate('form:contact.send', 5, 60);
```

The application chooses the key. This makes form and application scopes composable:

```php
$contact = rate('form:contact.send', 5, 60);
$account = rate('account:settings', 20, 300);
```

For a shared application-wide store, pass a callback that atomically increments the supplied bucket by `$cost`, ensures it expires after `$ttl`, and returns the cumulative integer count:

```php
$global = rate(
    'app:contact.send',
    200,
    60,
    $atomicHit
);
```

Callback contract:

```php
function (string $bucket, int $cost, int $ttl): int
```

The callback must provide atomicity appropriate to its backing store. BADHAT does not wrap Redis, APCu, SQL, or another storage engine. Prefix application keys when a store is shared by multiple deployments.

The implementation uses fixed windows. It is small and deterministic, but traffic around a boundary can use one full allowance immediately before reset and another immediately after reset. Coarse global floods and IP limits still belong at the proxy or web server.

The default session path relies on normal PHP session serialization. A custom session handler that disables locking must provide its own concurrency guarantees.

Submitted identities are application data. To add identity-specific limits, append an application-owned keyed hash rather than a raw email address or username.

---

## Results

Every check returns the same minimum shape:

```php
[
    'ok'   => bool,
    'code' => string,
]
```

Additional metadata may include field names, signed timestamps, age, remaining capacity, reset time, and retry delay. Submitted values, the form proof, the session ID, and the application secret are never copied into results.

`combine()` returns `form.invalid` when any supplied result failed and retains the individual checks and failures for internal handling.

Application checks can use the same shape:

```php
use function bad\form\result;

$email = result(
    filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) !== false,
    'application.email'
);
```

Do not expose detailed result codes to the public response.

### Codes

| Code | Meaning |
|---|---|
| `ok` | Check passed |
| `form.invalid` | One or more combined checks failed |
| `csrf.missing` | No proof was submitted |
| `csrf.malformed` | Proof type, format, or encoding is invalid |
| `csrf.invalid` | HMAC, purpose, secret, or session binding does not match |
| `csrf.future` | Signed issue time is in the future |
| `csrf.expired` | Signed expiry has passed |
| `submission.early` | Proof is younger than the required minimum |
| `honeypot.missing` | Honeypot field is absent |
| `honeypot.scalar` | Honeypot field is array-shaped or otherwise non-string |
| `honeypot.filled` | Honeypot field is not exactly empty |
| `shape.invalid` | One or more request-shape checks failed |
| `field.missing` | Required field is absent |
| `field.scalar` | Expected field is not a string |
| `field.utf8` | Expected field is not valid UTF-8 |
| `field.null` | Expected field contains a null byte |
| `field.unknown` | Unknown field was submitted under strict mode |
| `file.unexpected` | `$_FILES` contains an entry without delegated upload handling |
| `rate.limited` | Counter exceeded the configured allowance |

---

## Tests

```bash
php test/form.php
```

The test script covers proof integrity, purpose and session binding, expiry, submission age, honeypots, array injection, UTF-8 and null rejection, unknown fields, unexpected uploads, session limits, custom atomic stores, and result composition.
