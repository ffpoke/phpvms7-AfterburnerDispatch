# RouteRequest v2.6.0 — Afterburner Edition for phpVMS v7 (DisposableTheme v3)

**Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue**

Official Repository: `ffpoke/phpvms7-RouteRequest`

> [!WARNING]
> **PHP 8.3+ STRICT REQUIREMENT (PHP 8.1 & PHP 8.2 SUPPORT DROPPED)**
> **RouteRequest v2.4.0+ / v2.6.0** (and **RouteRequestServer v1.3.0+ / v1.5.0**) strictly require **PHP 8.3 or newer (`>= 8.3.0`)** for maximum speed and security. PHP 8.1 and PHP 8.2 are no longer supported.

---

## What's New in Version 2.6.0 — Afterburner Edition (PHP 8.3+)

1. **`✓ Mark Completed (No More Routes)` Button & Max-Cap Highlight:**
   * If staff accidentally click **⚡ + Create Flight(s) & Add Another Destination** or hit the per-request route cap (12/12 or 15/15), a dedicated **`✓ Mark Completed (No More Routes)`** button appears (and pulses with a green highlight when the cap is reached) to finalize the request via AJAX, dispatch pilot email & Discord `#new-schedules` notifications, and collapse the drawer without creating another flight.
2. **Two-Layer Duplicate Route Checker + Different-Routing Bypass:**
   * Before generating any flight, `AdminController::generateFlight()` checks both the current request and your global phpVMS `flights` table for `DEP → ARR` (and `ARR → DEP` when the Return Flight checkbox is checked) in a single optimized SQL query.
   * If a duplicate is found, staff see the existing flight number(s) and route string alongside a **`⚠ Bypass Duplicate Check (Different Routing / Alternate Flight)`** checkbox to proceed when creating an alternate routing.
3. **PaxStudio Compatibility Floor (`v2.22.0`) & 1-Click Automatic Minor/Major Updater (Tier 3 Flagship):**
   * Reads `/modules/PaxStudio/module.json`, warns if the installed PaxStudio build is older than the minimum compatible floor (`v2.22.0`), and enables 1-click automatic minor and major updates proxied securely from your Master DRM Server.
4. **PHP 8.3+ PSR-4 Optimization:**
   * Stripped redundant runtime `class_exists` / `require_once` guards from controllers for cleaner OPcache execution, and excluded personal `push-to-github.*` scripts from automated GitHub Release ZIPs.

---

## Credits & Attribution

**Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue**
