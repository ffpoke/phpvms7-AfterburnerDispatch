# RouteRequest v2.8.0 — Afterburner Edition for phpVMS v7 (DisposableTheme v3)

**Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue**

Official Repository: `ffpoke/phpvms7-RouteRequest`

> [!WARNING]
> **PHP 8.3+ STRICT REQUIREMENT (PHP 8.1 & PHP 8.2 SUPPORT DROPPED)**
> **RouteRequest v2.4.0+ / v2.8.0** (and **RouteRequestServer v1.3.0+ / v1.6.0**) strictly require **PHP 8.3 or newer (`>= 8.3.0`)** with the standard built-in `sodium` extension for maximum speed and cryptographic license security.

---

## What's New in Version 2.8.0 — Afterburner Edition (PHP 8.3+)

1. **Bulk Subfleet-to-Route Auto-Sync Engine (`Support/SubfleetSyncService.php`):**
   * Open **`✈ Bulk Subfleet Route Sync`** in `/admin/routerequest` whenever you add new subfleets to your VA.
   * Select **one or multiple target subfleets**, pick a match rule (**Shared Type Rating**, **Same Subfleet Type**, or **All Flights**), and apply **Airline Restrictions** (**Match Subfleet's Own Airline**, **Custom Single/Multiple VA Airlines**, or **All VA Airlines**).
   * Click **`🔍 Dry-Run Preview`** to inspect exact flight counts, per-subfleet breakdowns, and sample routes across your entire phpVMS `flights` table, then click **`⚡ 1-Click Sync`** to bulk-attach via chunked `INSERT IGNORE` without touching existing subfleet assignments.

---

## Credits & Attribution

**Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue**
