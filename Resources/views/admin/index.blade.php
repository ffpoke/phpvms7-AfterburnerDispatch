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
  $ent = $drmEntitlements ?? [];
  $activeTier = $ent['tier'] ?? 'basic';
  $tierLabel = $ent['tier_label'] ?? 'Tier 1: Basic Dispatch Edition';
  $licenseKey = $ent['license_key'] ?? '';
  $storefront = $ent['storefront'] ?? [];
  $cs = $clientSettings ?? [];
  $hasDiscord = ($activeTier !== 'basic') || $hasFaMatcher;
  $canAdminAutoEst = !empty($ent['features']['admin_auto_est']);
  $canSyncBasic = !empty($ent['features']['subfleet_sync_basic']);
  $canSyncMulti = !empty($ent['features']['subfleet_sync_multi']);
  $canSyncPower = !empty($ent['features']['subfleet_sync_power']);
  $canTimeFixer = !empty($ent['features']['route_time_fixer']);
  $paxUpd = $paxStudioUpdate ?? [];
  $pendingReqCount = $requests->where('status', 'pending')->count();
  $maxReturnCap = (int) ($maxReturnPairs ?? ($ent['max_return_pairs'] ?? 3));
  $maxRoutesCap = (int) ($maxRoutesPerReq ?? ($ent['max_routes_per_req'] ?? 12));
  $weeklyCap = $ent['weekly_route_cap'] ?? ($activeTier === 'basic' ? 28 : null);
  $weeklyUsed = (int) ($ent['weekly_routes_used'] ?? 0);
  $hasFaMatcher = !empty($ent['features']['fa_auto_matcher']);
  $hasOneClick  = !empty($ent['features']['one_click_create']);
  $hasPaxStudio = !empty($ent['features']['pax_studio']);
  $paxInstalled = !empty($ent['paxstudio_installed']);
@endphp
<style>
  .rr-tabs-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    border-bottom: 2px solid #cbd5e1;
    margin: 14px 15px 14px 15px;
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
  #rr_subfleet_sync_box .text-muted,
  #rr_timefix_box .text-muted,
  #rr_dispatch_settings_box .text-muted,
  #rr_license_storefront_box .text-muted {
    color: #475569 !important;
  }


  @keyframes rrPulseHighlight {
    0% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.75); transform: scale(1); }
    50% { box-shadow: 0 0 0 10px rgba(22, 163, 74, 0); transform: scale(1.03); }
    100% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); transform: scale(1); }
  }
  .rr-btn-highlight-complete {
    animation: rrPulseHighlight 1.6s infinite;
    border: 2px solid #15803d !important;
    background-color: #16a34a !important;
    color: #ffffff !important;
    font-weight: bold !important;
  }
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
          Pilot Route Requests — v3.0.6 <span class="label label-default" style="font-size: 11px; margin-right: 4px;">PHP 8.3.36+</span> <span class="label label-primary" style="background: linear-gradient(90deg, #0284c7, #2563eb); color: #fff;">&#9889; Afterburner Edition</span>
          @if($activeTier === 'flagship')
            <span class="label label-primary" style="background: linear-gradient(90deg, #7c3aed, #2563eb); color: #fff; margin-left: 4px;">
              &#10022; {{ $tierLabel }}
            </span>
          @elseif($activeTier === 'executive')
            <span class="label label-info" style="margin-left: 4px;">
              &#9889; {{ $tierLabel }}
            </span>
          @else
            <span class="label label-default" style="margin-left: 4px;">
              {{ $tierLabel }}
            </span>
          @endif
        </h4>
        <p class="category" style="margin-bottom: 0;">
          Dispatch Email: <strong>{{ $recipientEmail }}</strong>
          &bull; Per-Request Cap: <strong>{{ $maxRoutesCap }} Routes ({{ $maxReturnCap }} RT Pairs)</strong>
          &bull; Weekly Quota:
          @if($weeklyCap !== null)
            <strong><span id="header_weekly_used">{{ $weeklyUsed }}</span> / {{ $weeklyCap }} Routes Used</strong>
          @else
            <strong class="text-success">&#8734; Unlimited Weekly Routes</strong>
          @endif
          @if($hasPaxStudio)
            &bull; <span class="label label-primary" style="background-color: #7c3aed; color: #fff;">
              &#10022; PaxStudio Expansion: {{ $paxInstalled ? 'Active & Bridge Ready' : 'Pre-Licensed & Unlocked' }}
            </span>
          @endif
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
          <button type="submit" class="btn btn-xs btn-info btn-fill" title="Check GitHub Releases ({{ $updateInfo['channel'] ?? 'Stable' }})">
            &#8635; Check for Updates
          </button>
        </form>
      </div>
    </div>

    @if(version_compare(PHP_VERSION, '8.3.37', '<'))
      <div class="alert alert-info" style="margin: 12px 15px 4px 15px; border-left: 5px solid #0284c7; background-color: #f0f9ff; color: #0c4a6e; font-size: 12px;">
        <strong>&#128737; PHP Security Recommendation:</strong> Running <strong>PHP {{ PHP_VERSION }}</strong> (minimum enforced: <strong>8.3.36</strong>). Upgrading to <strong>PHP 8.3.37+</strong> is recommended for the latest PHP 8.3 security patches.
      </div>
    @endif

    {{-- 7-Day Subscription Renewal Banner & 1-Click Renew (v3.0.6) --}}
    @if(!empty($renewalBanner))
      <div class="alert alert-danger" style="margin: 12px 15px 4px 15px; border-left: 5px solid #dc2626; background-color: #fef2f2; color: #7f1d1d;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
          <div>
            <h5 style="margin: 0 0 3px 0; font-weight: bold; color: #991b1b;">
              &#9200; Subscription Renewal Notice: {{ $renewalBanner['tier_label'] }}
            </h5>
            <div class="small">
              @if(!empty($renewalBanner['is_expired']))
                Your monthly subscription expired on <strong>{{ $renewalBanner['expires_at'] }}</strong>. Renew now to restore Executive/Flagship features.
              @else
                Your monthly subscription expires in <strong>{{ max(1, (int) $renewalBanner['days_left']) }} day(s)</strong> (<strong>{{ $renewalBanner['expires_at'] }}</strong>). Renew in 1 click to avoid dropping back to Tier 1 Basic.
              @endif
            </div>
          </div>
          <form method="POST" action="{{ route('routerequest.admin.license.checkout') }}" style="display: flex; gap: 6px; margin: 0;">
            @csrf
            <input type="hidden" name="tier" value="{{ $renewalBanner['tier'] }}">
            <input type="hidden" name="billing_cycle" value="monthly">
            <input type="hidden" name="va_name" value="{{ config('app.name', 'Virtual Airline') }}">
            <input type="hidden" name="admin_email" value="{{ optional(auth()->user())->email ?? '' }}">
            <button type="submit" name="provider" value="square" class="btn btn-sm btn-primary btn-fill">&#9889; 1-Click Renew 30 Days (Square)</button>
            <button type="submit" name="provider" value="paypal" class="btn btn-sm btn-info btn-fill" style="background: #0070ba; border-color: #0070ba;">&#9889; 1-Click Renew 30 Days (PayPal)</button>
          </form>
        </div>
      </div>
    @endif

    {{-- Organized Admin Navigation Tabs (v3.0.6) --}}
    <div class="rr-tabs-nav" id="rr_admin_tabs">
      <button type="button" class="rr-tab-btn rr-tab-active" data-rr-tab="adm_requests" onclick="rrSwitchAdminTab('adm_requests')">
        &#128203; Route Requests <span class="rr-tab-badge">{{ $pendingReqCount > 0 ? ($pendingReqCount . ' Pending') : $requests->total() }}</span>
      </button>
      <button type="button" class="rr-tab-btn" data-rr-tab="adm_subfleet" onclick="rrSwitchAdminTab('adm_subfleet')">
        &#9992; Subfleet Sync &amp; Route Tools
      </button>
      <button type="button" class="rr-tab-btn" data-rr-tab="adm_settings" onclick="rrSwitchAdminTab('adm_settings')">
        &#9881; Dispatch Settings &amp; Discord
      </button>
      <button type="button" class="rr-tab-btn" data-rr-tab="adm_license" onclick="rrSwitchAdminTab('adm_license')">
        &#128273; License &amp; Storefront
      </button>
    </div>

    {{-- Tab 2: Bulk Subfleet-to-Route Auto-Sync --}}
    <div id="rr_tab_pane_adm_subfleet" class="rr-tab-pane">
    {{-- Easy Bulk Subfleet-to-Route Sync (Presets + Checkbox Pills + Undo Last Batch) & Old Route Time Fixer (v3.0.6) --}}
    <div id="rr_subfleet_sync_box" class="content" style="margin: 0 15px 15px 15px; background: #f0fdf4; padding: 16px; border: 1px solid #16a34a; border-radius: 6px;">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;">
        <div>
          <h5 style="margin: 0; font-weight: bold; color: #166534;">
            &#9992; Easy Bulk Subfleet-to-Route Auto-Sync (All phpVMS Flights) @if(!$canSyncBasic)<span class="label label-warning" style="font-size: 11px;">Unlocks Dec 15 (Exec) / Feb 15 (Basic) — Flagship Unlocked Now</span>@elseif(!$canSyncPower)<span class="label label-info" style="font-size: 11px;">Presets &amp; Undo: Tier 3 Flagship Exclusive</span>@endif
          </h5>
          <div class="small text-muted">
            Click subfleet pills below (no Ctrl+Click needed!), save reusable presets, preview matched flights, and undo your last batch anytime in 1 click.
          </div>
        </div>
        <div id="rr_undo_batch_wrap">
          @if(!empty($lastSyncBatch))
            <button type="button" id="rr_sync_undo_btn" class="btn btn-xs btn-danger btn-fill" onclick="runSubfleetSyncUndo()" title="Removes ONLY the {{ $lastSyncBatch['links_added'] }} link(s) added by {{ $lastSyncBatch['batch_ref'] }} ({{ $lastSyncBatch['created_at'] }})">
              &#8617; Undo Last Sync ({{ $lastSyncBatch['batch_ref'] }}: -{{ $lastSyncBatch['links_added'] }} links)
            </button>
          @endif
        </div>
      </div>

      {{-- Saved Presets Bar --}}
      <div style="background: #ffffff; border: 1px solid #bbf7d0; border-radius: 5px; padding: 8px 10px; margin-bottom: 12px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between;">
        <div style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
          <strong class="small" style="color: #166534;">&#11088; Saved Presets:</strong>
          <div id="rr_sync_presets_list" style="display: inline-flex; flex-wrap: wrap; gap: 5px;">
            @forelse(($syncPresets ?? []) as $pr)
              <span class="btn-group btn-group-xs">
                <button type="button" class="btn btn-default" onclick='loadSyncPreset(@json($pr))'>{{ $pr['name'] }}</button>
                <button type="button" class="btn btn-danger" onclick="deleteSyncPreset({{ $pr['id'] }})" title="Delete preset">&times;</button>
              </span>
            @empty
              <span class="small text-muted">No presets saved yet.</span>
            @endforelse
          </div>
        </div>
        <div style="display: flex; gap: 5px; align-items: center;">
          <input type="text" id="rr_sync_preset_name" class="form-control input-sm" style="height: 26px; width: 180px; font-size: 12px;" placeholder="Preset name (e.g. A320 Fleet)">
          <button type="button" class="btn btn-xs btn-primary btn-fill" onclick="saveCurrentSyncPreset()">+ Save Preset</button>
        </div>
      </div>

      <div class="row">
        {{-- Step 1: Easy Searchable Subfleet Checkbox Pills --}}
        <div class="col-md-5" style="margin-bottom: 10px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
            <label class="small fw-bold" style="margin: 0;">1. Select Subfleet(s) to Attach <span class="text-danger">*</span></label>
            <div>
              <button type="button" class="btn btn-xs btn-default" onclick="rrToggleAllPills('rr_sf_pills_box', true)">All</button>
              <button type="button" class="btn btn-xs btn-default" onclick="rrToggleAllPills('rr_sf_pills_box', false)">Clear</button>
            </div>
          </div>
          <input type="text" class="form-control input-sm" style="margin-bottom: 6px; height: 26px; font-size: 12px;" placeholder="Filter subfleets (e.g. A320, B738, Charter)..." oninput="rrFilterPills('rr_sf_pills_box', this.value)">
          <div id="rr_sf_pills_box" style="max-height: 145px; overflow-y: auto; background: #fff; border: 1px solid #cbd5e1; border-radius: 5px; padding: 6px; display: flex; flex-wrap: wrap; gap: 5px;">
            @foreach($subfleets as $sf)
              @php
                $acCount = $sf->aircraft ? $sf->aircraft->count() : 0;
                $sfText = trim((optional($sf->airline)->icao ?? optional($sf->airline)->code ?? '') . ' ' . $sf->type . ' ' . $sf->name);
              @endphp
              <label class="rr-pill-item" data-search="{{ strtolower($sfText) }}" style="margin: 0; padding: 3px 8px; border: 1px solid #cbd5e1; border-radius: 14px; background: #f8fafc; font-size: 11px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; font-weight: 600;">
                <input type="checkbox" class="rr-sync-sf-cb" value="{{ $sf->id }}" style="margin: 0;">
                {{ optional($sf->airline)->icao ?? optional($sf->airline)->code }} — {{ $sf->type }} ({{ $sf->name }}) [{{ $acCount }}]
              </label>
            @endforeach
          </div>
        </div>

        {{-- Step 2: Match Rule & Airline Restriction --}}
        <div class="col-md-4" style="margin-bottom: 10px;">
          <label class="small fw-bold">2. Route Match Rule (Scans All phpVMS Flights)</label>
          <select id="rr_sync_match_mode" class="form-control input-sm no-select2" style="margin-bottom: 8px;">
            <option value="typerating" selected>Shared Type Rating (mirror sibling subfleets in same Type Rating)</option>
            <option value="subfleet_type">Same Subfleet Type Code (e.g. all B738 subfleets)</option>
            <option value="all_flights">All Flights in Selected Airline Scope</option>
          </select>

          <label class="small fw-bold">3. Airline Restriction</label>
          <select id="rr_sync_airline_mode" class="form-control input-sm no-select2" onchange="document.getElementById('rr_sync_custom_airlines_wrap').style.display=(this.value==='custom_airlines'?'block':'none');">
            <option value="same_airline" selected>Match Each Subfleet's Own VA Airline Only</option>
            <option value="custom_airlines">Custom Airline Restriction (Select 1 or Multiple VA Airlines)</option>
            <option value="all_airlines">All VA Airlines (No Airline Restriction)</option>
          </select>

          <div id="rr_sync_custom_airlines_wrap" style="display: none; margin-top: 8px;">
            <label class="small fw-bold" style="margin-bottom: 3px;">Allowed VA Airline(s):</label>
            <div id="rr_al_pills_box" style="max-height: 75px; overflow-y: auto; background: #fff; border: 1px solid #cbd5e1; border-radius: 5px; padding: 5px; display: flex; flex-wrap: wrap; gap: 5px;">
              @foreach($airlines as $al)
                <label style="margin: 0; padding: 2px 7px; border: 1px solid #cbd5e1; border-radius: 12px; background: #f8fafc; font-size: 11px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                  <input type="checkbox" class="rr-sync-al-cb" value="{{ $al->id }}" style="margin: 0;">
                  {{ $al->icao ?? $al->code }} — {{ $al->name }}
                </label>
              @endforeach
            </div>
          </div>

          <div class="form-check" style="margin-top: 6px;">
            <label class="small" style="font-weight: normal; cursor: pointer;">
              <input type="checkbox" id="rr_sync_active_only" checked> Active Flights Only (skip inactive schedules)
            </label>
          </div>
        </div>

        {{-- Step 3: Preview & 1-Click Sync --}}
        <div class="col-md-3" style="margin-bottom: 10px;">
          <label class="small fw-bold">4. Preview &amp; Run</label>
          <div style="display: flex; flex-direction: column; gap: 6px;">
            <button type="button" id="rr_sync_preview_btn" class="btn btn-sm btn-info btn-fill" onclick="runSubfleetSyncPreview()">
              &#128269; Dry-Run Preview
            </button>
            <button type="button" id="rr_sync_execute_btn" class="btn btn-sm btn-success btn-fill" onclick="runSubfleetSyncExecute()" disabled>
              &#9889; 1-Click Sync Now
            </button>
          </div>
        </div>
      </div>

      <div id="rr_sync_result_box" style="display: none; margin-top: 10px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 4px; padding: 10px;"></div>
    </div>

    {{-- Old Route Time Estimator Fixer (v3.0.6) --}}
    <div id="rr_timefix_box" class="content" style="margin: 0 15px 15px 15px; background: #eff6ff; padding: 16px; border: 1px solid #2563eb; border-radius: 6px;">
      <h5 style="margin: 0 0 4px 0; font-weight: bold; color: #1e40af;">
        &#9201; Old Route Time Estimator Fixer (Repair 0h 00m / Unrealistic Flight Times) @if(!$canTimeFixer)<span class="label label-primary" style="background: #7c3aed; font-size: 11px;">&#10022; Tier 3 Flagship Exclusive</span>@endif
      </h5>
      <div class="small text-muted" style="margin-bottom: 10px;">
        Scans your existing phpVMS <code>flights</code> table, computes Great-Circle distance (NM) + realistic cruise/taxi block time from airport coordinates, and repairs broken flight times in 1 click.
      </div>
      <div class="row" style="align-items: flex-end;">
        <div class="col-md-4" style="margin-bottom: 6px;">
          <label class="small fw-bold">Scan Scope</label>
          <select id="rr_timefix_mode" class="form-control input-sm no-select2">
            <option value="missing_only" selected>Missing / Broken Flight Times Only (0 or &lt; 15 mins)</option>
            <option value="unrealistic">Missing + Unrealistic Block Times (&gt;35% off GC estimate)</option>
          </select>
        </div>
        <div class="col-md-3" style="margin-bottom: 6px;">
          <label class="small" style="font-weight: normal; cursor: pointer; margin-top: 22px;">
            <input type="checkbox" id="rr_timefix_active_only" checked> Active Flights Only
          </label>
        </div>
        <div class="col-md-5 text-right" style="margin-bottom: 6px;">
          <button type="button" id="rr_timefix_preview_btn" class="btn btn-sm btn-info btn-fill" onclick="runFlightTimeFixPreview()">
            &#128269; Preview Routes Needing Fix
          </button>
          <button type="button" id="rr_timefix_exec_btn" class="btn btn-sm btn-primary btn-fill" onclick="runFlightTimeFixExecute()" disabled>
            &#9889; 1-Click Fix Flight Times
          </button>
        </div>
      </div>
      <div id="rr_timefix_result_box" style="display: none; margin-top: 10px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 4px; padding: 10px;"></div>
    </div>
    </div>

    {{-- Tab 3: Dispatch Settings & Discord Webhooks --}}
    <div id="rr_tab_pane_adm_settings" class="rr-tab-pane">
    <div id="rr_dispatch_settings_box" class="content" style="margin: 0 15px 15px 15px; background: #f8fafc; padding: 16px; border: 1px solid #94a3b8; border-radius: 6px;">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;">
        <h5 style="margin: 0; font-weight: bold; color: #0f172a;">
          &#9881; RouteRequest Dispatch Settings, Pilot Limits &amp; Discord Webhooks (v3.0.6)
        </h5>
        @if($hasDiscord)
          <form method="POST" action="{{ route('routerequest.admin.settings.test_discord') }}" style="margin: 0;">
            @csrf
            <button type="submit" class="btn btn-xs btn-info btn-fill">&#9889; Test Discord Webhooks</button>
          </form>
        @endif
      </div>
      <form method="POST" action="{{ route('routerequest.admin.settings.save') }}">
        @csrf
        <div class="row">
          <div class="col-md-3" style="margin-bottom: 8px;">
            <label class="small fw-bold">Max Open Requests Per Pilot</label>
            <input type="number" min="0" max="50" name="max_open_requests_per_pilot" class="form-control input-sm" value="{{ $cs['max_open_requests_per_pilot'] ?? '0' }}">
            <small class="text-muted">0 = Unlimited open requests (e.g. set 2 to prevent spam)</small>
          </div>
          <div class="col-md-3" style="margin-bottom: 8px;">
            <label class="small fw-bold">Pilot Request Cooldown (Minutes)</label>
            <input type="number" min="0" max="10080" name="pilot_cooldown_minutes" class="form-control input-sm" value="{{ $cs['pilot_cooldown_minutes'] ?? '0' }}">
            <small class="text-muted">0 = No cooldown (e.g. 60 = 1 hour, 1440 = 24 hours)</small>
          </div>
          <div class="col-md-3" style="margin-bottom: 8px;">
            <label class="small fw-bold">
              Discord Staff Dispatch Webhook
              @if(!$hasDiscord)<span class="label label-default">Exec/Flagship</span>@endif
            </label>
            <input type="url" name="discord_staff_webhook" class="form-control input-sm" value="{{ $cs['discord_staff_webhook'] ?? '' }}" placeholder="https://discord.com/api/webhooks/..." {{ $hasDiscord ? '' : 'disabled' }}>
            <small class="text-muted">Posts rich embed to <code>#staff-dispatch</code> on new request</small>
          </div>
          <div class="col-md-3" style="margin-bottom: 8px;">
            <label class="small fw-bold">
              Discord Public Schedules Webhook
              @if(!$hasDiscord)<span class="label label-default">Exec/Flagship</span>@endif
            </label>
            <input type="url" name="discord_public_webhook" class="form-control input-sm" value="{{ $cs['discord_public_webhook'] ?? '' }}" placeholder="https://discord.com/api/webhooks/..." {{ $hasDiscord ? '' : 'disabled' }}>
            <small class="text-muted">Announces newly built routes in <code>#new-schedules</code></small>
          </div>
        </div>
        <div class="row" style="margin-top: 4px;">
          <div class="col-md-12">
            <label class="small fw-bold">Custom Aircraft Range Guardrail Overrides (Optional JSON)</label>
            <input type="text" name="range_overrides_json" class="form-control input-sm" value="{{ $cs['range_overrides_json'] ?? '' }}" placeholder='e.g. {"A321": 4000, "B738": 3100} (leave blank to use built-in ICAO range table)'>
          </div>
        </div>
        <div class="text-right" style="margin-top: 8px;">
          <button type="submit" class="btn btn-xs btn-success btn-fill">&#10003; Save Dispatch &amp; Discord Settings</button>
        </div>
      </form>
    </div>

    </div>

    {{-- Tab 4: License & Storefront Center --}}
    <div id="rr_tab_pane_adm_license" class="rr-tab-pane">
    <div id="rr_license_storefront_box" class="content" style="margin: 0 15px 15px 15px; background: #f8fafc; padding: 16px; border: 1px solid #cbd5e1; border-radius: 6px;">
      <h5 style="margin-top: 0; font-weight: bold; color: #0f172a;">
        &#128273; RouteRequest License &amp; Storefront Center (v3.0.6)
      </h5>
      @php
        $isStandaloneBuild = ($licenseKey === 'MASTER-FLAGSHIP-STANDALONE');
        $isFlagshipUnlimited = ($activeTier === 'flagship' && empty($ent['expires_at']));
      @endphp

      @if($isStandaloneBuild)
        <div class="alert alert-success" style="margin-bottom: 0; border-left: 5px solid #7c3aed; background: #faf5ff; color: #3b0764;">
          <h6 style="margin: 0 0 4px 0; font-weight: bold; color: #5b21b6;">
            &#10022; Standalone Flagship Edition — Permanently Unlocked
          </h6>
          <div class="small" style="color: #4c1d95;">
            This installation is running the <strong>Afterburner Standalone</strong> build (<code>MASTER-FLAGSHIP-STANDALONE</code>).
            All Flagship features, PaxStudio Bridge integration, Bulk Subfleet Sync Power Suite, and the Old Route Time Estimator Fixer are permanently unlocked with zero DRM server calls.
          </div>
        </div>
      @else
        <p class="small text-muted">
          Activate a license key directly in your database (no FTP or <code>.env</code> editing required), claim a Free Tier 1 key in 1 click, start a 7-Day Executive Trial, or upgrade via Square or Business PayPal.
        </p>
        <div class="row">
          <div class="col-md-5" style="border-right: 1px solid #e2e8f0; padding-right: 15px; margin-bottom: 10px;">
            <h6 style="font-weight: bold; color: #334155;">Current License Status</h6>
            <div class="small" style="margin-bottom: 8px;">
              Active Tier: <strong>{{ $tierLabel }}</strong><br>
              License Key: <code style="font-weight: bold;">{{ $licenseKey ?: 'None (Unlicensed Basic Tier)' }}</code>
            </div>
            <form method="POST" action="{{ route('routerequest.admin.license.save') }}" style="margin-bottom: 10px;">
              @csrf
              <div class="form-group" style="margin-bottom: 6px;">
                <label class="small fw-bold">Paste / Save License Key</label>
                <input type="text" name="license_key" class="form-control input-sm" value="{{ $licenseKey }}" placeholder="RR-BSC-... / RR-EXEC-... / RR-FLG-...">
              </div>
              <button type="submit" class="btn btn-xs btn-info btn-fill">Save &amp; Verify Key</button>
            </form>
            @if($activeTier === 'basic')
              <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px;">
                <form method="POST" action="{{ route('routerequest.admin.license.claim_free') }}" style="margin: 0;" onsubmit="return confirm('Claim your Free Tier 1 (Basic Dispatch Edition) License Key instantly?');">
                  @csrf
                  <input type="hidden" name="va_name" value="{{ config('app.name', 'Virtual Airline') }}">
                  <input type="hidden" name="admin_email" value="{{ optional(auth()->user())->email ?? '' }}">
                  <button type="submit" class="btn btn-xs btn-success btn-fill">
                    &#10003; 1-Click Free Tier 1 Key
                  </button>
                </form>
                <form method="POST" action="{{ route('routerequest.admin.license.claim_trial') }}" style="margin: 0;" onsubmit="return confirm('Start your 7-Day Free Executive Edition Trial (1 per domain)? Automatically returns to Basic after 7 days unless upgraded.');">
                  @csrf
                  <input type="hidden" name="va_name" value="{{ config('app.name', 'Virtual Airline') }}">
                  <input type="hidden" name="admin_email" value="{{ optional(auth()->user())->email ?? '' }}">
                  <button type="submit" class="btn btn-xs btn-primary btn-fill" style="background: #7c3aed; border-color: #7c3aed;">
                    &#9889; Start 7-Day Free Executive Trial
                  </button>
                </form>
              </div>
            @endif
            <div class="small" style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed #cbd5e1;">
              Need to transfer your license to a new domain or print a receipt?<br>
              <a href="https://crew.flyfantasyair.net/routerequest-portal" target="_blank" style="font-weight: bold; color: #0284c7;">
                &#127760; Open Self-Service Domain Transfer &amp; Billing Portal &rarr;
              </a>
            </div>
          </div>

          <div class="col-md-7" style="padding-left: 15px;">
            @if($isFlagshipUnlimited)
              <div class="alert alert-success" style="margin-bottom: 0; border-left: 5px solid #7c3aed; background: #faf5ff; color: #3b0764;">
                <h6 style="margin: 0 0 4px 0; font-weight: bold; color: #5b21b6;">
                  &#10022; Top Tier Active — Flagship Unlimited Edition
                </h6>
                <div class="small" style="color: #4c1d95;">
                  Your Virtual Airline is on the highest tier with unlimited lifetime updates. All Flagship exclusives, PaxStudio Bridge &amp; Auto-Updater, and Pre-Release channel access are unlocked.
                </div>
              </div>
            @else
              <h6 style="font-weight: bold; color: #334155;">
                {{ $activeTier === 'flagship' ? 'Renew or Convert to Unlimited Flagship Edition (USD)' : 'Upgrade to Executive or Flagship Edition (USD)' }}
              </h6>
              <div class="row">
                @if($activeTier !== 'flagship')
                  <div class="col-md-6" style="background: #ffffff; border: 1px solid #0284c7; border-radius: 6px; padding: 10px; margin-bottom: 8px;">
                    <strong style="color: #0284c7;">Tier 2: Executive Dispatch Edition</strong>
                    <div class="small text-muted" style="font-size: 11px; margin-bottom: 4px;">
                      ${{ $storefront['tier2']['monthly_price'] ?? '2.99' }}/mo rolling updates OR ${{ $storefront['tier2']['lifetime_price'] ?? '39.99' }} Unlimited Afterburner + Tier 3 trickle-down updates.
                    </div>
                    <form method="POST" action="{{ route('routerequest.admin.license.checkout') }}">
                      @csrf
                      <input type="hidden" name="tier" value="executive">
                      <input type="hidden" name="va_name" value="{{ config('app.name', 'Virtual Airline') }}">
                      <input type="hidden" name="admin_email" value="{{ optional(auth()->user())->email ?? '' }}">
                      <select name="billing_cycle" class="form-control input-sm no-select2" style="height: 24px; padding: 1px 4px; font-size: 11px; margin-bottom: 6px;">
                        <option value="lifetime">Unlimited Updates (${{ $storefront['tier2']['lifetime_price'] ?? '39.99' }} USD)</option>
                        <option value="monthly">Monthly Rolling (${{ $storefront['tier2']['monthly_price'] ?? '2.99' }}/mo USD)</option>
                      </select>
                      <input type="text" name="promo_code" class="form-control input-sm" style="height: 24px; padding: 1px 6px; font-size: 11px; margin-bottom: 6px; text-transform: uppercase;" placeholder="Promo / Discount Code (Optional)">
                      <div style="display: flex; gap: 4px;">
                        <button type="submit" name="provider" value="square" class="btn btn-xs btn-primary btn-fill" style="flex: 1;">Pay via Square</button>
                        <button type="submit" name="provider" value="paypal" class="btn btn-xs btn-info btn-fill" style="flex: 1; background: #0070ba; border-color: #0070ba;">Pay via PayPal</button>
                      </div>
                    </form>
                  </div>
                @endif
                <div class="{{ $activeTier === 'flagship' ? 'col-md-12' : 'col-md-6' }}" style="background: #ffffff; border: 1px solid #7c3aed; border-radius: 6px; padding: 10px; margin-bottom: 8px;">
                  <strong style="color: #7c3aed;">Tier 3: Flagship Edition (+ PaxStudio)</strong>
                  <div class="small text-muted" style="font-size: 11px; margin-bottom: 4px;">
                    ${{ $storefront['tier3']['monthly_price'] ?? '4.99' }}/mo rolling updates OR ${{ $storefront['tier3']['lifetime_price'] ?? '59.99' }} Unlimited Afterburner + PaxStudio Bridge &amp; hand-selected next-gen features.
                  </div>
                  <form method="POST" action="{{ route('routerequest.admin.license.checkout') }}">
                    @csrf
                    <input type="hidden" name="tier" value="flagship">
                    <input type="hidden" name="va_name" value="{{ config('app.name', 'Virtual Airline') }}">
                    <input type="hidden" name="admin_email" value="{{ optional(auth()->user())->email ?? '' }}">
                    <select name="billing_cycle" class="form-control input-sm no-select2" style="height: 24px; padding: 1px 4px; font-size: 11px; margin-bottom: 6px;">
                      <option value="lifetime">Unlimited Updates (${{ $storefront['tier3']['lifetime_price'] ?? '59.99' }} USD)</option>
                      <option value="monthly">Monthly Rolling (${{ $storefront['tier3']['monthly_price'] ?? '4.99' }}/mo USD)</option>
                    </select>
                    <input type="text" name="promo_code" class="form-control input-sm" style="height: 24px; padding: 1px 6px; font-size: 11px; margin-bottom: 6px; text-transform: uppercase;" placeholder="Promo / Discount Code (Optional)">
                    <div style="display: flex; gap: 4px;">
                      <button type="submit" name="provider" value="square" class="btn btn-xs btn-primary btn-fill" style="flex: 1; background: #7c3aed; border-color: #7c3aed;">Pay via Square</button>
                      <button type="submit" name="provider" value="paypal" class="btn btn-xs btn-info btn-fill" style="flex: 1; background: #0070ba; border-color: #0070ba;">Pay via PayPal</button>
                    </div>
                  </form>
                </div>
              </div>
            @endif
          </div>
        </div>
      @endif

      {{-- Read-Only Baked-In Feature Drop Timetable (v3.0.6 — Immutable until Module Update) --}}
      @php
        $rrServerTs = !empty($ent['signed_at']) ? (int) $ent['signed_at'] : time();
        $rrDropRows = \Modules\RouteRequest\Support\DropSchedule::getTimetable($activeTier, $rrServerTs);
      @endphp
      <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid #cbd5e1;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px; margin-bottom: 6px;">
          <h6 style="margin: 0; font-weight: bold; color: #0f172a;">
            &#128197; Baked-In Feature Drop Timetable (Auto-Unlocks Automatically on UTC Schedule)
          </h6>
          <span class="label label-default">Schedule: {{ \Modules\RouteRequest\Support\DropSchedule::SCHEDULE_VERSION }} (Read-Only)</span>
        </div>
        <div class="table-responsive">
          <table class="table table-condensed table-striped small" style="background: #ffffff; margin-bottom: 0;">
            <thead>
              <tr>
                <th>Feature</th>
                <th>Your Status ({{ ucfirst($activeTier) }})</th>
                <th>Tier 1: Basic</th>
                <th>Tier 2: Executive</th>
                <th>Tier 3: Flagship</th>
              </tr>
            </thead>
            <tbody>
              @foreach($rrDropRows as $dr)
                <tr>
                  <td><strong>{{ $dr['label'] }}</strong></td>
                  <td>
                    @if($dr['unlocked'])
                      <span class="label label-success">&#10003; Unlocked</span>
                    @elseif($dr['permanent_flagship'])
                      <span class="label label-primary" style="background: #7c3aed;">&#10022; {{ $dr['status'] }}</span>
                    @else
                      <span class="label label-warning">{{ $dr['status'] }}</span>
                    @endif
                  </td>
                  <td>{{ $dr['basic_col'] }}</td>
                  <td>{{ $dr['executive_col'] }}</td>
                  <td><strong>{{ $dr['flagship_col'] }}</strong></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    </div>

    {{-- Tab 1: Route Requests Queue & Dispatch Generator (Default Active) --}}
    <div id="rr_tab_pane_adm_requests" class="rr-tab-pane rr-tab-pane-active">
    {{-- PaxStudio Compatibility Floor & 1-Click Minor/Major Auto-Updater Banner (v3.0.6) --}}
    @if($hasPaxStudio && (!empty($paxUpd['too_old']) || !empty($paxUpd['update_available'])))
      <div class="alert {{ !empty($paxUpd['too_old']) ? 'alert-danger' : 'alert-info' }}" style="margin: 12px 15px 4px 15px; border-left: 5px solid {{ !empty($paxUpd['too_old']) ? '#dc2626' : '#7c3aed' }}; background-color: {{ !empty($paxUpd['too_old']) ? '#fef2f2' : '#faf5ff' }}; color: {{ !empty($paxUpd['too_old']) ? '#7f1d1d' : '#4c1d95' }};">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
          <div>
            <h5 style="margin: 0 0 4px 0; font-weight: bold;">
              &#10022; PaxStudio {{ !empty($paxUpd['too_old']) ? 'Compatibility Warning' : (strtoupper($paxUpd['update_type'] ?? 'minor') . ' Update Available') }}:
              v{{ $paxUpd['latest_version'] ?? '' }}
            </h5>
            <div class="small">
              Installed PaxStudio: <strong>v{{ $paxUpd['current_version'] ?? 'Unknown' }}</strong> &bull;
              Minimum Compatible Floor: <strong>v{{ $paxUpd['min_version'] ?? '2.22.0' }}</strong> &bull;
              Latest Release: <strong>v{{ $paxUpd['latest_version'] ?? '' }}</strong>
              @if(!empty($paxUpd['too_old']))
                <br><strong class="text-danger">Your installed PaxStudio build is older than the minimum compatible floor (v{{ $paxUpd['min_version'] ?? '2.22.0' }}). Please update for full Loadsheet &amp; ACARS stability.</strong>
              @endif
            </div>
          </div>
          <div>
            @if(!empty($paxUpd['has_auto_zip']))
              <form method="POST" action="{{ route('routerequest.admin.install_paxstudio_update') }}" style="margin: 0;" onsubmit="return confirm('Automatically download and install PaxStudio v{{ $paxUpd['latest_version'] ?? '' }} over /modules/PaxStudio now?');">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary btn-fill" style="background-color: #7c3aed; border-color: #7c3aed;">
                  &#9889; 1-Click Update PaxStudio to v{{ $paxUpd['latest_version'] ?? '' }}
                </button>
              </form>
            @else
              <span class="label label-default">Configure PaxStudio GitHub PAT on Master Server for 1-Click Auto-Update</span>
            @endif
          </div>
        </div>
      </div>
    @endif

    {{-- Method B + Bonus 1-Click Self-Updater Banner (Supports Pre-Releases on Tier 3 Flagship) --}}
    @if(!empty($updateInfo['update_available']))
      <div class="alert alert-warning" style="margin: 12px 15px 4px 15px; border-left: 5px solid #d97706; background-color: #fffbeb; color: #78350f;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
          <div>
            <h5 style="margin: 0 0 4px 0; font-weight: bold; color: #92400e;">
              &#128640; Update Available: RouteRequest v{{ $updateInfo['latest_version'] }}
              @if(!empty($updateInfo['is_prerelease']))
                <span class="label label-primary" style="background-color: #7c3aed; color: #fff;">FLAGSHIP PRE-RELEASE</span>
              @endif
              @if(!empty($updateInfo['release_name']))
                ({{ $updateInfo['release_name'] }})
              @endif
            </h5>
            <div class="small">
              Installed Version: <strong>v{{ $updateInfo['current_version'] }}</strong> &bull;
              Latest Release: <strong>v{{ $updateInfo['latest_version'] }}</strong> &bull;
              Channel: <strong>{{ $updateInfo['channel'] ?? 'Stable' }}</strong>
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
                $existingTotalRoutes = $genFlights->count();
                $existingReturnCount = $genFlights->filter(function ($gf) use ($req) {
                  return strtoupper((string) $gf->arr_airport_id) === strtoupper((string) $req->dep_airport_id);
                })->count();

                $isDrawerOpen = isset($openGenId) && (int) $openGenId === (int) $req->id;
                $isMultiDest = (bool) $req->multiple_destinations || $existingTotalRoutes > 2;
                $pilotWantsReturn = (bool) $req->request_return && ($existingReturnCount < $maxReturnCap) && ($existingTotalRoutes + 2 <= $maxRoutesCap);
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
                        &#9745; MULTI-DEST (<span id="row_route_count_{{ $req->id }}">{{ $existingTotalRoutes }}</span>/{{ $maxRoutesCap }} Routes &bull; <span id="row_return_count_{{ $req->id }}">{{ $existingReturnCount }}</span>/{{ $maxReturnCap }} RT Pairs)
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
                    @if($hasOneClick)
                      <form method="POST" action="{{ route('routerequest.admin.quick_generate', $req->id) }}" style="margin-top: 5px; background: #f8fafc; padding: 4px 6px; border: 1px solid #cbd5e1; border-radius: 4px;">
                        @csrf
                        @if(count($faUrls) > 0 && $hasFaMatcher)
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
                    @else
                      <div style="margin-top: 5px;">
                        <span class="label label-default" title="Upgrade to Tier 2 (Executive Dispatch Edition) or Tier 3 (Flagship Edition) to unlock 1-Click Auto-Create">
                          &#128274; 1-Click Auto-Create (Executive / Flagship Tier)
                        </span>
                      </div>
                    @endif
                  @endif

                  <div id="row_generated_flights_wrap_{{ $req->id }}" style="margin-top: 5px; {{ $existingTotalRoutes > 0 ? '' : 'display: none;' }}">
                    <small class="text-muted"><strong>Generated (<span id="row_gen_total_{{ $req->id }}">{{ $existingTotalRoutes }}</span>/{{ $maxRoutesCap }}):</strong></small><br>
                    <div id="row_gen_badges_{{ $req->id }}">
                      @foreach($genFlights as $gf)
                        @php $gfCode = optional($gf->airline)->icao ?? optional($gf->airline)->code ?? ''; @endphp
                        <a href="{{ url('/admin/flights/' . $gf->id . '/edit') }}" class="btn btn-xs btn-primary" style="margin-top: 2px;" target="_blank" title="Edit Flight {{ $gfCode }}{{ $gf->flight_number }}">
                          &#9998; {{ $gfCode }}{{ $gf->flight_number }} ({{ $gf->dpt_airport_id }}&rarr;{{ $gf->arr_airport_id }})
                        </a>
                        @if($hasPaxStudio && $paxInstalled)
                          <a href="{{ url('/paxstudio/loadsheet?flight_id=' . $gf->id) }}" class="btn btn-xs btn-info" style="margin-top: 2px; background-color: #7c3aed; border-color: #7c3aed; color: #fff;" target="_blank" title="Open {{ $gfCode }}{{ $gf->flight_number }} in PaxStudio Loadsheet">
                            &#10022; PaxStudio
                          </a>
                        @endif
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
                        <option value="completed" {{ $req->status === 'completed' ? 'selected' : '' }} {{ (empty($req->arr_airport_id) && $existingTotalRoutes === 0) ? 'disabled' : '' }}>
                          Completed — Route Active {{ (empty($req->arr_airport_id) && $existingTotalRoutes === 0) ? '(Requires Flight/Dest)' : '(Done)' }}
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

              <!-- Built-In Automated Flight Schedule Generator Drawer (v3.0.6 Afterburner 3-Tier DRM Edition) for Request #{{ $req->id }} -->
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
                      <span class="label label-primary" style="background-color: #7c3aed; color: #fff; margin-left: 6px;">
                        Routes: <span id="drawer_route_count_{{ $req->id }}">{{ $existingTotalRoutes }}</span>/{{ $maxRoutesCap }} &bull;
                        Return Pairs: <span id="drawer_return_count_{{ $req->id }}">{{ $existingReturnCount }}</span>/{{ $maxReturnCap }}
                      </span>
                      <small style="color: #475569; font-weight: normal; display: block; margin-top: 2px;">
                        Type Rating: <strong>{{ $req->aircraft_type }}</strong> &bull; Counterpart: <strong>{{ $req->realworld_airline }}</strong> &bull; Active License: <strong>{{ $tierLabel }}</strong>
                      </small>
                    </h5>
                    <button type="button" class="btn btn-xs btn-default" style="width: auto !important;" onclick="toggleFlightGenerator({{ $req->id }})">
                      Close &times;
                    </button>
                  </div>

                  {{-- FlightAware Link Auto-Matcher (Unlocked on Tier 2 Executive & Tier 3 Flagship; Locked on Tier 1 Basic) --}}
                  @if($hasFaMatcher)
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
                  @else
                    <div class="alert alert-warning" style="padding: 8px 12px; margin-bottom: 12px; background-color: #f8fafc; border-left: 4px solid #64748b; color: #334155;">
                      <strong>&#128274; FlightAware Route &amp; Altitude Auto-Matcher Locked (Tier 1: Basic Dispatch Edition):</strong>
                      Upgrade to <strong>Tier 2: Executive Dispatch Edition</strong> or <strong>Tier 3: Flagship Edition</strong> to automatically scrape and match Outbound &amp; Return routes/altitudes from FlightAware links.
                    </div>
                  @endif

                  {{-- Live AJAX Banner for Multi-Destination Flights Generated --}}
                  <div id="drawer_gen_banner_{{ $req->id }}" class="alert alert-info" style="padding: 8px 12px; margin-bottom: 12px; border-left: 4px solid #0284c7; {{ $existingTotalRoutes > 0 ? '' : 'display: none;' }}">
                    <strong>&#9992; Flights Generated for Request #{{ $req->id }} (<span id="drawer_gen_total_{{ $req->id }}">{{ $existingTotalRoutes }}</span>/{{ $maxRoutesCap }} routes):</strong>
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
                          <label>Flight Time (Hours &amp; Minutes) @if($canAdminAutoEst)<button type="button" class="btn btn-xs btn-default" style="padding: 0 5px; font-size: 10px; margin-left: 4px;" onclick="autoEstimateAdminBlockTime({{ $req->id }})" title="Calculate Great-Circle distance &amp; auto-fill realistic cruise + taxi block time">&#9889; Auto-Est</button>@endif</label>
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
                      <!-- 5. Departure Airport -->
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

                      <!-- 6. Arrival Airport -->
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

                    {{-- Return Leg (Counts as 2 routes toward the 12/15 per-request & 28/week caps; capped at 3 return pairs on Tier 1 Basic, 4 on Tier 2/3) --}}
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
                            {{ ($existingReturnCount >= $maxReturnCap || ($existingTotalRoutes + 2 > $maxRoutesCap)) ? 'disabled' : '' }}
                            onchange="onReturnLegToggle({{ $req->id }})"
                          >
                          <label class="form-check-label fw-bold text-dark small" for="create_return_leg_{{ $req->id }}">
                            &#8644; Also Create Return Flight (Counts as 2 Routes: Outbound + Return)
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
                            &#10006; Reject Return Leg / Outbound Only (Counts as 1 Route)
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

                      <!-- 12. Duplicate Bypass + Afterburner Instant AJAX & Mark Completed Buttons (v3.0.6) -->
                      <div class="col-md-4 text-right" style="padding-top: 4px;">
                        <div id="dup_warning_box_{{ $req->id }}" class="alert alert-warning text-left" style="display: none; padding: 6px 8px; margin-bottom: 6px; font-size: 11px; border-left: 4px solid #d97706;">
                          <div id="dup_warning_text_{{ $req->id }}" style="margin-bottom: 4px; font-weight: bold; color: #92400e;"></div>
                        </div>
                        <div class="form-check text-left" style="margin-bottom: 6px;">
                          <input type="hidden" name="bypass_duplicate" value="0">
                          <label class="small" style="font-weight: 600; color: #b45309; cursor: pointer;">
                            <input type="checkbox" name="bypass_duplicate" id="bypass_duplicate_{{ $req->id }}" value="1">
                            &#9888; Bypass Duplicate Check (Different Routing / Alternate Flight)
                          </label>
                        </div>

                        <button
                          type="button"
                          id="btn_ajax_add_more_{{ $req->id }}"
                          class="btn btn-sm btn-info btn-fill"
                          style="margin-bottom: 4px;"
                          {{ $existingTotalRoutes >= $maxRoutesCap ? 'disabled' : '' }}
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
                          id="btn_final_complete_{{ $req->id }}"
                          class="btn btn-sm btn-success btn-fill"
                          style="margin-bottom: 4px;"
                          {{ $existingTotalRoutes >= $maxRoutesCap ? 'disabled' : '' }}
                          title="Creates flight(s), marks Request #{{ $req->id }} as Completed (Route Active), and dispatches the email in the background"
                        >
                          &#10003; Create Final Flight(s) &amp; Mark Completed
                        </button>
                        <br>
                        <button
                          type="button"
                          id="btn_mark_completed_{{ $req->id }}"
                          class="btn btn-sm btn-success btn-fill {{ $existingTotalRoutes >= $maxRoutesCap ? 'rr-btn-highlight-complete' : '' }}"
                          style="{{ ($existingTotalRoutes > 0 && $req->status !== 'completed') ? '' : 'display: none;' }} margin-top: 2px;"
                          onclick="markRequestCompletedAjax({{ $req->id }})"
                          title="Finalize this request as Completed without creating any more flights (sends pilot email &amp; Discord announcement)"
                        >
                          &#10003; Mark Completed (No More Routes)
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

    </div>

    <div class="content" style="border-top: 1px solid #e2e8f0; padding-top: 10px; margin-top: 10px; text-align: center; color: #64748b; font-size: 12px;">
      RouteRequest v3.0.6 — Afterburner Edition ({{ $tierLabel }}) &bull; Made by Alexander Leard of Fantasy Air VA &amp; Regal Artistic Avenue
    </div>
  </div>
</div>
@endsection

@section('scripts')
@parent
<script>
  var RR_MODULE_VERSION      = '3.0.6';
  var RR_AIRPORTS_HASH       = '{{ $cacheHash ?? "v3.0.6_default" }}';
  var RR_ADMIN_BUNDLE_URL    = '{{ route("routerequest.admin.cache.bundle") }}';
  var RR_ADMIN_SEARCH_URL    = '{{ route("routerequest.admin.airports.search") }}';
  var RR_NEXT_FLIGHT_NUMBERS = @json($nextFlightNumbers ?? []);
  var RR_HAS_PAXSTUDIO       = {{ ($hasPaxStudio && $paxInstalled) ? 'true' : 'false' }};

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

  function submitGeneratorAjaxAddMore(reqId) {
    var form = document.getElementById('gen_flight_form_' + reqId);
    var btn = document.getElementById('btn_ajax_add_more_' + reqId);
    var finalBtn = document.getElementById('btn_final_complete_' + reqId);
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
          alert((res && res.message) ? res.message : 'Could not create flight.');
          return;
        }

        var banner = document.getElementById('drawer_gen_banner_' + reqId);
        if (banner) banner.style.display = 'block';

        var drawerTotal = document.getElementById('drawer_gen_total_' + reqId);
        if (drawerTotal) drawerTotal.textContent = res.total_flights;
        var drawerRouteCnt = document.getElementById('drawer_route_count_' + reqId);
        if (drawerRouteCnt) drawerRouteCnt.textContent = res.total_flights;

        var rowWrap = document.getElementById('row_generated_flights_wrap_' + reqId);
        if (rowWrap) rowWrap.style.display = 'block';

        var rowTotal = document.getElementById('row_gen_total_' + reqId);
        if (rowTotal) rowTotal.textContent = res.total_flights;
        var rowRouteCnt = document.getElementById('row_route_count_' + reqId);
        if (rowRouteCnt) rowRouteCnt.textContent = res.total_flights;

        var headerWeekly = document.getElementById('header_weekly_used');
        if (headerWeekly && typeof res.weekly_routes_used !== 'undefined') {
          headerWeekly.textContent = res.weekly_routes_used;
        }

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

              if (RR_HAS_PAXSTUDIO && cf.paxstudio_url) {
                var px = document.createElement('a');
                px.href = cf.paxstudio_url;
                px.className = 'btn btn-xs btn-info';
                px.style.cssText = 'margin-top: 2px; margin-right: 2px; background-color: #7c3aed; border-color: #7c3aed; color: #fff;';
                px.target = '_blank';
                px.innerHTML = '&#10022; PaxStudio';
                rowBadges.appendChild(px);
              }
            }
          });
        }

        var drawerRetCnt = document.getElementById('drawer_return_count_' + reqId);
        if (drawerRetCnt) drawerRetCnt.textContent = res.return_pairs_used;
        var rowRetCnt = document.getElementById('row_return_count_' + reqId);
        if (rowRetCnt) rowRetCnt.textContent = res.return_pairs_used;

        if (res.return_cap_reached || (res.total_flights + 2 > res.max_routes_per_req)) {
          var createCb = document.getElementById('create_return_leg_' + reqId);
          var capWarn = document.getElementById('return_cap_warning_' + reqId);
          if (createCb) {
            createCb.checked = false;
            createCb.disabled = true;
            onReturnLegToggle(reqId);
          }
          if (capWarn && res.return_cap_reached) capWarn.style.display = 'inline';
        }

        var dupBox = document.getElementById('dup_warning_box_' + reqId);
        if (dupBox) dupBox.style.display = 'none';
        var bypassCb = document.getElementById('bypass_duplicate_' + reqId);
        if (bypassCb) bypassCb.checked = false;

        var markCompBtn = document.getElementById('btn_mark_completed_' + reqId);
        if (markCompBtn) {
          markCompBtn.style.display = 'inline-block';
        }

        if (res.route_cap_reached) {
          if (btn) btn.disabled = true;
          if (finalBtn) finalBtn.disabled = true;
          if (markCompBtn) {
            markCompBtn.classList.add('rr-btn-highlight-complete');
          }
        }

        var outNumEl = document.getElementById('gen_flight_num_' + reqId);
        var retNumEl = document.getElementById('return_flight_num_' + reqId);
        if (outNumEl && res.next_outbound_flight_num) {
          outNumEl.value = res.next_outbound_flight_num;
        }
        if (retNumEl && res.next_return_flight_num) {
          retNumEl.value = res.next_return_flight_num;
        }

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
        if (xhr.status === 409 && xhr.responseJSON && xhr.responseJSON.duplicate) {
          var dupBox = document.getElementById('dup_warning_box_' + reqId);
          var dupTxt = document.getElementById('dup_warning_text_' + reqId);
          if (dupBox && dupTxt) {
            dupTxt.textContent = errMsg;
            dupBox.style.display = 'block';
            return;
          }
        }
        alert(errMsg);
      }
    });
  }

  function rrFilterPills(containerId, term) {
    var q = (term || '').trim().toLowerCase();
    var box = document.getElementById(containerId);
    if (!box) return;
    box.querySelectorAll('.rr-pill-item').forEach(function (lbl) {
      var hay = lbl.getAttribute('data-search') || '';
      lbl.style.display = (!q || hay.indexOf(q) !== -1) ? 'inline-flex' : 'none';
    });
  }

  function rrToggleAllPills(containerId, checked) {
    var box = document.getElementById(containerId);
    if (!box) return;
    box.querySelectorAll('.rr-pill-item').forEach(function (lbl) {
      if (lbl.style.display !== 'none') {
        var cb = lbl.querySelector('input[type="checkbox"]');
        if (cb) cb.checked = checked;
      }
    });
  }

  function getSubfleetSyncPayload() {
    var sfIds = Array.from(document.querySelectorAll('.rr-sync-sf-cb:checked')).map(function (c) { return c.value; });
    var alIds = Array.from(document.querySelectorAll('.rr-sync-al-cb:checked')).map(function (c) { return c.value; });
    return {
      _token: '{{ csrf_token() }}',
      subfleet_ids: sfIds,
      match_mode: document.getElementById('rr_sync_match_mode') ? document.getElementById('rr_sync_match_mode').value : 'typerating',
      airline_mode: document.getElementById('rr_sync_airline_mode') ? document.getElementById('rr_sync_airline_mode').value : 'same_airline',
      airline_ids: alIds,
      active_only: document.getElementById('rr_sync_active_only') && document.getElementById('rr_sync_active_only').checked ? 1 : 0
    };
  }

  function loadSyncPreset(pr) {
    if (!pr) return;
    var sfSet = new Set((pr.subfleet_ids || []).map(String));
    var alSet = new Set((pr.airline_ids || []).map(String));
    document.querySelectorAll('.rr-sync-sf-cb').forEach(function (c) { c.checked = sfSet.has(String(c.value)); });
    document.querySelectorAll('.rr-sync-al-cb').forEach(function (c) { c.checked = alSet.has(String(c.value)); });
    var mm = document.getElementById('rr_sync_match_mode');
    if (mm && pr.match_mode) mm.value = pr.match_mode;
    var am = document.getElementById('rr_sync_airline_mode');
    if (am && pr.airline_mode) {
      am.value = pr.airline_mode;
      var wrap = document.getElementById('rr_sync_custom_airlines_wrap');
      if (wrap) wrap.style.display = (pr.airline_mode === 'custom_airlines') ? 'block' : 'none';
    }
    var ao = document.getElementById('rr_sync_active_only');
    if (ao) ao.checked = !!pr.active_only;
    runSubfleetSyncPreview();
  }

  function saveCurrentSyncPreset() {
    var payload = getSubfleetSyncPayload();
    var nameEl = document.getElementById('rr_sync_preset_name');
    payload.name = nameEl ? nameEl.value.trim() : '';
    if (!payload.name) { alert('Please enter a preset name first.'); return; }
    if (!payload.subfleet_ids.length) { alert('Please select at least one subfleet first.'); return; }
    jQuery.ajax({
      url: '{{ route("routerequest.admin.subfleet_sync.presets.save") }}',
      method: 'POST',
      data: payload,
      dataType: 'json',
      success: function (res) {
        if (nameEl) nameEl.value = '';
        alert(res.message || 'Preset saved!');
        window.location.reload();
      },
      error: function (xhr) {
        alert((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Could not save preset.');
      }
    });
  }

  function deleteSyncPreset(id) {
    if (!confirm('Delete this saved preset?')) return;
    jQuery.ajax({
      url: '{{ url("/admin/routerequest/subfleet-sync/presets") }}/' + id,
      method: 'POST',
      data: { _token: '{{ csrf_token() }}', _method: 'DELETE' },
      dataType: 'json',
      success: function () { window.location.reload(); }
    });
  }

  function runSubfleetSyncUndo() {
    if (!confirm('Undo the last Bulk Subfleet Sync batch? This will remove ONLY the exact links added by that batch.')) return;
    var btn = document.getElementById('rr_sync_undo_btn');
    if (btn) { btn.disabled = true; btn.innerHTML = '&#8617; Undoing...'; }
    jQuery.ajax({
      url: '{{ route("routerequest.admin.subfleet_sync.undo") }}',
      method: 'POST',
      data: { _token: '{{ csrf_token() }}' },
      dataType: 'json',
      success: function (res) {
        alert(res.message || 'Undo complete!');
        window.location.reload();
      },
      error: function (xhr) {
        if (btn) { btn.disabled = false; btn.innerHTML = '&#8617; Undo Last Sync'; }
        alert((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Undo failed.');
      }
    });
  }

  function runFlightTimeFixPreview() {
    var prevBtn = document.getElementById('rr_timefix_preview_btn');
    var execBtn = document.getElementById('rr_timefix_exec_btn');
    var box = document.getElementById('rr_timefix_result_box');
    if (prevBtn) { prevBtn.disabled = true; prevBtn.innerHTML = '&#9889; Scanning Flights...'; }
    if (execBtn) execBtn.disabled = true;
    jQuery.ajax({
      url: '{{ route("routerequest.admin.time_fix.preview") }}',
      method: 'POST',
      data: {
        _token: '{{ csrf_token() }}',
        mode: document.getElementById('rr_timefix_mode').value,
        active_only: document.getElementById('rr_timefix_active_only').checked ? 1 : 0
      },
      dataType: 'json',
      success: function (res) {
        if (prevBtn) { prevBtn.disabled = false; prevBtn.innerHTML = '&#128269; Preview Routes Needing Fix'; }
        if (box) {
          box.style.display = 'block';
          var html = '<div style="font-weight: bold; color: #1e40af; margin-bottom: 6px;">Found <u>' + res.total_count + ' flight(s)</u> needing flight time repair.</div>';
          if (Array.isArray(res.samples) && res.samples.length) {
            html += '<table class="table table-condensed table-striped small" style="margin-bottom: 0;"><thead><tr><th>Flight</th><th>Route</th><th>Distance</th><th>Old Time</th><th>New Est. Time</th></tr></thead><tbody>';
            res.samples.forEach(function (s) {
              html += '<tr><td><code>' + s.ident + '</code></td><td>' + s.route + '</td><td>' + s.dist_nm + ' NM</td><td class="text-danger">' + s.old_time + '</td><td class="text-success"><strong>' + s.new_time + '</strong></td></tr>';
            });
            html += '</tbody></table>';
          }
          box.innerHTML = html;
        }
        if (execBtn) {
          execBtn.disabled = (res.total_count <= 0);
          execBtn.innerHTML = res.total_count > 0 ? ('&#9889; 1-Click Fix ' + res.total_count + ' Flight(s)') : '&#10003; All Flight Times Valid';
        }
      },
      error: function (xhr) {
        if (prevBtn) { prevBtn.disabled = false; prevBtn.innerHTML = '&#128269; Preview Routes Needing Fix'; }
        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : ('Error scanning flight times (HTTP ' + xhr.status + ').');
        alert(msg);
      }
    });
  }

  function runFlightTimeFixExecute() {
    if (!confirm('Update flight_time (and missing distance) on all matched phpVMS flights now?')) return;
    var execBtn = document.getElementById('rr_timefix_exec_btn');
    var box = document.getElementById('rr_timefix_result_box');
    if (execBtn) { execBtn.disabled = true; execBtn.innerHTML = '&#9889; Fixing Flight Times...'; }
    jQuery.ajax({
      url: '{{ route("routerequest.admin.time_fix.execute") }}',
      method: 'POST',
      data: {
        _token: '{{ csrf_token() }}',
        mode: document.getElementById('rr_timefix_mode').value,
        active_only: document.getElementById('rr_timefix_active_only').checked ? 1 : 0
      },
      dataType: 'json',
      success: function (res) {
        if (box) {
          box.style.display = 'block';
          box.innerHTML = '<div class="text-success" style="font-weight: bold;">&#10003; ' + res.message + '</div>';
          if (res.last_batch) {
            var uw = document.getElementById('rr_undo_batch_wrap');
            if (uw) uw.innerHTML = '<button type="button" id="rr_sync_undo_btn" class="btn btn-xs btn-danger btn-fill" onclick="runSubfleetSyncUndo()">&#8617; Undo Last Sync (' + res.last_batch.batch_ref + ': -' + res.last_batch.links_added + ' links)</button>';
          }
        }
        if (execBtn) { execBtn.disabled = true; execBtn.innerHTML = '&#10003; Flight Times Fixed'; }
      },
      error: function (xhr) {
        if (execBtn) { execBtn.disabled = false; execBtn.innerHTML = '&#9889; 1-Click Fix Flight Times'; }
        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : ('Error updating flight times (HTTP ' + xhr.status + ').');
        alert(msg);
      }
    });
  }

  function autoEstimateAdminBlockTime(reqId) {
    var form = document.getElementById('gen_flight_form_' + reqId);
    if (!form) return;
    var depEl = form.querySelector('[name="dpt_airport_id"]');
    var arrEl = form.querySelector('[name="arr_airport_id"]');
    var hEl = form.querySelector('[name="flight_hours"]');
    var mEl = form.querySelector('[name="flight_minutes"]');
    if (!depEl || !arrEl || !hEl || !mEl) return;
    var depId = (depEl.value || '').trim().toUpperCase();
    var arrId = (arrEl.value || '').trim().toUpperCase();
    if (!depId || !arrId) { alert('Select both Departure and Arrival airports first.'); return; }
    if (!window.RRAfterburnerAdminCache || !window.RRAfterburnerAdminCache.getAirportById) {
      alert('Local Airport DB is still syncing — try again in a second.');
      return;
    }
    var d = window.RRAfterburnerAdminCache.getAirportById(depId);
    var a = window.RRAfterburnerAdminCache.getAirportById(arrId);
    if (!d || !a || (!d.lat && !d.lon) || (!a.lat && !a.lon)) {
      alert('Airport coordinates not found in cache — click Refresh Cache first.');
      return;
    }
    var toRad = function (x) { return x * Math.PI / 180; };
    var dLat = toRad(a.lat - d.lat), dLon = toRad(a.lon - d.lon);
    var h = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(toRad(d.lat)) * Math.cos(toRad(a.lat)) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
    var nm = Math.round(3440.065 * 2 * Math.atan2(Math.sqrt(h), Math.sqrt(Math.max(0, 1 - h))));
    var mins = Math.max(20, Math.round((nm / 450) * 60) + (nm < 120 ? 18 : 25));
    hEl.value = Math.floor(mins / 60);
    mEl.value = mins % 60;
  }

  function runSubfleetSyncPreview() {
    var payload = getSubfleetSyncPayload();
    var box = document.getElementById('rr_sync_result_box');
    var prevBtn = document.getElementById('rr_sync_preview_btn');
    var execBtn = document.getElementById('rr_sync_execute_btn');

    if (!payload.subfleet_ids.length) {
      alert('Please select at least one target Subfleet first.');
      return;
    }
    if (prevBtn) { prevBtn.disabled = true; prevBtn.innerHTML = '&#9889; Scanning phpVMS Flights...'; }
    if (execBtn) { execBtn.disabled = true; }

    jQuery.ajax({
      url: '{{ route("routerequest.admin.subfleet_sync.preview") }}',
      method: 'POST',
      data: payload,
      dataType: 'json',
      success: function (res) {
        if (prevBtn) { prevBtn.disabled = false; prevBtn.innerHTML = '&#128269; Dry-Run Preview (Check Matching Flights)'; }
        if (!box) return;
        box.style.display = 'block';

        var html = '<div style="font-weight: bold; color: #0369a1; margin-bottom: 6px;">' +
          'Dry-Run Preview: Found <u>' + res.unique_flights_count + ' flight(s)</u> needing <u>' + res.total_links_to_add + ' new subfleet link(s)</u>.</div>';

        if (Array.isArray(res.breakdown) && res.breakdown.length) {
          html += '<ul class="small" style="margin-bottom: 6px; padding-left: 18px;">';
          res.breakdown.forEach(function (b) {
            html += '<li><strong>' + b.subfleet_name + '</strong>: will attach to <strong>' + b.matched_count + '</strong> flight(s) <span class="text-muted">(' + b.note + ')</span></li>';
          });
          html += '</ul>';
        }

        if (Array.isArray(res.samples) && res.samples.length) {
          html += '<div class="small text-muted"><strong>Sample Matched Routes (up to 20):</strong> ';
          html += res.samples.map(function (s) { return '<code>' + s.ident + ' (' + s.route + ')</code>'; }).join(' ');
          html += '</div>';
        }

        box.innerHTML = html;
        if (execBtn) {
          execBtn.disabled = (res.total_links_to_add <= 0);
          execBtn.innerHTML = res.total_links_to_add > 0
            ? ('&#9889; 1-Click Sync ' + res.total_links_to_add + ' Link(s) Across ' + res.unique_flights_count + ' Flight(s)')
            : '&#10003; Already Up To Date (0 Missing Links)';
        }
      },
      error: function (xhr) {
        if (prevBtn) { prevBtn.disabled = false; prevBtn.innerHTML = '&#128269; Dry-Run Preview (Check Matching Flights)'; }
        alert((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Preview failed.');
      }
    });
  }

  function runSubfleetSyncExecute() {
    var payload = getSubfleetSyncPayload();
    var box = document.getElementById('rr_sync_result_box');
    var execBtn = document.getElementById('rr_sync_execute_btn');

    if (!confirm('Attach the selected subfleet(s) to all matched phpVMS flights now? (Existing subfleet assignments will NOT be removed.)')) {
      return;
    }

    if (execBtn) { execBtn.disabled = true; execBtn.innerHTML = '&#9889; Syncing Routes...'; }

    jQuery.ajax({
      url: '{{ route("routerequest.admin.subfleet_sync.execute") }}',
      method: 'POST',
      data: payload,
      dataType: 'json',
      success: function (res) {
        if (box) {
          box.style.display = 'block';
          box.innerHTML = '<div class="text-success" style="font-weight: bold;">&#10003; ' + res.message + '</div>';
          if (res.last_batch) {
            var uw = document.getElementById('rr_undo_batch_wrap');
            if (uw) uw.innerHTML = '<button type="button" id="rr_sync_undo_btn" class="btn btn-xs btn-danger btn-fill" onclick="runSubfleetSyncUndo()">&#8617; Undo Last Sync (' + res.last_batch.batch_ref + ': -' + res.last_batch.links_added + ' links)</button>';
          }
        }
        if (execBtn) {
          execBtn.disabled = true;
          execBtn.innerHTML = '&#10003; Sync Completed';
        }
      },
      error: function (xhr) {
        if (execBtn) { execBtn.disabled = false; execBtn.innerHTML = '&#9889; 1-Click Sync Selected Subfleet(s) Now'; }
        alert((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Sync failed.');
      }
    });
  }

  function markRequestCompletedAjax(reqId) {
    var btn = document.getElementById('btn_mark_completed_' + reqId);
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '&#9889; Marking Completed...';
    }
    jQuery.ajax({
      url: '{{ url("/admin/routerequest") }}/' + reqId + '/mark-completed',
      method: 'POST',
      data: { _token: '{{ csrf_token() }}', ajax_mode: 1 },
      dataType: 'json',
      headers: { 'Accept': 'application/json' },
      success: function (res) {
        if (!res || !res.success) {
          if (btn) {
            btn.disabled = false;
            btn.innerHTML = '&#10003; Mark Completed (No More Routes)';
          }
          alert((res && res.message) ? res.message : 'Could not mark request completed.');
          return;
        }
        var statusBadgeEl = document.getElementById('row_status_badge_' + reqId);
        if (statusBadgeEl) {
          statusBadgeEl.innerHTML = '<span class="label label-success">Completed (Route Active)</span>';
        }
        var row = document.getElementById('flight_gen_row_' + reqId);
        if (row) {
          row.style.setProperty('display', 'none', 'important');
        }
      },
      error: function (xhr) {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = '&#10003; Mark Completed (No More Routes)';
        }
        alert((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error marking request completed.');
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
            statusEl.innerHTML = (res && res.error) ? res.error : 'No valid FlightAware URLs found. Paste 1 or 2 FlightAware links above and try again.';
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
      error: function (xhr) {
        if (statusEl) {
          statusEl.style.color = '#b91c1c';
          var errMsg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Could not reach FlightAware or parse the link(s).';
          statusEl.innerHTML = errMsg;
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

  function rrSwitchAdminTab(tabId) {
    var panes = document.querySelectorAll('[id^="rr_tab_pane_adm_"]');
    panes.forEach(function (p) { p.classList.remove('rr-tab-pane-active'); });
    var btns = document.querySelectorAll('#rr_admin_tabs .rr-tab-btn');
    btns.forEach(function (b) { b.classList.remove('rr-tab-active'); });

    var targetPane = document.getElementById('rr_tab_pane_' + tabId);
    var targetBtn = document.querySelector('#rr_admin_tabs .rr-tab-btn[data-rr-tab="' + tabId + '"]');
    if (targetPane) targetPane.classList.add('rr-tab-pane-active');
    if (targetBtn) targetBtn.classList.add('rr-tab-active');

    try { localStorage.setItem('rr_admin_active_tab', tabId); } catch (e) {}
  }

  document.addEventListener('DOMContentLoaded', function () {
    var hasOpenGen = {{ !empty($openGenId) ? 'true' : 'false' }};
    if (hasOpenGen) {
      rrSwitchAdminTab('adm_requests');
    } else {
      var saved = null;
      try { saved = localStorage.getItem('rr_admin_active_tab'); } catch (e) {}
      if (saved && document.getElementById('rr_tab_pane_' + saved)) {
        rrSwitchAdminTab(saved);
      }
    }
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
