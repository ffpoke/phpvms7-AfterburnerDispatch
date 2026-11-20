# RouteRequest v2.7.0 — Afterburner Edition for phpVMS v7 (DisposableTheme v3)

**Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue**

Official Repository: `ffpoke/phpvms7-RouteRequest`

> [!WARNING]
> **PHP 8.3+ STRICT REQUIREMENT (PHP 8.1 & PHP 8.2 SUPPORT DROPPED)**
> **RouteRequest v2.4.0+ / v2.7.0** (and **RouteRequestServer v1.3.0+ / v1.6.0**) strictly require **PHP 8.3 or newer (`>= 8.3.0`)** with the standard built-in `sodium` extension for maximum speed and cryptographic license security.

---

## What's New in Version 2.7.0 — Afterburner Edition (PHP 8.3+ Security Hardening)

1. **Ed25519 Cryptographically Signed Entitlement Tokens (`sodium`):**
   * `RouteRequestServer v1.6.0` signs every `/entitlements` response with a private Ed25519 key using PHP 8.3's native `libsodium` extension.
   * `RouteRequest v2.7.0` verifies the detached Ed25519 signature and timestamp against the embedded public key, blocking forged or replayed license responses with zero extra host extensions required.
2. **Server-Gated Discord Webhook Dispatch (`/api/routerequest-drm/discord/dispatch`):**
   * Rich Discord embed formatting and delivery for `#staff-dispatch` and `#new-schedules` are now verified and dispatched directly by your Master DRM Server for active Tier 2 Executive and Tier 3 Flagship licenses.

---

## Credits & Attribution

**Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue**
