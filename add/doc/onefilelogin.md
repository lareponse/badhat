## .htpasswd: How to Not Screw It Up

`.htpasswd` is not insecure. The way people use it is.

Here’s how to do it properly.

---

### 1. Never put `.htpasswd` in `public/`

If someone can `GET` the file, your access control is worthless.

Use `/etc/apache2/.htpasswd`, `/var/www/private/.htpasswd`, or any location outside your web root.

Set permissions to `640` or stricter. Apache needs to read it; PHP must **never** be able to serve it.

Block it explicitly with your server config:

```apache
<Files ".ht*">
    Require all denied
</Files>
```

---

### 2. Use bcrypt only

Legacy `.htpasswd` entries use `crypt()` or unsalted MD5. Both are weak.

Use:

```bash
htpasswd -B /etc/apache2/.htpasswd username
```

Or in PHP:

```php
password_hash('password', PASSWORD_BCRYPT)
```

If your hashes don’t start with `$2y$`, you’re doing it wrong.

---

### 3. Don’t rely on `.htaccess` unless your server allows it

Apache needs `AllowOverride AuthConfig` to parse `.htaccess`.

Without it, Apache ignores your rules and serves unprotected routes.

Prefer putting rules directly in your virtual host config:

```apache
<Directory "/var/www/secure">
    AuthType Basic
    AuthName "Restricted Area"
    AuthUserFile /etc/apache2/.htpasswd
    Require valid-user
</Directory>
```

Test everything. Don’t assume it’s working. Apache will not warn you if it's misconfigured.

---

### 4. Enforce HTTPS

Basic Auth sends credentials with **every request**. If it’s not encrypted, it’s compromised.

* Redirect all HTTP to HTTPS
* Enable HSTS
* Never allow negotiation with plaintext

---

### 5. Rate-limit login attempts

Basic Auth has no built-in lockout. Brute-force is trivial unless you stop it.

Use:

* `mod_evasive`
* `mod_security`
* `fail2ban`
* Rate limiting at a reverse proxy (e.g., NGINX)

No rate limiting = no security.

---

### 6. Log access

Enable access logs on secure routes.

Watch for repeated 401s and unusual activity.

If you don’t log it, you can’t audit it.

---

### 7. Generate `.htpasswd` from source of truth

If users are stored in a database, regenerate `.htpasswd` from a trusted script or cron job.

Do **not**:

* Let a web form write `.htpasswd`
* Sync passwords via HTTP
* Handle writes from user input

`.htpasswd` is an access control file, not a writable store.

---

### 8. Never trust unvalidated `REMOTE_USER` or headers

Some frameworks use `REMOTE_USER` or `X-Auth-User` for identity.

Only trust those if they’re set by locked-down Apache rules or a hardened reverse proxy.

Do not trust headers from the client.

---

### Summary

`.htpasswd` is one of the safest login methods available—**if you treat it like a privileged access file**, not a casual config.

* Store it securely
* Hash it with bcrypt
* Protect it from public access
* Enforce HTTPS
* Rate-limit login attempts
* Never trust input-based writes
* Test that your access rules work
* Strip spoofable headers

Do it right, and you get simple, file-based, high-trust authentication—without needing a framework or a login form.
