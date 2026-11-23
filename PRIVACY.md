# RouteRequest Privacy & Data Protection Policy (California & US)

**Effective Date:** November 22, 2026  
**Business Operator:** Alexander Leard d/b/a **Regal Artistic Avenue & Fantasy Air VA** (Small Business — California, United States)  
**Privacy & Deletion Contact:** `Admin@Flyfantasyair.Net`

This Privacy & Data Protection Policy explains what data **RouteRequest** and **RouteRequestServer** collect, how that data is stored and protected today and in future releases, and your privacy rights under **California** and **United States** law.

---

## 1. Small Business & California Privacy Law Commitment
Regal Artistic Avenue & Fantasy Air VA is a small business based in **California, United States**:
* **CalOPPA (California Online Privacy Protection Act):** Because we operate an online SaaS licensing portal and API accessible to California residents, we comply with CalOPPA by clearly disclosing the categories of data collected, third-party processors, Do Not Track handling, and how you can review or delete your data.
* **CCPA / CPRA (California Consumer Privacy Act / California Privacy Rights Act):** Although our small business currently operates below the statutory CCPA/CPRA revenue and volume thresholds, we **voluntarily honor core California privacy rights** (Right to Know/Access, Right to Delete, Right to Correct, and Right to Non-Discrimination) for all VA administrators.
* **No Sale or Sharing of Personal Information:** We **never sell** your personal information and **never share** it with third parties for cross-context behavioral advertising or third-party direct marketing (California "Shine the Light" Law, Cal. Civ. Code § 1798.83).
* **COPPA (Children's Online Privacy Protection Act):** We do not knowingly collect personal information from children under 13.

---

## 2. What Data We Collect & What We Never Collect

### A. Data Collected by `RouteRequestServer` (Cloud DRM & Storefront)
1. **License & Billing Metadata:** Bound VA domain(s), administrator/billing email address, license tier, activation status, order reference IDs, payment processor transaction IDs (Square/PayPal), and invoice amounts.
2. **Non-PII Installation Telemetry (Cloud DRM Client Only):** Installation web address (`app.url` domain), VA site name, installed RouteRequest version, build edition, PHP version, and license activation state (`activated` / `unactivated` + key prefix).
3. **Transient Cloud Proxy Requests:** When you use optional cloud-proxied features (FlightAware parser or Discord webhook dispatcher), the flight URL or webhook payload is forwarded in transit and is not sold or mined.

### B. Data We NEVER Collect or Store
* **No Pilot Personal Data (PII):** RouteRequest telemetry does **not** collect pilot names, pilot emails, pilot IP addresses, PIREP flight logs, or simulator credentials. All pilot route requests remain strictly inside your own VA's phpVMS v7 database.
* **No Payment Card Numbers:** Credit card and bank details are entered directly on **Square** or **PayPal** hosted checkouts. We never touch, process, or store raw payment card numbers.
* **Standalone Builds:** Afterburner Standalone installations (`MASTER-FLAGSHIP-STANDALONE`) make **zero** telemetry or DRM server calls.

---

## 3. How Data Is Stored & Protected (Current & Future Safeguards)
We apply defense-in-depth technical and administrative safeguards designed for a modern PHP 8.3+ SaaS platform:
* **Encryption in Transit:** All API calls, license checks, telemetry pings, payment webhooks, and update downloads are strictly enforced over **HTTPS / TLS**.
* **Cryptographic Integrity:** Cloud DRM entitlements are cryptographically signed using **libsodium Ed25519** (`sodium_crypto_sign_detached`) so payloads cannot be forged or tampered with in transit.
* **Update & Supply-Chain Hardening:** In-panel updates enforce native **Zip-Slip path-traversal**, null-byte, and symlink checks plus edition-locked archive headers (`RR-META`) before extraction.
* **Access Controls & Minimization:** Server databases are restricted to least-privilege administrative access, separated from public webroots, and limited to the minimum fields needed to validate licenses and fulfill orders.
* **Payment Security:** Delegated exclusively to **PCI-DSS Level 1** compliant payment processors (**Square** and **PayPal**).

---

## 4. Data Retention & Deletion Schedule
* **Active License & Portal Records:** Retained for as long as your license remains active or needed to provide customer support and domain transfers.
* **Financial & Tax Records:** Order receipts and transaction references are retained for up to **7 years** as required by United States federal and California state tax and accounting laws.
* **Installation Telemetry (`routerequest_installs`):** Retained only while an installation is active; inactive or unactivated domain records are periodically pruned or deleted upon request.
* **How to Request Access, Correction, or Deletion:** Email **`Admin@Flyfantasyair.Net`** from your billing email address (or with your license key/domain) with the subject *"Privacy Request"*. We will verify your request and respond within **45 calendar days** (and sooner wherever possible), deleting all non-legally-required data promptly.

---

## 5. Third-Party Service Providers & Do Not Track
* **Service Providers:** We use trusted infrastructure and processing partners solely to deliver the service: **Square** and **PayPal** (payment processing), **GitHub** (release distribution), **Discord** (only when you configure a webhook URL), and **VATSIM** (public ATC status feed).
* **Do Not Track (DNT):** We do not track users across third-party websites or run third-party ad trackers; our portal uses only essential session/CSRF cookies required for security and checkout.

---

## 6. Future Features & Data Protection Commitments
As RouteRequest evolves (for example, future optional modules such as an HTTP Discord Slash-Command Bot, Multi-Domain Alliance Sync, or enhanced dispatch analytics), we commit to:
1. **Privacy by Design & Data Minimization:** Collecting only the minimum technical data strictly required for the feature to operate.
2. **Opt-In for Any New Personal Identifiers:** Any future feature that stores additional user or Discord identifiers will be clearly labeled and opt-in by the VA administrator.
3. **Advance Notice:** Updating this `PRIVACY.md` file with a new Effective Date and changelog entry whenever data handling practices change.
