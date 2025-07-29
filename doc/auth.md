# Authentication Methods in BADHAT Context

## Overview

This document outlines practical authentication strategies for use in minimalistic PHP applications, specifically within the BADHAT philosophy: procedural, no boilerplate, no classes, and minimal dependencies.

---

## Supported Authentication Sources

### 1. `PHP_AUTH_USER` (HTTP Basic Auth)

**Origin**: Automatically set by PHP when Apache is configured to require Basic Authentication.

**Use cases**:

* Staging or internal environments
* Curl/wget access
* Legacy apps needing zero-code login protection

**Pros**:

* No PHP logic needed
* Built into web server
* Universally supported by clients

**Cons**:

* Sends credentials in every request (use HTTPS)
* Doesn’t support roles or advanced auth
* Tied to Apache/CGI config

---

### 2. `HTTP_X_AUTH_USER` (Header-based Auth)

**Origin**: Set by a trusted upstream (reverse proxy, SSO system, or API gateway) after validating user identity.

**Use cases**:

* Apps behind OAuth2/JWT/SSO systems
* Stateless microservices
* Trusted proxy setups

**Pros**:

* Decouples app from auth logic
* Integrates with SSO, JWT
* Stateless, scalable

**Cons**:

* Requires secure proxy setup
* Risk of spoofing if headers not stripped from client
* Needs server infra beyond Apache defaults

---

## More Secure Alternatives (Apache-compatible)

### 3. Client Certificate Authentication (Mutual TLS)

**Use case**: Internal high-security apps

**Setup**:

```apache
SSLVerifyClient require
SSLCACertificateFile /etc/ssl/certs/ca.pem
```

**PHP access**: `$_SERVER['SSL_CLIENT_S_DN_Email']`

**Pros**:

* Tied to HTTPS connection
* Practically unforgeable

**Cons**:

* Requires cert management
* Complex for general users

---

### 4. Apache OIDC Module (e.g. `mod_auth_openidc`)

**Use case**: Apps behind enterprise SSO (Google, Azure AD, Keycloak)

**Behavior**: Apache handles the login and injects `REMOTE_USER`

**PHP access**:

```php
$user = $_SERVER['REMOTE_USER'] ?? null;
```

**Pros**:

* Secure validation of identity tokens
* No need to touch PHP session logic

**Cons**:

* Requires Apache module install and config
* More complex initial setup

---

## BADHAT Helper Function

```php
function current_user(): ?string {
    return $_SERVER['HTTP_X_AUTH_USER']
        ?? $_SERVER['PHP_AUTH_USER']
        ?? $_SERVER['REMOTE_USER']
        ?? null;
}
```

This provides a single entry point for user identity in your app. Customize if your environment demands.

---

## Recommendation Matrix

| Scenario                         | Recommended Method          |
| -------------------------------- | --------------------------- |
| Simple staging or legacy site    | `PHP_AUTH_USER`             |
| Behind SSO or JWT proxy          | `HTTP_X_AUTH_USER`          |
| Internal secure access (dev ops) | Client cert (Mutual TLS)    |
| Enterprise login (SSO providers) | Apache + `mod_auth_openidc` |

---

## Final Note

Avoid trusting `X-Auth-User` unless you fully control the proxy and strip incoming headers. For maximum safety, lean on Apache's built-in mechanisms: they’re harder to misconfigure than anything PHP-side.

Stay lean. Stay secure. Stay BADHAT.
