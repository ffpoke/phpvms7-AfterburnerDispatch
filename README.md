# RouteRequest v2.5.0 — Afterburner Edition for phpVMS v7 (DisposableTheme v3)

**Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue**

Official Repository: `ffpoke/phpvms7-RouteRequest`

> [!WARNING]
> **PHP 8.3+ STRICT REQUIREMENT (PHP 8.1 & PHP 8.2 SUPPORT DROPPED)**
> **RouteRequest v2.4.0+ / v2.5.0** (and **RouteRequestServer v1.3.0+ / v1.4.0**) strictly require **PHP 8.3 or newer (`>= 8.3.0`)** for maximum speed and security. PHP 8.1 and PHP 8.2 are no longer supported.

---

## What's New in Version 2.5.0 — Afterburner Edition (PHP 8.3+)

1. **Discord Webhook Dispatch Notifications (`Support/DiscordNotifier.php` — Tier 2 Executive & Tier 3 Flagship):**
   * Configure separate `#staff-dispatch` and `#new-schedules` Discord Webhook URLs directly inside **⚙ Dispatch Settings & Discord** in `/admin/routerequest`.
   * Posts rich, non-blocking Discord embeds when pilots submit new requests and when staff publish completed flights, complete with a **⚡ Test Discord Webhooks** button.
2. **7-Day Subscription Renewal Banner & 1-Click Renew:**
   * Monthly Executive and Flagship installations automatically display a renewal warning banner inside `/admin/routerequest` starting 7 days before expiration, with 1-click **Renew 30 Days (Square / PayPal)** buttons.
3. **Pilot Request Cooldown & Max Open Requests Limits (`Support/ClientSettings.php` — All Tiers):**
   * Admins can now cap how many pending/active requests a single pilot can have open simultaneously (`max_open_requests_per_pilot`) and enforce a cooldown window in minutes (`pilot_cooldown_minutes`) directly from the Admin Settings drawer.
4. **Smart Promo / Discount Code Support in Storefront:**
   * Client VAs can enter smart promo codes (percentage or fixed USD off, restricted by tier/cycle/uses/expiry) directly inside the **🔑 License & Storefront Center** before checking out via Square or PayPal.

---

## First-Time Installation & Setup Guide (Requires PHP 8.3+ — No `.env` Editing Required)

1. Confirm your phpVMS v7 server is running **PHP 8.3+** (with `bcmath`, `curl`, `fileinfo`, `intl`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `xml`, and `zip`).
2. Download **`RouteRequest-phpvms7-module-v2.5.0-php83-DRM-Client.zip`** from the [Releases](https://github.com/ffpoke/phpvms7-RouteRequest/releases) Assets section and extract the `RouteRequest` folder into `/modules/RouteRequest`.
3. In **Admin -> Maintenance**, click **Clear Application Cache** (`php artisan optimize:clear`).
4. Open **`/admin/routerequest`**, click **`🔑 License & Storefront Center`**, and click **`✓ 1-Click Claim Free Tier 1 Key (Instant)`** (or paste a manual key / upgrade via Square or PayPal).

---

## Credits & Attribution

**Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue**
