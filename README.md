# RouteRequest v3.1.0-beta.7 — Afterburner Edition for phpVMS v7 (DisposableTheme v3)

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
   * `RouteRequestServer v2.0.7` embeds Ed25519-signed date-gated feature flags into `/entitlements` so v3.0 features automatically trickle down on **Dec 15, 2026**, **Jan 15, 2027**, and **Feb 15, 2027** without requiring client reinstalls.
2. **Bundled Pilot & Admin User Guides (`PILOT-GUIDE.md` & `ADMIN-GUIDE.md`):**
   * Complete step-by-step manuals for both pilots and VA dispatch/admin staff included directly in the repository and module archives.
3. **Interactive Great-Circle Map, Auto-Block-Time Estimator & Aircraft Range Guardrail (v3.0.0+):**
   * Embedded Leaflet.js map with live NM distance, realistic cruise+taxi block times, and on-demand FlightAware & Flightradar24 verification when a route exceeds aircraft range.
4. **Easy Bulk Subfleet Sync + Saved Presets + `↩ Undo Last Batch` + `⏱ Old Route Time Estimator Fixer`:**
   * Searchable checkbox pills, named presets, 1-click batch undo, and 1-click repair for legacy `0h 00m` phpVMS flights.
5. **SaaS Growth (`RouteRequestServer v2.0.7`):**
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

### Version 3.1.0-beta.7 — Legal Entity DBA Correction (Current Beta — PHP 8.3.36+ Enforced / 8.3.37+ Recommended)
* **Corrected Business DBA Attribution (`LICENSE`, `TERMS.md`, `PRIVACY.md`):** Updated legal entity attribution to **`Alexander Leard d/b/a Regal Artistic Avenue`** (removed Fantasy Air VA from business DBA legal notices as Fantasy Air VA is a personal project).

### Version 3.1.0-beta.6 — California & US Small Business Privacy, EULA & Data Protection Policy (PHP 8.3.36+ Enforced / 8.3.37+ Recommended)
* **California & US Law EULA & SaaS Terms (`LICENSE`, `TERMS.md`):** Updated governing law to the State of California & US federal law, added California Automatic Renewal Law (ARL) subscription disclosures, COPPA eligibility, liability cap, and small business attribution (`Alexander Leard d/b/a Regal Artistic Avenue`).
* **Added `PRIVACY.md` (Data Storage, Security & Future Protection Policy):** Full CalOPPA compliance, voluntary CCPA/CPRA rights (Right to Know, Delete, Correct, Non-Discrimination, No Sale/Sharing), TLS/Ed25519/PCI-DSS security documentation, retention/deletion timelines, and future-feature privacy-by-design commitments.

### Version 3.1.0-beta.5 — Proprietary Source-Available License, SaaS Terms & Install Telemetry (PHP 8.3.36+ Enforced / 8.3.37+ Recommended)
* **Relicensed to Proprietary Source-Available EULA (`LICENSE`, `TERMS.md`, `composer.json`):** Replaced MIT with a Proprietary Source-Available EULA (`"license": "proprietary"`) and Cloud DRM SaaS Terms of Service protecting local/server features from DRM stripping, unauthorized redistribution, and key sharing.
* **Fail-Open Install & Activation Telemetry (DRM Client Only — `Support/InstallTelemetry.php`):** Automatically reports non-PII installation metadata (site domain, VA name, version, PHP version, and activated/unactivated status) to `RouteRequestServer v2.0.7` on install, activation, and 24-hour heartbeats without disrupting offline/local installs. Standalone remains 100% zero-DRM-server-calls.

### Version 3.1.0-beta.4 — Unified Split Chips, SimBrief & VATSIM Bridges, Queue Filters & DropSchedule v3.1 (PHP 8.3.36+ Enforced / 8.3.37+ Recommended)
* **Unified Split Flight Chips + Collapsible Multi-Dest Drawer (`frontend/index.blade.php`):** Restored paired `[✈ Fly/Bid] [✦ PaxStudio] [🗺 SimBrief]` chips and the `▾ Show All X Flights` toggle with pure VA theme font inheritance (no hardcoded font or frosted surface overrides).
* **SimBrief OFP Instant Dispatch Chip (`simbrief_bridge`):** 1-click pre-filled SimBrief dispatch link on every completed route.
* **Live VATSIM ATC Overlay (`Support/VatsimOverlayService.php`):** 2-minute server-cached VATSIM ATC pins toggleable directly on the Great-Circle Map.
* **Admin Queue Filters, Live Search & Bulk Cleanup (`admin/index.blade.php`):** Status filter pills, instant search, and 1-click 30-day resolved request cleanup, plus `end_date` Auto-Expiring Charter support.
* **Updated Baked-In `DropSchedule.php` (`2026.11.21-v3.1.0`):** Synchronized across `RouteRequest v3.1.0-beta.7` and `RouteRequestServer v2.0.7`.

### Version 3.1.0-beta.3 — Native Theme Look Restoration + Full v3.1 Beta Stack (PHP 8.3.36+ Enforced / 8.3.37+ Recommended)
* **Reverted `v3.0.5` & `v3.0.6` Frontend Visual/Font Overrides (`Resources/views/frontend/index.blade.php`):** Restored the clean `v3.0.4` frontend layout, badges, Type Ratings tab, and full VA theme font inheritance (no hardcoded sans-serif overrides or frosted card backings) so custom VA fonts render naturally across `/routerequest`.
* **Preserved All `v3.0.7` & `v3.1.0-beta.1 / beta.2` Features:** Keeps the Great-Circle Map coordinate lookup fix (`getAirportById`), PaxStudio + Flagship + DisposableBasic Guided Travel & Admin Move Queue, Companion Modules 1-Click Installer/Updater (`DisposableBasic` & `ManualPirepControl`), Dual-Layer ZIP Header + `module.json` Edition/Downgrade Guard, Standalone `.zip` URL Updater with **🔍 Test ZIP URL** and local rollback snapshots, and Discord `#staff-dispatch` Move Request alerts.

### Version 3.1.0-beta.2 — Flagship Pre-Release Hardening (PHP 8.3.36+ Enforced / 8.3.37+ Recommended)
* **Dual-Layer ZIP Header + `module.json` Edition & Downgrade Guard (`Support/UpdateManager.php`):** Every generated ZIP is stamped with a native ZIP archive comment header (`RR-META|edition=...|version=...|min_php=8.3.36`) plus ``"edition": "drm-client"`` in `module.json`. Blocks accidental Standalone ↔ DRM Client cross-flashes and accidental downgrades before extraction.
* **Strict Edition Separation:** DRM Client builds contain **zero** direct `.zip` URL install routes or UI; Standalone builds include **🔍 Test ZIP URL** pre-flight inspection, automatic local pre-update backup snapshots (`storage/app/rr_backups/`, keeping last 2), and local snapshot rollback.
* **Discord `#staff-dispatch` Ping on Pilot Move Requests (`Support/DiscordNotifier.php`):** Automatically alerts staff in Discord whenever a pilot with insufficient Pilot Pay submits an Admin Move Request.
* **Live `DisposableBasic` Rate Readout:** Displays the active JumpSeat and Aircraft Transfer `$/NM` rates inside **🧩 Companion Modules & Moves**.

### Version 3.1.0-beta.1 — Flagship Pre-Release (PHP 8.3.36+ Enforced / 8.3.37+ Recommended)
> [!CAUTION]
> **EXPERIMENTAL PRE-RELEASE (TIER 3 FLAGSHIP ONLY):** Features in this beta build are under active testing. Use the **↩ Revert to Last Stable Release** button in `/admin/routerequest` anytime to roll back to Stable `v3.0.7`.

* **PaxStudio + Flagship + DisposableBasic Guided Travel & Admin Move Queue (`Support/TravelDispatchService.php`):** When PaxStudio is installed, Flagship is active, DisposableBasic is enabled, and Manual PIREP filing is disabled via `ManualPirepControl`, pilots launching a flight without being positioned (or without an eligible aircraft at DEP) get an interactive Aircraft Picker sorted by **Cheapest/Closest**, **Last Used First**, or **Not In Use Recently**. Checks Pilot Pay balance, blocks travel if funds are insufficient, and lets pilots submit an **Ask Admin to Move Me** request with **Allow / Deny** controls in `/admin/routerequest`.
* **Companion Modules 1-Click Installer & Updater (`Support/CompanionModuleManager.php`):** Detects and 1-click installs/updates **`DisposableBasic`** (`FatihKoz/DisposableBasic`) and **`ManualPirepControl`** (`parkho/ManualPirepControl`) with Zip-Slip protection, reminding admins to configure each module in its own settings panel so other modules remain intact.
* **↩ 1-Click Revert to Last Stable Release (`Support/UpdateManager.php`):** Automatic pre-release detection with 1-click rollback to the latest Stable GitHub Release.
* **Standalone Direct `.zip` Updater URL:** Added a Standalone-only HTTPS `.zip` updater inside **🔑 License & Storefront** (disabled on DRM Client).
* **Disabled GitHub Actions Auto-Release ZIP Builds:** `.github/workflows/release.yml` now performs validation only and no longer auto-generates Release ZIPs.

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
* **Immutable `DropSchedule` (`Support/DropSchedule.php`):** Baked all tier drop dates directly into `final class DropSchedule` typed PHP constants in both `RouteRequest` and `RouteRequestServer v2.0.7` (no DB settings, no `.env` overrides, no admin editors — cannot be altered without a module update).
* **Clock-Spoof Resistant Auto-Unlock:** Client enforces baked-in drop dates against the Master Server's Ed25519-signed UTC timestamp (`signed_at`) intersected with server entitlements, so features auto-unlock automatically at 00:00 UTC on their scheduled dates.
* **Read-Only Admin Timetable:** Added a read-only **📅 Baked-In Feature Drop Timetable** inside `/admin/routerequest` (**🔑 License & Storefront** tab).

### Version 3.0.2 — Security Hardening Release (PHP 8.3.36+ Enforced / 8.3.37+ Recommended)
* **Zip-Slip Protection (`Support/UpdateManager.php`):** Added strict pre-extraction ZIP entry validation blocking path traversal (`..`), absolute paths, null bytes, and POSIX symlinks (`0xA000`) in both the RouteRequest and PaxStudio 1-click updaters, plus isolated staging extraction for PaxStudio.
* **Leaflet 1.9.4 Subresource Integrity (SRI):** Added official `sha256` SRI hashes (`integrity="sha256-..."`) to `leaflet.css` and `leaflet.js` CDN tags in `Resources/views/frontend/index.blade.php`.
* **Patched PHP 8.3 Enforcement:** Enforced minimum **PHP 8.3.36** in `composer.json`, `module.json`, controllers, and `UpdateManager` (with an in-panel **PHP 8.3.37+** upgrade recommendation banner).

### Version 3.0.1 — Afterburner Edition (PHP 8.3+)
* Wired automated server-side date-gated trickle-down feature flags (`Dec 15, 2026`, `Jan 15, 2027`, `Feb 15, 2027`) signed inside Ed25519 entitlements (`RouteRequestServer v2.0.7`).
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
