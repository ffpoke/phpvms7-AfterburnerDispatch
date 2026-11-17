@extends('admin.app')
@section('title', 'Pilot Route Requests')

@section('content')
@php
  $daysOfWeek = [
    1  => 'Mon',
    2  => 'Tue',
    4  => 'Wed',
    8  => 'Thu',
    16 => 'Fri',
    32 => 'Sat',
    64 => 'Sun',
  ];
  $maxReturnCap = $maxReturnPairs ?? 4;
@endphp
<style>
  /* Mobile & Tablet Responsive Fix for phpVMS v7 Admin Panel (< 992px) */
  @media (max-width: 991.98px) {
    .rr-admin-table-wrap {
      overflow-x: hidden !important;
      width: 100% !important;
      max-width: 100% !important;
    }
    table.rr-admin-table,
    table.rr-admin-table tbody {
      display: block !important;
      width: 100% !important;
      max-width: 100% !important;
    }
    table.rr-admin-table thead {
      display: none !important;
    }
    table.rr-admin-table tr.rr-req-row {
      display: block !important;
      width: 100% !important;
      max-width: 100% !important;
      margin-bottom: 14px !important;
      border: 1px solid #cbd5e1 !important;
      border-radius: 6px !important;
      background: #ffffff !important;
      padding: 10px 12px !important;
      box-sizing: border-box !important;
    }
    table.rr-admin-table tr.rr-req-row > td {
      display: block !important;
      width: 100% !important;
      max-width: 100% !important;
      min-width: 0 !important;
      padding: 5px 0 !important;
      border: none !important;
      border-bottom: 1px dashed #e2e8f0 !important;
      white-space: normal !important;
      box-sizing: border-box !important;
      text-align: left !important;
    }
    table.rr-admin-table tr.rr-req-row > td:last-child {
      border-bottom: none !important;
    }
    table.rr-admin-table tr.rr-req-row > td[data-label]::before {
      content: attr(data-label) ": ";
      font-weight: 700;
      color: #334155;
      display: inline-block;
      margin-right: 6px;
    }
    table.rr-admin-table tr.rr-gen-drawer-row {
      display: block !important;
      width: 100% !important;
      max-width: 100% !important;
      margin-top: -10px !important;
      margin-bottom: 16px !important;
      box-sizing: border-box !important;
    }
    table.rr-admin-table tr.rr-gen-drawer-row > td {
      display: block !important;
      width: 100% !important;
      max-width: 100% !important;
      padding: 12px !important;
      box-sizing: border-box !important;
      white-space: normal !important;
    }
    .rr-gen-drawer-card .row {
      margin-left: 0 !important;
      margin-right: 0 !important;
    }
    .rr-gen-drawer-card [class*="col-"] {
      padding-left: 0 !important;
      padding-right: 0 !important;
      width: 100% !important;
      max-width: 100% !important;
      margin-bottom: 8px !important;
    }
    .rr-gen-drawer-card .rr-time-row [class*="col-xs-6"] {
      width: 48% !important;
      display: inline-block !important;
      vertical-align: top !important;
    }
    .rr-gen-drawer-card .select2-container {
      width: 100% !important;
      max-width: 100% !important;
    }
    .rr-gen-drawer-card .btn {
      width: 100% !important;
      margin-bottom: 6px !important;
      white-space: normal !important;
    }
    .rr-status-form .form-group,
    .rr-status-form .form-control,
    .rr-status-form .btn {
      width: 100% !important;
      display: block !important;
      margin-bottom: 6px !important;
    }
  }
</style>

<div class="card border-blue-bottom">
  <div class="content">
    <div class="header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
      <div>
        <h4 class="title" style="margin-bottom: 4px;">
          Pilot Route Requests — v2.1.0 <span class="label label-primary" style="background: linear-gradient(90deg, #0284c7, #2563eb); color: #fff;">&#9889; Afterburner Edition</span>
        </h4>
        <p class="category">
          Default Dispatch Notification Email: <strong>{{ $recipientEmail }}</strong>
          &bull; GitHub Auto-Updater (<code>{{ $updateInfo['repo'] ?? 'ffpoke/phpvms7-RouteRequest' }}</code>)
          &bull; Local IndexedDB Airport Engine
        </p>
      </div>
      <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 6px;">
        <span id="rr_admin_cache_badge" class="label label-info" style="font-size: 12px; padding: 5px 10px;">
          &#9889; Syncing Local Airport DB...
        </span>
        <button type="button" class="btn btn-xs btn-default" onclick="window.RRAfterburnerAdminCache && window.RRAfterburnerAdminCache.forceRefresh()">
          &#8635; Refresh Cache
        </button>
        <form method="POST" action="{{ route('routerequest.admin.check_update') }}" style="display: inline-block; margin: 0;">
          @csrf
          <button type="submit" class="btn btn-xs btn-info btn-fill" title="Check GitHub Releases (ffpoke/phpvms7-RouteRequest) for a newer version">
            &#8635; Check for Updates
          </button>
        </form>
      </div>
    </div>

    {{-- Method B + Bonus 1-Click Self-Updater Banner --}}
    @if(!empty($updateInfo['update_available']))
      <div class="alert alert-warning" style="margin: 12px 15px 4px 15px; border-left: 5px solid #d97706; background-color: #fffbeb; color: #78350f;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
          <div>
            <h5 style="margin: 0 0 4px 0; font-weight: bold; color: #92400e;">
              &#128640; Update Available: RouteRequest v{{ $updateInfo['latest_version'] }}
              @if(!empty($updateInfo['release_name']))
                ({{ $updateInfo['release_name'] }})
              @endif
            </h5>
            <div class="small">
              Installed Version: <strong>v{{ $updateInfo['current_version'] }}</strong> &bull;
              Latest GitHub Release: <strong>v{{ $updateInfo['latest_version'] }}</strong>
              @if(!empty($updateInfo['published_at']))
                &bull; Published: {{ $updateInfo['published_at'] }}
              @endif
            </div>
          </div>
          <div style="display: flex; gap: 6px; flex-wrap: wrap;">
            <form method="POST" action="{{ route('routerequest.admin.install_update') }}" style="display: inline-block; margin: 0;" onsubmit="return confirm('Download and install RouteRequest v{{ $updateInfo['latest_version'] }} directly over /modules/RouteRequest now?');">
              @csrf
              <button type="submit" class="btn btn-sm btn-success btn-fill">
                &#9889; 1-Click Install Update (v{{ $updateInfo['latest_version'] }})
              </button>
            </form>
            @if(!empty($updateInfo['download_url']))
              <a href="{{ $updateInfo['download_url'] }}" target="_blank" class="btn btn-sm btn-primary btn-fill">
                &#8681; Direct Download ZIP
              </a>
            @endif
          </div>
        </div>
        @if(!empty($updateInfo['changelog']))
          <div style="margin-top: 8px; padding-top: 8px; border-top: 1px dashed #f59e0b; font-size: 12px; white-space: pre-line;">{{ $updateInfo['changelog'] }}</div>
        @endif
      </div>
    @endif

    <div class="content table-responsive table-full-width rr-admin-table-wrap">
      @if($requests->count() === 0)
        <p class="text-center p-4">No pilot route requests have been submitted yet.</p>
      @else
        <table class="table table-hover table-striped rr-admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Pilot</th>
              <th>Dep &rarr; Arr, Trip Type &amp; Dispatch</th>
              <th>Signed-Off Type Rating &amp; Fleet Status</th>
              <th>Real-World / Charter Counterpart &amp; Lookup</th>
              <th>Current Status</th>
              <th>Pilot Notes &amp; FA Links</th>
              <th>Update Status &amp; Notify</th>
              <th>Delete</th>
            </tr>
          </thead>
          <tbody>
            @foreach($requests as $req)
              @php
                $linkedSubfleets = ($req->typerating && $req->typerating->subfleets)
                  ? $req->typerating->subfleets
                  : collect();

                $defaultSubfleetIds = $linkedSubfleets->pluck('id')->toArray();
                $linkedSubfleetCount = $linkedSubfleets->count();

                $linkedAircraftCount = $linkedSubfleets->sum(function ($sf) {
                  return $sf->aircraft ? $sf->aircraft->count() : 0;
                });

                $defaultAirlineId = null;
                foreach ($linkedSubfleets as $lsf) {
                  if (!empty($lsf->airline_id)) {
                    $defaultAirlineId = (int) $lsf->airline_id;
                    break;
                  }
                }
                if (!$defaultAirlineId && $airlines->isNotEmpty()) {
                  $defaultAirlineId = (int) $airlines->first()->id;
                }

                $defaultOutboundNum = ($defaultAirlineId && isset($nextFlightNumbers[$defaultAirlineId]))
                  ? (int) $nextFlightNumbers[$defaultAirlineId]
                  : 100;
                $defaultReturnNum = $defaultOutboundNum + 1;

                $rwLower = strtolower($req->realworld_airline ?? '');
                $isCharter = str_contains($rwLower, 'charter') || str_contains($rwLower, 'cobalt') || str_contains($rwLower, 'xo') || str_contains($rwLower, 'netjets') || str_contains($rwLower, 'vistajet') || str_contains($rwLower, 'flexjet') || str_contains($rwLower, 'wheels up') || str_contains($rwLower, 'jsx') || str_contains($rwLower, 'planesense');
                $defaultFlightType = $isCharter ? 'C' : 'J';

                $genFlights = $req->generated_flight_models;
                $existingReturnCount = $genFlights->filter(function ($gf) use ($req) {
                  return strtoupper((string) $gf->arr_airport_id) === strtoupper((string) $req->dep_airport_id);
                })->count();

                $isDrawerOpen = isset($openGenId) && (int) $openGenId === (int) $req->id;
                $isMultiDest = (bool) $req->multiple_destinations || $genFlights->count() > 2;
                $pilotWantsReturn = (bool) $req->request_return && ($existingReturnCount < $maxReturnCap);
                $faUrls = $req->flight_aware_urls;
                $canQuickCreate = !empty($req->arr_airport_id) || count($faUrls) > 0;
              @endphp
              <tr class="rr-req-row">
                <td data-label="Request ID">#{{ $req->id }}</td>
                <td data-label="Pilot">
                  @if($req->user)
                    <strong>{{ $req->user->ident }}</strong> - {{ $req->user->name_private }}
                    <br><small class="text-muted">{{ $req->user->email }}</small>
                  @else
                    User #{{ $req->user_id }}
                  @endif
                </td>
                <td data-label="Route & Trip Type">
                  <strong>{{ $req->dep_airport_id }}</strong>
                  @if(!$req->dep_airport)
                    <span class="label label-danger" title="Airport not found in phpVMS airports table">!</span>
                  @endif
                  &rarr;
                  <strong>{{ $req->arr_airport_id ?: 'ANY (Open)' }}</strong>
                  @if($req->arr_airport_id && !$req->arr_airport)
                    <span class="label label-danger" title="Airport not found in phpVMS airports table">!</span>
                  @endif

                  <div style="margin-top: 4px;">
                    @if($req->request_return)
                      <span class="label label-success" style="display: inline-block; margin-bottom: 3px;">
                        &#8644; ROUND-TRIP REQUESTED
                      </span>
                    @else
                      <span class="label label-default" style="display: inline-block; margin-bottom: 3px;">
                        &rarr; ONE-WAY ONLY REQUESTED
                      </span>
                    @endif

                    @if($isMultiDest)
                      <span id="row_multi_badge_{{ $req->id }}" class="label label-primary" style="background-color: #7c3aed; color: #fff; font-weight: bold; display: inline-block; margin-bottom: 3px;">
                        &#9745; MULTI-DEST (<span id="row_return_count_{{ $req->id }}">{{ $existingReturnCount }}</span>/{{ $maxReturnCap }} Return Pairs)
                      </span>
                    @endif
                  </div>

                  <div style="margin-top: 3px;">
                    <button
                      type="button"
                      class="btn btn-xs btn-success"
                      onclick="toggleFlightGenerator({{ $req->id }})"
                      title="Open built-in phpVMS Flight Schedule Generator for Request #{{ $req->id }}"
                    >
                      + Generate Flight{{ ($isMultiDest || $req->request_return) ? '(s)' : '' }}
                    </button>
                  </div>

                  @if($canQuickCreate && $req->status !== 'completed')
                    <form method="POST" action="{{ route('routerequest.admin.quick_generate', $req->id) }}" style="margin-top: 5px; background: #f8fafc; padding: 4px 6px; border: 1px solid #cbd5e1; border-radius: 4px;">
                      @csrf
                      @if(count($faUrls) > 0)
                        <div style="margin-bottom: 3px;">
                          <small class="text-primary fw-bold">FA Link Data:</small>
                          <select name="fa_import_mode" class="form-control input-sm no-select2" style="height: 24px; padding: 1px 4px; font-size: 11px; display: inline-block; width: auto;">
                            <option value="route_only">Route Only (No Fixed Alt)</option>
                            <option value="route_and_altitude">Route + Altitude</option>
                            <option value="none">Ignore FA Link</option>
                          </select>
                        </div>
                      @else
                        <input type="hidden" name="fa_import_mode" value="none">
                      @endif
                      <button type="submit" class="btn btn-xs btn-warning btn-fill" title="1-Click Auto-Create {{ $req->request_return ? 'Round-Trip' : 'One-Way' }} with Auto VA Airline &amp; Next Flight #">
                        &#9889; 1-Click Auto-Create ({{ $req->request_return ? 'Round-Trip' : 'One-Way' }})
                      </button>
                    </form>
                  @endif

                  <div id="row_generated_flights_wrap_{{ $req->id }}" style="margin-top: 5px; {{ $genFlights->count() > 0 ? '' : 'display: none;' }}">
                    <small class="text-muted"><strong>Generated (<span id="row_gen_total_{{ $req->id }}">{{ $genFlights->count() }}</span>):</strong></small><br>
                    <div id="row_gen_badges_{{ $req->id }}">
                      @foreach($genFlights as $gf)
                        @php $gfCode = optional($gf->airline)->icao ?? optional($gf->airline)->code ?? ''; @endphp
                        <a href="{{ url('/admin/flights/' . $gf->id . '/edit') }}" class="btn btn-xs btn-primary" style="margin-top: 2px;" target="_blank" title="Edit Flight {{ $gfCode }}{{ $gf->flight_number }}">
                          &#9998; {{ $gfCode }}{{ $gf->flight_number }} ({{ $gf->dpt_airport_id }}&rarr;{{ $gf->arr_airport_id }})
                        </a>
                      @endforeach
                    </div>
                  </div>
                </td>
                <td data-label="Type Rating & Fleet">
                  <strong>{{ $req->aircraft_type }}</strong>
                  <br>
                  @if($linkedSubfleetCount === 0)
                    <span class="label label-warning" title="No Subfleet is linked to this Type Rating yet!">
                      &#9888; No Subfleet Linked
                    </span>
                  @elseif($linkedAircraftCount === 0)
                    <span class="label label-warning" title="Subfleet(s) linked, but 0 aircraft exist in those subfleets!">
                      &#9888; 0 Aircraft in Subfleet ({{ $linkedSubfleetCount }} SF)
                    </span>
                  @else
                    <span class="label label-success" title="Active subfleets and aircraft are linked to this Type Rating">
                      &#10003; {{ $linkedSubfleetCount }} Subfleet(s) &bull; {{ $linkedAircraftCount }} Aircraft
                    </span>
                  @endif
                </td>
                <td data-label="Counterpart & Lookup">
                  <strong>{{ $req->realworld_airline }}</strong>
                  <div style="margin-top: 4px;">
                    @if($req->arr_airport_id && !$isMultiDest)
                      <a
                        href="https://www.flightaware.com/live/findflight?origin={{ urlencode($req->dep_airport_id) }}&destination={{ urlencode($req->arr_airport_id) }}"
                        target="_blank"
                        class="btn btn-xs btn-default"
                        title="Search real-world flights from {{ $req->dep_airport_id }} to {{ $req->arr_airport_id }} on FlightAware"
                      >
                        FA Route
                      </a>
                    @else
                      <a
                        href="https://www.flightaware.com/live/airport/{{ urlencode($req->dep_airport_id) }}"
                        target="_blank"
                        class="btn btn-xs btn-default"
                        title="View live departures from {{ $req->dep_airport_id }} on FlightAware"
                      >
                        FA Airport
                      </a>
                    @endif

                    <a
                      href="https://www.flightradar24.com/data/airports/{{ strtolower(urlencode($req->dep_airport_id)) }}/routes"
                      target="_blank"
                      class="btn btn-xs btn-default"
                      title="View real-world routes from {{ $req->dep_airport_id }} on Flightradar24"
                    >
                      FR24 Routes
                    </a>
                  </div>
                </td>
                <td data-label="Status">
                  <span id="row_status_badge_{{ $req->id }}">
                    @if($req->status === 'completed')
                      <span class="label label-success">Completed (Route Active)</span>
                    @elseif($req->status === 'approved' || $req->status === 'in_progress')
                      <span class="label label-info">Accepted (In Progress)</span>
                    @elseif($req->status === 'cancelled')
                      <span class="label label-default">Cancelled by Pilot</span>
                    @elseif($req->status === 'rejected')
                      <span class="label label-danger">Rejected / Unable</span>
                    @else
                      <span class="label label-warning">Pending Review</span>
                    @endif
                  </span>
                  <br><small><code>{{ $req->recipient_email }}</code></small>
                </td>
                <td data-label="Pilot Notes">
                  {{ $req->notes ?: '—' }}
                  @if(count($faUrls) > 0)
                    <div style="margin-top: 4px;">
                      <span class="label label-info">{{ count($faUrls) }} FlightAware Link(s) Detected</span>
                    </div>
                  @endif
                </td>
                <td data-label="Update Status" style="min-width: 280px;">
                  <form method="POST" action="{{ route('routerequest.admin.status', $req->id) }}" class="form-inline rr-status-form">
                    @csrf
                    <div class="form-group mb-1">
                      <select name="status" class="form-control input-sm no-select2">
                        <option value="pending" {{ $req->status === 'pending' ? 'selected' : '' }}>
                          Pending — Awaiting Review
                        </option>
                        <option value="approved" {{ ($req->status === 'approved' || $req->status === 'in_progress') ? 'selected' : '' }}>
                          Accepted — In Progress (Building Route)
                        </option>
                        <option value="completed" {{ $req->status === 'completed' ? 'selected' : '' }} {{ (empty($req->arr_airport_id) && $genFlights->count() === 0) ? 'disabled' : '' }}>
                          Completed — Route Active {{ (empty($req->arr_airport_id) && $genFlights->count() === 0) ? '(Requires Flight/Dest)' : '(Done)' }}
                        </option>
                        <option value="rejected" {{ $req->status === 'rejected' ? 'selected' : '' }}>
                          Rejected — Unable to Fulfill
                        </option>
                        <option value="cancelled" {{ $req->status === 'cancelled' ? 'selected' : '' }}>
                          Cancelled by Pilot
                        </option>
                      </select>
                    </div>
                    <div class="form-group mb-1">
                      <input
                        type="email"
                        name="recipient_email"
                        class="form-control input-sm"
                        value="{{ $req->recipient_email }}"
                        placeholder="Email recipient"
                      >
                    </div>
                    <div class="form-group mb-1">
                      <input
                        type="text"
                        name="admin_notes"
                        id="row_admin_notes_{{ $req->id }}"
                        class="form-control input-sm"
                        value="{{ $req->admin_notes }}"
                        placeholder="Admin notes / added flight #"
                      >
                    </div>
                    <button type="submit" class="btn btn-sm btn-info">Update &amp; Email</button>
                  </form>
                </td>
                <td data-label="Delete">
                  <form method="POST" action="{{ route('routerequest.admin.destroy', $req->id) }}" onsubmit="return confirm('Delete this route request?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                  </form>
                </td>
              </tr>

              <!-- Built-In Automated Flight Schedule Generator Drawer (v2.0.0 Afterburner Edition) for Request #{{ $req->id }} -->
              <tr id="flight_gen_row_{{ $req->id }}" class="rr-gen-drawer-row" style="{{ $isDrawerOpen ? '' : 'display: none !important;' }} background-color: #f0f9ff;">
                <td colspan="9" class="rr-gen-drawer-card" style="padding: 15px 20px; border-left: 4px solid #0284c7;">
                  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px; margin-bottom: 10px;">
                    <h5 style="margin: 0; font-weight: bold; color: #0369a1;">
                      Generate Flight Schedule for Request #{{ $req->id }}
                      @if($req->request_return)
                        <span class="label label-success" style="margin-left: 6px;">&#8644; PILOT REQUESTED ROUND-TRIP</span>
                      @else
                        <span class="label label-default" style="margin-left: 6px;">&rarr; PILOT REQUESTED ONE-WAY</span>
                      @endif
                      @if($isMultiDest)
                        <span class="label label-primary" style="background-color: #7c3aed; color: #fff; margin-left: 6px;">
                          &#9745; MULTI-DEST (<span id="drawer_return_count_{{ $req->id }}">{{ $existingReturnCount }}</span>/{{ $maxReturnCap }} Return Pairs Used)
                        </span>
                      @endif
                      <small style="color: #475569; font-weight: normal; display: block; margin-top: 2px;">
                        Type Rating: <strong>{{ $req->aircraft_type }}</strong> &bull; Counterpart: <strong>{{ $req->realworld_airline }}</strong>
                      </small>
                    </h5>
                    <button type="button" class="btn btn-xs btn-default" style="width: auto !important;" onclick="toggleFlightGenerator({{ $req->id }})">
                      Close &times;
                    </button>
                  </div>

                  {{-- Speedup C: FlightAware Link Auto-Matcher & Route / Altitude Importer --}}
                  <div class="alert alert-info" style="padding: 10px 12px; margin-bottom: 12px; background-color: #e0f2fe; border-left: 4px solid #0284c7; color: #0c4a6e;">
                    <div class="row" style="align-items: center;">
                      <div class="col-md-5">
                        <strong>&#9992; FlightAware Route &amp; Altitude Auto-Matcher:</strong>
                        <div class="small">
                          @if(count($faUrls) > 0)
                            Detected <strong>{{ count($faUrls) }}</strong> FlightAware link(s) in Pilot Notes. Auto-matches Outbound vs Return even if pasted backwards!
                          @else
                            Paste 1 or 2 FlightAware flight URLs below (or in Pilot Notes) to auto-fill Route (or Route + Altitude):
                          @endif
                        </div>
                        <input
                          type="text"
                          id="fa_custom_urls_{{ $req->id }}"
                          class="form-control input-sm"
                          style="margin-top: 4px;"
                          value="{{ implode(' ', $faUrls) }}"
                          placeholder="Paste 1 or 2 FlightAware URLs (Outbound & Return)..."
                        >
                      </div>
                      <div class="col-md-4">
                        <label class="small fw-bold" style="color: #0c4a6e;">Select Data to Import from FlightAware:</label>
                        <select id="fa_import_mode_{{ $req->id }}" class="form-control input-sm no-select2">
                          <option value="route_only" selected>Use Flight Data for ROUTE ONLY (Leave Altitude Blank)</option>
                          <option value="route_and_altitude">Use Flight Data for ROUTE + ALTITUDE</option>
                        </select>
                      </div>
                      <div class="col-md-3" style="padding-top: 18px;">
                        <button
                          type="button"
                          class="btn btn-sm btn-primary btn-fill"
                          onclick="fetchAndApplyFlightAware({{ $req->id }})"
                        >
                          &#8681; Fetch &amp; Match FA Data
                        </button>
                      </div>
                    </div>
                    <div id="fa_match_status_{{ $req->id }}" class="small" style="margin-top: 6px; display: none; font-weight: bold;"></div>
                  </div>

                  {{-- Live AJAX Banner for Multi-Destination Flights Generated --}}
                  <div id="drawer_gen_banner_{{ $req->id }}" class="alert alert-info" style="padding: 8px 12px; margin-bottom: 12px; border-left: 4px solid #0284c7; {{ $genFlights->count() > 0 ? '' : 'display: none;' }}">
                    <strong>&#9992; Flights Generated for Request #{{ $req->id }} (<span id="drawer_gen_total_{{ $req->id }}">{{ $genFlights->count() }}</span> total):</strong>
                    <span id="drawer_gen_badges_{{ $req->id }}">
                      @foreach($genFlights as $gf)
                        @php $gfCode = optional($gf->airline)->icao ?? optional($gf->airline)->code ?? ''; @endphp
                        <span class="label label-primary" style="margin-right: 4px; display: inline-block; margin-top: 2px;">
                          {{ $gfCode }}{{ $gf->flight_number }} ({{ $gf->dpt_airport_id }} &rarr; {{ $gf->arr_airport_id }})
                        </span>
                      @endforeach
                    </span>
                    <div id="drawer_ajax_msg_{{ $req->id }}" class="small text-success fw-bold" style="margin-top: 4px;"></div>
                  </div>

                  @if($linkedSubfleetCount === 0)
                    <div class="alert alert-warning" style="padding: 8px 12px; margin-bottom: 12px; border-left: 4px solid #d97706;">
                      <strong>&#9888; Warning — No Subfleet Linked to Type Rating ({{ $req->aircraft_type }}):</strong>
                      This Type Rating does not currently have any Subfleet assigned to it in phpVMS.
                    </div>
                  @elseif($linkedAircraftCount === 0)
                    <div class="alert alert-warning" style="padding: 8px 12px; margin-bottom: 12px; border-left: 4px solid #d97706;">
                      <strong>&#9888; Warning — 0 Active Aircraft in Linked Subfleet(s):</strong>
                      The subfleet(s) linked to <strong>{{ $req->aircraft_type }}</strong> currently have <strong>0 aircraft registrations</strong> in your fleet.
                    </div>
                  @endif

                  <form method="POST" action="{{ route('routerequest.admin.generate_flight', $req->id) }}" id="gen_flight_form_{{ $req->id }}">
                    @csrf
                    <div class="row">
                      <!-- 1. Auto-Selected VA Airline -->
                      <div class="col-md-3">
                        <div class="form-group">
                          <label>VA Airline (Auto-Matched) <span class="text-danger">*</span></label>
                          <select
                            name="airline_id"
                            id="gen_airline_{{ $req->id }}"
                            class="form-control input-sm no-select2"
                            onchange="onAdminAirlineChange({{ $req->id }}, this.value)"
                            required
                          >
                            <option value="">-- Select VA Airline --</option>
                            @foreach($airlines as $airline)
                              <option value="{{ $airline->id }}" {{ (int) $defaultAirlineId === (int) $airline->id ? 'selected' : '' }}>
                                {{ $airline->icao ?? $airline->code }} — {{ $airline->name }}
                              </option>
                            @endforeach
                          </select>
                        </div>
                      </div>

                      <!-- 2. Outbound Flight Number -->
                      <div class="col-md-2">
                        <div class="form-group">
                          <label>Outbound Flight # <span class="text-danger">*</span></label>
                          <input
                            type="number"
                            name="flight_number"
                            class="form-control input-sm"
                            id="gen_flight_num_{{ $req->id }}"
                            value="{{ $defaultOutboundNum }}"
                            placeholder="Enter Flight #"
                            min="1"
                            max="99999"
                            oninput="onOutboundFlightNumInput({{ $req->id }}, this.value)"
                            required
                          >
                          <small class="text-muted">Next unused #</small>
                        </div>
                      </div>

                      <!-- 3. Flight Type -->
                      <div class="col-md-3">
                        <div class="form-group">
                          <label>Type of Flight (Flight Type) <span class="text-danger">*</span></label>
                          <select name="flight_type" class="form-control input-sm no-select2" required>
                            @foreach($flightTypes as $ftKey => $ftLabel)
                              <option value="{{ $ftKey }}" {{ $ftKey === $defaultFlightType ? 'selected' : '' }}>
                                {{ $ftLabel }}
                              </option>
                            @endforeach
                          </select>
                        </div>
                      </div>

                      <!-- 4. Flight Time (Hours & Minutes) -->
                      <div class="col-md-4">
                        <div class="form-group">
                          <label>Flight Time (Hours &amp; Minutes)</label>
                          <div class="row rr-time-row">
                            <div class="col-xs-6" style="padding-right: 4px;">
                              <input
                                type="number"
                                name="flight_hours"
                                class="form-control input-sm"
                                value="0"
                                min="0"
                                max="48"
                                placeholder="Hours (0)"
                                required
                              >
                              <small class="text-muted">Hours (0)</small>
                            </div>
                            <div class="col-xs-6" style="padding-left: 4px;">
                              <input
                                type="number"
                                name="flight_minutes"
                                class="form-control input-sm"
                                value="0"
                                min="0"
                                max="59"
                                placeholder="Minutes (0)"
                                required
                              >
                              <small class="text-muted">Minutes (0)</small>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="row" style="margin-top: 8px;">
                      <!-- 5. Departure Airport (Powered by Local IndexedDB 0ms Search) -->
                      <div class="col-md-3">
                        <div class="form-group">
                          <label>
                            Departure Airport (Verified) <span class="text-danger">*</span>
                          </label>
                          <select
                            name="dep_airport_id"
                            id="admin_dep_airport_{{ $req->id }}"
                            class="form-control input-sm admin_airport_select no-select2"
                            style="width: 100%;"
                            required
                          >
                            @if($req->dep_airport)
                              <option value="{{ $req->dep_airport->id }}" selected>
                                {{ $req->dep_airport->id }}{{ !empty($req->dep_airport->iata) ? ' / ' . $req->dep_airport->iata : '' }} — {{ $req->dep_airport->name }}
                              </option>
                            @elseif(!empty($req->dep_airport_id))
                              <option value="{{ $req->dep_airport_id }}" selected>
                                {{ $req->dep_airport_id }}
                              </option>
                            @endif
                          </select>
                        </div>
                      </div>

                      <!-- 6. Arrival Airport (Powered by Local IndexedDB 0ms Search) -->
                      <div class="col-md-3">
                        <div class="form-group">
                          <label>
                            Arrival Airport (Verified) <span class="text-danger">*</span>
                            @if($isMultiDest)
                              <small class="text-primary">(Multi-Dest)</small>
                            @elseif(empty($req->arr_airport_id))
                              <small class="text-warning">(Pilot left ANY)</small>
                            @endif
                          </label>
                          <select
                            name="arr_airport_id"
                            id="admin_arr_airport_{{ $req->id }}"
                            class="form-control input-sm admin_airport_select no-select2"
                            style="width: 100%;"
                            required
                          >
                            <option value="">-- Type ICAO (KATL), IATA, or City --</option>
                            @if(!$isMultiDest && $req->arr_airport)
                              <option value="{{ $req->arr_airport->id }}" selected>
                                {{ $req->arr_airport->id }}{{ !empty($req->arr_airport->iata) ? ' / ' . $req->arr_airport->iata : '' }} — {{ $req->arr_airport->name }}
                              </option>
                            @elseif(!$isMultiDest && !empty($req->arr_airport_id))
                              <option value="{{ $req->arr_airport_id }}" selected>
                                {{ $req->arr_airport_id }}
                              </option>
                            @endif
                          </select>
                        </div>
                      </div>

                      <!-- 7. Outbound Flight Altitude -->
                      <div class="col-md-3">
                        <div class="form-group">
                          <label>Outbound Altitude (Feet — Opt)</label>
                          <input
                            type="number"
                            name="level"
                            id="outbound_level_{{ $req->id }}"
                            class="form-control input-sm"
                            value=""
                            min="0"
                            max="60000"
                            step="500"
                            placeholder="Optional (leave blank if no fixed alt)"
                          >
                        </div>
                      </div>

                      <!-- 8. Optional ATC Callsign -->
                      <div class="col-md-3">
                        <div class="form-group">
                          <label>ATC Callsign (Optional)</label>
                          <input
                            type="text"
                            name="callsign"
                            class="form-control input-sm"
                            style="text-transform: uppercase;"
                            value=""
                            placeholder="Optional (e.g. XOJ712)"
                          >
                        </div>
                      </div>
                    </div>

                    {{-- Return Leg (Synced to Pilot's Request Return Checkbox, capped at 4 return pairs for Multi-Dest) --}}
                    <div class="row" style="margin-top: 8px; background-color: #e0f2fe; padding: 10px; border-radius: 4px; border: 1px dashed #0284c7;">
                      <div class="col-md-5">
                        <div class="form-check mb-1">
                          <input type="hidden" name="create_return_leg" value="0">
                          <input
                            class="form-check-input"
                            type="checkbox"
                            name="create_return_leg"
                            id="create_return_leg_{{ $req->id }}"
                            value="1"
                            {{ $pilotWantsReturn ? 'checked' : '' }}
                            {{ $existingReturnCount >= $maxReturnCap ? 'disabled' : '' }}
                            onchange="onReturnLegToggle({{ $req->id }})"
                          >
                          <label class="form-check-label fw-bold text-dark small" for="create_return_leg_{{ $req->id }}">
                            &#8644; Also Create Return Flight (Arr &rarr; Dep, Same VA Airline, Flight # + 1)
                            <span id="return_cap_warning_{{ $req->id }}" class="text-danger" style="{{ $existingReturnCount >= $maxReturnCap ? '' : 'display: none;' }}">(Max {{ $maxReturnCap }} Return Pairs Reached)</span>
                          </label>
                        </div>
                        <div class="form-check">
                          <input type="hidden" name="reject_return_leg" value="0">
                          <input
                            class="form-check-input"
                            type="checkbox"
                            name="reject_return_leg"
                            id="reject_return_leg_{{ $req->id }}"
                            value="1"
                            onchange="onRejectReturnToggle({{ $req->id }})"
                          >
                          <label class="form-check-label text-danger small fw-bold" for="reject_return_leg_{{ $req->id }}">
                            &#10006; Reject Return Leg / Outbound Only (Disables Return Flight)
                          </label>
                        </div>
                      </div>

                      <div class="col-md-7" id="return_options_div_{{ $req->id }}" style="{{ $pilotWantsReturn ? 'display: block;' : 'display: none;' }}">
                        <div class="row">
                          <div class="col-md-4">
                            <label class="small fw-bold">Return Flight # (Same Airline)</label>
                            <input
                              type="number"
                              name="return_flight_num"
                              id="return_flight_num_{{ $req->id }}"
                              class="form-control input-sm return-field-{{ $req->id }}"
                              value="{{ $defaultReturnNum }}"
                              placeholder="Outbound + 1"
                              min="1"
                              max="99999"
                              {{ $pilotWantsReturn ? '' : 'disabled' }}
                            >
                          </div>
                          <div class="col-md-4">
                            <label class="small fw-bold">Return Altitude (Feet — Opt)</label>
                            <input
                              type="number"
                              name="return_level"
                              id="return_level_{{ $req->id }}"
                              class="form-control input-sm return-field-{{ $req->id }}"
                              placeholder="Optional (no fixed alt)"
                              min="0"
                              max="60000"
                              step="500"
                              {{ $pilotWantsReturn ? '' : 'disabled' }}
                            >
                          </div>
                          <div class="col-md-4">
                            <label class="small fw-bold">Return Route (Separate — Opt)</label>
                            <input
                              type="text"
                              name="return_route"
                              id="return_route_{{ $req->id }}"
                              class="form-control input-sm return-field-{{ $req->id }}"
                              style="text-transform: uppercase;"
                              placeholder="Blank = SimBrief auto-route"
                              {{ $pilotWantsReturn ? '' : 'disabled' }}
                            >
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="row" style="margin-top: 8px;">
                      <!-- 9. Days of the Week -->
                      <div class="col-md-12">
                        <div class="form-group" style="margin-bottom: 6px;">
                          <label style="margin-right: 10px;">
                            Days of the Week <small class="text-muted">(Leave all unchecked for no days assigned, or check specific days):</small>
                          </label>
                          @foreach($daysOfWeek as $bitVal => $dayName)
                            <label class="checkbox-inline" style="margin-right: 12px; font-weight: normal; cursor: pointer;">
                              <input type="checkbox" name="days[]" value="{{ $bitVal }}"> {{ $dayName }}
                            </label>
                          @endforeach
                          <button
                            type="button"
                            class="btn btn-xs btn-default"
                            style="margin-left: 6px; width: auto !important;"
                            onclick="var cbs=this.closest('.form-group').querySelectorAll('input[type=checkbox]'); var allChecked=Array.from(cbs).every(function(c){return c.checked;}); cbs.forEach(function(c){c.checked=!allChecked;});"
                          >
                            Toggle All Days
                          </button>
                        </div>
                      </div>
                    </div>

                    <div class="row" style="margin-top: 4px;">
                      <!-- 10. Outbound Route -->
                      <div class="col-md-4">
                        <div class="form-group">
                          <label>Outbound Route (Waypoints / Airways — Opt)</label>
                          <textarea
                            name="route"
                            id="outbound_route_{{ $req->id }}"
                            rows="2"
                            class="form-control input-sm"
                            style="text-transform: uppercase;"
                            placeholder="Optional outbound routing (or leave blank for SimBrief auto-route)"
                          ></textarea>
                        </div>
                      </div>

                      <!-- 11. Attached Subfleets -->
                      <div class="col-md-4">
                        <div class="form-group">
                          <label>Assign Subfleets (Pre-matched from Type Rating)</label>
                          <select name="subfleet_ids[]" class="form-control input-sm no-select2" multiple style="height: 54px;">
                            @foreach($subfleets as $sf)
                              @php $acCount = $sf->aircraft ? $sf->aircraft->count() : 0; @endphp
                              <option value="{{ $sf->id }}" {{ in_array($sf->id, $defaultSubfleetIds) ? 'selected' : '' }}>
                                {{ optional($sf->airline)->icao ?? optional($sf->airline)->code }} — {{ $sf->type }} ({{ $sf->name }}) [{{ $acCount }} acft]
                              </option>
                            @endforeach
                          </select>
                        </div>
                      </div>

                      <!-- 12. Afterburner Instant AJAX & Final Submit Buttons -->
                      <div class="col-md-4 text-right" style="padding-top: 14px;">
                        <button
                          type="button"
                          id="btn_ajax_add_more_{{ $req->id }}"
                          class="btn btn-sm btn-info btn-fill"
                          style="margin-bottom: 4px;"
                          onclick="submitGeneratorAjaxAddMore({{ $req->id }})"
                          title="Zero-Reload Afterburner AJAX: Creates flight(s) instantly in the background, increments flight numbers, and clears Arrival Airport for your next destination!"
                        >
                          &#9889; + Create Flight(s) &amp; Add Another Destination
                        </button>
                        <br>
                        <button
                          type="submit"
                          name="submit_action"
                          value="complete"
                          class="btn btn-sm btn-success btn-fill"
                          title="Creates flight(s), marks Request #{{ $req->id }} as Completed (Route Active), and dispatches the email in the background"
                        >
                          &#10003; Create Final Flight(s) &amp; Mark Completed
                        </button>
                      </div>
                    </div>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>

        <div class="text-center">
          {{ $requests->links() }}
        </div>
      @endif
    </div>

    <div class="content" style="border-top: 1px solid #e2e8f0; padding-top: 10px; margin-top: 10px; text-align: center; color: #64748b; font-size: 12px;">
      RouteRequest v2.1.0 — Afterburner Edition &bull; Made by Alexander Leard of Fantasy Air VA &amp; Regal Artistic Avenue
    </div>
  </div>
</div>
@endsection

@section('scripts')
@parent
<script>
  var RR_MODULE_VERSION     = '2.1.0';
  var RR_AIRPORTS_HASH      = '{{ $cacheHash ?? "v2.0.0_default" }}';
  var RR_ADMIN_BUNDLE_URL   = '{{ route("routerequest.admin.cache.bundle") }}';
  var RR_ADMIN_SEARCH_URL   = '{{ route("routerequest.admin.airports.search") }}';
  var RR_NEXT_FLIGHT_NUMBERS = @json($nextFlightNumbers ?? []);

  /**
   * RouteRequest v2.0.0 — Afterburner Edition Admin IndexedDB Engine
   * Shares the same `RouteRequestAfterburnerDB` IndexedDB database on the device so
   * opening the Admin generator and typing an ICAO/IATA/City resolves in < 1 ms locally!
   */
  window.RRAfterburnerAdminCache = (function () {
    var dbName = 'RouteRequestAfterburnerDB';
    var storeName = 'bundle';
    var memoryAirports = null;

    function updateBadge(text, labelClass) {
      var el = document.getElementById('rr_admin_cache_badge');
      if (!el) return;
      el.className = 'label ' + (labelClass || 'label-success');
      el.innerHTML = '&#9889; ' + text;
    }

    function requestPersistentStorage() {
      if (navigator.storage && navigator.storage.persist) {
        navigator.storage.persist().catch(function () {});
      }
    }

    function openDB(cb) {
      if (!window.indexedDB) { cb(null); return; }
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
      updateBadge('Downloading Airport DB...', 'label-info');
      fetch(RR_ADMIN_BUNDLE_URL, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data && Array.isArray(data.airports)) {
            memoryAirports = data.airports;
            saveBundle(data, function () {
              requestPersistentStorage();
              updateBadge('Afterburner Cache: ' + memoryAirports.length + ' Airports Local', 'label-success');
              if (cb) cb(memoryAirports);
            });
          } else {
            updateBadge('Live Server Search', 'label-default');
            if (cb) cb(null);
          }
        })
        .catch(function () {
          updateBadge('Live Server Search', 'label-default');
          if (cb) cb(null);
        });
    }

    function init(cb) {
      getStoredBundle(function (cached) {
        if (cached && cached.version === RR_MODULE_VERSION && cached.hash === RR_AIRPORTS_HASH && Array.isArray(cached.airports)) {
          memoryAirports = cached.airports;
          requestPersistentStorage();
          updateBadge('Afterburner Cache: ' + memoryAirports.length + ' Airports Local', 'label-success');
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

    return {
      init: init,
      searchLocal: searchLocal,
      forceRefresh: function () { fetchFromServer(); }
    };
  })();

  function onAdminAirlineChange(reqId, airlineId) {
    var outInput = document.getElementById('gen_flight_num_' + reqId);
    var retInput = document.getElementById('return_flight_num_' + reqId);
    if (!outInput) return;

    var nextNum = RR_NEXT_FLIGHT_NUMBERS[airlineId] ? parseInt(RR_NEXT_FLIGHT_NUMBERS[airlineId], 10) : 100;
    outInput.value = nextNum;
    if (retInput) {
      retInput.value = nextNum + 1;
    }
  }

  function onOutboundFlightNumInput(reqId, val) {
    var retInput = document.getElementById('return_flight_num_' + reqId);
    var parsed = parseInt(val || 0, 10);
    if (retInput && parsed > 0) {
      retInput.value = parsed + 1;
    }
  }

  function onReturnLegToggle(reqId) {
    var createCb = document.getElementById('create_return_leg_' + reqId);
    var rejectCb = document.getElementById('reject_return_leg_' + reqId);
    var panel = document.getElementById('return_options_div_' + reqId);
    var fields = document.querySelectorAll('.return-field-' + reqId);

    if (!createCb) return;

    if (createCb.checked) {
      if (rejectCb) rejectCb.checked = false;
      if (panel) {
        panel.style.display = 'block';
        panel.style.opacity = '1';
      }
      fields.forEach(function (el) { el.disabled = false; });
    } else {
      if (panel) {
        panel.style.display = 'none';
      }
      fields.forEach(function (el) { el.disabled = true; });
    }
  }

  function onRejectReturnToggle(reqId) {
    var createCb = document.getElementById('create_return_leg_' + reqId);
    var rejectCb = document.getElementById('reject_return_leg_' + reqId);
    var panel = document.getElementById('return_options_div_' + reqId);
    var fields = document.querySelectorAll('.return-field-' + reqId);

    if (!rejectCb) return;

    if (rejectCb.checked) {
      if (createCb) {
        createCb.checked = false;
        createCb.disabled = true;
      }
      if (panel) {
        panel.style.display = 'none';
      }
      fields.forEach(function (el) { el.disabled = true; });
    } else {
      if (createCb) {
        createCb.disabled = false;
        createCb.checked = true;
      }
      if (panel) {
        panel.style.display = 'block';
        panel.style.opacity = '1';
      }
      fields.forEach(function (el) { el.disabled = false; });
    }
  }

  /**
   * v2.0.0 Afterburner Edition:
   * Zero-Reload AJAX submission when clicking "+ Create Flight(s) & Add Another Destination"!
   */
  function submitGeneratorAjaxAddMore(reqId) {
    var form = document.getElementById('gen_flight_form_' + reqId);
    var btn = document.getElementById('btn_ajax_add_more_' + reqId);
    var msgEl = document.getElementById('drawer_ajax_msg_' + reqId);
    if (!form) return;

    var formData = jQuery(form).serializeArray();
    formData.push({ name: 'submit_action', value: 'add_more' });
    formData.push({ name: 'ajax_mode', value: '1' });

    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '&#9889; Creating Flight(s)...';
    }

    jQuery.ajax({
      url: form.action,
      method: 'POST',
      data: jQuery.param(formData),
      dataType: 'json',
      headers: { 'Accept': 'application/json' },
      success: function (res) {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = '&#9889; + Create Flight(s) &amp; Add Another Destination';
        }
        if (!res || !res.success) {
          alert((res && res.message) ? res.message : 'Could not create flight. Please verify Arrival Airport.');
          return;
        }

        // 1. Update drawer & row generated flight badges without reloading the page
        var banner = document.getElementById('drawer_gen_banner_' + reqId);
        if (banner) banner.style.display = 'block';

        var drawerTotal = document.getElementById('drawer_gen_total_' + reqId);
        if (drawerTotal) drawerTotal.textContent = res.total_flights;

        var rowWrap = document.getElementById('row_generated_flights_wrap_' + reqId);
        if (rowWrap) rowWrap.style.display = 'block';

        var rowTotal = document.getElementById('row_gen_total_' + reqId);
        if (rowTotal) rowTotal.textContent = res.total_flights;

        var drawerBadges = document.getElementById('drawer_gen_badges_' + reqId);
        var rowBadges = document.getElementById('row_gen_badges_' + reqId);

        if (Array.isArray(res.created_flights)) {
          res.created_flights.forEach(function (cf) {
            if (drawerBadges) {
              var sp = document.createElement('span');
              sp.className = 'label label-primary';
              sp.style.cssText = 'margin-right: 4px; display: inline-block; margin-top: 2px;';
              sp.innerHTML = cf.ident + ' (' + cf.dpt + ' &rarr; ' + cf.arr + ')';
              drawerBadges.appendChild(sp);
            }
            if (rowBadges) {
              var a = document.createElement('a');
              a.href = cf.edit_url;
              a.className = 'btn btn-xs btn-primary';
              a.style.cssText = 'margin-top: 2px; margin-right: 2px;';
              a.target = '_blank';
              a.innerHTML = '&#9998; ' + cf.ident + ' (' + cf.dpt + '&rarr;' + cf.arr + ')';
              rowBadges.appendChild(a);
            }
          });
        }

        // 2. Update Return Pairs counter & enforce Max 4 Return Pairs cap
        var drawerRetCnt = document.getElementById('drawer_return_count_' + reqId);
        if (drawerRetCnt) drawerRetCnt.textContent = res.return_pairs_used;
        var rowRetCnt = document.getElementById('row_return_count_' + reqId);
        if (rowRetCnt) rowRetCnt.textContent = res.return_pairs_used;

        if (res.return_cap_reached) {
          var createCb = document.getElementById('create_return_leg_' + reqId);
          var capWarn = document.getElementById('return_cap_warning_' + reqId);
          if (createCb) {
            createCb.checked = false;
            createCb.disabled = true;
            onReturnLegToggle(reqId);
          }
          if (capWarn) capWarn.style.display = 'inline';
        }

        // 3. Auto-increment Outbound & Return Flight Numbers for the next destination
        var outNumEl = document.getElementById('gen_flight_num_' + reqId);
        var retNumEl = document.getElementById('return_flight_num_' + reqId);
        if (outNumEl && res.next_outbound_flight_num) {
          outNumEl.value = res.next_outbound_flight_num;
        }
        if (retNumEl && res.next_return_flight_num) {
          retNumEl.value = res.next_return_flight_num;
        }

        // 4. Clear Arrival Airport, Outbound Route, Return Route, and Altitudes for the next destination
        var $arr = jQuery('#admin_arr_airport_' + reqId);
        if ($arr.length) {
          $arr.val(null).trigger('change');
        }
        var outRouteEl = document.getElementById('outbound_route_' + reqId);
        if (outRouteEl) outRouteEl.value = '';
        var retRouteEl = document.getElementById('return_route_' + reqId);
        if (retRouteEl) retRouteEl.value = '';
        var outLvlEl = document.getElementById('outbound_level_' + reqId);
        if (outLvlEl) outLvlEl.value = '';
        var retLvlEl = document.getElementById('return_level_' + reqId);
        if (retLvlEl) retLvlEl.value = '';

        // 5. Update Admin Notes & Status badge on the row
        var adminNotesEl = document.getElementById('row_admin_notes_' + reqId);
        if (adminNotesEl && res.admin_notes) {
          adminNotesEl.value = res.admin_notes;
        }
        var statusBadgeEl = document.getElementById('row_status_badge_' + reqId);
        if (statusBadgeEl) {
          statusBadgeEl.innerHTML = '<span class="label label-info">Accepted (In Progress)</span>';
        }

        if (msgEl) {
          msgEl.innerHTML = '&#10003; ' + res.message;
        }
      },
      error: function (xhr) {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = '&#9889; + Create Flight(s) &amp; Add Another Destination';
        }
        var errMsg = (xhr.responseJSON && xhr.responseJSON.message)
          ? xhr.responseJSON.message
          : 'Please select a valid Arrival Airport from the dropdown before creating the flight.';
        alert(errMsg);
      }
    });
  }

  function fetchAndApplyFlightAware(reqId) {
    var statusEl = document.getElementById('fa_match_status_' + reqId);
    var urlsInput = document.getElementById('fa_custom_urls_' + reqId);
    var modeSelect = document.getElementById('fa_import_mode_' + reqId);
    var depSelect = document.getElementById('admin_dep_airport_' + reqId);
    var arrSelect = document.getElementById('admin_arr_airport_' + reqId);

    var mode = modeSelect ? modeSelect.value : 'route_only';
    var customUrls = urlsInput ? urlsInput.value : '';
    var depIcao = depSelect ? depSelect.value : '';
    var arrIcao = arrSelect ? arrSelect.value : '';

    if (statusEl) {
      statusEl.style.display = 'block';
      statusEl.style.color = '#0369a1';
      statusEl.innerHTML = 'Fetching & matching FlightAware flight data...';
    }

    jQuery.ajax({
      url: '{{ url("/admin/routerequest") }}/' + reqId + '/parse-fa',
      method: 'POST',
      data: {
        _token: '{{ csrf_token() }}',
        custom_urls: customUrls,
        dep_airport_id: depIcao,
        arr_airport_id: arrIcao
      },
      success: function (res) {
        if (!res || !res.success) {
          if (statusEl) {
            statusEl.style.color = '#b91c1c';
            statusEl.innerHTML = 'No valid FlightAware URLs found. Paste 1 or 2 FlightAware links above and try again.';
          }
          return;
        }

        var messages = [];
        if (res.swapped_order) {
          messages.push('&#9888; Detected links were pasted in reverse order — automatically swapped Outbound & Return to match ' + depIcao + '!');
        }

        if (res.detected_arr_airport && arrSelect && !arrSelect.value) {
          var $arr = jQuery(arrSelect);
          var newOpt = new Option(res.detected_arr_airport.text, res.detected_arr_airport.id, true, true);
          $arr.append(newOpt).trigger('change');
          messages.push('Auto-selected Arrival Airport: ' + res.detected_arr_airport.id);
        }

        var outRouteEl = document.getElementById('outbound_route_' + reqId);
        var outLevelEl = document.getElementById('outbound_level_' + reqId);
        var retRouteEl = document.getElementById('return_route_' + reqId);
        var retLevelEl = document.getElementById('return_level_' + reqId);

        if (res.outbound) {
          if (outRouteEl && res.outbound.route) {
            outRouteEl.value = res.outbound.route;
          }
          if (outLevelEl) {
            outLevelEl.value = (mode === 'route_and_altitude' && res.outbound.altitude_ft) ? res.outbound.altitude_ft : '';
          }
          messages.push(
            'Outbound (' + (res.outbound.origin || '?') + ' &rarr; ' + (res.outbound.destination || '?') + '): ' +
            'Route=[' + (res.outbound.route || 'None') + ']' +
            (mode === 'route_and_altitude' ? ', Alt=[' + (res.outbound.altitude_ft ? res.outbound.altitude_ft + ' ft' : 'N/A') + ']' : ' (Altitude skipped: Route Only mode)')
          );
        }

        if (res.return) {
          var createCb = document.getElementById('create_return_leg_' + reqId);
          if (createCb && !createCb.disabled && !createCb.checked) {
            createCb.checked = true;
            onReturnLegToggle(reqId);
          }
          if (retRouteEl && res.return.route) {
            retRouteEl.value = res.return.route;
          }
          if (retLevelEl) {
            retLevelEl.value = (mode === 'route_and_altitude' && res.return.altitude_ft) ? res.return.altitude_ft : '';
          }
          messages.push(
            'Return (' + (res.return.origin || '?') + ' &rarr; ' + (res.return.destination || '?') + '): ' +
            'Route=[' + (res.return.route || 'None') + ']' +
            (mode === 'route_and_altitude' ? ', Alt=[' + (res.return.altitude_ft ? res.return.altitude_ft + ' ft' : 'N/A') + ']' : ' (Altitude skipped: Route Only mode)')
          );
        }

        if (statusEl) {
          statusEl.style.color = '#15803d';
          statusEl.innerHTML = messages.join('<br>');
        }
      },
      error: function () {
        if (statusEl) {
          statusEl.style.color = '#b91c1c';
          statusEl.innerHTML = 'Could not reach FlightAware or parse the link(s). You can still enter the route manually below.';
        }
      }
    });
  }

  function initAdminAirportSelect2(rowEl) {
    if (!window.jQuery || !jQuery.fn.select2) return;
    var selects = rowEl ? jQuery(rowEl).find('.admin_airport_select') : jQuery('.admin_airport_select');
    selects.each(function () {
      var $el = jQuery(this);
      if ($el.data('rr-ajax-init')) {
        return;
      }
      if ($el.hasClass('select2-hidden-accessible')) {
        try {
          $el.select2('destroy');
        } catch (e) {}
      }
      $el.select2({
        width: '100%',
        placeholder: '-- Type ICAO (KATL), IATA (ATL), or City to Verify --',
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
          transport: function (params, success, failure) {
            var q = (params.data && params.data.q) ? params.data.q : '';
            var localRes = window.RRAfterburnerAdminCache ? window.RRAfterburnerAdminCache.searchLocal(q) : null;
            if (localRes !== null) {
              success({ results: localRes });
              return { abort: function () {} };
            }
            var $request = jQuery.ajax(params);
            $request.then(success);
            $request.fail(failure);
            return $request;
          },
          url: RR_ADMIN_SEARCH_URL,
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
      });
      $el.data('rr-ajax-init', true);
    });
  }

  function toggleFlightGenerator(id) {
    var row = document.getElementById('flight_gen_row_' + id);
    if (!row) return;
    var isHidden = (row.style.display === 'none' || (row.getAttribute('style') && row.getAttribute('style').indexOf('display: none') !== -1));
    if (isHidden) {
      row.style.setProperty('display', 'table-row', 'important');
      initAdminAirportSelect2(row);
    } else {
      row.style.setProperty('display', 'none', 'important');
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    window.RRAfterburnerAdminCache.init();

    setTimeout(function () {
      @if(!empty($openGenId))
        var openRow = document.getElementById('flight_gen_row_{{ (int) $openGenId }}');
        if (openRow) {
          initAdminAirportSelect2(openRow);
          openRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      @endif
    }, 100);
  });
</script>
@endsection
