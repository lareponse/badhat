# BADHAT HTTP Header Guide: Protocol-Adaptive Edition

> This guide maintains BADHAT's core philosophy of separation of concerns while embracing the evolution of HTTP protocols. We draw firm boundaries between infrastructure, directory context, and application code, all while remaining web server agnostic.

This protocol-adaptive approach ensures optimal security and performance across all client capabilities. Your application code remains focused on business logic, while infrastructure handles the protocol-specific adaptations - all independent of your specific web server technology.

## Core Principles

1. **Infrastructure defines protocol-aware guardrails** that adapt to client capabilities
2. **Directory context unlocks only what's explicitly scoped** within protocol constraints
3. **Application code injects only truly dynamic values** and remains protocol-agnostic

HTTP headers remain the front lines of security, performance, and compliance. By intelligently applying headers based on protocol version, we maximize effectiveness without sacrificing the clean separation that defines BADHAT.

## Implementation Layers

Headers follow a version-aware override chain:

1. **Global** (server configuration) for protocol-detecting operational defaults
2. **Directory** (contextual configuration) to adjust policies based on segment needs
3. **PHP** (`header()`) for dynamic, per-request context

**Guideline:** Assign each feature's policy to exactly one layer—no duplicate declarations—while allowing protocol-specific adaptations at the infrastructure level.

---

# Security Policies

Fundamental defenses against common web threats, applied with protocol awareness.

## Secure Transport

**Function:** Prevent network eavesdropping and protocol downgrade.

* **Header:** Strict-Transport-Security
* **Placement:** Global server configuration
* **Protocol consideration:** Critical for all HTTP versions, but HTTP/3 benefits from longer max-age due to QUIC's integrated security

```
Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
```

## Clickjacking Prevention

**Function:** Block unwanted framing to protect user interactions.

* **Headers:** X-Frame-Options AND Content-Security-Policy (frame-ancestors)
* **Placement:** Global server configuration; directory-level config to relax on trusted segments
* **Protocol consideration:** Apply both headers for backward compatibility

```
X-Frame-Options "SAMEORIGIN"
Content-Security-Policy "frame-ancestors 'self'"
```

## MIME Sniffing Defense

**Function:** Ensure browsers respect declared content types.

* **Header:** X-Content-Type-Options
* **Placement:** Global server configuration
* **Protocol consideration:** Critical for all HTTP versions

```
X-Content-Type-Options "nosniff"
```

## Referrer Privacy

**Function:** Control leakage of page URLs to external sites.

* **Header:** Referrer-Policy
* **Placement:** Global server configuration; directory-level to loosen where safe
* **Protocol consideration:** Same policy across protocols

```
Referrer-Policy "strict-origin-when-cross-origin"
```

## Cross-Origin Isolation

**Function:** Enable advanced APIs and prevent cross-origin data leaks.

* **Headers:** Cross-Origin-Opener-Policy, Cross-Origin-Embedder-Policy
* **Placement:** Global server configuration with protocol detection
* **Protocol consideration:** More aggressive policies for HTTP/2+

Server configuration should implement protocol-based conditions to set appropriate headers:
- For HTTP/2+ clients: More restrictive policies
- For HTTP/1.x clients: More permissive policies if needed

## Cross-Site Scripting Mitigation

**Function:** Prevent inline script/style injection.

* **Header:** Content-Security-Policy with nonces/hashes
* **Placement:** PHP `header()` in application code only
* **Protocol consideration:** Protocol-agnostic implementation

---

# Performance & Optimization

Improve load times, bandwidth usage, and efficient resource delivery with protocol-aware techniques.

## Compression & Connections

**Function:** Reduce payload size and manage connections optimally.

* **Headers:** Content-Encoding, Vary, Keep-Alive, Connection
* **Placement:** Global server configuration with protocol detection
* **Protocol consideration:** HTTP/1.1 requires explicit connection management, HTTP/2+ handles this internally

Server configuration should:
1. Enable compression for all supported content types
2. Set appropriate Vary headers
3. Configure connection management differently based on protocol:
   - For HTTP/1.1: Set explicit Connection and Keep-Alive headers
   - For HTTP/2+: Omit these headers as they're harmful

## Protocol-Specific Optimizations

**Function:** Leverage the unique capabilities of each HTTP version.

### HTTP/1.1 Optimizations

* **Strategy:** Minimize round trips, consolidate resources, domain sharding
* **Placement:** Global server configuration with HTTP/1.1 detection

For HTTP/1.1 connections:
- Consider domain sharding for parallel connections
- Set explicit keep-alive parameters
- Consolidate resources where possible

### HTTP/2 Optimizations

* **Strategy:** Multiplexing, header compression, server push
* **Placement:** Global server configuration with HTTP/2 detection

For HTTP/2 connections:
- Consider server push for critical assets
- Optimize for multiplexing by removing connection limitations
- Leverage header compression

### HTTP/3 Optimizations

* **Strategy:** QUIC protocol, 0-RTT, connection migration
* **Placement:** Global server configuration with Alt-Svc advertisement

For HTTP/3 support:
- Advertise HTTP/3 capability via Alt-Svc header
- Enable 0-RTT when supported
- Optimize for QUIC's connection handling

## Caching & Data Clearance

**Function:** Control browser and proxy caches for sensitive or immutable content.

* **Headers:** Cache-Control, Clear-Site-Data, ETag
* **Placement:** PHP `header()` in controllers for sensitive routes; server config for public static assets
* **Protocol consideration:** More aggressive caching for HTTP/2+ due to efficient revalidation

---

# Cross-Origin & Resource Sharing

Manage cross-origin requests with protocol awareness.

## CORS for Public Resources

**Function:** Permit controlled cross-origin access to static files or open APIs.

* **Headers:** Access-Control-Allow-Origin, etc.
* **Placement:** Global server configuration for truly public endpoints
* **Protocol consideration:** Same policy across protocols

## Dynamic CORS Policies

**Function:** Enforce origin checks or credential requirements on authenticated APIs.

* **Headers:** Same as above
* **Placement:** PHP `header()` in application code
* **Protocol consideration:** Protocol-agnostic implementation

---

# Internationalization & SEO

Tailor responses for locale and guide search engine behavior.

## Internationalization

**Function:** Direct clients to appropriate language or locale.

* **Headers:** Content-Language, Vary: Accept-Language
* **Placement:** PHP `header()` when rendering localized content
* **Protocol consideration:** Protocol-agnostic implementation

## SEO & Robots Control

**Function:** Guide crawlers and indexing behavior of pages and assets.

* **Header:** X-Robots-Tag
* **Placement:** Directory-level config in segments to noindex; PHP for dynamic control
* **Protocol consideration:** Protocol-agnostic implementation

---

# Diagnostics & Monitoring

Expose metrics and allow observability tooling, with protocol insights.

## Performance Timing

**Function:** Report server-side timing to browser or monitoring systems.

* **Headers:** Server-Timing, Timing-Allow-Origin
* **Placement:** PHP `header()` where code measures execution
* **Protocol enhancement:** Include protocol version in timing data for performance analysis

```php
// In PHP application code
$startTime = microtime(true);
// Process request
$endTime = microtime(true);
$processingTime = ($endTime - $startTime) * 1000;
header('Server-Timing: app;desc="Application Processing";dur=' . $processingTime . ', protocol;desc="' . $_SERVER['SERVER_PROTOCOL'] . '"');
```

---

# Protocol Detection in PHP

When PHP needs to be aware of the client's HTTP protocol version:

```php
// Detect protocol in PHP
$httpVersion = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
$isModern = (strpos($httpVersion, 'HTTP/2') === 0 || strpos($httpVersion, 'HTTP/3') === 0);

// Adapt behavior if needed
if ($isModern) {
    // Behavior optimized for HTTP/2+
} else {
    // HTTP/1.1 fallback behavior
}
```

---

# Deprecated Headers - Updated Status

| Header | Current Status | Protocol Relevance | Recommendation |
|--------|---------------|-------------------|----------------|
| **X-XSS-Protection** | Harmful in modern browsers | Any version | **Remove** and replace with robust CSP |
| **Public-Key-Pins (HPKP)** | Fully deprecated | Any version | **Remove** and use CT logs |
| **X-Content-Type-Options** | **Still critical** | All versions | **Keep** with "nosniff" value |
| **X-Frame-Options** | Still useful | All versions | **Keep** alongside CSP frame-ancestors |
| **Keep-Alive / Connection** | Essential for HTTP/1.1 | HTTP/1.1 only | **Keep for HTTP/1.1**, remove for HTTP/2+ |
| **X-Content-Security-Policy** | Deprecated vendor prefix | Any version | **Remove**, use standard CSP |
| **X-WebKit-CSP** | Deprecated vendor prefix | Any version | **Remove**, use standard CSP |
| **X-Download-Options** | IE-specific, obsolete | Any version | **Remove** |
| **X-Permitted-Cross-Domain-Policies** | Flash-era, obsolete | Any version | **Remove** |
| **X-Powered-By** | Security risk | Any version | **Remove** to minimize fingerprinting |

---

# Implementation & Testing Strategy

To properly implement this protocol-adaptive approach:

1. **Identify your supported protocols** - Configure your web server for HTTP/1.1, HTTP/2, and optionally HTTP/3
2. **Implement version detection** in your server configuration
3. **Apply base security headers** for all protocols
4. **Add protocol-specific optimizations** with appropriate conditionals
5. **Test with multiple protocol versions**:
   - Test with HTTP/1.1 clients
   - Test with HTTP/2 clients
   - Test with HTTP/3 clients (if supported)
   - Use browser DevTools Protocol selection (Chrome/Firefox)
6. **Monitor real-world protocol distribution** in your analytics

## Web Server Implementation Notes

This guide is deliberately web server agnostic. The fundamental principles apply regardless of your server technology, but implementation syntax will vary:

### Apache
Use `mod_headers` with `<If>` conditionals based on `%{HTTP_VERSION}` or `%{SERVER_PROTOCOL}`

### Nginx
Use `add_header` directives with `map` variables based on `$server_protocol`

### Caddy
Use `header` directives with conditionals based on protocol detection

### IIS
Use URL Rewrite module with server variables for protocol detection

### PHP-FPM/FastCGI
Protocol detection via `$_SERVER['SERVER_PROTOCOL']` regardless of front-end server

---
