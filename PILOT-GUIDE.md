# RouteRequest v4.0.0-beta.3 — Pilot User Guide

**Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue**

Welcome to **RouteRequest**! This guide walks pilots through submitting custom route requests, using the interactive Great-Circle map, understanding the Aircraft Range Guardrail, and tracking your requests from submission to dispatch.

---

## 1. Opening the Route Request Portal
Navigate to **`/routerequest`** from your Virtual Airline's top menu. You will see three organized tabs:
* **✈ Request a New Route** — The main submission form.
* **📋 My Requested Routes** — Live history of your submitted requests, active flight links, and PaxStudio Loadsheet links (if enabled by your VA).
* **🎖 My Signed-Off Type Ratings** — Displays all active Type Ratings currently assigned to your pilot profile.

*Note: You must have at least one active **Type Rating** assigned to your profile to submit a route request.*

---

## 2. Submitting a New Route Request
1. **Select Your Type Rating:** Choose the aircraft Type Rating you want to fly. Only ratings you are signed off for appear in this list.
2. **Real-World / Charter Airline Counterpart:** Select the real-world airline you are simulating (e.g., Delta, Lufthansa, Ryanair), or choose **OTHER** to type a custom charter or cargo operator.
3. **Starting & Ending Airports (`< 1 ms` Local Search):**
   * Start typing an ICAO code, IATA code, airport name, or city (e.g., `KORD`, `LHR`, `Paris`). Results appear instantaneously using RouteRequest's local `IndexedDB` cache.
   * Your current pilot location is pre-selected as the Starting Airport by default.
   * Leave **Ending Airport** blank (or check **Multiple Destinations**) if you want Dispatch to build multiple legs out of your departure airport.
4. **Interactive Great-Circle Map & Block-Time Preview:**
   * As soon as both Starting and Ending airports are selected, an interactive map draws the curved Great-Circle route and displays:
     * **Distance (NM)**
     * **Estimated Block Time** (cruise + taxi/climb buffer)
     * **Typical Max Range (NM)** for your selected Type Rating
5. **Aircraft Range Guardrail:**
   * If your requested route exceeds the typical operational range of your selected aircraft, the map line turns red and a warning banner appears.
   * On Executive/Flagship VAs, RouteRequest automatically performs a quick check on **FlightAware** and **Flightradar24**. If real-world flights operate that city pair, your override is approved automatically.
   * If no real-world flight is detected, you can either pick a longer-range Type Rating or check the **Extended-Range / Charter Exception** confirmation box to submit anyway.
6. **Trip Options & Notes:**
   * **Also Request Return Flight:** Check this to request a Round-Trip (Outbound + Return back to your Starting Airport).
   * **Multiple Destinations:** Check this if you are requesting several destinations from one hub (list them in the Notes box).
   * **Notes / FlightAware URLs:** Paste a FlightAware flight link (e.g., `https://www.flightaware.com/live/flight/DAL40`) so Dispatch can auto-match the real-world routing and cruise altitude.
7. Click **✓ Submit Route Request**. Your request is dispatched immediately to your VA's staff (via email and Discord `#staff-dispatch`).

---

## 3. Tracking & Cancelling Requests
Open the **📋 My Requested Routes** tab to monitor your requests:
* **Pending Dispatch Review:** Your request is in the queue. If you made a mistake, click **Cancel** to withdraw it before staff start building it.
* **Accepted (In Progress):** Dispatch is actively generating your flight schedules.
* **Completed (Route Active):** Your flights are live! Click any generated flight badge to view/bid the flight directly, or click **✦ PaxStudio Loadsheet** (on Flagship VAs) to jump straight into payload & OFP planning.

## What's New for Pilots in v4.0.0-beta.3
* **Unified Split Flight Chips & SimBrief OFP:** Completed requests display paired `[✈ Fly/Bid] [✦ PaxStudio] [🗺 SimBrief]` chips (with a collapsible `▾ Show All Flights` drawer for multi-destination requests). Click **🗺 SimBrief** to launch a pre-filled SimBrief dispatch in one click.
* **Live VATSIM ATC Overlay:** On the Interactive Great-Circle Map, click **📡 Toggle Live VATSIM ATC** to view currently staffed VATSIM airports along your route.
* **Guided Travel & Aircraft Repositioning (Flagship + PaxStudio):** When your VA disables manual PIREPs via `ManualPirepControl` and uses `DisposableBasic`, clicking a flight when you or an eligible aircraft aren't at the departure airport opens an Aircraft Picker (sorted by Cheapest/Closest, Last Used, or Idle First). If your Pilot Pay balance isn't enough to cover both moves, click **📨 Ask Admin to Move Me** to request an admin relocation.
