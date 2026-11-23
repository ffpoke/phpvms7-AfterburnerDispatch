# RouteRequest Cloud DRM & SaaS Terms of Service

**Effective Date:** November 2026  
**Operator:** Alexander Leard — Fantasy Air VA & Regal Artistic Avenue

By installing, activating, or purchasing a license for **RouteRequest** for phpVMS v7, you agree to these Terms of Service alongside the Proprietary Source-Available `LICENSE`.

## 1. Licensing Tiers & Domain Binding
* **Tier 1 (Basic / Free):** Includes core route requests and scheduled feature drops per the baked-in `DropSchedule.php` timetable.
* **Tier 2 (Executive) & Tier 3 (Flagship):** Unlock cloud-gated features, automated dispatch tools, and Flagship exclusives (including PaxStudio Bridge and Pre-Release channel access) for **one (1) bound VA domain** per license key.
* **Domain Transfers:** Self-service domain transfers are available through the RouteRequest Customer Portal (`/routerequest-portal`), subject to reasonable anti-abuse limits.

## 2. Billing, Trials & Refunds
* **7-Day Executive Trial:** Limited to one trial per VA domain.
* **Subscriptions & Lifetime Keys:** Processed securely via Square or PayPal. Recurring subscriptions may be canceled before the next renewal cycle. Because digital license keys and cloud entitlements are delivered immediately, refunds are evaluated on a case-by-case basis within 7 days if a verified technical incompatibility prevents the module from running on PHP 8.3.36+ / phpVMS v7.

## 3. Cloud API Availability & Offline Grace
* RouteRequest Cloud DRM caches signed Ed25519 entitlements locally for **72 hours**, allowing your VA to continue operating smoothly during brief network maintenance.
* An active internet connection is required for initial activation, license verification, FlightAware/Discord cloud proxies, and 1-click updates.

## 4. Installation Telemetry & Privacy
* The Cloud DRM Client sends lightweight, non-PII installation heartbeats (site URL/domain, VA name, module version, PHP version, and activation status) to `RouteRequestServer` upon install, activation, and daily checks.
* No pilot personal data, credentials, or flight logs are transmitted by telemetry.

## 5. Abuse & Revocation
* Attempting to strip DRM checks, tamper with `DropSchedule.php`, share keys across multiple VAs, or flood the `RouteRequestServer` API may result in immediate key suspension or revocation without refund.
