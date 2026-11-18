# RouteRequest v2.2.0 — Afterburner Edition for phpVMS v7 (DisposableTheme v3)

**Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue**

Official Repository: `ffpoke/phpvms7-RouteRequest`

## What's New in Version 2.2.0 — Afterburner Edition
1. **3-Tier Cloud DRM System & Route Quota Enforcement (`Support/DrmEntitlements.php`):**
   - **Tier 1: Basic Dispatch Edition (Free):** Capped at **12 routes per request**, **3 round-trip pairs per request**, and **28 routes per rolling 7-day week** (each round-trip counts as 2 routes). Locks the FlightAware Auto-Matcher and 1-Click Auto-Create.
   - **Tier 2: Executive Dispatch Edition (Paid):** Up to **15 routes per request**, **4 round-trip pairs per request**, **unlimited weekly routes**, and unlocks both the FlightAware Route/Altitude Auto-Matcher and 1-Click Auto-Create.
   - **Tier 3: Flagship Edition (Paid + PaxStudio & Pre-Releases):** Includes all Executive features plus **PaxStudio Loadsheet & OFP Bridge** (`/paxstudio/loadsheet?flight_id=...`) and access to **Pre-Release / Beta updates** via the 1-Click Updater.
2. **GitHub Releases Update Checker & ⚡ 1-Click Self-Updater (`Support/UpdateManager.php`):**
   - Checks `ffpoke/phpvms7-RouteRequest` on GitHub Releases (Stable channel for Tier 1/2; Stable + Pre-Release channel for Tier 3 Flagship).
3. **Creator Attribution:**
   - Displays plain-text creator attribution (`Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue`) at the bottom of all main pages.
4. **Local `IndexedDB` Airport Engine (`RouteRequestAfterburnerDB`) & Zero-Reload AJAX Dispatch:**
   - Instant `< 1 ms` local airport autocomplete and zero-reload AJAX multi-destination flight creation.
