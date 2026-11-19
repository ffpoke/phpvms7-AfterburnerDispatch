# RouteRequest v2.4.0 — Afterburner Edition for phpVMS v7 (DisposableTheme v3)

**Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue**

Official Repository: `ffpoke/phpvms7-RouteRequest`

> [!WARNING]
> **PHP 8.3+ STRICT REQUIREMENT (PHP 8.1 & PHP 8.2 SUPPORT DROPPED)**
> Starting with **RouteRequest v2.4.0** (and **RouteRequestServer v1.3.0**), **PHP 8.3 or newer (`>= 8.3.0`) is strictly required** for maximum execution speed, JIT/OPcache efficiency, and modern runtime security. Support for PHP 8.1 and PHP 8.2 has been permanently dropped. Installing this release on PHP 8.1 or PHP 8.2 will result in syntax/compile errors due to native PHP 8.3 features (`declare(strict_types=1)`, typed class constants, `#[\Override]` attributes, and `json_validate()`).

**RouteRequest** is a commercial-grade Pilot Route Request and Automated Flight Schedule Dispatch system built for **phpVMS v7** (optimized for **DisposableTheme v3** and standard Bootstrap 5 / AdminLTE layouts). It bridges the gap between pilot-requested real-world/charter operations and Virtual Airline schedule creation—allowing pilots to request routes tied strictly to their signed-off **Type Ratings**, and enabling staff to generate verified Outbound, Return, and Multi-Destination flight schedules in seconds.

---

## What's New in Version 2.4.0 — Afterburner Edition (PHP 8.3+ Performance & Security Release)

1. **PHP 8.3+ Engine Optimization & Security Hardening (Drops PHP 8.1 & 8.2):**
   * Added `declare(strict_types=1);` across every PHP controller, provider, model, support engine, and form request to eliminate implicit runtime type coercion overhead.
   * Upgraded class constants to native PHP 8.3 **Typed Class Constants** (`public const string`, `public const int`).
   * Added native PHP 8.3 **`#[\Override]`** attributes on ServiceProviders, FormRequests, and Widgets for compile-time signature verification.
   * Replaced legacy JSON parsing checks on GitHub release feeds and Square/PayPal webhooks with PHP 8.3's zero-allocation **`json_validate()`** C function.
2. **In-Admin License & Storefront Center (`/admin/routerequest`):**
   * **Zero-FTP / Zero-`.env` Activation:** License keys are stored directly in your phpVMS database (`routerequest_client_settings`) via the collapsible **🔑 License & Storefront Center** drawer—no `.env` or FTP file editing required.
   * **1-Click Free Tier 1 Key Provisioning:** New Virtual Airlines can click **✓ 1-Click Claim Free Tier 1 Key (Instant)** inside `/admin/routerequest` to automatically generate and bind a free Basic key to their domain.
   * **Automated Square & Business PayPal Upgrades:** Upgrade directly inside `/admin/routerequest` to **Tier 2: Executive Dispatch Edition** ($2.99/mo rolling updates or $39.99 Unlimited) or **Tier 3: Flagship Edition** ($4.99/mo rolling updates or $59.99 Unlimited) via Square or PayPal with automatic in-place key injection upon payment.
3. **3-Tier Cloud DRM System & Route Quota Enforcement (`Support/DrmEntitlements.php`):**
   * **Tier 1: Basic Dispatch Edition (Free):** Capped at **12 routes per request**, **3 round-trip pairs per request**, and **28 routes per rolling 7-day week** (each round-trip counts as 2 routes). Includes all bugfixes and trickle-down features from Afterburner Versions.
   * **Tier 2: Executive Dispatch Edition (Paid):** Up to **15 routes per request**, **4 round-trip pairs per request**, **unlimited weekly routes**, plus full access to the **FlightAware Route & Altitude Auto-Matcher**, **⚡ 1-Click Auto-Create**, and trickle-down updates from Afterburner Tier 3.
   * **Tier 3: Flagship Edition (Paid + PaxStudio & Pre-Releases):** Includes all Executive features plus the **✦ PaxStudio Loadsheet & OFP Bridge** (`/paxstudio/loadsheet?flight_id=...`), **Pre-Release / Beta updates** via the 1-Click Self-Updater, and hand-selected new features from the next version.
4. **GitHub Releases Update Checker & ⚡ 1-Click Self-Updater (`Support/UpdateManager.php`):**
   * Automatically checks `ffpoke/phpvms7-RouteRequest` for new releases (Stable channel for Tier 1/2; Stable + Pre-Release channel for Tier 3 Flagship) and upgrades `/admin/routerequest` in one click.
5. **Local `IndexedDB` Airport Engine (`RouteRequestAfterburnerDB`) & Zero-Reload AJAX Dispatch:**
   * Offloads the phpVMS `airports` database to the pilot and admin browser for `< 1 ms` local autocomplete searches and sequential AJAX multi-destination flight creation.

---

## License Editions & Feature Matrix (3-Tier Cloud DRM)

*Note: Each generated leg counts as 1 route; a Round-Trip pair (Outbound + Return) counts as 2 routes toward per-request and weekly limits.*

| Feature / Quota | Tier 1: Basic Dispatch Edition (Free) | Tier 2: Executive Dispatch Edition (Paid) | Tier 3: Flagship Edition (Paid + PaxStudio) |
| :--- | :--- | :--- | :--- |
| **Pricing (USD)** | **FREE** (1-Click Claim) | **$2.99/mo** or **$39.99 Unlimited** | **$4.99/mo** or **$59.99 Unlimited** |
| **Max Routes Per Request** | **12 routes** | **15 routes** | **15 routes** |
| **Max Round-Trip Pairs / Request** | **3 pairs** (6 flights) | **4 pairs** (8 flights) | **4 pairs** (8 flights) |
| **Weekly Route Creation Quota** | **28 routes / rolling 7 days** | Unlimited | Unlimited |
| **Local `IndexedDB` Airport Engine** | Included | Included | Included |
| **FlightAware Route/Altitude Matcher** | Locked (Manual entry) | Unlocked | Unlocked |
| **⚡ 1-Click Auto-Create** | Locked (Standard drawer) | Unlocked | Unlocked |
| **Update Channel** | Stable Releases Only | Stable + Tier 3 Trickle-Down | **Stable + Pre-Release / Beta Access** |
| **✦ PaxStudio (`v2.25+`) Bridge** | Not Included | Not Included | **Unlocked & Pre-Licensed** |

---

## First-Time Installation & Setup Guide (Requires PHP 8.3+ — No `.env` Editing Required)

1. **Verify Server PHP Version (`>= 8.3.0`):**
   * Ensure your phpVMS v7 server is running **PHP 8.3** (with `bcmath`, `curl`, `fileinfo`, `intl`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `xml`, and `zip` extensions enabled).
2. **Download the Release Archive:**
   * Go to the [Releases](https://github.com/ffpoke/phpvms7-RouteRequest/releases) page and download the latest **`RouteRequest-phpvms7-module-vX.Y.Z-DRM-Client.zip`** file from the **Assets** section.
3. **Extract into `/modules/RouteRequest`:**
   * Upload and extract the ZIP into your phpVMS v7 `/modules` directory so that `module.json` is located at `/modules/RouteRequest/module.json`.
4. **Clear Application Cache:**
   * In your phpVMS Admin Panel, go to **Admin -> Maintenance** and click **Clear Application Cache** (`php artisan optimize:clear`).
5. **Activate or Upgrade Your License In-Panel:**
   * Open **Admin -> Route Requests** (`/admin/routerequest`) and click **`🔑 License & Storefront Center`** in the top-right header.
   * Click **`✓ 1-Click Claim Free Tier 1 Key (Instant)`** to activate your free license automatically, paste a manually issued key into the **License Key** box, or select **Pay via Square / PayPal** to upgrade to Executive or Flagship Edition with instant auto-activation.

---

## How to Update RouteRequest

1. Ensure your server is running **PHP 8.3+** before upgrading to `v2.4.0+`.
2. Open **Admin -> Route Requests** (`/admin/routerequest`).
3. Click **`↻ Check for Updates`** in the top-right header.
4. When a new release is detected, click **`⚡ 1-Click Install Update`** in the top banner to automatically download, extract, migrate, and clear caches.

---

## Full Changelog

### Version 2.4.0 — Afterburner Edition (Current Release — PHP 8.3+ Only)
* **Dropped PHP 8.1 & PHP 8.2 Support — Strictly PHP 8.3+ (`>= 8.3.0`):** Optimized the entire codebase for PHP 8.3 speed and security with `declare(strict_types=1);`, typed class constants, `#[\Override]` method attributes, and `json_validate()` payload validation.

### Version 2.3.0 — Afterburner Edition
* Added the collapsible **🔑 License & Storefront Center** drawer in `/admin/routerequest` with database-backed license key storage (`routerequest_client_settings`)—eliminating `.env` and FTP editing.
* Added **✓ 1-Click Claim Free Tier 1 Key (Instant)** domain-locked provisioning against the Master DRM Server.
* Added integrated **Square** and **Business PayPal** checkout flows for **Tier 2: Executive Dispatch Edition** ($2.99/mo or $39.99 Unlimited) and **Tier 3: Flagship Edition** ($4.99/mo or $59.99 Unlimited) with automatic in-place key injection upon payment.

### Version 2.2.0 — Afterburner Edition
* Introduced the **3-Tier Cloud DRM System & Quota Enforcement** (`Support/DrmEntitlements.php`): Tier 1 Basic Dispatch (12/req, 3 RT pairs, 28/week), Tier 2 Executive Dispatch (15/req, 4 RT pairs, Unlimited/week, FlightAware Auto-Matcher, 1-Click Auto-Create), and Tier 3 Flagship Edition (+ PaxStudio Bridge & Pre-Release channel).
* Added **PaxStudio (`v2.25.0+`)** detection and direct **✦ PaxStudio Loadsheet** dispatch links (`/paxstudio/loadsheet?flight_id=...`).

### Version 2.1.0 — Afterburner Edition
* Added **GitHub Releases Update Checker & ⚡ 1-Click Self-Updater** (`Support/UpdateManager.php`) pointed at `ffpoke/phpvms7-RouteRequest`.
* Added plain-text creator attribution (`Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue`) across all main frontend, widget, admin, and email views.

### Version 2.0.0 — Afterburner Edition
* Added **Local `IndexedDB` Airport & Airline Engine (`RouteRequestAfterburnerDB`)** with persistent browser storage (`< 1 ms` autocomplete searches) and automatic version/airport-hash cache invalidation.
* Added **Zero-Reload AJAX Multi-Destination Flight Creation**, batch `Flight` hydration (`hydrateGeneratedFlights()`), grouped SQL next-flight-number calculation, and non-blocking background emails (`app()->terminating()`).

### Version 1.0.0 — 1.10.1
* Built Pilot Route Request frontend (`/routerequest`), Quick Request Blade widget (`<x-routerequest-form />`), Admin Schedule Generator drawer, FlightAware route/altitude auto-matcher, self-healing `SchemaHelper`, and mobile/tablet responsive layouts.

---

## Credits & Attribution

**Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue**
