# Production Use

ADDBAD is not a “secure-by-default” framework.  
It’s a **secure-if-you-give-a-shit** framework.

There are no magic protections, no runtime patching, no silent escaping.  
You know what you're doing—or you're not using this framework.

---

## What’s protected

ADDBAD includes a core `security.php` module that handles the **essentials** with zero dependencies, zero indirection, and full developer control.

### ✅ Stateless CSRF Protection

- Tokens are HMAC-signed, include a timestamp, and are tied to a form ID.
- No sessions, no storage.
- Manual validation.
- Expires after 1 hour (configurable).
- Tokens are encoded and stored in hidden inputs, not headers.

You want CSRF protection? Write this:

```php
if (!csrf_validate($req['body']['_csrf'] ?? null)) {
    return ['status' => 403, 'body' => 'Invalid CSRF token'];
}
````

Don't trust your users. Don't trust your forms. Trust your code.

---

### ✅ CSP (Content Security Policy)

* Automatic nonce generation per request
* Nonces can be scoped (`script`, `style`, `analytics`, etc.)
* Injected with `csp_nonce('script')`
* Headers applied manually via `apply_security_headers()`

No `unsafe-inline`.
No `unsafe-eval`.
No bullshit.

---

### ✅ Security Headers

The following headers are applied by default:

```http
Content-Security-Policy
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
Referrer-Policy: no-referrer
Strict-Transport-Security: max-age=63072000; includeSubDomains; preload
```

You don’t configure them. You set them. With intent.

---

### ✅ Secure Cookies

You want a secure cookie?

```php
secure_cookie('session', $token);
```

That sets:

* `HttpOnly`
* `Secure` (if HTTPS)
* `SameSite=Strict`
* No path leaks, no guesswork

---

### ✅ CSP Reporting

Add `/csp-report` as a POST endpoint. Here’s how:

```php
function csp_report($req) {
    error_log('[CSP] ' . file_get_contents('php://input'));
    return ['status' => 204, 'body' => ''];
}
```

No parser. No storage. Just raw signal. Log it. Deal with it.

---

## What’s not protected

You are not protected from:

* Yourself
* Inline JavaScript you paste from StackOverflow
* Bad SQL you write manually
* Frontend frameworks that inject garbage into your DOM
* Cookie-based CSRF if you ignore `SameSite`
* Third-party scripts that betray you

---

## Summary

| Vector           | Status       | Protection                        |
| ---------------- | ------------ | --------------------------------- |
| CSRF             | ✅ Manual     | Stateless, form-scoped token      |
| XSS (output)     | ✅ Your job   | Use `htmlspecialchars()`          |
| SQL Injection    | ✅ Your job   | Use `prepare()` and `qb_insert()` |
| Clickjacking     | ✅ Automatic  | `X-Frame-Options: DENY`           |
| Content sniffing | ✅ Automatic  | `X-Content-Type-Options: nosniff` |
| Mixed content    | ✅ Implicit   | HSTS + HTTPS                      |
| CSP bypass       | ✅ Controlled | Manual nonces                     |
| CSP logging      | ✅ Optional   | `/csp-report` endpoint available  |
| Cookie leaks     | ✅ Manual     | `secure_cookie()`                 |

---

## Security Philosophy

> **ADDBAD does not protect you from your mistakes.
> It prevents you from pretending you didn’t make them.**

If you're building something serious: read every line.
If you're not: don’t use ADDBAD.

