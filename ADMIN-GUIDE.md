# RouteRequest v3.1.0-beta.5 — Admin & Dispatch User Guide

**Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue**

This guide covers every administrative tab inside **`/admin/routerequest`** (Client Module) and **`/admin/routerequest-server`** (Master DRM Server).

---

## Part A: Client Admin Panel (`/admin/routerequest`)

The Admin interface is organized into **4 persistent tabs** (your active tab is remembered across reloads):

### Tab 1: 📋 Route Requests (Dispatch Queue & Flight Generator)
* **Opening the Flight Generator:** Click **Generate Flight(s)** on any Pending or In Progress request to expand its inline generator drawer.
* **FlightAware Auto-Matcher (Tier 2+):** If the pilot included a FlightAware URL, click **⚡ Auto-Fill from FlightAware** to pull real-world route strings and cruise altitudes.
* **Auto-Estimate Block Time (Tier 2+):** Click **⚡ Auto-Est** next to *Flight Time (Hours & Minutes)* to calculate Great-Circle distance and fill realistic block time automatically.
* **Two-Layer Duplicate Route Checker:** Before saving, RouteRequest checks both the current request and your global phpVMS `flights` table for `DEP → ARR` (and `ARR → DEP` if Return Flight is checked). If a duplicate exists, you'll see the existing flight number and route string. Check **⚠ Bypass Duplicate Check (Different Routing)** only if you intentionally want an alternate routing.
* **Multi-Destination Zero-Reload Creation:**
  * Click **⚡ + Create Flight(s) & Add Another Destination** to create the flight via AJAX, auto-increment the next flight number, and clear Arrival Airport for the next leg.
  * When finished (or if you hit the 12/12 or 15/15 per-request cap, which pulses the button green), click **✓ Mark Completed (No More Routes)** to finalize the request, notify the pilot by email & Discord `#new-schedules`, and collapse the drawer.
* **PaxStudio & RouteRequest 1-Click Updaters:** Banners appear at the top of this tab whenever a new RouteRequest or PaxStudio release is available (or if installed PaxStudio is older than the `v2.22.0` compatibility floor).

### Tab 2: ✈ Subfleet Sync & Route Tools
1. **Easy Bulk Subfleet-to-Route Auto-Sync:**
   * **Step 1:** Filter and click the checkbox pills for one or multiple newly added subfleets (no `Ctrl+Click` needed).
   * **Step 2:** Choose a **Route Match Rule** (*Shared Type Rating*, *Same Subfleet Type Code*, or *All Flights*) and an **Airline Restriction** (*Subfleet's Own Airline*, *Custom Single/Multiple VA Airlines*, or *All VA Airlines*).
   * **Step 3:** Click **🔍 Dry-Run Preview** to see exact flight counts, per-subfleet breakdowns, and up to 20 sample matched routes before making any changes.
   * **Step 4:** Click **⚡ 1-Click Sync Now** to bulk-attach via chunked `INSERT IGNORE` (never deletes existing subfleet assignments).
   * **⭐ Saved Presets & ↩ Undo Last Sync (Tier 3 Flagship):** Save frequently used sync rules as named presets, or click **↩ Undo Last Sync** to cleanly remove *only* the exact links inserted by your last batch.
2. **⏱ Old Route Time Estimator Fixer (Tier 3 Flagship):**
   * Scans your existing phpVMS `flights` table for missing (`0h 00m` / `< 15 min`) or unrealistic flight times, previews the exact `Old Time → New Est. Time` changes, and repairs them in 1 click.

### Tab 3: ⚙ Dispatch Settings & Discord
* **Pilot Limits (All Tiers):** Set **Max Open Requests Per Pilot** (`0` = unlimited) and **Pilot Request Cooldown (Minutes)** (`0` = disabled) to prevent request spam.
* **Discord Webhooks (Tier 2+):** Paste your `#staff-dispatch` and `#new-schedules` Discord Webhook URLs and click **⚡ Test Discord Webhooks** to verify delivery.
* **Custom Aircraft Range Overrides (Optional JSON):** Override built-in ICAO ranges for custom fleet variants (e.g. `{"A321": 4000, "B738": 3100}`).

### Tab 4: 🔑 License & Storefront
* **Zero-`.env` Activation:** Paste a license key and click **Save & Verify Key**, click **✓ 1-Click Free Tier 1 Key**, or click **⚡ Start 7-Day Free Executive Trial** (1 per domain).
* **Instant Upgrades:** Enter an optional Smart Promo Code and check out via **Square** or **Business PayPal** for automatic in-place key activation.
* **Self-Service Portal Link:** Direct link to `/routerequest-portal` to transfer your license between staging and live domains (once every 30 days) or print payment receipts.

---

## Part B: Master DRM Server (`/admin/routerequest-server` — v2.0.1)

* **Pinned Top Strips:** Live 4-card **Revenue & License Telemetry KPI Bar** and **📡 Fleet Version Telemetry** (tracking installed RouteRequest, PaxStudio, and PHP versions across active client VAs), plus the manual **✉ Run Expiry Reminders** button.
* **Tab 1 — 🔑 Licenses & Issue:** Issue manual keys, change tiers/billing cycles, reset Basic weekly quotas, or revoke keys.
* **Tab 2 — 🏷 Smart Promo Codes:** Create percentage or fixed-USD promo codes restricted by Tier, Plan, Max Uses, and Expiry Date (`100%`-off promos auto-fulfill instantly without gateway redirect).
* **Tab 3 — 🧾 Recent Orders:** Audit completed, pending, and failed Square/PayPal orders and applied promo discounts.
* **Tab 4 — ⚙ Pricing, Square, PayPal & PaxStudio APIs:** Configure USD pricing, test live/sandbox Square & PayPal connections with 1-click **Test API** buttons, and configure your PaxStudio GitHub PAT / Compatibility Floor (`v2.22.0`).
