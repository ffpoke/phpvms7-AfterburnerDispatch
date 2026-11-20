# RouteRequest v2.9.0 — Afterburner Edition for phpVMS v7 (DisposableTheme v3)

**Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue**

Official Repository: `ffpoke/phpvms7-RouteRequest`

> [!WARNING]
> **PHP 8.3+ STRICT REQUIREMENT (PHP 8.1 & PHP 8.2 SUPPORT DROPPED)**
> Starting with **RouteRequest v2.4.0+** (and **RouteRequestServer v1.3.0+**), **PHP 8.3 or newer (`>= 8.3.0`)** with the standard built-in `sodium` extension is strictly required for maximum execution speed, JIT/OPcache efficiency, and cryptographic license security. Support for PHP 8.1 and PHP 8.2 has been permanently dropped. Installing this release on PHP 8.1 or PHP 8.2 will result in syntax/compile errors due to native PHP 8.3 features (`declare(strict_types=1)`, typed class constants, `#[\Override]` attributes, and `json_validate()`).

**RouteRequest** is a commercial-grade Pilot Route Request and Automated Flight Schedule Dispatch system built for **phpVMS v7** (optimized for **DisposableTheme v3** and standard Bootstrap 5 / AdminLTE layouts). It bridges the gap between pilot-requested real-world/charter operations and Virtual Airline schedule creation—allowing pilots to request routes tied strictly to their signed-off **Type Ratings**, and enabling staff to generate verified Outbound, Return, and Multi-Destination flight schedules in seconds.

---

## What's New in Version 2.9.0 — Afterburner Edition (PHP 8.3+)

1. **Organized Tabbed UI Throughout (`/admin/routerequest`, `/routerequest`, `/admin/routerequest-server`):**
   * **Admin Panel Tabs:** Replaced stacked collapsible drawers with 4 persistent, theme-agnostic tabs: **📋 Route Requests** (with live pending badge), **✈ Bulk Subfleet Sync**, **⚙ Dispatch Settings & Discord**, and **🔑 License & Storefront**. Active tab state is remembered across reloads (`localStorage`), and `?open_gen=` deep-links automatically focus the Route Requests tab.
   * **Pilot Frontend Tabs:** Organized `/routerequest` into 3 full-width tabs: **✈ Request a New Route**, **📋 My Requested Routes** (with count badge), and **🎖 My Signed-Off Type Ratings** (with count badge).
   * **Master DRM Server Tabs (`RouteRequestServer v1.7.0`):** Keeps the 4-card Revenue & License Telemetry bar pinned at the top with 4 tabs: **🔑 Licenses & Issue**, **🏷 Smart Promo Codes**, **🧾 Recent Orders**, and **⚙ Pricing, Square, PayPal & PaxStudio APIs**.
2. **Bulk Subfleet-to-Route Auto-Sync Engine (`Support/SubfleetSyncService.php`):**
   * Select one or multiple newly added subfleets, pick a match rule (**Shared Type Rating**, **Same Subfleet Type Code**, or **All Flights**), and apply **Airline Restrictions** (**Subfleet's Own Airline**, **Custom Single/Multiple VA Airlines**, or **All VA Airlines**).
   * Run a **🔍 Dry-Run Preview** across your entire phpVMS `flights` table before executing a chunked **⚡ 1-Click Sync** (`INSERT IGNORE` into `flight_subfleet`) that never removes existing subfleet assignments.
3. **Ed25519 Signed Entitlements & Server-Gated Discord Dispatch:**
   * Verifies detached `libsodium` Ed25519 cryptographic signatures on `/entitlements` responses with a 15-minute freshness window.
   * Server-gated Discord Webhook embeds (`#staff-dispatch` and `#new-schedules`) verified and dispatched via `RouteRequestServer`.
4. **Two-Layer Duplicate Route Checker, `✓ Mark Completed (No More Routes)` & PaxStudio Auto-Updater:**
   * Checks both the current request and your global phpVMS `flights` table (`DEP → ARR` and optional `ARR → DEP`) in a single SQL query with a **⚠ Bypass Duplicate Check (Different Routing)** checkbox.
   * Includes a pulsing **`✓ Mark Completed (No More Routes)`** AJAX button when hitting per-request caps, plus a **PaxStudio Compatibility Floor (`v2.22.0`)** and 1-click minor/major auto-updater for Tier 3 Flagship VAs.

---

## License Editions & Feature Matrix (3-Tier Cloud DRM)

*Note: Each generated leg counts as 1 route; a Round-Trip pair (Outbound + Return) counts as 2 routes toward per-request and weekly limits. All tiers include bugfixes and trickle-down features from Afterburner Versions.*

| Feature / Quota | Tier 1: Basic Dispatch Edition (Free) | Tier 2: Executive Dispatch Edition (Paid) | Tier 3: Flagship Edition (Paid + PaxStudio) |
| :--- | :--- | :--- | :--- |
| **Pricing (USD)** | **FREE** (1-Click Claim) | **$2.99/mo** or **$39.99 Unlimited** | **$4.99/mo** or **$59.99 Unlimited** |
| **Max Routes Per Request** | **12 routes** | **15 routes** | **15 routes** |
| **Max Round-Trip Pairs / Request** | **3 pairs** (6 flights) | **4 pairs** (8 flights) | **4 pairs** (8 flights) |
| **Weekly Route Creation Quota** | **28 routes / rolling 7 days** | Unlimited | Unlimited |
| **Local `IndexedDB` Airport Engine** | Included | Included | Included |
| **Bulk Subfleet-to-Route Auto-Sync** | Included | Included | Included |
| **Pilot Cooldown & Max Open Limits** | Included | Included | Included |
| **Two-Layer Duplicate Checker + Bypass** | Included | Included | Included |
| **FlightAware Route/Altitude Matcher** | Locked (Manual entry) | Unlocked | Unlocked |
| **⚡ 1-Click Auto-Create** | Locked (Standard drawer) | Unlocked | Unlocked |
| **Discord Webhook Dispatch Notifications** | Locked | Unlocked | Unlocked |
| **Update Channel** | Stable Releases Only | Stable + Tier 3 Trickle-Down | **Stable + Pre-Release / Beta Access** |
| **✦ PaxStudio (`v2.22.0+`) Bridge & Auto-Updater** | Not Included | Not Included | **Unlocked & Pre-Licensed** |

---

## First-Time Installation & Setup Guide (Requires PHP 8.3+ — No `.env` Editing Required)

1. **Verify Server PHP Version (`>= 8.3.0`):**
   * Ensure your phpVMS v7 server is running **PHP 8.3+** with `bcmath`, `curl`, `fileinfo`, `intl`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `sodium`, `xml`, and `zip` extensions enabled.
2. **Download the Release Archive:**
   * Go to the [Releases](https://github.com/ffpoke/phpvms7-RouteRequest/releases) page and download the latest **`RouteRequest-phpvms7-module-vX.Y.Z-DRM-Client.zip`** file from the **Assets** section.
3. **Extract into `/modules/RouteRequest`:**
   * Upload and extract the ZIP into your phpVMS v7 `/modules` directory so that `module.json` is located at `/modules/RouteRequest/module.json`.
   * *Important:* Never leave old backup folders (such as `RouteRequest_BAK`) inside `/modules/`, as Laravel Modules scans every subfolder in `/modules/`.
4. **Clear Application Cache:**
   * In your phpVMS Admin Panel, go to **Admin -> Maintenance** and click **Clear Application Cache** (`php artisan optimize:clear`).
   * *Self-Healing Database:* `SchemaHelper` automatically creates the `route_requests` and `routerequest_client_settings` tables/columns the first time you open `/routerequest` or `/admin/routerequest`.
5. **Activate or Upgrade Your License In-Panel (No `.env` Editing):**
   * Open **Admin -> Route Requests** (`/admin/routerequest`) and click the **`🔑 License & Storefront`** tab.
   * Click **`✓ 1-Click Claim Free Tier 1 Key (Instant)`** to activate your free license automatically, paste a manually issued key into the **License Key** box, or select **Pay via Square / PayPal** (with optional Promo Code) to upgrade to Executive or Flagship Edition with instant auto-activation.

---

## How to Update RouteRequest

1. Ensure your server is running **PHP 8.3+** before upgrading to `v2.4.0+`.
2. Open **Admin -> Route Requests** (`/admin/routerequest`).
3. Click **`↻ Check for Updates`** in the top-right header.
4. When a new release is detected, click **`⚡ 1-Click Install Update`** in the top banner to automatically download, extract, migrate, and clear caches.
5. *(Manual Update Alternative):* Download the latest ZIP from [Releases](https://github.com/ffpoke/phpvms7-RouteRequest/releases), overwrite `/modules/RouteRequest`, and click **Clear Application Cache** (`php artisan optimize:clear`) in **Admin -> Maintenance**.

---

## Full Changelog

### Version 2.9.0 — Afterburner Edition (Current Release — PHP 8.3+)
* **Organized Tabbed UI Throughout:** Converted all stacked drawers into persistent, theme-agnostic tabs across **Admin (`/admin/routerequest`)**, **Pilot Frontend (`/routerequest`)**, and **Master DRM Server (`RouteRequestServer v1.7.0`)** with `localStorage` tab persistence.

### Version 2.8.0 — Afterburner Edition (PHP 8.3+)
* **Bulk Subfleet-to-Route Auto-Sync Engine (`Support/SubfleetSyncService.php`):** Added a 1-click tool with **Dry-Run Preview** to attach one or multiple newly added subfleets to all matching phpVMS flights by Shared Type Rating, Subfleet Type Code, or Airline Scope—with custom single/multi-airline restrictions.

### Version 2.7.0 — Afterburner Edition (PHP 8.3+)
* **Ed25519 Signed Entitlements (`libsodium`):** `RouteRequestServer v1.6.0` cryptographically signs `/entitlements` responses with a private Ed25519 key; `RouteRequest v2.7.0` verifies the detached signature and 15-minute timestamp window against the embedded public key.
* **Server-Gated Discord Webhook Dispatch:** Moved `#staff-dispatch` and `#new-schedules` Discord embed assembly and dispatch to `RouteRequestServer` for active Tier 2/3 keys.

### Version 2.6.0 — Afterburner Edition (PHP 8.3+)
* **`✓ Mark Completed (No More Routes)` Button & Max-Cap Highlight:** Added a pulsing AJAX finalize button inside the generator drawer when routes are already created or per-request caps (12/12 or 15/15) are reached.
* **Two-Layer Duplicate Route Checker + Bypass:** Single-query check across the current request and global phpVMS `flights` table (`DEP → ARR` and optional `ARR → DEP`) with a **⚠ Bypass Duplicate Check (Different Routing)** checkbox.
* **PaxStudio Compatibility Floor (`v2.22.0`) & 1-Click Auto-Updater:** Added installed version checking and 1-click minor/major updates via `RouteRequestServer v1.5.0`.
* **PHP 8.3 PSR-4 Optimization:** Stripped redundant runtime `class_exists`/`require_once` guards from controllers and excluded personal push scripts from automated GitHub Release ZIPs.

### Version 2.5.0 — Afterburner Edition (PHP 8.3+)
* **Discord Webhook Dispatch Notifications:** Added `#staff-dispatch` and `#new-schedules` Discord webhook settings and test button (Tier 2 Executive & Tier 3 Flagship).
* **7-Day Subscription Renewal Banner & 1-Click Renew:** Added an automatic renewal warning banner with 1-click Square/PayPal renewal buttons for monthly plans.
* **Pilot Request Cooldown & Max Open Requests Limits:** Added configurable per-pilot open request caps and minute cooldowns (`Support/ClientSettings.php`).
* **Smart Promo / Discount Codes:** Added promo code input to the Storefront drawer backed by `RouteRequestServer v1.4.0` (which also added Square/PayPal API Test buttons, 4-card Revenue & License Telemetry, and automated `CronDaily` expiry reminders).

### Version 2.4.0 — Afterburner Edition (PHP 8.3+ Only)
* **Dropped PHP 8.1 & PHP 8.2 Support — Strictly PHP 8.3+ (`>= 8.3.0`):** Optimized the entire codebase with `declare(strict_types=1);`, typed class constants, `#[\Override]` attributes, and native `json_validate()` payload validation.

### Version 2.3.0 — Afterburner Edition
* Added the **🔑 License & Storefront Center** in `/admin/routerequest` with database-backed license storage (`routerequest_client_settings`)—eliminating `.env` and FTP editing.
* Added **✓ 1-Click Claim Free Tier 1 Key (Instant)** domain-locked provisioning and integrated **Square** & **Business PayPal** checkout flows (`RouteRequestServer v1.2.0`).

### Version 2.2.0 — Afterburner Edition
* Introduced the **3-Tier Cloud DRM System & Quota Enforcement** (`Support/DrmEntitlements.php`) and **PaxStudio (`v2.25.0+`)** Loadsheet Bridge links (`/paxstudio/loadsheet?flight_id=...`).

### Version 2.1.0 — Afterburner Edition
* Added **GitHub Releases Update Checker & ⚡ 1-Click Self-Updater** (`Support/UpdateManager.php`) pointed at `ffpoke/phpvms7-RouteRequest` and plain-text creator attribution across all views.

### Version 2.0.0 — Afterburner Edition
* Added **Local `IndexedDB` Airport & Airline Engine (`RouteRequestAfterburnerDB`)** (`< 1 ms` autocomplete), **Zero-Reload AJAX Multi-Destination Flight Creation**, batch `Flight` hydration, and non-blocking background emails (`app()->terminating()`).

### Version 1.0.0 — 1.10.1
* Built Pilot Route Request frontend (`/routerequest`), Quick Request Blade widget (`<x-routerequest-form />`), Admin Schedule Generator drawer, FlightAware route/altitude auto-matcher, self-healing `SchemaHelper`, and mobile/tablet responsive layouts.

---

## Credits & Attribution

**Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue**
