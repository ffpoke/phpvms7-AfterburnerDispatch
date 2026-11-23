# RouteRequest Cloud DRM & SaaS Terms of Service

**Effective Date:** November 22, 2026  
**Operator:** Alexander Leard d/b/a **Regal Artistic Avenue & Fantasy Air VA** (a small business based in California, United States)  
**Contact:** `Admin@Flyfantasyair.Net`

By installing, activating, or purchasing a license for **RouteRequest** for phpVMS v7, you agree to these Terms of Service, the Proprietary Source-Available `LICENSE`, and our `PRIVACY.md` Data Protection Policy.

---

## 1. Eligibility & Account Responsibility
* The RouteRequest Cloud DRM Portal and paid licensing services are intended for virtual airline administrators aged **18 or older** (or operating with verifiable parental/guardian consent; never for children under 13 in compliance with the U.S. Children's Online Privacy Protection Act, **COPPA**).
* You are responsible for safeguarding your license key, portal access, and Discord webhook URLs.

## 2. Licensing Tiers & Domain Binding
* **Tier 1 (Basic / Free):** Includes core route requests and scheduled feature drops per the baked-in `DropSchedule.php` timetable.
* **Tier 2 (Executive) & Tier 3 (Flagship):** Unlock cloud-gated features, automated dispatch tools, and Flagship exclusives (including PaxStudio Bridge and Pre-Release channel access) for **one (1) bound VA domain** per license key.
* **Domain Transfers:** Self-service domain transfers are available through the RouteRequest Customer Portal (`/routerequest-portal`), subject to reasonable anti-abuse limits.

## 3. Billing, Trials, Auto-Renewal (California ARL) & Refunds
* **7-Day Executive Trial:** Limited to one (1) trial per VA domain.
* **Payment Processing:** Payments are processed securely by third-party PCI-DSS compliant processors (**Square** or **PayPal**). We never receive or store your full credit card or bank account numbers.
* **Recurring Subscriptions (California Automatic Renewal Law Disclosure):** If you select a recurring monthly or annual plan, you will be clearly shown the recurring price, billing interval, and renewal terms before checkout. We send automated renewal reminder emails prior to expiration, and you may cancel recurring billing online at any time before your next billing date so you will not be charged again.
* **Refunds:** Because digital license keys and cloud entitlements are provisioned immediately upon payment, refunds are evaluated on a case-by-case basis within **7 days of purchase** if a verified technical incompatibility prevents the module from functioning on a supported PHP 8.3.36+ / phpVMS v7 environment.

## 4. Cloud API Availability & 72-Hour Offline Grace
* RouteRequest Cloud DRM caches Ed25519-signed entitlements locally on your server for **72 hours**, allowing your VA to continue operating smoothly during brief network maintenance.
* An active internet connection is required for initial activation, license verification, FlightAware/Discord cloud proxies, and 1-click updates.

## 5. Data Storage, Security & Privacy (California & US Laws)
* Our data collection, encryption, retention, and California/US privacy rights (including **CalOPPA** and voluntary **CCPA/CPRA** protections) are governed by **`PRIVACY.md`**, which is incorporated by reference into these Terms.

## 6. Acceptable Use, Abuse & Revocation
* Attempting to strip DRM checks, tamper with `DropSchedule.php`, share keys across multiple VAs, or flood the `RouteRequestServer` API may result in immediate key suspension or revocation without refund.

## 7. Governing Law, Venue & Updates to Terms
* These Terms are governed by the laws of the **State of California** and applicable **United States federal laws**, without regard to conflict-of-law rules. Any disputes shall be resolved in the state or federal courts of California, USA.
* We may update these Terms and `PRIVACY.md` as new features are introduced. Material changes will be reflected by an updated Effective Date and release notes.
