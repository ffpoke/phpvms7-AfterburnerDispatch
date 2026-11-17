# RouteRequest v2.1.0 — Afterburner Edition for phpVMS v7 (DisposableTheme v3)

**Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue**

Official Repository: `ffpoke/phpvms7-RouteRequest`

## What's New in Version 2.1.0 — Afterburner Edition
1. **GitHub Releases Update Checker & ⚡ 1-Click Self-Updater (`Support/UpdateManager.php`):**
   - Automatically checks `ffpoke/phpvms7-RouteRequest` on GitHub Releases (cached for 6 hours, with a manual **↻ Check for Updates** button in the Admin header).
   - Displays a top banner in `/admin/routerequest` when a newer version is available, showing the release changelog, a **Direct Download ZIP** button, and a **⚡ 1-Click Install Update** button that downloads, verifies, extracts over `/modules/RouteRequest`, runs migrations, and clears application caches automatically.
2. **Creator Attribution:**
   - Displays plain-text creator attribution (`Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue`) at the bottom of the Pilot Route Request page, Pilot Quick Request widget, Admin Route Requests page, Master DRM License Manager page, and dispatch notification emails.
3. **Local `IndexedDB` Airport & Airline Engine (`RouteRequestAfterburnerDB`) + Persistent Storage:**
   - Offloads your phpVMS `airports` table onto the pilot's and admin's local device using `IndexedDB` and `navigator.storage.persist()` for `< 1 ms` local searches and automatic version/hash invalidation.
4. **Zero-Reload AJAX Multi-Destination Flight Creation & FlightAware Auto-Matcher:**
   - Instant AJAX flight creation for multi-destination requests, automatic Outbound/Return FlightAware link direction matching, and non-blocking background emails (`app()->terminating()`).
