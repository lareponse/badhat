## .htpasswd: How to Not Screw It Up

`.htpasswd` is not insecure. The way people use it is.

Here’s how to do it properly.

---

### 1. Never put `.htpasswd` in `public/`

If someone can `GET` the file, your access control is worthless.

Use `/etc/apache2/.htpasswd`, `/var/www/private/.htpasswd`, or any location outside your web root.

---

### 2. Use bcrypt only

Legacy `.htpasswd` entries use `crypt()` or unsalted MD5. Both are weak.

Use:

    htpasswd -B /etc/apache2/.htpasswd username

Or in PHP:

    password_hash('password', PASSWORD_BCRYPT)

If your hashes don't start with `$2y$`, you're doing it wrong.

---

### 3. Don’t rely on `.htaccess` unless your server allows it

Apache needs `AllowOverride AuthConfig` to parse `.htaccess`.

Without it, Apache ignores your rules and serves unprotected routes.

Test everything. Don't assume.

---

### 4. Enforce HTTPS

Basic Auth sends credentials on every request. Unencrypted means compromised.

Redirect all HTTP to HTTPS. Use HSTS. Do not negotiate with plaintext.

---

### 5. Rate-limit login attempts

Basic Auth has no lockout mechanism. Attackers can brute force forever.

Use `mod_evasive`, `fail2ban`, or reverse proxy rate limiting.

If someone can retry passwords without resistance, it's not protected.

---

### 6. Log access

Enable access logs for secure routes. Watch for repeated 401s.

You can’t monitor what you don’t record.

---

### 7. Generate `.htpasswd` from source of truth

If you store users in a database (like `users.sqlite`), regenerate `.htpasswd` from that.

Do not let your web interface write `.htpasswd` directly.  
Do not sync passwords via HTTP.  
Only trusted scripts should touch that file.

---

### Summary

`.htpasswd` is one of the safest login methods available—if you treat it like an access control file, not an afterthought.

- Store it securely  
- Hash it correctly  
- Protect it with TLS  
- Regenerate it with intent  
- Never expose it, write to it, or trust it casually

Do that, and you get simple, file-based, high-trust authentication—without needing a framework or a login form.
