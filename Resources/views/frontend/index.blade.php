@extends('app')
@section('title', 'Afterburner Dispatch')

@section('content')
@php
  if (empty($realworldAirlines) && class_exists(\Modules\AfterburnerDispatch\Support\RealWorldAirlines::class)) {
    $realworldAirlines = \Modules\AfterburnerDispatch\Support\RealWorldAirlines::all();
  }
@endphp
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
  @media (max-width: 767.98px) {
    .rr-desktop-table { display: none !important; }
    .rr-mobile-card { display: block !important; }
  }
  @media (min-width: 768px) {
    .rr-desktop-table { display: table !important; }
    .rr-mobile-card { display: none !important; }
  }
  .rr-afterburner-badge {
    background: linear-gradient(90deg, #0284c7, #2563eb);
    color: #fff;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
  }

  .rr-tabs-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    border-bottom: 2px solid #cbd5e1;
    margin: 0 0 12px 0;
    padding-bottom: 0;
  }
  .rr-tab-btn {
    appearance: none;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-bottom: none;
    border-radius: 6px 6px 0 0;
    padding: 8px 14px;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
    margin-bottom: -2px;
    transition: background-color 0.15s ease, color 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .rr-tab-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
  }
  .rr-tab-btn.rr-tab-active {
    background: #ffffff;
    color: #0284c7;
    border-color: #0284c7;
    border-top: 3px solid #0284c7;
    border-bottom: 2px solid #ffffff;
  }
  .rr-tab-badge {
    display: inline-block;
    padding: 1px 6px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
    background: #e2e8f0;
    color: #334155;
  }
  .rr-tab-btn.rr-tab-active .rr-tab-badge {
    background: #0284c7;
    color: #ffffff;
  }
  .rr-tab-pane {
    display: none;
  }
  .rr-tab-pane.rr-tab-pane-active {
    display: block;
  }
  /* v4.0.0: Pilot Frontend Formatting Polish & Community Queue Upvoting */
  .rr-tabs-nav { gap: 4px; margin-bottom: 14px; }
  .rr-tab-btn { padding: 8px 13px; border-radius: 7px 7px 0 0; line-height: 1.25; }
  .rr-card-clean { border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06); }
  .rr-card-clean .card-header { padding: 10px 14px !important; }
  .rr-card-clean table.table th { font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; padding: 9px 10px; white-space: nowrap; }
  .rr-card-clean table.table td { padding: 9px 10px; vertical-align: middle; }
  .rr-upvote-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600;
    border: 1px solid #cbd5e1; background: #f8fafc; color: #334155;
    cursor: pointer; transition: all 0.15s ease;
  }
  .rr-upvote-btn:hover { border-color: #0284c7; color: #0284c7; background: #f0f9ff; }
  .rr-upvote-btn.rr-upvoted { border-color: #0284c7; background: #0284c7; color: #ffffff; }


  /* v4.0.0: Travel & Aircraft Repositioning Modal (inherits VA theme font) */
  #rr_travel_modal_backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.72);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 14px;
  }
  #rr_travel_modal_card {
    background: #ffffff;
    color: #0f172a;
    border-radius: 10px;
    max-width: 680px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
    border: 1px solid #cbd5e1;
  }


  /* v4.0.0: Unified Split Flight Chips (pure VA theme font inheritance) */
  .rr-flight-chips-wrap {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 5px;
    margin-top: 6px;
  }
  .rr-flight-chip {
    display: inline-flex;
    align-items: center;
    border-radius: 5px;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.14);
    font-size: 0.78em;
    font-weight: 600;
    line-height: 1.25;
  }
  .rr-flight-chip-bid {
    background: #0284c7;
    color: #ffffff !important;
    padding: 3px 7px;
    text-decoration: none !important;
  }
  .rr-flight-chip-bid:hover { background: #0369a1; }
  .rr-flight-chip-pax {
    background: linear-gradient(90deg, #7c3aed, #4f46e5);
    color: #ffffff !important;
    padding: 3px 7px;
    text-decoration: none !important;
    border-left: 1px solid rgba(255, 255, 255, 0.25);
  }
  .rr-flight-chip-sb {
    background: #0f766e;
    color: #ffffff !important;
    padding: 3px 7px;
    text-decoration: none !important;
    border-left: 1px solid rgba(255, 255, 255, 0.25);
  }
  .rr-flight-chip-sb:hover { background: #115e59; }

</style>

<div class="rr-tabs-nav" id="rr_pilot_tabs">
  <button type="button" class="rr-tab-btn rr-tab-active" data-rr-tab="fe_request" onclick="rrSwitchPilotTab('fe_request')">
    <i class="fas fa-route"></i> Request a New Route
  </button>
  <button type="button" class="rr-tab-btn" data-rr-tab="fe_history" onclick="rrSwitchPilotTab('fe_history')">
    <i class="fas fa-history"></i> My Requested Routes <span class="rr-tab-badge">{{ count($myRequests) }}</span>
  </button>
  @if(!empty($drmEntitlements['features']['community_queue']))
    <button type="button" class="rr-tab-btn" data-rr-tab="fe_community" onclick="rrSwitchPilotTab('fe_community')">
      <i class="fas fa-fire"></i> Community Queue <span class="rr-tab-badge">{{ isset($communityQueue) ? count($communityQueue) : 0 }}</span>
    </button>
  @endif
  <button type="button" class="rr-tab-btn" data-rr-tab="fe_ratings" onclick="rrSwitchPilotTab('fe_ratings')">
    <i class="fas fa-certificate"></i> My Signed-Off Type Ratings <span class="rr-tab-badge">{{ $pilotTypeRatings->count() }}</span>
  </button>
</div>

<div class="row">
  <div id="rr_tab_pane_fe_request" class="col-12 mb-3 rr-tab-pane rr-tab-pane-active">
    <div class="card mb-3 rr-card-clean">
      <div class="card-header p-2 d-flex justify-content-between align-items-center">
        <h5 class="m-0">
          <i class="fas fa-route me-1"></i>
          Request a New Route
        </h5>
        <span class="rr-afterburner-badge" title="Afterburner Dispatch Suite v4.0.0 (Local IndexedDB Engine)">
          <i class="fas fa-bolt me-1"></i>v4.0.0 Afterburner
        </span>
      </div>

      @if($pilotTypeRatings->isEmpty())
        <div class="card-body p-3">
          @include('flash::message')
          <div class="alert alert-warning mb-0">
            <h6 class="fw-bold mb-1">
              <i class="fas fa-exclamation-triangle me-1"></i> No Signed-Off Type Ratings Found
            </h6>
            <p class="small mb-0">
              You do not currently hold any active Type Ratings on your pilot profile. Please contact Staff / Training to have your Type Rating(s) assigned before submitting a route request.
            </p>
          </div>
        </div>
        <div class="card-footer p-1 text-end">
          <button type="button" class="btn btn-sm btn-secondary" disabled>
            <i class="fas fa-lock me-1"></i> Type Rating Required
          </button>
        </div>
      @else
        <form method="POST" action="{{ route('afterburner.store') }}" id="rr_pilot_form">
          @csrf
          <div class="card-body p-3">
            @include('flash::message')

            @if($errors->any())
              <div class="alert alert-danger p-2 mb-2 small">
                <ul class="mb-0 ps-3">
                  @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <!-- 1. Pilot's Signed-Off Native Type Rating -->
            <div class="mb-3">
              <label class="form-label small fw-bold">
                <i class="fas fa-id-badge text-primary me-1"></i> Type Rating <span class="text-danger">*</span>
              </label>
              <select
                name="typerating_id"
                id="typerating_id"
                class="form-control select2 @error('typerating_id') is-invalid @enderror"
                style="width: 100%;"
                required
              >
                <option value="">-- Select Your Signed-Off Type Rating --</option>
                @foreach($pilotTypeRatings as $tr)
                  <option value="{{ $tr->id }}" {{ (string) old('typerating_id', $defaultTr) === (string) $tr->id ? 'selected' : '' }}>
                    {{ $tr->type }} — {{ $tr->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <!-- 2. Real-World / Charter Airline Counterpart (Hydrated via Local IndexedDB or Server Fallback) -->
            <div class="mb-3">
              <label class="form-label small fw-bold">
                <i class="fas fa-globe-americas text-primary me-1"></i> Real Airline / Operator <span class="text-danger">*</span>
              </label>
              <select
                name="realworld_airline"
                id="realworld_airline"
                class="form-control select2 @error('realworld_airline') is-invalid @enderror"
                onchange="toggleOtherAirline(this.value)"
                style="width: 100%;"
                required
              >
                <option value="">-- Select Real-World or Charter Operator --</option>
                @foreach($realworldAirlines as $groupLabel => $operators)
                  <optgroup label="{{ $groupLabel }}">
                    @foreach($operators as $val => $label)
                      <option value="{{ $val }}" {{ old('realworld_airline') === $val ? 'selected' : '' }}>
                        {{ $label }}
                      </option>
                    @endforeach
                  </optgroup>
                @endforeach
                <optgroup label="Unlisted Real-World / Charter Operator">
                  <option value="OTHER" {{ old('realworld_airline') === 'OTHER' ? 'selected' : '' }}>
                    Other Real-World / Charter Operator (Specify Below)...
                  </option>
                </optgroup>
              </select>
            </div>

            <div class="mb-3" id="other_airline_wrapper" style="{{ old('realworld_airline') === 'OTHER' ? '' : 'display: none;' }}">
              <label class="form-label small fw-bold">Specify Operator Name &amp; ICAO <span class="text-danger">*</span></label>
              <input
                type="text"
                name="realworld_airline_other"
                id="realworld_airline_other"
                class="form-control @error('realworld_airline_other') is-invalid @enderror"
                placeholder="Enter operator name & ICAO..."
                value="{{ old('realworld_airline_other') }}"
              >
            </div>

            <!-- 3. Starting Airport (Required — Powered by Local IndexedDB 0ms Search) -->
            <div class="mb-3">
              <label class="form-label small fw-bold">
                <i class="fas fa-plane-departure text-success me-1"></i> Starting Airport <span class="text-danger">*</span>
              </label>
              <select
                name="dep_airport_id"
                id="dep_airport_id"
                class="form-control airport_search @error('dep_airport_id') is-invalid @enderror"
                style="width: 100%;"
                required
              >
                <option value="">-- Type ICAO (KLAX), IATA (LAX), or City --</option>
                @if($defaultDep)
                  <option value="{{ $defaultDep }}" selected>
                    {{ $defaultDep }}{{ $defaultDepAirport && $defaultDepAirport->iata ? ' / ' . $defaultDepAirport->iata : '' }} — {{ $defaultDepAirport ? $defaultDepAirport->name : 'Selected Airport' }}
                  </option>
                @endif
              </select>
            </div>

            <!-- 4. Ending Airport (Optional — Powered by Local IndexedDB 0ms Search) -->
            <div class="mb-3">
              <label class="form-label small fw-bold">
                <i class="fas fa-plane-arrival text-info me-1"></i> Ending Airport
              </label>
              <select
                name="arr_airport_id"
                id="arr_airport_id"
                class="form-control airport_search @error('arr_airport_id') is-invalid @enderror"
                style="width: 100%;"
              >
                <option value="">-- Optional: Leave Blank for ANY Destination --</option>
                @if($defaultArr)
                  <option value="{{ $defaultArr }}" selected>
                    {{ $defaultArr }}{{ $defaultArrAirport && $defaultArrAirport->iata ? ' / ' . $defaultArrAirport->iata : '' }} — {{ $defaultArrAirport ? $defaultArrAirport->name : 'Selected Airport' }}
                  </option>
                @endif
              </select>
            </div>

            <!-- v4.0.0: Live Duplicate Route Detection & Community Merge -->
            @if(!empty($drmEntitlements['features']['duplicate_route_guard']))
              <div id="rr_duplicate_guard_box" class="alert alert-warning py-2 px-3 mb-3 small" style="display: none;"></div>
            @endif

            <!-- v4.0.0: Interactive Great-Circle Map, Auto-Block-Time Estimator & Aircraft Range Guardrail -->
            <div id="rr_gc_map_panel" class="mb-3 rounded border" style="display: none; background: #f8fafc; overflow: hidden;">
              <div class="p-2 d-flex justify-content-between align-items-center flex-wrap gap-2" style="background: #e0f2fe; border-bottom: 1px solid #bae6fd;">
                <div class="small fw-bold" style="color: #0369a1;">
                  <i class="fas fa-globe-americas me-1"></i>
                  <span id="rr_gc_route_title">DEP ➔ ARR</span>
                </div>
                <div class="d-flex flex-wrap gap-2 small">
                  <span class="badge bg-primary" id="rr_gc_dist_badge">0 NM</span>
                  <span class="badge bg-success" id="rr_gc_time_badge">Est. Block: 0h 00m</span>
                  <span class="badge bg-secondary" id="rr_gc_range_badge">Max Range: —</span>
                </div>
              </div>
              <div id="rr_pilot_leaflet_map" style="height: 210px; width: 100%;"></div>
              @if(!empty($drmEntitlements['features']['vatsim_atc_overlay']))
                <div class="p-1 px-2 border-top d-flex justify-content-between align-items-center small">
                  <span id="rr_vatsim_status" class="text-muted">Live VATSIM ATC Overlay available</span>
                  <button type="button" class="btn btn-xs btn-outline-success py-0 px-2" onclick="window.rrToggleVatsimOverlay && window.rrToggleVatsimOverlay()">
                    &#128225; Toggle Live VATSIM ATC
                  </button>
                </div>
              @endif

              <!-- Range Guardrail Warning + FR24/FA Real-World Check (ONLY shown when route exceeds max range) -->
              <div id="rr_range_guardrail_box" class="p-2" style="display: none; background: #fef2f2; border-top: 2px solid #dc2626; font-size: 12px;">
                <div class="fw-bold text-danger mb-1" id="rr_range_guardrail_title">
                  &#9888; Aircraft Range Guardrail Triggered
                </div>
                <div id="rr_range_fr24_status" class="mb-1 text-dark">
                  <i class="fas fa-spinner fa-spin me-1"></i> Checking FlightAware &amp; Flightradar24 for real-world flights on this route...
                </div>
                <div id="rr_range_override_wrap" class="form-check mt-1" style="display: none;">
                  <input class="form-check-input" type="checkbox" id="rr_range_override_cb" name="range_override_confirmed" value="1">
                  <label class="form-check-label fw-bold text-danger" for="rr_range_override_cb">
                    I confirm this route uses an extended-range variant / charter exception and want to submit anyway.
                  </label>
                </div>
              </div>
            </div>

            <!-- 5. Trip Options: Return Flight (Round-Trip) & Multiple Destinations -->
            <div class="p-2 mb-3 rounded border bg-light bg-opacity-10">
              <div class="form-check mb-2">
                <input type="hidden" name="request_return" value="0">
                <input
                  class="form-check-input"
                  type="checkbox"
                  name="request_return"
                  id="request_return"
                  value="1"
                  {{ old('request_return') ? 'checked' : '' }}
                >
                <label class="form-check-label small fw-bold" for="request_return">
                  <i class="fas fa-exchange-alt text-success me-1"></i>
                  Also Request Return Flight (Round-Trip back to Starting Airport)
                </label>
                <div class="text-muted" style="font-size: 11px;">
                  Leave unchecked if you only want a One-Way flight without returning to the same airport.
                </div>
              </div>

              <div class="form-check mb-0">
                <input type="hidden" name="multiple_destinations" value="0">
                <input
                  class="form-check-input"
                  type="checkbox"
                  name="multiple_destinations"
                  id="multiple_destinations"
                  value="1"
                  onchange="toggleMultipleDestinations(this.checked)"
                  {{ old('multiple_destinations') ? 'checked' : '' }}
                >
                <label class="form-check-label small fw-bold" for="multiple_destinations">
                  <i class="fas fa-project-diagram text-primary me-1"></i>
                  Request Multiple Destinations (Requires Ending Airport to be ANY / Open — Max 4 Return Pairs)
                </label>
              </div>
            </div>

            <!-- Target Notification Email -->
            @if(config('afterburner.allow_custom_recipient', false))
              <div class="mb-3">
                <label class="form-label small fw-bold">Send To Email <span class="text-danger">*</span></label>
                <input
                  type="email"
                  name="recipient_email"
                  id="recipient_email"
                  class="form-control"
                  value="{{ old('recipient_email', $defaultEmail) }}"
                  required
                >
              </div>
            @else
              <input type="hidden" name="recipient_email" value="{{ $defaultEmail }}">
            @endif

            <!-- Pilot Remarks / FlightAware Links -->
            <div class="mb-2">
              <label class="form-label small fw-bold">
                Route Remarks / FlightAware Link(s)
              </label>
              <textarea
                name="notes"
                id="notes"
                rows="2"
                class="form-control"
                placeholder="Optional: Paste 1 or 2 FlightAware flight links (outbound/return), preferred destinations, or remarks..."
              >{{ old('notes') }}</textarea>
            </div>
          </div>

          <div class="card-footer p-2 d-flex justify-content-between align-items-center flex-wrap gap-1">
            <div class="small text-muted">
              <span id="rr_cache_status_badge" class="badge bg-secondary text-white">
                <i class="fas fa-bolt me-1"></i>Syncing Local Cache...
              </span>
              <button type="button" class="btn btn-link btn-sm p-0 ms-1 text-decoration-none small" onclick="window.RRAfterburnerCache && window.RRAfterburnerCache.forceRefresh()" title="Force refresh local airport & airline cache">
                &#8635; Refresh
              </button>
            </div>
            <button type="submit" class="btn btn-sm bg-primary text-white">
              <i class="fas fa-check me-1"></i> Submit Route Request
            </button>
          </div>
        </form>
      @endif
    </div>

  </div>

  <!-- Tab 3: Pilot's Signed-Off Type Ratings Summary Card -->
  <div id="rr_tab_pane_fe_ratings" class="col-12 mb-3 rr-tab-pane">
    <div class="card mb-3 rr-card-clean">
      <div class="card-header p-2">
        <h5 class="m-0">
          <i class="fas fa-certificate float-end"></i>
          My Signed-Off Type Ratings ({{ $pilotTypeRatings->count() }})
        </h5>
      </div>
      <div class="card-body p-2 small">
        @if($pilotTypeRatings->isEmpty())
          <span class="text-muted">No active Type Ratings assigned to your pilot profile.</span>
        @else
          <div class="d-flex flex-wrap gap-1">
            @foreach($pilotTypeRatings as $tr)
              <span class="badge bg-info text-dark p-1">
                <i class="fas fa-check-circle me-1"></i>{{ $tr->type }} — {{ $tr->name }}
              </span>
            @endforeach
          </div>
        @endif
      </div>
    </div>
  </div>

  <!-- Tab 2b: Community Route Queue & Pilot Upvoting (v4.0.0) -->
  @if(!empty($drmEntitlements['features']['community_queue']))
    <div id="rr_tab_pane_fe_community" class="col-12 rr-tab-pane">
      <div class="card mb-3 rr-card-clean">
        <div class="card-header p-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h5 class="m-0">
            <i class="fas fa-fire me-1"></i> Community Route Queue &amp; Pilot Upvoting
          </h5>
          <span class="small text-muted">Upvote open crew requests so dispatchers can prioritize high-demand routes</span>
        </div>
        <div class="card-body p-0">
          @if(!isset($communityQueue) || count($communityQueue) === 0)
            <div class="p-4 text-center text-muted small">
              <i class="fas fa-check-circle me-1"></i> No open community route requests right now. Submit one in the Request tab!
            </div>
          @else
            <div class="table-responsive mb-0">
              <table class="table table-sm table-striped align-middle mb-0">
                <thead>
                  <tr>
                    <th class="ps-3">Route &amp; Trip</th>
                    <th>Type Rating</th>
                    <th>Operator</th>
                    <th>Requested By</th>
                    <th class="text-end pe-3">Demand / Upvote</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($communityQueue as $cq)
                    @php
                      $isOwn = (int) $cq->user_id === (int) $user->id;
                      $hasUpvoted = in_array((int) $cq->id, $myUpvotedIds ?? [], true);
                      $upvCount = (int) ($cq->upvotes_count ?? 0);
                    @endphp
                    <tr>
                      <td class="ps-3">
                        <strong>{{ $cq->dep_airport_id }} &rarr; {{ $cq->arr_airport_id ?: 'ANY' }}</strong>
                        @if($cq->request_return)
                          <span class="badge bg-info text-dark ms-1">Round-Trip</span>
                        @endif
                        @if($cq->multiple_destinations)
                          <span class="badge bg-secondary ms-1">Multi-Dest</span>
                        @endif
                        @if(!empty($cq->notes))
                          <div class="small text-muted text-truncate" style="max-width: 280px;" title="{{ $cq->notes }}">{{ $cq->notes }}</div>
                        @endif
                      </td>
                      <td>
                        <span class="badge bg-light text-dark border">{{ $cq->typerating ? $cq->typerating->type : ($cq->aircraft_type ?: 'Any') }}</span>
                      </td>
                      <td class="small">{{ $cq->realworld_airline ?: 'Virtual / Charter' }}</td>
                      <td class="small text-muted">
                        {{ $cq->user ? $cq->user->ident : ('Pilot #' . $cq->user_id) }}
                        @if($isOwn) <span class="badge bg-secondary ms-1">Yours</span> @endif
                      </td>
                      <td class="text-end pe-3">
                        @if($isOwn)
                          <span class="badge bg-light text-dark border" title="Your own request">&#9650; {{ $upvCount }} {{ $upvCount === 1 ? 'Upvote' : 'Upvotes' }}</span>
                        @else
                          <button
                            type="button"
                            class="rr-upvote-btn {{ $hasUpvoted ? 'rr-upvoted' : '' }}"
                            id="rr_upvote_btn_{{ $cq->id }}"
                            onclick="rrToggleCommunityUpvote({{ $cq->id }}, this)"
                          >
                            <span>&#9650;</span>
                            <span class="rr-upv-label">{{ $hasUpvoted ? 'Upvoted' : 'Upvote' }}</span>
                            <span class="rr-upv-count">({{ $upvCount }})</span>
                          </button>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
    </div>
  @endif

  <!-- Tab 2: Pilot's Submitted Route Requests -->
  <div id="rr_tab_pane_fe_history" class="col-12 rr-tab-pane">
    <div class="card mb-3 rr-card-clean">
      <div class="card-header p-2">
        <h5 class="m-0">
          <i class="fas fa-history float-end"></i>
          My Requested Routes
        </h5>
      </div>
      <div class="card-body p-0">
        @if(isset($myMoveRequests) && count($myMoveRequests) > 0)
          <div class="p-2 border-bottom">
            <div class="small fw-bold mb-1">
              <i class="fas fa-suitcase-rolling me-1"></i> Recent Admin Move Requests
            </div>
            @foreach($myMoveRequests as $mr)
              <div class="small d-flex justify-content-between align-items-center flex-wrap gap-1 py-1 border-top">
                <span>
                  To <strong>{{ $mr->to_airport_id }}</strong>
                  @if($mr->pilot_from_airport) (from {{ $mr->pilot_from_airport }}) @endif
                  &bull; Est. ${{ number_format((float) $mr->total_cost, 2) }}
                  @if($mr->admin_notes) — <em>{{ $mr->admin_notes }}</em> @endif
                </span>
                <span>
                  @if($mr->status === 'approved')
                    <span class="badge bg-success">Allowed &amp; Moved</span>
                  @elseif($mr->status === 'denied')
                    <span class="badge bg-danger">Denied</span>
                  @else
                    <span class="badge bg-warning text-dark">Pending Admin Review</span>
                  @endif
                </span>
              </div>
            @endforeach
          </div>
        @endif
        @if(count($myRequests) === 0)
          <div class="p-3 text-center text-muted small">
            <i class="fas fa-info-circle me-1"></i> You have not submitted any route requests yet.
          </div>
        @else
          {{-- Desktop Table View --}}
          <div class="table-responsive rr-desktop-table mb-0">
            <table class="table table-sm table-borderless table-striped text-center align-middle mb-0">
              <thead>
                <tr>
                  <th class="text-start ps-3">Route &amp; Trip Type</th>
                  <th>Type Rating</th>
                  <th>Real-World Operator</th>
                  <th>Status / Action</th>
                  <th class="text-end pe-3">Submitted</th>
                </tr>
              </thead>
              <tbody>
                @foreach($myRequests as $req)
                  @php $genFlights = $req->generated_flight_models; @endphp
                  <tr>
                    <td class="text-start ps-3">
                      <a href="{{ route('frontend.airports.show', [$req->dep_airport_id]) }}" class="fw-bold">
                        {{ $req->dep_airport_id }}
                      </a>
                      <i class="fas fa-angle-double-right mx-1 text-muted"></i>
                      @if($req->multiple_destinations || $genFlights->count() > 2)
                        <span class="badge bg-primary" title="Multiple destinations requested">
                          MULTI-DEST ({{ $genFlights->count() > 0 ? $genFlights->count() . ' Flights' : 'ANY' }})
                        </span>
                      @elseif($req->arr_airport_id)
                        <a href="{{ route('frontend.airports.show', [$req->arr_airport_id]) }}" class="fw-bold">
                          {{ $req->arr_airport_id }}
                        </a>
                      @else
                        <span class="badge bg-secondary">ANY / OPEN</span>
                      @endif
                      <div class="mt-1">
                        @if($req->request_return)
                          <span class="badge bg-success bg-opacity-75"><i class="fas fa-exchange-alt me-1"></i>Round-Trip</span>
                        @else
                          <span class="badge bg-secondary bg-opacity-75"><i class="fas fa-long-arrow-alt-right me-1"></i>One-Way</span>
                        @endif
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-info text-dark">{{ $req->aircraft_type }}</span>
                    </td>
                    <td>
                      <strong>{{ $req->realworld_airline }}</strong>
                    </td>
                    <td>
                      @if($req->status === 'completed')
                        <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Completed</span>
                      @elseif($req->status === 'approved' || $req->status === 'in_progress')
                        <span class="badge bg-primary"><i class="fas fa-spinner fa-spin me-1"></i>Accepted</span>
                      @elseif($req->status === 'cancelled')
                        <span class="badge bg-secondary"><i class="fas fa-ban me-1"></i>Cancelled</span>
                      @elseif($req->status === 'rejected')
                        <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Rejected</span>
                      @else
                        <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Pending</span>
                        <div class="mt-1">
                          <form method="POST" action="{{ route('afterburner.cancel', $req->id) }}" onsubmit="return confirm('Cancel this pending route request?');">
                            @csrf
                            <button type="submit" class="btn btn-xs btn-danger p-1 fs-7">Cancel Request</button>
                          </form>
                        </div>
                      @endif

                      @if($genFlights->count() > 0)
                        @php
                          $hasPaxLink = !empty($drmEntitlements['features']['pax_studio']) && !empty($drmEntitlements['paxstudio_installed']);
                          $hasSbLink = !empty($drmEntitlements['features']['simbrief_bridge']);
                          $visibleCount = 4;
                          $extraCount = max(0, $genFlights->count() - $visibleCount);
                        @endphp
                        <div class="rr-flight-chips-wrap">
                          @foreach($genFlights->take($visibleCount) as $gf)
                            @php
                              $gfCode = optional($gf->airline)->icao ?? optional($gf->airline)->iata ?? '';
                              $sbUrl = 'https://dispatch.simbrief.com/options/custom?airline=' . urlencode($gfCode) . '&fltnum=' . urlencode((string) $gf->flight_number) . '&orig=' . urlencode((string) $gf->dpt_airport_id) . '&dest=' . urlencode((string) $gf->arr_airport_id) . ($gf->route ? '&route=' . urlencode((string) $gf->route) : '');
                            @endphp
                            <span class="rr-flight-chip">
                              <a href="{{ route('frontend.flights.show', [$gf->id]) }}" class="rr-flight-chip-bid" data-rr-travel-flight="{{ $gf->id }}" onclick="return rrHandleFlightLaunch(event, '{{ $gf->id }}', this.href);" title="View / Bid Flight">
                                <i class="fas fa-plane-departure me-1"></i>{{ $gfCode }}{{ $gf->flight_number }} ({{ $gf->dpt_airport_id }}&rarr;{{ $gf->arr_airport_id }})
                              </a>
                              @if($hasPaxLink)
                                <a href="{{ url('/paxstudio/loadsheet?flight_id=' . $gf->id) }}" class="rr-flight-chip-pax" data-rr-travel-flight="{{ $gf->id }}" onclick="return rrHandleFlightLaunch(event, '{{ $gf->id }}', this.href);" title="Open in PaxStudio Loadsheet &amp; OFP Planner">
                                  &#10022; PaxStudio
                                </a>
                              @endif
                              @if($hasSbLink)
                                <a href="{{ $sbUrl }}" target="_blank" rel="noopener" class="rr-flight-chip-sb" title="Dispatch Instant OFP on SimBrief">
                                  &#128506; SimBrief
                                </a>
                              @endif
                            </span>
                          @endforeach
                        </div>

                        @if($extraCount > 0)
                          <div id="rr_extra_flights_{{ $req->id }}" class="rr-flight-chips-wrap" style="display: none; margin-top: 5px;">
                            @foreach($genFlights->slice($visibleCount) as $gf)
                              @php
                                $gfCode = optional($gf->airline)->icao ?? optional($gf->airline)->iata ?? '';
                                $sbUrl = 'https://dispatch.simbrief.com/options/custom?airline=' . urlencode($gfCode) . '&fltnum=' . urlencode((string) $gf->flight_number) . '&orig=' . urlencode((string) $gf->dpt_airport_id) . '&dest=' . urlencode((string) $gf->arr_airport_id) . ($gf->route ? '&route=' . urlencode((string) $gf->route) : '');
                              @endphp
                              <span class="rr-flight-chip">
                                <a href="{{ route('frontend.flights.show', [$gf->id]) }}" class="rr-flight-chip-bid" data-rr-travel-flight="{{ $gf->id }}" onclick="return rrHandleFlightLaunch(event, '{{ $gf->id }}', this.href);" title="View / Bid Flight">
                                  <i class="fas fa-plane-departure me-1"></i>{{ $gfCode }}{{ $gf->flight_number }} ({{ $gf->dpt_airport_id }}&rarr;{{ $gf->arr_airport_id }})
                                </a>
                                @if($hasPaxLink)
                                  <a href="{{ url('/paxstudio/loadsheet?flight_id=' . $gf->id) }}" class="rr-flight-chip-pax" data-rr-travel-flight="{{ $gf->id }}" onclick="return rrHandleFlightLaunch(event, '{{ $gf->id }}', this.href);" title="Open in PaxStudio Loadsheet &amp; OFP Planner">
                                    &#10022; PaxStudio
                                  </a>
                                @endif
                                @if($hasSbLink)
                                  <a href="{{ $sbUrl }}" target="_blank" rel="noopener" class="rr-flight-chip-sb" title="Dispatch Instant OFP on SimBrief">
                                    &#128506; SimBrief
                                  </a>
                                @endif
                              </span>
                            @endforeach
                          </div>
                          <div class="mt-1">
                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.78em;" onclick="var el=document.getElementById('rr_extra_flights_{{ $req->id }}'); var open=(el.style.display==='none'); el.style.display=open?'flex':'none'; this.innerHTML=open?'&#9652; Hide Extra Flights':'&#9662; Show All {{ $genFlights->count() }} Flights ({{ $extraCount }} more)';">
                              &#9662; Show All {{ $genFlights->count() }} Flights ({{ $extraCount }} more)
                            </button>
                          </div>
                        @endif
                      @endif
                    </td>
                    <td class="text-end pe-3 small text-muted">
                      {{ $req->created_at->format('d.M.Y H:i') }}
                    </td>
                  </tr>
                  @if($req->admin_notes || $req->notes)
                    <tr>
                      <td colspan="5" class="text-start ps-3 pe-2 py-1 small bg-transparent">
                        @if($req->notes)
                          <div class="text-muted"><i class="fas fa-sticky-note me-1"></i><strong>Your Note:</strong> {{ $req->notes }}</div>
                        @endif
                        @if($req->admin_notes)
                          <div class="text-success"><i class="fas fa-user-shield me-1"></i><strong>Dispatch Response:</strong> {{ $req->admin_notes }}</div>
                        @endif
                      </td>
                    </tr>
                  @endif
                @endforeach
              </tbody>
            </table>
          </div>

          {{-- Mobile Card View for Smaller Screens (< 768px) --}}
          <div class="rr-mobile-card p-2">
            @foreach($myRequests as $req)
              @php $genFlights = $req->generated_flight_models; @endphp
              <div class="card mb-2 p-2 border shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <div>
                    <span class="fw-bold fs-6">{{ $req->dep_airport_id }}</span>
                    <i class="fas fa-arrow-right mx-1 text-muted"></i>
                    @if($req->multiple_destinations || $genFlights->count() > 2)
                      <span class="badge bg-primary">MULTI-DEST</span>
                    @elseif($req->arr_airport_id)
                      <span class="fw-bold fs-6">{{ $req->arr_airport_id }}</span>
                    @else
                      <span class="badge bg-secondary">ANY</span>
                    @endif
                    @if($req->request_return)
                      <span class="badge bg-success ms-1">Round-Trip</span>
                    @else
                      <span class="badge bg-secondary ms-1">One-Way</span>
                    @endif
                  </div>
                  <div>
                    @if($req->status === 'completed')
                      <span class="badge bg-success">Completed</span>
                    @elseif($req->status === 'approved' || $req->status === 'in_progress')
                      <span class="badge bg-primary">Accepted</span>
                    @elseif($req->status === 'cancelled')
                      <span class="badge bg-secondary">Cancelled</span>
                    @elseif($req->status === 'rejected')
                      <span class="badge bg-danger">Rejected</span>
                    @else
                      <span class="badge bg-warning text-dark">Pending</span>
                    @endif
                  </div>
                </div>

                <div class="small text-muted mb-1">
                  <strong>Type Rating:</strong> {{ $req->aircraft_type }}<br>
                  <strong>Operator:</strong> {{ $req->realworld_airline }}<br>
                  <strong>Submitted:</strong> {{ $req->created_at->format('d.M.Y H:i') }}
                </div>

                @if($req->status === 'pending')
                  <div class="mb-2">
                    <form method="POST" action="{{ route('afterburner.cancel', $req->id) }}" onsubmit="return confirm('Cancel this pending route request?');">
                      @csrf
                      <button type="submit" class="btn btn-xs btn-danger w-100">Cancel Request</button>
                    </form>
                  </div>
                @endif

                @if($genFlights->count() > 0)
                  <div class="mb-1 d-flex flex-column gap-1">
                    @foreach($genFlights as $gf)
                      @php $gfCode = optional($gf->airline)->icao ?? optional($gf->airline)->iata ?? ''; @endphp
                      <a href="{{ route('frontend.flights.show', [$gf->id]) }}" class="btn btn-xs btn-primary text-white w-100" data-rr-travel-flight="{{ $gf->id }}" onclick="return rrHandleFlightLaunch(event, '{{ $gf->id }}', this.href);">
                        <i class="fas fa-plane-departure me-1"></i>Fly / Bid: {{ $gfCode }}{{ $gf->flight_number }} ({{ $gf->dpt_airport_id }}&rarr;{{ $gf->arr_airport_id }})
                      </a>
                      @if(!empty($drmEntitlements['features']['pax_studio']) && !empty($drmEntitlements['paxstudio_installed']))
                        <a href="{{ url('/paxstudio/loadsheet?flight_id=' . $gf->id) }}" class="btn btn-xs text-white w-100" style="background: linear-gradient(90deg, #7c3aed, #2563eb);" data-rr-travel-flight="{{ $gf->id }}" onclick="return rrHandleFlightLaunch(event, '{{ $gf->id }}', this.href);">
                          &#10022; PaxStudio Loadsheet: {{ $gfCode }}{{ $gf->flight_number }}
                        </a>
                      @endif
                    @endforeach
                  </div>
                @endif

                @if($req->notes || $req->admin_notes)
                  <div class="border-top pt-1 mt-1 small">
                    @if($req->notes)
                      <div class="text-muted"><strong>Note:</strong> {{ $req->notes }}</div>
                    @endif
                    @if($req->admin_notes)
                      <div class="text-success"><strong>Dispatch:</strong> {{ $req->admin_notes }}</div>
                    @endif
                  </div>
                @endif
              </div>
            @endforeach
          </div>
        @endif
      </div>
      <div class="card-footer p-2 d-flex justify-content-between align-items-center flex-wrap small text-muted">
        <span>Afterburner Dispatch Suite v4.0.0 &bull; Made by Alexander Leard of Fantasy Air VA &amp; Regal Artistic Avenue</span>
        <span>Total Requests: {{ count($myRequests) }}</span>
      </div>
    </div>
  </div>
</div>

{{-- v4.0.0: PaxStudio + Flagship + DisposableBasic Guided Travel & Aircraft Repositioning Modal --}}
<div id="rr_travel_modal_backdrop">
  <div id="rr_travel_modal_card">
    <div style="background: linear-gradient(90deg, #0284c7, #7c3aed); color: #fff; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center;">
      <div>
        <h5 style="margin: 0; font-weight: 700; font-size: 16px;">&#9992; Travel &amp; Aircraft Repositioning Required</h5>
        <div id="rr_tm_subtitle" style="font-size: 12px; opacity: 0.92;">Checking pilot location, eligible aircraft &amp; Pilot Pay...</div>
      </div>
      <button type="button" onclick="rrCloseTravelModal()" style="background: rgba(255,255,255,0.2); border: none; color: #fff; border-radius: 5px; padding: 4px 10px; font-weight: 700; cursor: pointer;">&times;</button>
    </div>
    <div style="padding: 16px 18px;">
      <div id="rr_tm_summary_bar" style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 12px; margin-bottom: 12px; font-size: 13px;"></div>

      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 8px;">
        <label style="font-weight: 700; font-size: 13px; margin: 0;">Select Eligible Aircraft to Reposition:</label>
        <select id="rr_tm_sort" class="form-select form-select-sm" style="width: auto; font-size: 12px;" onchange="rrReloadTravelOptions()">
          <option value="cheapest" selected>Cheapest / Closest First</option>
          <option value="last_used">Last Used By Me First</option>
          <option value="least_recent">Not In Use Recently (Idle First)</option>
        </select>
      </div>

      <div id="rr_tm_aircraft_list" style="max-height: 240px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px; margin-bottom: 12px;"></div>

      <div id="rr_tm_pay_status" style="display: none; border-radius: 6px; padding: 10px 12px; margin-bottom: 12px; font-size: 13px;"></div>

      <div style="display: flex; justify-content: flex-end; flex-wrap: wrap; gap: 8px;">
        <button type="button" class="btn btn-sm btn-secondary" onclick="rrCloseTravelModal()">Cancel</button>
        <button type="button" id="rr_tm_pay_btn" class="btn btn-sm btn-success" style="display: none;" onclick="rrExecuteSelfTravel()">
          &#9889; Pay &amp; Travel to Airport
        </button>
        <button type="button" id="rr_tm_ask_admin_btn" class="btn btn-sm btn-warning text-dark fw-bold" style="display: none;" onclick="rrRequestAdminMove()">
          &#128232; Ask Admin to Move Me
        </button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
@parent
<script>
  var RR_MODULE_VERSION = '4.0.0';
  var RR_AIRPORTS_HASH  = '{{ $cacheHash ?? "v2.0.0_default" }}';
  var RR_BUNDLE_URL     = '{{ route("afterburner.cache.bundle") }}';
  var RR_SEARCH_URL     = '{{ route("afterburner.airports.search") }}';

  /**
   * RouteRequest v2.0.0 — Afterburner Edition IndexedDB Local Cache Engine
   * Stores the entire phpVMS `airports` table in IndexedDB (`RouteRequestAfterburnerDB`)
   * and requests Persistent Storage (`navigator.storage.persist()`) so searches run in < 1 ms locally!
   * Automatically refreshes whenever `RR_MODULE_VERSION` or `RR_AIRPORTS_HASH` changes.
   */
  window.RRAfterburnerCache = (function () {
    var dbName = 'RouteRequestAfterburnerDB';
    var storeName = 'bundle';
    var memoryAirports = null;

    function updateBadge(text, bgClass) {
      var el = document.getElementById('rr_cache_status_badge');
      if (!el) return;
      el.className = 'badge ' + (bgClass || 'bg-success') + ' text-white';
      el.innerHTML = '<i class="fas fa-bolt me-1"></i>' + text;
    }

    function requestPersistentStorage() {
      if (navigator.storage && navigator.storage.persist) {
        navigator.storage.persist().catch(function () {});
      }
    }

    function openDB(cb) {
      if (!window.indexedDB) {
        cb(null);
        return;
      }
      try {
        var req = indexedDB.open(dbName, 1);
        req.onupgradeneeded = function (e) {
          var db = e.target.result;
          if (!db.objectStoreNames.contains(storeName)) {
            db.createObjectStore(storeName, { keyPath: 'key' });
          }
        };
        req.onsuccess = function (e) { cb(e.target.result); };
        req.onerror = function () { cb(null); };
      } catch (err) {
        cb(null);
      }
    }

    function getStoredBundle(cb) {
      openDB(function (db) {
        if (!db) { cb(null); return; }
        try {
          var tx = db.transaction([storeName], 'readonly');
          var st = tx.objectStore(storeName);
          var getReq = st.get('main_bundle');
          getReq.onsuccess = function () { cb(getReq.result || null); };
          getReq.onerror = function () { cb(null); };
        } catch (e) {
          cb(null);
        }
      });
    }

    function saveBundle(bundleData, cb) {
      openDB(function (db) {
        if (!db) { if (cb) cb(); return; }
        try {
          var tx = db.transaction([storeName], 'readwrite');
          var st = tx.objectStore(storeName);
          st.put({
            key: 'main_bundle',
            version: bundleData.version,
            hash: bundleData.hash,
            airports: bundleData.airports || []
          });
          tx.oncomplete = function () { if (cb) cb(); };
          tx.onerror = function () { if (cb) cb(); };
        } catch (e) {
          if (cb) cb();
        }
      });
    }

    function fetchFromServer(cb) {
      updateBadge('Downloading Local Airport DB...', 'bg-info');
      fetch(RR_BUNDLE_URL, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data && Array.isArray(data.airports)) {
            memoryAirports = data.airports;
            saveBundle(data, function () {
              requestPersistentStorage();
              updateBadge('Afterburner Cache: ' + memoryAirports.length + ' Airports Local', 'bg-success');
              try { window.dispatchEvent(new CustomEvent('rr-cache-ready')); } catch (e) {}
              if (cb) cb(memoryAirports);
            });
          } else {
            updateBadge('Live Server Search Active', 'bg-secondary');
            if (cb) cb(null);
          }
        })
        .catch(function () {
          updateBadge('Live Server Search Active', 'bg-secondary');
          if (cb) cb(null);
        });
    }

    function init(cb) {
      getStoredBundle(function (cached) {
        if (cached && cached.version === RR_MODULE_VERSION && cached.hash === RR_AIRPORTS_HASH && Array.isArray(cached.airports)) {
          memoryAirports = cached.airports;
          requestPersistentStorage();
          updateBadge('Afterburner Cache: ' + memoryAirports.length + ' Airports Local', 'bg-success');
          try { window.dispatchEvent(new CustomEvent('rr-cache-ready')); } catch (e) {}
              if (cb) cb(memoryAirports);
        } else {
          fetchFromServer(cb);
        }
      });
    }

    function searchLocal(term) {
      if (!memoryAirports) return null;
      var q = (term || '').trim().toUpperCase();
      if (q === '') {
        return memoryAirports.slice(0, 75).map(function (a) {
          return { id: a.id, text: a.text };
        });
      }
      var exact = [];
      var starts = [];
      var contains = [];
      for (var i = 0; i < memoryAirports.length; i++) {
        var a = memoryAirports[i];
        if (a.id === q || a.iata === q) {
          exact.push({ id: a.id, text: a.text });
        } else if (a.id.indexOf(q) === 0 || (a.iata && a.iata.indexOf(q) === 0)) {
          starts.push({ id: a.id, text: a.text });
        } else if (a.search.indexOf(q) !== -1) {
          contains.push({ id: a.id, text: a.text });
        }
        if (exact.length + starts.length + contains.length >= 120) break;
      }
      return exact.concat(starts, contains).slice(0, 75);
    }

    function getAirportById(icao) {
      if (!icao || !Array.isArray(memoryAirports)) return null;
      var up = String(icao).trim().toUpperCase();
      for (var i = 0; i < memoryAirports.length; i++) {
        if (memoryAirports[i].id === up) return memoryAirports[i];
      }
      return null;
    }

    return {
      init: init,
      searchLocal: searchLocal,
      getAirportById: getAirportById,
      forceRefresh: function () { fetchFromServer(); }
    };
  })();

  function toggleOtherAirline(val) {
    var wrapper = document.getElementById('other_airline_wrapper');
    var input = document.getElementById('realworld_airline_other');
    if (!wrapper) return;
    if (val === 'OTHER') {
      wrapper.style.display = '';
      if (input) input.required = true;
    } else {
      wrapper.style.display = 'none';
      if (input) input.required = false;
    }
  }

  function toggleMultipleDestinations(isChecked) {
    if (!window.jQuery) return;
    var $arr = jQuery('#arr_airport_id');
    if (isChecked) {
      $arr.val(null).trigger('change');
      $arr.prop('disabled', true);
    } else {
      $arr.prop('disabled', false);
    }
  }

  function buildAfterburnerSelect2Config(placeholderText) {
    return {
      width: '100%',
      placeholder: placeholderText,
      allowClear: true,
      tags: false,
      minimumInputLength: 0,
      ajax: {
        transport: function (params, success, failure) {
          var q = (params.data && params.data.q) ? params.data.q : '';
          var localRes = window.RRAfterburnerCache ? window.RRAfterburnerCache.searchLocal(q) : null;
          if (localRes !== null) {
            success({ results: localRes });
            return { abort: function () {} };
          }
          var $request = jQuery.ajax(params);
          $request.then(success);
          $request.fail(failure);
          return $request;
        },
        url: RR_SEARCH_URL,
        dataType: 'json',
        delay: 20,
        data: function (params) {
          return { q: params.term || '' };
        },
        processResults: function (data) {
          return { results: data.results || [] };
        },
        cache: true
      }
    };
  }

  document.addEventListener('DOMContentLoaded', function () {
    window.RRAfterburnerCache.init();

    try {
      var rwSelect = document.getElementById('realworld_airline');
      if (rwSelect && !rwSelect.value) {
        var savedAirline = localStorage.getItem('rr_last_realworld_airline');
        if (savedAirline) {
          rwSelect.value = savedAirline;
          toggleOtherAirline(savedAirline);
        }
      }
    } catch (e) {}

    if (window.jQuery && jQuery.fn.select2) {
      jQuery('#typerating_id, #realworld_airline').select2({
        width: '100%'
      });

      jQuery('#realworld_airline').on('change', function () {
        toggleOtherAirline(this.value);
        try {
          if (this.value) {
            localStorage.setItem('rr_last_realworld_airline', this.value);
          }
        } catch (e) {}
      });

      jQuery('#dep_airport_id').select2(
        buildAfterburnerSelect2Config('-- Type ICAO (KLAX), IATA (LAX), or City --')
      );

      jQuery('#arr_airport_id').select2(
        buildAfterburnerSelect2Config('-- Optional: Leave Blank for ANY Destination --')
      );

      var multiCheck = document.getElementById('multiple_destinations');
      if (multiCheck && multiCheck.checked) {
        toggleMultipleDestinations(true);
      }
    }
  });

  function rrSwitchPilotTab(tabId) {
    var panes = document.querySelectorAll('[id^="rr_tab_pane_fe_"]');
    panes.forEach(function (p) { p.classList.remove('rr-tab-pane-active'); });
    var btns = document.querySelectorAll('#rr_pilot_tabs .rr-tab-btn');
    btns.forEach(function (b) { b.classList.remove('rr-tab-active'); });

    var targetPane = document.getElementById('rr_tab_pane_' + tabId);
    var targetBtn = document.querySelector('#rr_pilot_tabs .rr-tab-btn[data-rr-tab="' + tabId + '"]');
    if (targetPane) targetPane.classList.add('rr-tab-pane-active');
    if (targetBtn) targetBtn.classList.add('rr-tab-active');

    try { localStorage.setItem('rr_pilot_active_tab', tabId); } catch (e) {}
  }

  document.addEventListener('DOMContentLoaded', function () {
    var saved = null;
    try { saved = localStorage.getItem('rr_pilot_active_tab'); } catch (e) {}
    if (saved && document.getElementById('rr_tab_pane_' + saved)) {
      rrSwitchPilotTab(saved);
    }
  });
</script>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
  (function () {
    var RR_RANGE_TABLE = @json($rangeTable ?? []);
    var RR_CAN_GC_MAP = {{ !empty($drmEntitlements['features']['gc_map_pilot']) ? 'true' : 'false' }};
    var RR_CAN_RANGE_BASIC = {{ !empty($drmEntitlements['features']['range_guardrail_basic']) ? 'true' : 'false' }};
    var RR_CAN_RANGE_LIVE = {{ !empty($drmEntitlements['features']['range_guardrail_live_check']) ? 'true' : 'false' }};
    var RR_VERIFY_RANGE_URL = '{{ route("afterburner.verify_range") }}';
    var RR_CSRF = '{{ csrf_token() }}';
    var RR_INITIAL_COORDS = {
      @if($defaultDepAirport && $defaultDepAirport->lat)
      '{{ strtoupper($defaultDepAirport->id) }}': { lat: {{ (float) $defaultDepAirport->lat }}, lon: {{ (float) $defaultDepAirport->lon }}, name: @json($defaultDepAirport->name) },
      @endif
      @if($defaultArrAirport && $defaultArrAirport->lat)
      '{{ strtoupper($defaultArrAirport->id) }}': { lat: {{ (float) $defaultArrAirport->lat }}, lon: {{ (float) $defaultArrAirport->lon }}, name: @json($defaultArrAirport->name) },
      @endif
    };

    var pilotMap = null;
    var mapLayerGroup = null;
    var lastRangeCheckKey = '';
    var rangeCheckFailed = false;
    var rangeVerifiedRealWorld = false;

    function toRad(d) { return d * Math.PI / 180; }
    function toDeg(r) { return r * 180 / Math.PI; }

    function calcGreatCircleNm(lat1, lon1, lat2, lon2) {
      var R = 3440.065;
      var dLat = toRad(lat2 - lat1);
      var dLon = toRad(lon2 - lon1);
      var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
              Math.sin(dLon / 2) * Math.sin(dLon / 2);
      return Math.round(R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(Math.max(0, 1 - a))));
    }

    function generateGreatCirclePoints(lat1, lon1, lat2, lon2, steps) {
      var f1 = toRad(lat1), l1 = toRad(lon1), f2 = toRad(lat2), l2 = toRad(lon2);
      var d = 2 * Math.asin(Math.sqrt(Math.pow(Math.sin((f2 - f1) / 2), 2) + Math.cos(f1) * Math.cos(f2) * Math.pow(Math.sin((l2 - l1) / 2), 2)));
      if (d < 0.0001) return [[lat1, lon1], [lat2, lon2]];
      var pts = [];
      for (var i = 0; i <= steps; i++) {
        var f = i / steps;
        var A = Math.sin((1 - f) * d) / Math.sin(d);
        var B = Math.sin(f * d) / Math.sin(d);
        var x = A * Math.cos(f1) * Math.cos(l1) + B * Math.cos(f2) * Math.cos(l2);
        var y = A * Math.cos(f1) * Math.sin(l1) + B * Math.cos(f2) * Math.sin(l2);
        var z = A * Math.sin(f1) + B * Math.sin(f2);
        pts.push([toDeg(Math.atan2(z, Math.sqrt(x * x + y * y))), toDeg(Math.atan2(y, x))]);
      }
      return pts;
    }

    function estimateBlockTime(distNm, acText) {
      var up = (acText || '').toUpperCase();
      var kts = 450;
      if (/(C172|C208|PA28|SR22)/.test(up)) kts = 145;
      else if (/(DH8|Q400|AT4|AT7|PC12|B350|TBM|SF34)/.test(up)) kts = 285;
      else if (/(CRJ|E135|E145|E170|E175|E190|E195)/.test(up)) kts = 430;
      else if (/(B74|B77|B78|A33|A34|A35|A38|MD11)/.test(up)) kts = 485;
      var buf = distNm < 120 ? 18 : 25;
      var mins = Math.max(20, Math.round((distNm / kts) * 60) + buf);
      return { total: mins, h: Math.floor(mins / 60), m: mins % 60 };
    }

    function resolveMaxRange(acText) {
      var up = (acText || '').toUpperCase();
      var keys = Object.keys(RR_RANGE_TABLE);
      for (var i = 0; i < keys.length; i++) {
        if (up.indexOf(keys[i]) !== -1) return Number(RR_RANGE_TABLE[keys[i]]);
      }
      if (up.indexOf('737') !== -1) return 3000;
      if (up.indexOf('777') !== -1) return 7370;
      if (up.indexOf('787') !== -1) return 7500;
      if (up.indexOf('320') !== -1 || up.indexOf('321') !== -1) return 3400;
      if (up.indexOf('330') !== -1) return 6800;
      if (up.indexOf('350') !== -1) return 8100;
      return 3500;
    }

    function getAirportCoords(icao) {
      if (!icao) return null;
      var up = String(icao).trim().toUpperCase();
      if (window.RRAfterburnerCache && window.RRAfterburnerCache.getAirportById) {
        var found = window.RRAfterburnerCache.getAirportById(up);
        if (found && (found.lat || found.lon)) return found;
      }
      if (RR_INITIAL_COORDS[up]) return RR_INITIAL_COORDS[up];
      return null;
    }

    function updatePilotMapAndGuardrail() {
      var depEl = document.getElementById('dep_airport_id');
      var arrEl = document.getElementById('arr_airport_id');
      var trEl  = document.getElementById('typerating_id');
      var panel = document.getElementById('rr_gc_map_panel');
      var guardBox = document.getElementById('rr_range_guardrail_box');
      if (!depEl || !arrEl || !panel) return;

      var depId = (depEl.value || '').trim().toUpperCase();
      var arrId = (arrEl.value || '').trim().toUpperCase();
      var depC = getAirportCoords(depId);
      var arrC = getAirportCoords(arrId);

      if (!RR_CAN_GC_MAP || !depId || !arrId || !depC || !arrC || (depC.lat === 0 && depC.lon === 0) || (arrC.lat === 0 && arrC.lon === 0)) {
        panel.style.display = 'none';
        rangeCheckFailed = false;
        rangeVerifiedRealWorld = false;
        return;
      }

      var trText = trEl && trEl.selectedIndex >= 0 ? trEl.options[trEl.selectedIndex].text : '';
      var distNm = calcGreatCircleNm(depC.lat, depC.lon, arrC.lat, arrC.lon);
      var est = estimateBlockTime(distNm, trText);
      var maxRange = resolveMaxRange(trText);

      panel.style.display = 'block';
      document.getElementById('rr_gc_route_title').textContent = depId + ' ➔ ' + arrId;
      document.getElementById('rr_gc_dist_badge').textContent = distNm.toLocaleString() + ' NM';
      document.getElementById('rr_gc_time_badge').textContent = 'Est. Block: ' + est.h + 'h ' + (est.m < 10 ? '0' : '') + est.m + 'm';
      document.getElementById('rr_gc_range_badge').textContent = 'Max Range: ' + maxRange.toLocaleString() + ' NM';

      if (window.L) {
        if (!pilotMap) {
          pilotMap = L.map('rr_pilot_leaflet_map', { scrollWheelZoom: false });
          window.rrPilotMapInstance = pilotMap;
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 12
          }).addTo(pilotMap);
          mapLayerGroup = L.layerGroup().addTo(pilotMap);
        }
        mapLayerGroup.clearLayers();
        var pts = generateGreatCirclePoints(depC.lat, depC.lon, arrC.lat, arrC.lon, 40);
        var lineColor = distNm > maxRange ? '#dc2626' : '#0284c7';
        var poly = L.polyline(pts, { color: lineColor, weight: 3, dashArray: distNm > maxRange ? '6,6' : null }).addTo(mapLayerGroup);
        L.circleMarker([depC.lat, depC.lon], { radius: 6, color: '#16a34a', fillColor: '#16a34a', fillOpacity: 0.9 }).bindPopup(depId).addTo(mapLayerGroup);
        L.circleMarker([arrC.lat, arrC.lon], { radius: 6, color: '#dc2626', fillColor: '#dc2626', fillOpacity: 0.9 }).bindPopup(arrId).addTo(mapLayerGroup);
        setTimeout(function () {
          pilotMap.invalidateSize();
          pilotMap.fitBounds(poly.getBounds(), { padding: [25, 25] });
        }, 80);
      }

      if (RR_CAN_RANGE_BASIC && distNm > maxRange) {
        rangeCheckFailed = true;
        guardBox.style.display = 'block';
        document.getElementById('rr_range_guardrail_title').innerHTML =
          '&#9888; Range Guardrail Exceeded: ' + distNm.toLocaleString() + ' NM exceeds ' + (trText || 'Aircraft') + ' typical range (' + maxRange.toLocaleString() + ' NM).';

        var checkKey = depId + '_' + arrId;
        if (lastRangeCheckKey !== checkKey) {
          lastRangeCheckKey = checkKey;
          rangeVerifiedRealWorld = false;
          var statusEl = document.getElementById('rr_range_fr24_status');
          var ovWrap = document.getElementById('rr_range_override_wrap');
          var ovCb = document.getElementById('rr_range_override_cb');
          if (ovCb) ovCb.checked = false;
          if (ovWrap) ovWrap.style.display = 'none';
          if (statusEl) statusEl.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Checking FlightAware &amp; Flightradar24 for real-world flights on ' + depId + ' ➔ ' + arrId + '...';

          if (!RR_CAN_RANGE_LIVE) {
            rangeVerifiedRealWorld = false;
            if (statusEl) statusEl.innerHTML = '<span class="badge bg-warning text-dark me-1">Range Warning</span> Exceeds standard ICAO range for this Type Rating. Confirm override below to submit.';
            if (ovWrap) ovWrap.style.display = 'block';
            return;
          }
          fetch(RR_VERIFY_RANGE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': RR_CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ dep_airport_id: depId, arr_airport_id: arrId })
          })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            if (res && res.verified) {
              rangeVerifiedRealWorld = true;
              if (statusEl) statusEl.innerHTML = '<span class="badge bg-success me-1">&#10003; Real-World Verified</span> ' + res.message;
              if (ovWrap) ovWrap.style.display = 'none';
            } else {
              rangeVerifiedRealWorld = false;
              if (statusEl) statusEl.innerHTML = '<span class="badge bg-warning text-dark me-1">Unverified</span> ' + ((res && res.message) ? res.message : 'No real-world flights found on FR24/FA.');
              if (ovWrap) ovWrap.style.display = 'block';
            }
          })
          .catch(function () {
            rangeVerifiedRealWorld = false;
            if (statusEl) statusEl.innerHTML = 'Could not reach FlightAware/FR24 right now. You may confirm an extended-range override below.';
            if (ovWrap) ovWrap.style.display = 'block';
          });
        }
      } else {
        rangeCheckFailed = false;
        rangeVerifiedRealWorld = false;
        lastRangeCheckKey = '';
        guardBox.style.display = 'none';
      }
    }

    document.addEventListener('DOMContentLoaded', function () {
      var form = document.getElementById('rr_pilot_form');
      if (form) {
        form.addEventListener('submit', function (e) {
          var ovCb = document.getElementById('rr_range_override_cb');
          if (rangeCheckFailed && !rangeVerifiedRealWorld && (!ovCb || !ovCb.checked)) {
            e.preventDefault();
            alert('This route exceeds the selected Type Rating max range and was not verified on FlightAware/FR24. Please select a longer-range Type Rating or check the Range Override confirmation box.');
          }
        });
      }
      if (window.jQuery) {
        jQuery('#dep_airport_id, #arr_airport_id, #typerating_id').on('change select2:select', function (e) {
          if (e && e.params && e.params.data && e.params.data.id && (e.params.data.lat || e.params.data.lon)) {
            RR_INITIAL_COORDS[String(e.params.data.id).trim().toUpperCase()] = {
              lat: Number(e.params.data.lat),
              lon: Number(e.params.data.lon),
              name: e.params.data.text || e.params.data.id
            };
          }
          setTimeout(updatePilotMapAndGuardrail, 120);
        });
      }
      window.addEventListener('rr-cache-ready', function () {
        setTimeout(updatePilotMapAndGuardrail, 80);
      });
      setTimeout(updatePilotMapAndGuardrail, 900);
    });
  })();
</script>

<script>
  (function () {
    var RR_TRAVEL_ACTIVE = {{ !empty($travelFeatureActive) ? 'true' : 'false' }};
    var RR_TRAVEL_OPTS_URL = '{{ route("afterburner.travel.options") }}';
    var RR_TRAVEL_EXEC_URL = '{{ route("afterburner.travel.execute") }}';
    var RR_TRAVEL_ADMIN_URL = '{{ route("afterburner.travel.request_admin_move") }}';
    var RR_TRAVEL_CSRF = '{{ csrf_token() }}';

    var currentFlightId = null;
    var currentTargetHref = null;
    var currentTravelData = null;
    var selectedAircraftId = null;

    window.rrCloseTravelModal = function () {
      var bg = document.getElementById('rr_travel_modal_backdrop');
      if (bg) bg.style.display = 'none';
    };

    window.rrHandleFlightLaunch = function (ev, flightId, targetHref) {
      if (!RR_TRAVEL_ACTIVE) return true;
      ev.preventDefault();
      currentFlightId = flightId;
      currentTargetHref = targetHref;
      rrFetchTravelOptions('cheapest', true);
      return false;
    };

    window.rrReloadTravelOptions = function () {
      var sortEl = document.getElementById('rr_tm_sort');
      rrFetchTravelOptions(sortEl ? sortEl.value : 'cheapest', false);
    };

    function rrFetchTravelOptions(sortBy, autoNavigateIfReady) {
      fetch(RR_TRAVEL_OPTS_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': RR_TRAVEL_CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ flight_id: currentFlightId, sort_by: sortBy })
      })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res || !res.success) {
          alert((res && res.message) ? res.message : 'Unable to check aircraft availability.');
          return;
        }
        if (!res.needs_reposition && autoNavigateIfReady) {
          window.location.href = currentTargetHref;
          return;
        }
        currentTravelData = res;
        rrRenderTravelModal(res);
        var bg = document.getElementById('rr_travel_modal_backdrop');
        if (bg) bg.style.display = 'flex';
      })
      .catch(function () {
        window.location.href = currentTargetHref;
      });
    }

    function rrRenderTravelModal(data) {
      document.getElementById('rr_tm_subtitle').textContent =
        'Flight ' + data.flight_ident + ' (' + data.dep_airport_id + ' ➔ ' + data.arr_airport_id + ') • Rates via ' + data.rates_source;

      document.getElementById('rr_tm_summary_bar').innerHTML =
        '<div><strong>Your Location:</strong> ' + data.pilot_curr_airport +
        (data.pilot_needs_travel ? (' <span class="badge bg-warning text-dark">Travel to ' + data.dep_airport_id + ' (' + data.pilot_dist_nm + ' NM — $' + Number(data.pilot_cost).toFixed(2) + ')</span>') : ' <span class="badge bg-success">Already at ' + data.dep_airport_id + '</span>') +
        '</div><div style="margin-top: 4px;"><strong>Your Pilot Pay Balance:</strong> <span style="color: #16a34a; font-weight: 700;">$' + Number(data.pilot_balance).toFixed(2) + '</span></div>';

      var listEl = document.getElementById('rr_tm_aircraft_list');
      var opts = data.aircraft_options || [];
      if (!opts.length) {
        listEl.innerHTML = '<div class="text-danger small p-2">No eligible idle aircraft found for this flight.</div>';
        return;
      }

      selectedAircraftId = opts[0].aircraft_id;
      var html = '';
      opts.forEach(function (o, idx) {
        var checked = idx === 0 ? 'checked' : '';
        var affordBadge = o.can_afford
          ? '<span class="badge bg-success">Affordable</span>'
          : '<span class="badge bg-danger">Insufficient Pilot Pay</span>';
        html += '<label style="display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 8px; border-bottom: 1px solid #f1f5f9; cursor: pointer; font-size: 12.5px;">' +
          '<div><input type="radio" name="rr_tm_ac" value="' + o.aircraft_id + '" ' + checked + ' onchange="rrSelectTravelAircraft(' + o.aircraft_id + ')"> ' +
          '<strong>' + o.registration + '</strong> (' + o.icao + ') — Parked at <code>' + o.airport_id + '</code>' +
          (o.needs_move ? (' (' + o.distance_nm + ' NM • Ferry $' + Number(o.aircraft_cost).toFixed(2) + ')') : ' <span class="badge bg-success">At ' + data.dep_airport_id + '</span>') +
          '<div style="font-size: 11px; color: #64748b; margin-left: 18px;">Your Last Use: ' + o.pilot_last_used + ' &bull; Fleet Last Use: ' + o.global_last_used + '</div></div>' +
          '<div style="text-align: right; white-space: nowrap;"><strong>Total: $' + Number(o.total_cost).toFixed(2) + '</strong><br>' + affordBadge + '</div></label>';
      });
      listEl.innerHTML = html;
      rrSelectTravelAircraft(selectedAircraftId);
    }

    window.rrSelectTravelAircraft = function (acId) {
      selectedAircraftId = acId;
      if (!currentTravelData) return;
      var chosen = (currentTravelData.aircraft_options || []).find(function (o) { return Number(o.aircraft_id) === Number(acId); });
      if (!chosen) return;

      var payBox = document.getElementById('rr_tm_pay_status');
      var payBtn = document.getElementById('rr_tm_pay_btn');
      var askBtn = document.getElementById('rr_tm_ask_admin_btn');

      payBox.style.display = 'block';
      if (chosen.can_afford) {
        payBox.style.background = '#f0fdf4';
        payBox.style.border = '1px solid #16a34a';
        payBox.style.color = '#166534';
        payBox.innerHTML = '<strong>&#10003; Ready to Travel:</strong> Total combined cost is <strong>$' + Number(chosen.total_cost).toFixed(2) + '</strong> (Pilot: $' + Number(chosen.pilot_cost).toFixed(2) + ' + Aircraft ' + chosen.registration + ': $' + Number(chosen.aircraft_cost).toFixed(2) + ').';
        payBtn.style.display = 'inline-block';
        askBtn.style.display = 'none';
      } else {
        payBox.style.background = '#fef2f2';
        payBox.style.border = '1px solid #dc2626';
        payBox.style.color = '#7f1d1d';
        payBox.innerHTML = '<strong>&#9888; Travel Blocked — Insufficient Pilot Pay:</strong> You need <strong>$' + Number(chosen.total_cost).toFixed(2) + '</strong> to move both yourself and aircraft <strong>' + chosen.registration + '</strong>, but your Pilot Pay balance is only <strong>$' + Number(currentTravelData.pilot_balance).toFixed(2) + '</strong>. Please ask an Admin to move you.';
        payBtn.style.display = 'none';
        askBtn.style.display = 'inline-block';
      }
    };

    window.rrExecuteSelfTravel = function () {
      if (!selectedAircraftId || !currentFlightId) return;
      var btn = document.getElementById('rr_tm_pay_btn');
      btn.disabled = true;
      btn.innerHTML = '&#9889; Moving Pilot &amp; Aircraft...';
      fetch(RR_TRAVEL_EXEC_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': RR_TRAVEL_CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ flight_id: currentFlightId, aircraft_id: selectedAircraftId })
      })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        btn.disabled = false;
        btn.innerHTML = '&#9889; Pay &amp; Travel to Airport';
        if (res && res.success) {
          alert(res.message);
          window.location.href = currentTargetHref;
        } else {
          alert((res && res.message) ? res.message : 'Travel failed.');
        }
      });
    };

    window.rrRequestAdminMove = function () {
      if (!selectedAircraftId || !currentFlightId) return;
      var btn = document.getElementById('rr_tm_ask_admin_btn');
      btn.disabled = true;
      btn.innerHTML = '&#128232; Sending Request...';
      fetch(RR_TRAVEL_ADMIN_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': RR_TRAVEL_CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ flight_id: currentFlightId, aircraft_id: selectedAircraftId })
      })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        btn.disabled = false;
        btn.innerHTML = '&#128232; Ask Admin to Move Me';
        alert((res && res.message) ? res.message : 'Request processed.');
        if (res && res.success) {
          rrCloseTravelModal();
          window.location.reload();
        }
      });
    };
  })();
</script>

<script>
  (function () {
    var vatsimLayer = null;
    var vatsimLoaded = false;
    window.rrToggleVatsimOverlay = function () {
      var map = window.rrPilotMapInstance;
      var statusEl = document.getElementById('rr_vatsim_status');
      if (!map || !window.L) return;
      if (vatsimLayer && map.hasLayer(vatsimLayer)) {
        map.removeLayer(vatsimLayer);
        if (statusEl) statusEl.textContent = 'VATSIM ATC overlay hidden';
        return;
      }
      if (vatsimLoaded && vatsimLayer) {
        vatsimLayer.addTo(map);
        if (statusEl) statusEl.textContent = 'VATSIM ATC overlay active';
        return;
      }
      if (statusEl) statusEl.textContent = 'Loading live VATSIM ATC...';
      fetch('{{ route("afterburner.vatsim_atc") }}')
        .then(function (r) { return r.json(); })
        .then(function (res) {
          var list = (res && Array.isArray(res.airports)) ? res.airports : [];
          vatsimLayer = L.layerGroup();
          list.forEach(function (a) {
            var st = (a.stations || []).join('<br>');
            L.circleMarker([a.lat, a.lon], { radius: 5, color: '#15803d', fillColor: '#22c55e', fillOpacity: 0.85 })
              .bindPopup('<strong>' + a.icao + ' — ' + a.name + '</strong><br><small>' + st + '</small>')
              .addTo(vatsimLayer);
          });
          vatsimLayer.addTo(map);
          vatsimLoaded = true;
          if (statusEl) statusEl.textContent = 'Showing ' + list.length + ' staffed VATSIM airport(s)';
        })
        .catch(function () {
          if (statusEl) statusEl.textContent = 'Could not load VATSIM ATC feed';
        });
    };
  })();
</script>

<script>
  window.rrToggleCommunityUpvote = function (reqId, btn) {
    if (!btn || btn.disabled) return;
    btn.disabled = true;
    fetch('{{ url("/afterburner") }}/' + reqId + '/upvote', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      },
      body: JSON.stringify({})
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
      btn.disabled = false;
      if (!res || !res.success) {
        alert((res && res.message) ? res.message : 'Unable to update upvote.');
        return;
      }
      btn.classList.toggle('rr-upvoted', !!res.upvoted);
      var lbl = btn.querySelector('.rr-upv-label');
      var cnt = btn.querySelector('.rr-upv-count');
      if (lbl) lbl.textContent = res.upvoted ? 'Upvoted' : 'Upvote';
      if (cnt) cnt.textContent = '(' + res.count + ')';
    })
    .catch(function () { btn.disabled = false; });
  };
</script>

<script>
  (function () {
    var depEl = document.getElementById('dep_airport_id');
    var arrEl = document.getElementById('arr_airport_id');
    var box = document.getElementById('rr_duplicate_guard_box');
    if (!depEl || !arrEl || !box) return;

    var timer = null;
    function checkDup() {
      var dep = (depEl.value || '').trim().toUpperCase();
      var arr = (arrEl.value || '').trim().toUpperCase();
      if (dep.length < 3 || arr.length < 3) {
        box.style.display = 'none';
        return;
      }
      fetch('{{ route("afterburner.check_duplicate") }}?dep=' + encodeURIComponent(dep) + '&arr=' + encodeURIComponent(arr), {
        headers: { 'Accept': 'application/json' }
      })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res || !res.enabled || ((!res.flights || !res.flights.length) && (!res.open_requests || !res.open_requests.length))) {
          box.style.display = 'none';
          return;
        }
        var html = '<div class="fw-bold mb-1">&#9888; Existing Schedule / Open Request Found for ' + res.dep + ' &rarr; ' + res.arr + '</div>';
        if (res.flights && res.flights.length) {
          html += '<div class="mb-1"><strong>Already in Schedule:</strong> ';
          html += res.flights.map(function (fl) {
            return '<a href="' + fl.url + '" target="_blank" class="badge bg-success text-decoration-none me-1">&#9992; ' + fl.ident + ' (Fly/Bid)</a>';
          }).join(' ');
          html += '</div>';
        }
        if (res.open_requests && res.open_requests.length) {
          html += '<div><strong>Open Crew Requests:</strong> ';
          html += res.open_requests.map(function (oq) {
            if (oq.is_own) {
              return '<span class="badge bg-secondary me-1">#' + oq.id + ' (Your open request)</span>';
            }
            return '<button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 me-1" onclick="rrToggleCommunityUpvote(' + oq.id + ', this)">&#9650; Upvote #' + oq.id + ' (' + oq.pilot + ') <span class="rr-upv-count">(' + oq.upvotes + ')</span></button>';
          }).join(' ');
          html += '</div>';
        }
        box.innerHTML = html;
        box.style.display = 'block';
      })
      .catch(function () {});
    }

    function scheduleCheck() {
      clearTimeout(timer);
      timer = setTimeout(checkDup, 250);
    }

    depEl.addEventListener('change', scheduleCheck);
    arrEl.addEventListener('change', scheduleCheck);
    if (window.jQuery) {
      window.jQuery(depEl).on('select2:select change', scheduleCheck);
      window.jQuery(arrEl).on('select2:select change', scheduleCheck);
    }
  })();
</script>
@endsection