@extends('app')
@section('title', 'Pilot Route Request')

@section('content')
@php
  if (empty($realworldAirlines) && class_exists(\Modules\RouteRequest\Support\RealWorldAirlines::class)) {
    $realworldAirlines = \Modules\RouteRequest\Support\RealWorldAirlines::all();
  }
@endphp
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
</style>

<div class="row">
  <div class="col-lg-5 col-md-12 mb-3">
    <div class="card mb-2">
      <div class="card-header p-2 d-flex justify-content-between align-items-center">
        <h5 class="m-0">
          <i class="fas fa-route me-1"></i>
          Request a New Route
        </h5>
        <span class="rr-afterburner-badge" title="RouteRequest v2.0.0 — Afterburner Edition (Local IndexedDB Engine)">
          <i class="fas fa-bolt me-1"></i>v2.6.0 Afterburner
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
        <form method="POST" action="{{ route('routerequest.store') }}" id="rr_pilot_form">
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
            @if(config('routerequest.allow_custom_recipient', false))
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

    <!-- Pilot's Signed-Off Type Ratings Summary Card -->
    <div class="card mb-2">
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

  <!-- Right Column: Pilot's Submitted Route Requests -->
  <div class="col-lg-7 col-md-12">
    <div class="card mb-2">
      <div class="card-header p-2">
        <h5 class="m-0">
          <i class="fas fa-history float-end"></i>
          My Requested Routes
        </h5>
      </div>
      <div class="card-body p-0">
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
                          <form method="POST" action="{{ route('routerequest.cancel', $req->id) }}" onsubmit="return confirm('Cancel this pending route request?');">
                            @csrf
                            <button type="submit" class="btn btn-xs btn-danger p-1 fs-7">Cancel Request</button>
                          </form>
                        </div>
                      @endif

                      @if($genFlights->count() > 0)
                        <div class="mt-1 d-flex flex-wrap justify-content-center gap-1">
                          @foreach($genFlights as $gf)
                            @php $gfCode = optional($gf->airline)->icao ?? optional($gf->airline)->code ?? ''; @endphp
                            <a href="{{ route('frontend.flights.show', [$gf->id]) }}" class="badge bg-primary text-white text-decoration-none">
                              <i class="fas fa-plane-departure me-1"></i>Fly / Bid: {{ $gfCode }}{{ $gf->flight_number }} ({{ $gf->dpt_airport_id }}&rarr;{{ $gf->arr_airport_id }})
                            </a>
                            @if(!empty($drmEntitlements['features']['pax_studio']) && !empty($drmEntitlements['paxstudio_installed']))
                              <a href="{{ url('/paxstudio/loadsheet?flight_id=' . $gf->id) }}" class="badge text-white text-decoration-none" style="background: linear-gradient(90deg, #7c3aed, #2563eb);" title="Open in PaxStudio Loadsheet &amp; OFP Planner">
                                &#10022; PaxStudio Loadsheet
                              </a>
                            @endif
                          @endforeach
                        </div>
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
                    <form method="POST" action="{{ route('routerequest.cancel', $req->id) }}" onsubmit="return confirm('Cancel this pending route request?');">
                      @csrf
                      <button type="submit" class="btn btn-xs btn-danger w-100">Cancel Request</button>
                    </form>
                  </div>
                @endif

                @if($genFlights->count() > 0)
                  <div class="mb-1 d-flex flex-column gap-1">
                    @foreach($genFlights as $gf)
                      @php $gfCode = optional($gf->airline)->icao ?? optional($gf->airline)->code ?? ''; @endphp
                      <a href="{{ route('frontend.flights.show', [$gf->id]) }}" class="btn btn-xs btn-primary text-white w-100">
                        <i class="fas fa-plane-departure me-1"></i>Fly / Bid: {{ $gfCode }}{{ $gf->flight_number }} ({{ $gf->dpt_airport_id }}&rarr;{{ $gf->arr_airport_id }})
                      </a>
                      @if(!empty($drmEntitlements['features']['pax_studio']) && !empty($drmEntitlements['paxstudio_installed']))
                        <a href="{{ url('/paxstudio/loadsheet?flight_id=' . $gf->id) }}" class="btn btn-xs text-white w-100" style="background: linear-gradient(90deg, #7c3aed, #2563eb);">
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
        <span>RouteRequest v2.6.0 — Afterburner Edition &bull; Made by Alexander Leard of Fantasy Air VA &amp; Regal Artistic Avenue</span>
        <span>Total Requests: {{ count($myRequests) }}</span>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
@parent
<script>
  var RR_MODULE_VERSION = '2.6.0';
  var RR_AIRPORTS_HASH  = '{{ $cacheHash ?? "v2.0.0_default" }}';
  var RR_BUNDLE_URL     = '{{ route("routerequest.cache.bundle") }}';
  var RR_SEARCH_URL     = '{{ route("routerequest.airports.search") }}';

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
</script>
@endsection
