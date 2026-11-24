# RouteRequest v3.2.0-beta.5 — Afterburner Edition for phpVMS v7 (DisposableTheme v3)

**Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue**

Official Repository: `ffpoke/phpvms7-RouteRequest`

> [!WARNING]
> **PHP 8.3.36+ STRICTLY ENFORCED (PHP 8.3.37+ RECOMMENDED)**
> Starting with **RouteRequest v2.4.0+** (and **RouteRequestServer v1.3.0+**), **PHP 8.3.36 or newer (`>= 8.3.36`, with PHP 8.3.37+ recommended)** with the standard built-in `sodium` extension is strictly required for maximum execution speed, JIT/OPcache efficiency, and cryptographic license security. Support for PHP 8.1 and PHP 8.2 has been permanently dropped. Installing this release on PHP 8.1 or PHP 8.2 will result in syntax/compile errors due to native PHP 8.3 features (`declare(strict_types=1)`, typed class constants, `#[\Override]` attributes, and `json_validate()`).

**Documentation:**
* **[Pilot User Guide](PILOT-GUIDE.md)** — How pilots submit requests, use the Great-Circle map, and handle Range Guardrail checks.
* **[Admin & Dispatch User Guide](ADMIN-GUIDE.md)** — How staff dispatch routes, run Bulk Subfleet Sync, repair old route times, and manage licenses.

---

## What's New in Version 3.0.1 — Automated Monthly Trickle-Down Engine & Full User Guides (PHP 8.3+)

1. **Automated Date-Gated Feature Rollout (Aligned to the 15th of Each Month):**
   * `RouteRequestServer v2.2.0` embeds Ed25519-signed date-gated feature flags into `/entitlements` so v3.0 features automatically trickle down on **Dec 15, 2026**, **Jan 15, 2027**, and **Feb 15, 2027** without requiring client reinstalls.
2. **Bundled Pilot & Admin User Guides (`PILOT-GUIDE.md` & `ADMIN-GUIDE.md`):**
   * Complete step-by-step manuals for both pilots and VA dispatch/admin staff included directly in the repository and module archives.
3. **Interactive Great-Circle Map, Auto-Block-Time Estimator & Aircraft Range Guardrail (v3.0.0+):**
   * Embedded Leaflet.js map with live NM distance, realistic cruise+taxi block times, and on-demand FlightAware & Flightradar24 verification when a route exceeds aircraft range.
4. **Easy Bulk Subfleet Sync + Saved Presets + `↩ Undo Last Batch` + `⏱ Old Route Time Estimator Fixer`:**
   * Searchable checkbox pills, named presets, 1-click batch undo, and 1-click repair for legacy `0h 00m` phpVMS flights.
5. **SaaS Growth (`RouteRequestServer v2.2.0`):**
   * **7-Day Free Executive Trial** (1 per domain), **Self-Service Domain Transfer & Billing Portal** (`/routerequest-portal`), and **Installed Fleet Version Telemetry**.

---

## Automated v3.0 Feature Unlock Timetable (15th-of-Month Drops)

| Rollout Phase & Date | Tier 3: Flagship ($4.99/mo or $59.99) | Tier 2: Executive ($2.99/mo or $39.99) | Tier 1: Basic (Free) |
| :--- | :--- | :--- | :--- |
| **Phase 1: Launch**<br>**Nov 20, 2026** | **All v3.0 features unlocked immediately** | **Interactive GC Map + Auto-Block-Time (Pilot & Admin `⚡ Auto-Est`)** + Self-Service Portal | **7-Day Free Executive Trial** + Tabbed UI + PHP 8.3 Engine |
| **Phase 2: Holiday Drop**<br>**Dec 15, 2026** | Early access to next-gen pre-releases | Unlocks **Easy Multi-Subfleet Sync (Checkbox Pills + Custom Multi-Airline)** & **Aircraft Range Guardrail** | Unlocks **Pilot Interactive GC Map & Block-Time Preview** |
| **Phase 3: Jan Drop**<br>**Jan 15, 2027** | Permanent Flagship exclusives | Unlocks **On-Demand FR24 & FlightAware Real-World Range Check** | Unlocks **Basic Aircraft Range Guardrail** (built-in ICAO warnings) |
| **Phase 4: Feb Drop**<br>**Feb 15, 2027** | Permanent Flagship exclusives | Full Executive feature set | Unlocks **Basic Subfleet Sync** (1 subfleet at a time, Same-Airline only) |
| **Permanent Flagship Exclusives** | **✦ PaxStudio Bridge & Auto-Updater**, **⏱ Old Route Time Fixer**, **⭐ Subfleet Sync Saved Presets & ↩ Undo Last Batch**, **🚀 Pre-Release Channel** | — | — |

---

## License Editions & Feature Matrix (3-Tier Cloud DRM)

| Feature / Quota | Tier 1: Basic Dispatch Edition (Free) | Tier 2: Executive Dispatch Edition (Paid) | Tier 3: Flagship Edition (Paid + PaxStudio) |
| :--- | :--- | :--- | :--- |
| **Pricing (USD)** | **FREE** (1-Click Claim) | **$2.99/mo** or **$39.99 Unlimited** *(7-Day Free Trial)* | **$4.99/mo** or **$59.99 Unlimited** |
| **Max Routes Per Request** | **12 routes** | **15 routes** | **15 routes** |
| **Max Round-Trip Pairs / Request** | **3 pairs** (6 flights) | **4 pairs** (8 flights) | **4 pairs** (8 flights) |
| **Weekly Route Creation Quota** | **28 routes / rolling 7 days** | Unlimited | Unlimited |
| **Local `IndexedDB` Airport Engine** | Included | Included | Included |
| **Pilot Cooldown & Max Open Limits** | Included | Included | Included |
| **Two-Layer Duplicate Checker + Bypass** | Included | Included | Included |
| **FlightAware Route/Altitude Matcher** | Locked (Manual entry) | Unlocked | Unlocked |
| **⚡ 1-Click Auto-Create** | Locked (Standard drawer) | Unlocked | Unlocked |
| **Server-Gated Discord Webhook Dispatch** | Locked | Unlocked | Unlocked |
| **Bulk Subfleet Sync (Presets & Undo)** | Basic (Unlocks Feb 15, 2027) | Multi-Subfleet (Unlocks Dec 15, 2026) | **Full Suite + Saved Presets & Undo** |
| **⏱ Old Route Time Estimator Fixer** | Locked | Locked | **Permanent Flagship Exclusive** |
| **✦ PaxStudio (`v2.22.0+`) Bridge & Auto-Updater** | Not Included | Not Included | **Unlocked & Pre-Licensed** |

---

## First-Time Installation & Setup Guide (Requires PHP 8.3.36+ / 8.3.37+ Recommended — No `.env` Editing Required)

1. **Verify Server PHP Version (`>= 8.3.36` Enforced / `8.3.37+` Recommended):** Ensure your phpVMS v7 server is running **PHP 8.3+** with `bcmath`, `curl`, `fileinfo`, `intl`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `sodium`, `xml`, and `zip`.
2. **Download the Release Archive:** Download **`RouteRequest-phpvms7-module-vX.Y.Z-DRM-Client.zip`** from the [Releases](https://github.com/ffpoke/phpvms7-RouteRequest/releases) Assets section.
3. **Extract into `/modules/RouteRequest`:** Extract so `module.json` sits at `/modules/RouteRequest/module.json`. *(Never leave `_BAK` folders inside `/modules/`.)*
4. **Clear Application Cache:** Go to **Admin -> Maintenance** and click **Clear Application Cache** (`php artisan optimize:clear`).
5. **Activate, Trial, or Upgrade In-Panel:** Open `/admin/routerequest` → **`🔑 License & Storefront`** tab to claim a Free Tier 1 key, start a **7-Day Free Executive Trial**, paste a manual key, or upgrade via **Square / PayPal**.

---

## How to Update RouteRequest

1. Ensure your server is running **PHP 8.3+**.
2. Open **Admin -> Route Requests** (`/admin/routerequest`), click **`↻ Check for Updates`**, and click **`⚡ 1-Click Install Update`**.
3. *(Manual Alternative):* Overwrite `/modules/RouteRequest` with the latest ZIP and click **Clear Application Cache** in **Admin -> Maintenance**.

---

## Full Changelog

### Version 3.2.0-beta.5 — Targeted Performance Optimization & Code Cleanup (Current Beta — PHP 8.3.36+ Enforced / 8.3.37+ Recommended)
> [!CAUTION]
> **EXPERIMENTAL PRE-RELEASE (TIER 3 FLAGSHIP ONLY):** Features in this beta build are under active testing. Use **↩ Revert to Last Stable Release** in `/admin/routerequest` anytime to roll back to Stable `v3.1.0`.

* **Fixed & Memoized `ClientSettings` (`Support/ClientSettings.php`):** Added `digest_enabled`, `digest_window_hours`, `digest_recipient_email`, and `last_digest_sent_at` to the `ClientSettings` persistence whitelist so Scheduled Batch Route Digest settings save cleanly, and added per-request static memoization to cut repeated settings DB queries to 1 per request.
* **Eliminated N+1 Query in `checkDuplicate()` (`RouteRequestController.php`):** Replaced per-row upvote count queries with a single batched `WHERE IN ... GROUP BY` query.
* **Per-Request Memoization in `DrmEntitlements::current()` (DRM Client):** Caches resolved entitlements in static memory for the duration of the HTTP request to avoid redundant cache/DB lookups.
* **Master Server Performance Overhaul (`RouteRequestServer v2.2.4`):** Memoized & cached `ensureLicensesTableExists()` / `ensureInstallsTableExists()` (eliminating ~25 `INFORMATION_SCHEMA` queries per API call), memoized `PaymentGatewayService::getSettings()` with clean empty-string clearing for `routerequest_min_version`, and removed redundant `require_once` blocks.

### Version 3.2.0-beta.4 — Duplicate Guard, Canned Notes, Native Cron Force-Update & RRPKG2 XChaCha20 (Current Beta — PHP 8.3.36+ Enforced / 8.3.37+ Recommended)
> [!CAUTION]
> **EXPERIMENTAL PRE-RELEASE (TIER 3 FLAGSHIP ONLY):** Features in this beta build are under active testing. Use **↩ Revert to Last Stable Release** in `/admin/routerequest` anytime to roll back to Stable `v3.1.0`.

* **Live Duplicate Route Detection & Community Merge (`duplicate_route_guard`):** Checks active schedules and open requests in real time as pilots select `DEP → ARR`, linking directly to existing flights or offering 1-click Community Queue upvoting.
* **Admin Dispatcher Canned Responses & Presets (`dispatch_canned_notes`):** One-click status + admin note presets inside `/admin/routerequest` for rapid dispatch turnaround.
* **Built-In phpVMS Cron Hooks (`Listeners/CronAutoMaintenanceListener.php`):** Native `CronHourly` automatic force-update (DRM Client, atomic cache lock, zero ACARS impact) when `RouteRequestServer v2.2.3` enforces a minimum version floor, plus native `CronDaily` Scheduled Batch Route Digest execution.
* **Upgraded Encrypted Standalone (`RRPKG2` + `RRX2` `XChaCha20-Poly1305` — Standalone Only):** Upgrades the encrypted Standalone package and all 11/11 `.rrx` vault modules to 24-byte-nonce `XChaCha20-Poly1305` + Ed25519 signatures (keyed with your existing `RR_STANDALONE_KEY`).
* **Staggered-Flagship `DropSchedule.php` (`2026.11.24-v3.2.0`):** Synchronized across `RouteRequest v3.2.0-beta.5` and `RouteRequestServer v2.2.3` (including `portal_key_diagnostics`).

### Version 3.2.0-beta.3 — Community Route Queue, Scheduled Batch Digest & UI Polish (Current Beta — PHP 8.3.36+ Enforced / 8.3.37+ Recommended)
> [!CAUTION]
> **EXPERIMENTAL PRE-RELEASE (TIER 3 FLAGSHIP ONLY):** Features in this beta build are under active testing. Use **↩ Revert to Last Stable Release** in `/admin/routerequest` anytime to roll back to Stable `v3.1.0`.

* **Community Route Queue & Pilot Upvoting (`community_queue`):** New **🔥 Community Queue** tab on `/routerequest` where pilots can upvote open requests (`+1` toggle, 1 vote per pilot, cannot upvote own request). Dispatchers see **`▲ N`** upvote pills and a **🔥 Top Upvoted** sort toggle in `/admin/routerequest`.
* **Scheduled Batch Route Digest (`daily_dispatch_digest` — `Support/DispatchDigestService.php` & `php artisan routerequest:send-digest`):** Compiles open queue counts, top upvoted routes, completed routes, and charters expiring within 7 days into a single Discord `#staff-dispatch` embed and/or admin email—complete with **👁 Preview Digest**, **📨 Send Digest Now**, and a copyable cPanel cron command in Dispatch Settings.
* **Pilot Frontend & Admin Panel Formatting Cleanup:** Tighter card padding, unified table header/cell alignment, cleaner filter toolbar, and sharper badge hierarchy while strictly preserving VA theme font inheritance.
* **Updated Encrypted `DropSchedule.php` (`2026.11.23-v3.2.0`) & 11/11 Encrypted `.rrx` Standalone Vault:** Adds `community_queue` and `daily_dispatch_digest` to the Ed25519-signed encrypted timetable (paired with `RouteRequestServer v2.2.1`) and encrypts `DispatchDigestService.rrx` into the Standalone `.rrpkg` vault.

### Version 3.2.0-beta.2 — Encrypted Standalone Vault (.rrx + .rrpkg) (Current Beta — PHP 8.3.36+ Enforced / 8.3.37+ Recommended)
> [!CAUTION]
> **EXPERIMENTAL PRE-RELEASE (TIER 3 FLAGSHIP ONLY):** Features in this beta build are under active testing. Use **↩ Revert to Last Stable Release** in `/admin/routerequest` anytime to roll back to Stable `v3.1.0`.

* **Option 1 — Native `libsodium` Encrypted `.rrx` Standalone Vault (`Support/VaultLoader.php` — Standalone Only):** 10 core Standalone business-logic modules are encrypted with RFC 8439 ChaCha20-Poly1305 (`RR_STANDALONE_KEY`) and Ed25519-signed into `Support/Vault/*.rrx`, decrypted in memory on boot, with automatic cleanup of legacy plaintext `.php` files.
* **Option 4 — Signed & Encrypted `.rrpkg` Standalone Distribution (Standalone Only):** First fully encrypted `.rrpkg` Standalone release (`RRPKG1` Ed25519 + ChaCha20-Poly1305 envelope), with forward-compatible `RRPKG2` (`XChaCha20-Poly1305`) decoder support added to `UpdateManager.php`.
* **Synchronized Cloud DRM Client (`v3.2.0-beta.5`):** Version-aligned with zero Standalone encryption/updater code, paired with `RouteRequestServer v2.2.0` (`DropSchedule 2026.11.22-v3.2.0`).

### Version 3.2.0-beta.1 — Flagship Pre-Release (Current Beta — PHP 8.3.36+ Enforced / 8.3.37+ Recommended)
> [!CAUTION]
> **EXPERIMENTAL PRE-RELEASE (TIER 3 FLAGSHIP ONLY):** Features in this beta build are under active testing. Use **↩ Revert to Last Stable Release** in `/admin/routerequest` anytime to roll back to Stable `v3.1.0`.

* **Ed25519-Signed & ChaCha20-Poly1305 Encrypted `DropSchedule.php` (`2026.11.22-v3.2.0`):** Feature unlock dates are now encrypted with RFC 8439 ChaCha20-Poly1305 and cryptographically signed with Ed25519—any tampering invalidates the signature and fails closed to Tier 1 Basic.
* **Subfleet Range & Runway Guardrail in Admin Generator (`subfleet_dispatch_guard`):** Cross-checks selected subfleets against route Great-Circle distance and minimum runway length (with automatic `runways` / `disposable_runways` DB lookup when present) inside `/admin/routerequest`.
* **Customer Portal Telemetry & License Health (`portal_telemetry_view` in `RouteRequestServer v2.2.0`):** Displays live heartbeat status, last ping timestamp, installed RouteRequest/PHP/PaxStudio versions, route usage, and Ed25519 DropSchedule integrity inside `/routerequest-portal`.
* **Final Unencrypted Standalone Build + Option 1/4 `.rrpkg` Receiver:** `v3.2.0-beta.5` is the final unencrypted Standalone release and pre-wires the Standalone-only `.rrpkg` encrypted updater decoder (`RR_STANDALONE_KEY` in `.env`) for encrypted `v3.2.0-beta.5+` builds.

### Version 3.1.0 — Full Stable Release (PHP 8.3.36+ Enforced / 8.3.37+ Recommended)
* **Unified Split Flight Chips + Collapsible Multi-Dest Drawer (`frontend/index.blade.php`):** Paired `[✈ Fly/Bid] [✦ PaxStudio] [🗺 SimBrief]` chips and a `▾ Show All X Flights` toggle for requests with >4 flights, with pure VA theme font inheritance (clean `v3.0.4` look).
* **SimBrief OFP Instant Dispatch Bridge (`simbrief_bridge`):** 1-click pre-filled SimBrief dispatch link on every completed route.
* **Live VATSIM ATC Map Overlay (`Support/VatsimOverlayService.php`):** 2-minute server-cached active VATSIM ATC pins toggleable directly on the Great-Circle Map.
* **PaxStudio + Flagship + DisposableBasic Guided Travel & Admin Move Queue (`Support/TravelDispatchService.php`):** Interactive Aircraft Picker (Cheapest/Closest, Last Used First, Not In Use Recently), Pilot Pay balance check, **Ask Admin to Move Me** flow with Discord `#staff-dispatch` alerts, and **Allow / Deny** controls in `/admin/routerequest` when Manual PIREP filing is disabled via `ManualPirepControl`.
* **Companion Modules 1-Click Installer & Auto-Updater (`Support/CompanionModuleManager.php`):** Detects and 1-click installs/updates **`DisposableBasic`** (`FatihKoz/DisposableBasic`) and **`ManualPirepControl`** (`parkho/ManualPirepControl`) with Zip-Slip protection and live `DisposableBasic` rate readout.
* **Admin Queue Filters, Live Search, Bulk Cleanup & Auto-Expiring Charters (`admin/index.blade.php` & `AdminController.php`):** Filter pills (`All`, `Pending`, `Completed`, `Round-Trip`), live search, 1-click 30-day resolved cleanup, and optional **Auto-Expire Charter (7–90 Days)** `end_date` selector in the Flight Generator.
* **Dual-Layer `RR-META` ZIP Header + `module.json` Edition & Downgrade Guard (`Support/UpdateManager.php`):** Strict edition separation between DRM Client and Afterburner Standalone, 1-click **↩ Revert to Last Stable Release** for pre-releases, and Standalone-only `.zip` URL updater with **🔍 Test ZIP URL** and local rollback snapshots.
* **Proprietary Source-Available License, California/US SaaS Terms & Privacy Policy (`LICENSE`, `TERMS.md`, `PRIVACY.md`):** Updated legal suite for **Alexander Leard d/b/a Regal Artistic Avenue** (California, USA) with CalOPPA & voluntary CCPA/CPRA protections, plus fail-open non-PII install/activation telemetry on DRM Client.
* **Synchronized Baked-In `DropSchedule.php` (`2026.11.21-v3.1.0`):** Aligned across `RouteRequest v3.2.0-beta.5` and `RouteRequestServer v2.2.0`.

### Version 3.0.7 — Great-Circle Map & Admin Auto-Est Cache Lookup Fix (PHP 8.3.36+ Enforced / 8.3.37+ Recommended)
* **Fixed Interactive Great-Circle Map & Admin `⚡ Auto-Est` Lookup (`frontend/index.blade.php` & `admin/index.blade.php`):** Exposed `getAirportById()` on both `RRAfterburnerCache` and `RRAfterburnerAdminCache`, added an `rr-cache-ready` event hook so the Leaflet Great-Circle map renders immediately once coordinates load, and cached `select2:select` airport coordinates as a fallback.

### Version 3.0.6 — Type Ratings & Form Readability Polish (PHP 8.3.36+ Enforced / 8.3.37+ Recommended)
* **Upgraded Signed-Off Type Ratings Tab (`Resources/views/frontend/index.blade.php`):** Replaced tiny `.small` badges with large, high-contrast `14px` sans-serif Type Rating pills (`[CODE] Aircraft Name`) on a frosted white surface (`rgba(255,255,255,0.92)`).
* **Consistent Frontend Data Typography:** Extended the readable sans-serif data font and larger badge sizing to the Great-Circle Map stats, Range Guardrail alerts, and form inputs so custom display fonts never shrink operational text.

### Version 3.0.5 — Frontend Readability & Display-Font Polish (PHP 8.3.36+ Enforced / 8.3.37+ Recommended)
* **High-Legibility Frontend History Table (`Resources/views/frontend/index.blade.php`):** Applied a crisp system sans-serif data font to flight chips, ICAO codes, timestamps, and Dispatch Response notes so custom geometric display fonts (such as *Possibility Bold*) remain on headers/tabs without cramping dense flight data.
* **Paired Split Flight Chips + Collapsible Multi-Dest Drawer:** Combined each flight's `Fly / Bid` and `✦ PaxStudio` links into unified split chips and automatically collapses requests with more than 4 flights behind a **▾ Show All X Flights** toggle.
* **Frosted High-Contrast Surface:** Added a frosted white backing (`rgba(255,255,255,0.90)`) and styled green callout boxes for Dispatch Responses so text stays sharp over transparent VA background images.

### Version 3.0.4 — Bugfix & Admin UI Polish Release (PHP 8.3.36+ Enforced / 8.3.37+ Recommended)
* **Fixed Old Route Time Estimator Fixer (`Support/RouteEstimatorService.php`):** Replaced invalid `al.code` SQL column select with phpVMS v7's native `al.iata`, added `deleted_at` soft-delete filtering, and added structured JSON error handling.
* **License & Storefront UI Polish (`Resources/views/admin/index.blade.php`):** Hides key-entry and Square/PayPal storefront cards on Standalone and Unlimited Flagship installs, prevents showing Tier 2 Executive as an upgrade to Flagship users, darkened `.text-muted` contrast across admin cards, and updated the header badge to `PHP 8.3.36+`.

### Version 3.0.3 — Immutable Baked-In Drop Schedule (PHP 8.3.36+ Enforced / 8.3.37+ Recommended)
* **Immutable `DropSchedule` (`Support/DropSchedule.php`):** Baked all tier drop dates directly into `final class DropSchedule` typed PHP constants in both `RouteRequest` and `RouteRequestServer v2.2.0` (no DB settings, no `.env` overrides, no admin editors — cannot be altered without a module update).
* **Clock-Spoof Resistant Auto-Unlock:** Client enforces baked-in drop dates against the Master Server's Ed25519-signed UTC timestamp (`signed_at`) intersected with server entitlements, so features auto-unlock automatically at 00:00 UTC on their scheduled dates.
* **Read-Only Admin Timetable:** Added a read-only **📅 Baked-In Feature Drop Timetable** inside `/admin/routerequest` (**🔑 License & Storefront** tab).

### Version 3.0.2 — Security Hardening Release (PHP 8.3.36+ Enforced / 8.3.37+ Recommended)
* **Zip-Slip Protection (`Support/UpdateManager.php`):** Added strict pre-extraction ZIP entry validation blocking path traversal (`..`), absolute paths, null bytes, and POSIX symlinks (`0xA000`) in both the RouteRequest and PaxStudio 1-click updaters, plus isolated staging extraction for PaxStudio.
* **Leaflet 1.9.4 Subresource Integrity (SRI):** Added official `sha256` SRI hashes (`integrity="sha256-..."`) to `leaflet.css` and `leaflet.js` CDN tags in `Resources/views/frontend/index.blade.php`.
* **Patched PHP 8.3 Enforcement:** Enforced minimum **PHP 8.3.36** in `composer.json`, `module.json`, controllers, and `UpdateManager` (with an in-panel **PHP 8.3.37+** upgrade recommendation banner).

### Version 3.0.1 — Afterburner Edition (PHP 8.3+)
* Wired automated server-side date-gated trickle-down feature flags (`Dec 15, 2026`, `Jan 15, 2027`, `Feb 15, 2027`) signed inside Ed25519 entitlements (`RouteRequestServer v2.2.0`).
* Added complete bundled **`PILOT-GUIDE.md`** and **`ADMIN-GUIDE.md`** documentation.

### Version 3.0.0 — Afterburner Edition (PHP 8.3+)
* Added **Interactive Great-Circle Map & Auto-Block-Time Estimator**, **Aircraft Range Guardrail + On-Demand FR24/FlightAware Check**, **Easy Bulk Subfleet Sync (Checkbox Pills + Saved Presets + Undo Last Batch)**, **Old Route Time Estimator Fixer**, **7-Day Free Executive Trial**, **Self-Service Domain Transfer & Billing Portal** (`/routerequest-portal`), and **Installed Fleet Version Telemetry** (`RouteRequestServer v2.0.0`).

### Version 2.9.0 — Afterburner Edition (PHP 8.3+)
* Converted all drawers into organized, persistent tabs across Admin, Pilot Frontend, and Master DRM Server (`RouteRequestServer v1.7.0`).

### Version 2.8.0 — Afterburner Edition (PHP 8.3+)
* Added the **Bulk Subfleet-to-Route Auto-Sync Engine** (`Support/SubfleetSyncService.php`) with Dry-Run Preview and custom single/multi-airline restrictions.

### Version 2.7.0 — Afterburner Edition (PHP 8.3+)
* Added **Ed25519 Signed Entitlements (`libsodium`)** and **Server-Gated Discord Webhook Dispatch** (`RouteRequestServer v1.6.0`).

### Version 2.6.0 — Afterburner Edition (PHP 8.3+)
* Added **`✓ Mark Completed (No More Routes)`**, **Two-Layer Duplicate Route Checker + Bypass**, and **PaxStudio Compatibility Floor (`v2.22.0`) & 1-Click Auto-Updater** (`RouteRequestServer v1.5.0`).

### Version 2.5.0 — Afterburner Edition (PHP 8.3+)
* Added **Discord Webhook Notifications**, **7-Day Subscription Renewal Banner**, **Pilot Cooldown & Max Open Limits**, and **Smart Promo Codes** (`RouteRequestServer v1.4.0`).

### Version 2.4.0 — Afterburner Edition (PHP 8.3+ Only)
* Dropped PHP 8.1/8.2 support; upgraded strictly to PHP 8.3+ (`strict_types=1`, typed constants, `#[\Override]`, `json_validate()`).

### Version 1.0.0 — 2.3.0
* Core Pilot Route Request portal, Quick Request widget, Admin Schedule Generator, Local `IndexedDB` Afterburner engine, GitHub 1-Click Self-Updater, 3-Tier Cloud DRM, and in-admin Square/PayPal Storefront.

---

## Credits & Attribution

**Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue**
