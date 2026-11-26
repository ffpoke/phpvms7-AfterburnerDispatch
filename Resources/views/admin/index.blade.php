@extends('admin.app')
@section('title', 'Afterburner Dispatch Suite')

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
  $pendingMovesCount = collect($moveRequests ?? [])->where('status', 'pending')->count();
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
  /* v4.0.0: Admin Panel Formatting & Table Readability Polish */
  .rr-admin-table th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.03em; color: #475569; background: #f8fafc; vertical-align: middle !important; }
  .rr-admin-table td { vertical-align: middle !important; padding: 10px 8px !important; }
  .rr-upvote-pill { display: inline-block; padding: 2px 7px; border-radius: 999px; font-size: 11px; font-weight: 700; background: #e0f2fe; color: #0369a1; border: 1px solid #7dd3fc; margin-top: 3px; }
  .rr-digest-card { margin-top: 14px; padding: 12px 14px; background: #f0f9ff; border: 1px solid #7dd3fc; border-radius: 6px; }
</style>

<div class="card border-blue-bottom">
  <div class="content">
    <div class="header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
      <div>
        <h4 class="title" style="margin-bottom: 4px;">
          Afterburner Dispatch Suite — v4.1.3 <span class="label label-default" style="font-size: 11px; margin-right: 4px;">PHP 8.3.36+</span> <span class="label label-primary" style="background: linear-gradient(90deg, #0284c7, #2563eb); color: #fff;">&#9889; Afterburner Edition</span>
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
        <button type="button" class="btn btn-xs btn-success btn-fill" id="rr_self_test_btn" onclick="rrRunSystemSelfTest()" title="Verify ABX2 Vault / DRM Handshake, PHP 8.3.36+, Cron Hooks & Updater Permissions">
          &#129658; Self-Test
        </button>
        <form method="POST" action="{{ route('afterburner.admin.check_update') }}" style="display: inline-block; margin: 0;">
          @csrf
          <button type="submit" class="btn btn-xs btn-info btn-fill" title="Check GitHub Releases ({{ $updateInfo['channel'] ?? 'Stable' }})">
            &#8635; Check for Updates
          </button>
        </form>
      </div>
    </div>

    <div id="rr_self_test_result_box" style="display: none; margin: 10px 15px 4px 15px; padding: 12px 15px; background: #f8fafc; border: 1px solid #0ea5e9; border-radius: 6px; font-size: 12px;"></div>

    @if(version_compare(PHP_VERSION, '8.3.37', '<'))
      <div class="alert alert-info" style="margin: 12px 15px 4px 15px; border-left: 5px solid #0284c7; background-color: #f0f9ff; color: #0c4a6e; font-size: 12px;">
        <strong>&#128737; PHP Security Recommendation:</strong> Running <strong>PHP {{ PHP_VERSION }}</strong> (minimum enforced: <strong>8.3.36</strong>). Upgrading to <strong>PHP 8.3.37+</strong> is recommended for the latest PHP 8.3 security patches.
      </div>
    @endif

    {{-- Pre-Release / Beta Banner with 1-Click Revert to Last Stable Release (v4.0.0) --}}
    @if(!empty($isPrereleaseBuild))
      <div class="alert alert-warning" style="margin: 12px 15px 4px 15px; border-left: 5px solid #d97706; background-color: #fffbeb; color: #78350f;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
          <div>
            <h5 style="margin: 0 0 3px 0; font-weight: bold; color: #92400e;">
              &#129514; Flagship Pre-Release / Beta Build Active (v{{ \Modules\AfterburnerDispatch\Support\UpdateManager::getCurrentVersion() }})
            </h5>
            <div class="small" style="color: #78350f;">
              You are running an experimental Flagship pre-release build. If you installed this by accident or encounter an issue, revert cleanly to the latest Stable GitHub Release in 1 click.
            </div>
          </div>
          <form method="POST" action="{{ route('afterburner.admin.revert_stable') }}" style="margin: 0;" onsubmit="return confirm('Revert Afterburner Dispatch back to the latest Stable main release on GitHub?');">
            @csrf
            <button type="submit" class="btn btn-sm btn-warning btn-fill">
              &#8617; Revert to Last Stable Release
            </button>
          </form>
        </div>
      </div>
    @endif

    {{-- 7-Day Subscription Renewal Banner & 1-Click Renew (v4.0.0) --}}
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
          <form method="POST" action="{{ route('afterburner.admin.license.checkout') }}" style="display: flex; gap: 6px; margin: 0;">
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

    {{-- Organized Admin Navigation Tabs (v4.0.0) --}}
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
      <button type="button" class="rr-tab-btn" data-rr-tab="adm_companions" onclick="rrSwitchAdminTab('adm_companions')">
        &#129513; Companion Modules &amp; Moves @if($pendingMovesCount > 0)<span class="rr-tab-badge" style="background: #dc2626; color: #fff;">{{ $pendingMovesCount }}</span>@endif
      </button>
      <button type="button" class="rr-tab-btn" data-rr-tab="adm_license" onclick="rrSwitchAdminTab('adm_license')">
        &#128273; License &amp; Storefront
      </button>
    </div>

    {{-- Tab 2: Bulk Subfleet-to-Route Auto-Sync --}}
    <div id="rr_tab_pane_adm_subfleet" class="rr-tab-pane">
    {{-- Easy Bulk Subfleet-to-Route Sync (Presets + Checkbox Pills + Undo Last Batch) & Old Route Time Fixer (v4.0.0) --}}
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

    {{-- Old Route Time Estimator Fixer (v4.0.0) --}}
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
    {{-- v4.0.0: Automatic Subfleet Creator by Airline, Step-by-Step Cabin Fare Wizard & Subfleet Standardizer --}}
    @php
      $canSubfleetSingle = !empty($drmEntitlements['features']['subfleet_creator_single']);
      $canSubfleetBulk   = !empty($drmEntitlements['features']['subfleet_creator_bulk']);
      $canSubfleetAny    = $canSubfleetSingle || $canSubfleetBulk;
    @endphp
    <div id="rr_subfleet_creator_box" class="content" style="margin: 0 15px 15px 15px; background: #f8fafc; padding: 16px; border: 1px solid #0ea5e9; border-radius: 6px;">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;">
        <div>
          <h5 style="margin: 0 0 3px 0; font-weight: bold; color: #0369a1;">
            &#9992; Automatic Subfleet Creator &amp; Standardizer (Custom Syntax + Cabin Fare Wizard)
            @if(!$canSubfleetAny)
              <span class="label label-default">DropSchedule Locked</span>
            @elseif(!$canSubfleetBulk)
              <span class="label label-info">Single Subfleet Mode (Tier 1)</span>
            @else
              <span class="label label-success">Bulk + Standardizer Unlocked</span>
            @endif
          </h5>
          <div class="small" style="color: #475569;">
            Pick your destination phpVMS VA Airline separately from the Real-World Operator, customize <code>type</code> &amp; <code>name</code> syntax, optionally map cabin fares step-by-step (strict match with inline <strong>+ Create Fare Code</strong>), or standardize existing subfleets safely.
          </div>
        </div>
        @if($canSubfleetAny)
          <div style="display: flex; gap: 6px;">
            <button type="button" class="btn btn-xs btn-primary btn-fill" id="rr_sfc_mode_create_btn" onclick="rrSfcSwitchMode('create')">&#10010; Create Subfleets</button>
            @if($canSubfleetBulk)
              <button type="button" class="btn btn-xs btn-default" id="rr_sfc_mode_std_btn" onclick="rrSfcSwitchMode('standardize')">&#128295; Standardize Existing</button>
            @endif
          </div>
        @endif
      </div>

      @if($canSubfleetAny)
        {{-- Mode 1: Creator (Screen 1 + Screen 2) --}}
        <div id="rr_sfc_create_wrap">
          {{-- SCREEN 1: VA Airline, Operator, Syntax, Ranks, Aircraft Table --}}
          <div id="rr_sfc_screen1">
            <div id="rr_sfc_updates_banner" style="display: none; background: #f0f9ff; border: 1px solid #7dd3fc; border-left: 4px solid #0284c7; border-radius: 5px; padding: 8px 12px; margin-bottom: 10px; font-size: 11px;"></div>
            <div class="row">
              <div class="col-md-3" style="margin-bottom: 8px;">
                <label class="small fw-bold">1. Destination VA Airline <span class="text-danger">*</span></label>
                <select id="rr_sfc_va_airline" class="form-control input-sm no-select2" onchange="rrSfcRenderTable()">
                  @foreach($airlines as $al)
                    <option value="{{ $al->id }}" data-icao="{{ $al->icao }}" data-name="{{ $al->name }}">{{ $al->icao }} — {{ $al->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-3" style="margin-bottom: 8px;">
                <label class="small fw-bold">2. Real-World Operator Preset <span id="rr_sfc_op_count" class="text-muted" style="font-weight: normal;"></span></label>
                <input type="text" id="rr_sfc_op_filter" class="form-control input-sm" placeholder="Filter 430+ airlines (e.g. Delta, NEW, UPDATED, REV)..." oninput="rrSfcPopulateOperators(this.value)" style="margin-bottom: 3px; height: 24px; font-size: 11px;">
                <select id="rr_sfc_operator" class="form-control input-sm no-select2" onchange="rrSfcLoadOperator()"></select>
                <div id="rr_sfc_op_hint" class="small" style="margin-top: 2px; font-size: 10px; color: #0369a1;"></div>
              </div>
              <div class="col-md-2" style="margin-bottom: 8px;">
                <label class="small fw-bold">Operator ICAO</label>
                <input type="text" id="rr_sfc_op_icao" class="form-control input-sm" value="DAL" oninput="rrSfcRenderTable()">
              </div>
              <div class="col-md-1" style="margin-bottom: 8px;">
                <label class="small fw-bold">IATA</label>
                <input type="text" id="rr_sfc_op_iata" class="form-control input-sm" value="DL" oninput="rrSfcRenderTable()">
              </div>
              <div class="col-md-3" style="margin-bottom: 8px;">
                <label class="small fw-bold">Operator Display Name</label>
                <input type="text" id="rr_sfc_op_name" class="form-control input-sm" value="Delta" oninput="rrSfcRenderTable()">
              </div>
            </div>

            <div class="row">
              <div class="col-md-5" style="margin-bottom: 8px;">
                <label class="small fw-bold">Subfleet <code>type</code> Code Syntax (Unique)</label>
                <input type="text" id="rr_sfc_type_syntax" class="form-control input-sm" value="{{ $subfleetCreatorData['type_syntax'] ?? '{OP_ICAO}.{ICAO}.{VARIANT}' }}" oninput="rrSfcRenderTable()">
              </div>
              <div class="col-md-7" style="margin-bottom: 8px;">
                <label class="small fw-bold">Subfleet <code>name</code> Syntax</label>
                <input type="text" id="rr_sfc_name_syntax" class="form-control input-sm" value="{{ $subfleetCreatorData['name_syntax'] ?? '{OP_ICAO} {AIRCRAFT} {VARIANT} {CONFIG} ({PAX})' }}" oninput="rrSfcRenderTable()">
              </div>
            </div>
            <div class="small text-muted" style="margin-bottom: 8px;">
              Tokens: <code>{OP_ICAO}</code> <code>{OP_IATA}</code> <code>{OP_NAME}</code> <code>{AIRLINE_ICAO}</code> <code>{AIRLINE_NAME}</code> <code>{ICAO}</code> <code>{IATA}</code> <code>{AIRCRAFT}</code> <code>{VARIANT}</code> <code>{CONFIG}</code> <code>{PAX}</code> (empty tokens &amp; extra dots collapse automatically).
            </div>

            <div class="row" style="margin-bottom: 8px; align-items: center;">
              <div class="col-md-3">
                <label class="small fw-bold">Fuel Type</label>
                <select id="rr_sfc_fuel_type" class="form-control input-sm no-select2">
                  <option value="1" selected>Jet A</option>
                  <option value="0">100LL (Avgas)</option>
                  <option value="2">Mogas</option>
                </select>
              </div>
              <div class="col-md-5">
                <label class="small fw-bold">Attach Default Ranks (Optional)</label>
                <div style="display: flex; flex-wrap: wrap; gap: 8px; padding-top: 3px;">
                  @foreach(($subfleetCreatorData['ranks'] ?? []) as $rk)
                    <label class="small" style="font-weight: normal; margin: 0; cursor: pointer;">
                      <input type="checkbox" class="rr-sfc-rank-cb" value="{{ $rk['id'] }}"> {{ $rk['name'] }}
                    </label>
                  @endforeach
                </div>
              </div>
              <div class="col-md-4">
                <label class="small fw-bold" style="cursor: pointer; margin-top: 16px; color: #0369a1;">
                  <input type="checkbox" id="rr_sfc_map_fares" checked>
                  &#127915; Map &amp; Attach Cabin Fares (Step-by-Step Next Screen)
                </label>
              </div>
            </div>

            <div id="rr_sfc_rev_strip" style="display: none; background: #f8fafc; border: 1px solid #cbd5e1; border-left: 3px solid #0284c7; border-radius: 4px; padding: 6px 10px; margin-bottom: 6px; font-size: 11px;"></div>

            <div class="table-responsive" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 5px; max-height: 360px; overflow-y: auto;">
              <table class="table table-condensed table-striped" style="margin-bottom: 0; font-size: 12px;">
                <thead>
                  <tr>
                    <th style="width: 30px;">@if($canSubfleetBulk)<input type="checkbox" id="rr_sfc_sel_all" onclick="rrSfcToggleAll(this.checked)">@else Sel @endif</th>
                    <th>Aircraft Model</th>
                    <th style="width: 72px;">ICAO</th>
                    <th style="width: 66px;">IATA</th>
                    <th style="width: 72px;">Variant</th>
                    <th style="width: 88px;">Config</th>
                    <th style="width: 68px;">PAX</th>
                    <th style="width: 56px;">Tag</th>
                    <th style="width: 120px;">SimBrief Airframe</th>
                    <th style="width: 145px;">Auto Type Rating</th>
                    <th>Live Preview (<code>type</code> &rarr; <code>name</code>)</th>
                  </tr>
                </thead>
                <tbody id="rr_sfc_rows_tbody"></tbody>
              </table>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-top: 10px;">
              <button type="button" class="btn btn-xs btn-default" onclick="rrSfcAddCustomRow()">+ Add Custom Aircraft / Variant</button>
              <div style="display: flex; gap: 8px; align-items: center;">
                <span id="rr_sfc_sel_summary" class="small text-muted">0 selected</span>
                <button type="button" class="btn btn-sm btn-primary btn-fill" onclick="rrSfcProceedFromScreen1()">Next: Configure Cabin Fares / Create &rarr;</button>
              </div>
            </div>
          </div>

          {{-- SCREEN 2: Per-Plane Step-by-Step Cabin & Fare Mapping Wizard --}}
          <div id="rr_sfc_screen2" style="display: none; background: #ffffff; border: 1px solid #0284c7; border-radius: 6px; padding: 14px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">
              <div>
                <span class="label label-primary" id="rr_sfc_step_badge">Aircraft 1 of 1</span>
                <strong id="rr_sfc_step_title" style="font-size: 14px; margin-left: 6px; color: #0f172a;"></strong>
                <div class="small text-muted" id="rr_sfc_step_preview"></div>
              </div>
              <button type="button" class="btn btn-xs btn-default" onclick="rrSfcBackToScreen1()">&larr; Back to Fleet Selection</button>
            </div>

            <div class="small" style="color: #475569; margin-bottom: 8px;">
              Pick the existing phpVMS Fare for each cabin or type an editable Fare Code. Codes are <strong>strictly matched</strong> against your phpVMS Fares table—if a code doesn't exist yet, you'll see a warning with an inline <strong>+ Create Fare Code</strong> button.
            </div>

            <table class="table table-condensed table-bordered" style="font-size: 12px; margin-bottom: 8px;">
              <thead>
                <tr style="background: #f8fafc;">
                  <th style="width: 145px;">Cabin Label</th>
                  <th style="width: 90px;">Seats</th>
                  <th style="width: 230px;">Existing phpVMS Fare</th>
                  <th style="width: 110px;">Editable Code</th>
                  <th>Strict Match Status / Create Missing Fare</th>
                  <th style="width: 36px;"></th>
                </tr>
              </thead>
              <tbody id="rr_sfc_cabin_tbody"></tbody>
            </table>

            <button type="button" class="btn btn-xs btn-default" onclick="rrSfcAddCabinRow()">+ Add Cabin / Cargo Row</button>

            {{-- Inline Create New phpVMS Fare Code Panel --}}
            <div id="rr_sfc_new_fare_panel" style="display: none; margin-top: 10px; padding: 10px 12px; background: #fffbeb; border: 1px solid #f59e0b; border-radius: 5px;">
              <div style="font-weight: bold; color: #92400e; margin-bottom: 6px;">&#10010; Create New phpVMS Fare Code</div>
              <div class="row">
                <div class="col-md-2"><label class="small">Code *</label><input type="text" id="rr_sfc_nf_code" class="form-control input-sm" maxlength="20"></div>
                <div class="col-md-3"><label class="small">Fare Name *</label><input type="text" id="rr_sfc_nf_name" class="form-control input-sm" placeholder="e.g. Comfort+"></div>
                <div class="col-md-2"><label class="small">Price</label><input type="number" step="0.01" id="rr_sfc_nf_price" class="form-control input-sm" value="250"></div>
                <div class="col-md-2"><label class="small">Default Cap</label><input type="number" id="rr_sfc_nf_cap" class="form-control input-sm" value="0"></div>
                <div class="col-md-3"><label class="small">Type</label><select id="rr_sfc_nf_type" class="form-control input-sm no-select2"><option value="0">Passenger</option><option value="1">Cargo</option></select></div>
              </div>
              <div style="margin-top: 8px; display: flex; gap: 6px; align-items: center;">
                <button type="button" class="btn btn-xs btn-warning btn-fill" onclick="rrSfcSubmitNewFare()">&#10003; Save New Fare to phpVMS</button>
                <button type="button" class="btn btn-xs btn-default" onclick="document.getElementById('rr_sfc_new_fare_panel').style.display='none'">Cancel</button>
                <span id="rr_sfc_nf_status" class="small"></span>
              </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-top: 12px; padding-top: 10px; border-top: 1px solid #e2e8f0;">
              <div>
                <button type="button" class="btn btn-xs btn-default" id="rr_sfc_prev_btn" onclick="rrSfcStepNav(-1)">&larr; Previous</button>
                <button type="button" class="btn btn-xs btn-info" id="rr_sfc_next_btn" onclick="rrSfcStepNav(1)">Next Subfleet &rarr;</button>
              </div>
              <button type="button" class="btn btn-sm btn-success btn-fill" id="rr_sfc_finish_btn" onclick="rrSfcExecuteCreate()">&#9889; Create Subfleet(s) Now</button>
            </div>
          </div>

          <div id="rr_sfc_result_box" style="display: none; margin-top: 10px; padding: 10px; border-radius: 5px; font-size: 12px;"></div>
        </div>

        {{-- Mode 2: Existing Subfleet Standardizer & Cabin Layout Fixer (Bulk Unlocked) --}}
        @if($canSubfleetBulk)
          <div id="rr_sfc_std_wrap" style="display: none;">
            <div class="small text-muted" style="margin-bottom: 8px;">
              Fix broken existing subfleet <code>type</code>, <code>name</code>, SimBrief airframe, and cabin layouts (<code>subfleet_fare</code>) safely by ID without breaking linked flights, aircraft, or PIREPs. Pick a <strong>Real-World Operator Preset</strong> below and match rows automatically or individually.
            </div>

            <div class="row">
              <div class="col-md-4" style="margin-bottom: 8px;">
                <label class="small fw-bold">1. Real-World Operator Preset <span id="rr_sfc_std_op_count" class="text-muted" style="font-weight: normal;"></span></label>
                <input type="text" id="rr_sfc_std_op_filter" class="form-control input-sm" placeholder="Filter 430+ airlines (e.g. Delta, ACA, NEW, UPDATED)..." oninput="rrSfcStdPopulateOperators(this.value)" style="margin-bottom: 3px; height: 24px; font-size: 11px;">
                <select id="rr_sfc_std_operator" class="form-control input-sm no-select2" onchange="rrSfcStdLoadOperator()"></select>
                <div id="rr_sfc_std_op_hint" class="small" style="margin-top: 2px; font-size: 10px; color: #0369a1;"></div>
              </div>
              <div class="col-md-2" style="margin-bottom: 8px;">
                <label class="small fw-bold">Operator ICAO</label>
                <input type="text" id="rr_sfc_std_op_icao" class="form-control input-sm" value="DAL" oninput="rrSfcStdReapplySyntax()">
              </div>
              <div class="col-md-2" style="margin-bottom: 8px;">
                <label class="small fw-bold">IATA</label>
                <input type="text" id="rr_sfc_std_op_iata" class="form-control input-sm" value="DL" oninput="rrSfcStdReapplySyntax()">
              </div>
              <div class="col-md-4" style="margin-bottom: 8px;">
                <label class="small fw-bold">Operator Display Name</label>
                <input type="text" id="rr_sfc_std_op_name" class="form-control input-sm" value="Delta" oninput="rrSfcStdReapplySyntax()">
              </div>
            </div>

            <div class="row">
              <div class="col-md-5" style="margin-bottom: 8px;">
                <label class="small fw-bold">Subfleet <code>type</code> Code Syntax (Unique)</label>
                <input type="text" id="rr_sfc_std_type_syntax" class="form-control input-sm" value="{{ $subfleetCreatorData['type_syntax'] ?? '{OP_ICAO}.{ICAO}.{VARIANT}' }}" oninput="rrSfcStdReapplySyntax()">
              </div>
              <div class="col-md-7" style="margin-bottom: 8px;">
                <label class="small fw-bold">Subfleet <code>name</code> Syntax</label>
                <input type="text" id="rr_sfc_std_name_syntax" class="form-control input-sm" value="{{ $subfleetCreatorData['name_syntax'] ?? '{OP_ICAO} {AIRCRAFT} {VARIANT} {CONFIG} ({PAX})' }}" oninput="rrSfcStdReapplySyntax()">
              </div>
            </div>
            <div class="small text-muted" style="margin-bottom: 8px;">
              Tokens: <code>{OP_ICAO}</code> <code>{OP_IATA}</code> <code>{OP_NAME}</code> <code>{AIRLINE_ICAO}</code> <code>{AIRLINE_NAME}</code> <code>{ICAO}</code> <code>{IATA}</code> <code>{AIRCRAFT}</code> <code>{VARIANT}</code> <code>{CONFIG}</code> <code>{PAX}</code> (empty tokens &amp; extra dots collapse automatically).
            </div>

            <div id="rr_sfc_std_rev_strip" style="display: none; background: #f8fafc; border: 1px solid #cbd5e1; border-left: 3px solid #0284c7; border-radius: 4px; padding: 6px 10px; margin-bottom: 8px; font-size: 11px;"></div>

            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 5px; padding: 7px 10px;">
              <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <input type="text" id="rr_sfc_std_row_filter" class="form-control input-sm" placeholder="Filter existing subfleets (ID, type, name)..." oninput="rrSfcStdFilterRows(this.value)" style="width: 240px; height: 26px; font-size: 11px;">
                <label class="small fw-bold" style="cursor: pointer; margin: 0; color: #0369a1;">
                  <input type="checkbox" id="rr_sfc_std_fix_cabins" checked>
                  &#127915; Fix &amp; Replace Cabin Fares / Layouts (<code>subfleet_fare</code>)
                </label>
              </div>
              <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                <button type="button" class="btn btn-xs btn-info btn-fill" onclick="rrSfcStdAutoMatchVisible()">&#9889; Auto-Match Visible Rows to Preset</button>
                <button type="button" class="btn btn-xs btn-default" onclick="rrSfcStdOpenNewFare('', '', 0)">+ Create Fare Code</button>
              </div>
            </div>

            {{-- Inline Create Fare Code panel for Standardizer --}}
            <div id="rr_sfc_std_new_fare_panel" style="display: none; background: #fefce8; border: 1px solid #facc15; border-radius: 5px; padding: 10px; margin-bottom: 8px;">
              <div class="small fw-bold" style="color: #854d0e; margin-bottom: 6px;">+ Create Missing phpVMS Fare Code</div>
              <div class="row">
                <div class="col-md-2"><input type="text" id="rr_sfc_std_nf_code" class="form-control input-sm" placeholder="Code (e.g. W)"></div>
                <div class="col-md-4"><input type="text" id="rr_sfc_std_nf_name" class="form-control input-sm" placeholder="Name (e.g. Premium Economy)"></div>
                <div class="col-md-2"><input type="number" step="0.01" id="rr_sfc_std_nf_price" class="form-control input-sm" placeholder="Price" value="250"></div>
                <div class="col-md-2"><input type="number" id="rr_sfc_std_nf_cap" class="form-control input-sm" placeholder="Default Cap" value="20"></div>
                <div class="col-md-2">
                  <select id="rr_sfc_std_nf_type" class="form-control input-sm no-select2">
                    <option value="0">Passenger</option>
                    <option value="1">Cargo</option>
                  </select>
                </div>
              </div>
              <div style="margin-top: 6px; display: flex; gap: 6px; align-items: center;">
                <button type="button" class="btn btn-xs btn-warning btn-fill" onclick="rrSfcStdSubmitNewFare()">&#10003; Save New Fare to phpVMS</button>
                <button type="button" class="btn btn-xs btn-default" onclick="document.getElementById('rr_sfc_std_new_fare_panel').style.display='none'">Cancel</button>
                <span id="rr_sfc_std_nf_status" class="small"></span>
              </div>
            </div>

            <div class="table-responsive" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 5px; max-height: 420px; overflow-y: auto;">
              <table class="table table-condensed table-striped" style="margin-bottom: 0; font-size: 12px;">
                <thead>
                  <tr>
                    <th style="width: 30px;"><input type="checkbox" id="rr_sfc_std_sel_all" onclick="rrSfcStdToggleAll(this.checked)"></th>
                    <th style="width: 44px;">ID</th>
                    <th style="min-width: 175px;">Current <code>type</code> / <code>name</code> &amp; Cabins</th>
                    <th style="min-width: 195px;">Match Preset Aircraft</th>
                    <th style="width: 155px;">New <code>type</code></th>
                    <th style="min-width: 195px;">New <code>name</code></th>
                    <th style="width: 110px;">SimBrief ID</th>
                    <th style="min-width: 175px;">Cabin Layout Fix</th>
                  </tr>
                </thead>
                <tbody id="rr_sfc_std_tbody">
                  @foreach($subfleets as $esf)
                    <tr class="rr-sfc-std-row" data-id="{{ $esf->id }}" data-orig-type="{{ $esf->type }}" data-orig-name="{{ $esf->name }}" data-orig-sb="{{ $esf->simbrief_type ?? '' }}" data-airline-icao="{{ optional($esf->airline)->icao ?? '' }}" data-airline-name="{{ optional($esf->airline)->name ?? '' }}">
                      <td><input type="checkbox" class="rr-sfc-std-cb" value="{{ $esf->id }}"></td>
                      <td>#{{ $esf->id }}</td>
                      <td>
                        <code>{{ $esf->type }}</code><br>
                        <small class="text-muted">{{ $esf->name }}</small>
                        <div class="rr-sfc-std-cur-fares" data-id="{{ $esf->id }}" style="margin-top: 2px; font-size: 10px;"></div>
                      </td>
                      <td>
                        <select class="form-control input-sm no-select2 rr-sfc-std-preset-sel" data-id="{{ $esf->id }}" onchange="rrSfcStdApplyPresetToRow({{ $esf->id }}, this.value)" style="height: 24px; font-size: 11px; padding: 1px 4px;">
                          <option value="">— Keep / Manual —</option>
                        </select>
                      </td>
                      <td>
                        <input type="text" class="form-control input-sm rr-sfc-std-type" data-id="{{ $esf->id }}" value="{{ $esf->type }}" oninput="rrSfcStdCheckLen({{ $esf->id }})" style="height: 24px; font-size: 11px;">
                        <div class="rr-sfc-std-tlen" data-id="{{ $esf->id }}" style="font-size: 10px;"></div>
                      </td>
                      <td>
                        <input type="text" class="form-control input-sm rr-sfc-std-name" data-id="{{ $esf->id }}" value="{{ $esf->name }}" oninput="rrSfcStdCheckLen({{ $esf->id }})" style="height: 24px; font-size: 11px;">
                        <div class="rr-sfc-std-nlen" data-id="{{ $esf->id }}" style="font-size: 10px;"></div>
                      </td>
                      <td><input type="text" class="form-control input-sm rr-sfc-std-simbrief" data-id="{{ $esf->id }}" value="{{ $esf->simbrief_type ?? '' }}" placeholder="ICAO / ID" style="height: 24px; font-size: 11px;"></td>
                      <td>
                        <div class="rr-sfc-std-cabins-cell" data-id="{{ $esf->id }}" style="font-size: 11px;"><span class="text-muted">Unchanged</span></div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-top: 8px;">
              <span id="rr_sfc_std_sel_summary" class="small text-muted">Pick a Real-World Operator Preset and match broken rows to fix naming &amp; cabins.</span>
              <button type="button" class="btn btn-sm btn-warning btn-fill" onclick="rrSfcExecuteStandardize()">&#128295; Apply Selected Subfleet &amp; Cabin Fixes</button>
            </div>
            <div id="rr_sfc_std_result" style="display: none; margin-top: 8px; padding: 10px; border-radius: 5px; font-size: 12px;"></div>
          </div>
        @endif
      @endif
    </div>

    </div>

    {{-- Tab 3: Dispatch Settings & Discord Webhooks --}}
    <div id="rr_tab_pane_adm_settings" class="rr-tab-pane">
    <div id="rr_dispatch_settings_box" class="content" style="margin: 0 15px 15px 15px; background: #f8fafc; padding: 16px; border: 1px solid #94a3b8; border-radius: 6px;">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;">
        <h5 style="margin: 0; font-weight: bold; color: #0f172a;">
          &#9881; Afterburner Dispatch Settings, Pilot Limits &amp; Discord Webhooks (v4.0.0)
        </h5>
        @if($hasDiscord)
          <form method="POST" action="{{ route('afterburner.admin.settings.test_discord') }}" style="margin: 0;">
            @csrf
            <button type="submit" class="btn btn-xs btn-info btn-fill">&#9889; Test Discord Webhooks</button>
          </form>
        @endif
      </div>
      <form method="POST" action="{{ route('afterburner.admin.settings.save') }}">
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

        @php $hasDigest = !empty($drmEntitlements['features']['daily_dispatch_digest']); @endphp
        <div class="rr-digest-card">
          <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
            <div>
              <strong style="color: #0369a1;">&#128236; Scheduled Batch Route Digest</strong>
              @if(!$hasDigest)<span class="label label-default" style="margin-left: 4px;">Exec/Flagship</span>@endif
              <div class="small" style="color: #475569;">
                Sends one consolidated summary of pending routes, top community upvotes, completed routes, and expiring charters to Discord &amp;/or Email.
                @if(!empty($cs['last_digest_sent_at']))
                  <span style="color: #0f766e;">(Last sent: <strong>{{ $cs['last_digest_sent_at'] }}</strong>)</span>
                @endif
              </div>
            </div>
            @if($hasDigest)
              <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                <button type="button" class="btn btn-xs btn-default" onclick="rrPreviewBatchDigest()">&#128065; Preview Digest</button>
                <button type="button" class="btn btn-xs btn-primary btn-fill" onclick="document.getElementById('rr_send_digest_form').submit();">&#128232; Send Digest Now</button>
              </div>
            @endif
          </div>
          <div class="row">
            <div class="col-md-3" style="margin-bottom: 6px;">
              <label class="small fw-bold">SimBrief Button w/ PaxStudio</label>
              <select name="show_simbrief_with_paxstudio" class="form-control input-sm">
                <option value="0" {{ empty($cs['show_simbrief_with_paxstudio']) ? 'selected' : '' }}>Hide SimBrief (PaxStudio Only)</option>
                <option value="1" {{ !empty($cs['show_simbrief_with_paxstudio']) ? 'selected' : '' }}>Show Both PaxStudio &amp; SimBrief</option>
              </select>
            </div>
            <div class="col-md-3" style="margin-bottom: 6px;">
              <label class="small fw-bold">Scheduled Digest</label>
              <select name="digest_enabled" class="form-control input-sm" {{ $hasDigest ? '' : 'disabled' }}>
                <option value="0" {{ empty($cs['digest_enabled']) ? 'selected' : '' }}>Disabled</option>
                <option value="1" {{ !empty($cs['digest_enabled']) ? 'selected' : '' }}>Enabled (Cron Ready)</option>
              </select>
            </div>
            <div class="col-md-3" style="margin-bottom: 6px;">
              <label class="small fw-bold">Summary Lookback Window</label>
              <select name="digest_window_hours" class="form-control input-sm" {{ $hasDigest ? '' : 'disabled' }}>
                <option value="24" {{ ((int) ($cs['digest_window_hours'] ?? 24)) === 24 ? 'selected' : '' }}>Last 24 Hours (Daily)</option>
                <option value="72" {{ ((int) ($cs['digest_window_hours'] ?? 24)) === 72 ? 'selected' : '' }}>Last 72 Hours</option>
                <option value="168" {{ ((int) ($cs['digest_window_hours'] ?? 24)) === 168 ? 'selected' : '' }}>Last 7 Days (Weekly)</option>
              </select>
            </div>
            <div class="col-md-6" style="margin-bottom: 6px;">
              <label class="small fw-bold">Optional Admin Digest Email (in addition to Discord)</label>
              <input type="email" name="digest_recipient_email" class="form-control input-sm" value="{{ $cs['digest_recipient_email'] ?? '' }}" placeholder="dispatch@myvirtualairline.com" {{ $hasDigest ? '' : 'disabled' }}>
            </div>
          </div>
          <div class="small" style="margin-top: 4px; color: #334155;">
            <strong>Automatic via phpVMS <code>CronDaily</code> (no extra cron needed) — or manual CLI:</strong>
            <code>0 8 * * * cd {{ base_path() }} &amp;&amp; php artisan afterburner:send-digest &gt;/dev/null 2&gt;&amp;1</code>
          </div>
          <pre id="rr_digest_preview_box" style="display: none; margin-top: 8px; padding: 10px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 11px; white-space: pre-wrap;"></pre>
        </div>
      </form>
      <form id="rr_send_digest_form" method="POST" action="{{ route('afterburner.admin.digest.send') }}" style="display: none;">@csrf</form>
    </div>

    </div>

    {{-- Tab 5: Companion Modules Installer/Updater & Pilot Move Requests Queue (v4.0.0) --}}
    <div id="rr_tab_pane_adm_companions" class="rr-tab-pane">
      {{-- v4.1.3: SkyAdventures Bridge Health & VA Recommended Scenery Overrides --}}
      @if(!empty($skyAdventuresHealth))
        <div style="border:1px solid #cbd5e1;border-radius:6px;padding:12px;margin:10px 0;background:#f8fafc;font-size:12px;">
          <strong>&#129517; SkyAdventures Bridge Status (MANFahrer-GF/SkyAdventures):</strong>
          @if(!empty($skyAdventuresHealth['enabled']))
            <span class="label label-success">ACTIVE v{{ $skyAdventuresHealth['version'] ?? '?' }}</span>
            &bull; Locked Ferry Aircraft Guarded: <strong>{{ $skyAdventuresHealth['locked_ferry_count'] ?? 0 }}</strong>
          @else
            <span class="label label-default">NOT INSTALLED / INACTIVE</span>
          @endif
          @if(!empty($skyAdventuresHealth['warning']))
            <div class="alert alert-warning" style="margin-top:8px;margin-bottom:0;padding:6px 10px;">&#9888; {{ $skyAdventuresHealth['warning'] }}</div>
          @endif
        </div>
      @endif

      <div style="border:1px solid #bfdbfe;border-radius:6px;padding:12px;margin:10px 0;background:#eff6ff;font-size:12px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
          <h5 style="margin:0;font-weight:700;">&#127758; VA Admin "Recommended by VA" Scenery Pins</h5>
          <form method="POST" action="{{ route('afterburner.admin.scenery_cache.purge') }}" onsubmit="return confirm('Clear local scenery cache so airports re-scrape on next view?');">
            @csrf
            <button type="submit" class="btn btn-xs btn-warning">&#8635; Clear Local Scenery Cache</button>
          </form>
        </div>
        <form method="POST" action="{{ route('afterburner.admin.scenery_overrides.save') }}" class="row" style="margin-bottom:10px;">
          @csrf
          <div class="col-md-1"><input type="text" name="icao" class="form-control input-sm" placeholder="ICAO" required></div>
          <div class="col-md-2">
            <select name="simulator" class="form-control input-sm">
              <option value="all">All Simulators</option>
              @foreach(($supportedSimulators ?? []) as $sk => $sl)
                <option value="{{ $sk }}">{{ $sl }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2"><input type="text" name="title" class="form-control input-sm" placeholder="Scenery Title" required></div>
          <div class="col-md-3"><input type="url" name="url" class="form-control input-sm" placeholder="https://download-or-store-link" required></div>
          <div class="col-md-2"><input type="text" name="instructions" class="form-control input-sm" placeholder="Install note / GSX profile"></div>
          <div class="col-md-2"><button type="submit" class="btn btn-sm btn-primary btn-fill">+ Pin Scenery</button></div>
        </form>
        @if(isset($sceneryOverrides) && $sceneryOverrides->count() > 0)
          <table class="table table-condensed table-bordered" style="background:#fff;margin-bottom:0;">
            <thead><tr><th>ICAO</th><th>Simulator</th><th>Title</th><th>URL</th><th>Action</th></tr></thead>
            <tbody>
              @foreach($sceneryOverrides as $ov)
                <tr>
                  <td><code>{{ $ov->icao }}</code></td>
                  <td>{{ $ov->simulator }}</td>
                  <td><strong>{{ $ov->title }}</strong> <span class="text-muted">{{ $ov->instructions }}</span></td>
                  <td><a href="{{ $ov->url }}" target="_blank" rel="noopener">Open &rarr;</a></td>
                  <td>
                    <form method="POST" action="{{ route('afterburner.admin.scenery_overrides.delete', $ov->id) }}">
                      @csrf @method('DELETE')
                      <button type="submit" class="btn btn-xs btn-danger">Remove</button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @endif
      </div>

      <div class="content" style="margin: 0 15px 15px 15px; background: #f8fafc; padding: 16px; border: 1px solid #0284c7; border-radius: 6px;">
        <h5 style="margin: 0 0 4px 0; font-weight: bold; color: #0369a1;">
          &#129513; Companion Modules (DisposableBasic, ManualPirepControl, PaxStudio &amp; SkyAdventures)
        </h5>
        <div class="small" style="color: #475569; margin-bottom: 10px;">
          To activate the <strong>Flagship + PaxStudio Guided Pilot &amp; Aircraft Travel</strong> workflow (when Manual PIREPs are disabled), both <strong>DisposableBasic</strong> and <strong>ManualPirepControl</strong> must be installed and enabled.
          <br><strong style="color: #b45309;">&#9888; Important:</strong> Configure JumpSeat/Aircraft Transfer pricing inside <strong>DisposableBasic</strong> and Manual PIREP filing inside <strong>Admin &rarr; Settings &rarr; PIREPs</strong> — AfterburnerDispatch reads those modules directly so it never conflicts with or breaks other modules.
        </div>

        @php
          $abHasGhPat = \Modules\AfterburnerDispatch\Support\ClientSettings::hasGitHubPat();
          $abMaskedGhPat = \Modules\AfterburnerDispatch\Support\ClientSettings::getGitHubPatMasked();
        @endphp
        <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 12px; margin-bottom: 12px;">
          <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px; margin-bottom: 6px;">
            <strong class="small" style="color: #0f172a;">&#128273; Private GitHub Repository Access — Personal Access Token (Classic)</strong>
            @if($abHasGhPat)
              <span class="label label-success">&#10003; Configured Locally ({{ $abMaskedGhPat }})</span>
            @else
              <span class="label label-default">Not Configured (Optional — Enables Private Repo Auto-Updates)</span>
            @endif
          </div>
          <div class="small text-muted" style="margin-bottom: 8px;">
            To enable 1-click updates for private repositories (<strong>PaxStudio</strong> &amp; <strong>SkyAdventures</strong>) without sharing developer credentials, generate your own <strong>GitHub Personal Access Token (classic)</strong> with <code>repo</code> scope (<em>GitHub &rarr; Settings &rarr; Developer settings &rarr; Personal access tokens &rarr; Tokens (classic)</em>). Your token is encrypted at rest in your local database (or set <code>AB_GITHUB_PAT</code> in <code>.env</code>) and is never sent to AfterburnerServer.
          </div>
          <form method="POST" action="{{ route('afterburner.admin.companion.github_pat') }}" style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center; margin: 0;">
            @csrf
            <input type="password" name="github_pat_classic" class="form-control input-sm" style="flex: 1; min-width: 240px;" placeholder="{{ $abHasGhPat ? 'Paste new ghp_... token to replace existing' : 'ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' }}" autocomplete="new-password">
            <button type="submit" class="btn btn-xs btn-primary btn-fill">&#128190; Save GitHub PAT</button>
            @if($abHasGhPat)
              <button type="submit" name="clear_github_pat" value="1" class="btn btn-xs btn-danger" onclick="return confirm('Clear your saved GitHub Personal Access Token from the local database?');">&#10005; Clear Token</button>
            @endif
          </form>
        </div>

        <div class="row">
          @foreach(($companionModules ?? []) as $cm)
            <div class="col-md-6" style="margin-bottom: 10px;">
              <div style="background: #ffffff; border: 1px solid {{ $cm['installed'] ? '#16a34a' : '#f59e0b' }}; border-radius: 6px; padding: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                  <strong style="font-size: 14px; color: #0f172a;">{{ $cm['name'] }}</strong>
                  @if($cm['installed'] && $cm['enabled'])
                    <span class="label label-success">&#10003; Installed ({{ $cm['current_version'] }})</span>
                  @elseif($cm['installed'])
                    <span class="label label-warning">Installed (Disabled)</span>
                  @else
                    <span class="label label-danger">Not Installed</span>
                  @endif
                </div>
                <div class="small" style="color: #475569; margin-bottom: 8px;">
                  {{ $cm['purpose'] }}<br>
                  <strong>Configure in:</strong> <code>{{ $cm['config_hint'] }}</code> &bull;
                  <a href="{{ $cm['html_url'] }}" target="_blank">GitHub ({{ $cm['repo'] }})</a>
                </div>
                @if(!empty($cm['private_repo']) && empty($cm['pat_configured']))
                  <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <a href="{{ $cm['releases_url'] ?? $cm['html_url'] }}" target="_blank" rel="noopener" class="btn btn-xs btn-default">
                      &#128279; Open GitHub Releases (Manual Update) &rarr;
                    </a>
                    <span class="label label-info" style="font-weight: normal;">Private Repo — Add GitHub PAT Above for Auto-Update</span>
                  </div>
                @else
                  <form method="POST" action="{{ route('afterburner.admin.companion.install') }}" style="margin: 0;" onsubmit="return confirm('Download and install/update {{ $cm['name'] }} via AfterburnerDispatch Zip-Slip-hardened updater now?');">
                    @csrf
                    <input type="hidden" name="module_key" value="{{ $cm['key'] }}">
                    @if(!$cm['installed'])
                      <button type="submit" class="btn btn-xs btn-success btn-fill">&#9889; 1-Click Install {{ $cm['name'] }}</button>
                    @elseif($cm['update_available'])
                      <button type="submit" class="btn btn-xs btn-primary btn-fill">&#8635; 1-Click Update {{ $cm['name'] }} (v{{ $cm['latest_version'] }})</button>
                    @else
                      <button type="submit" class="btn btn-xs btn-default">&#8635; Reinstall / Refresh {{ $cm['name'] }}</button>
                    @endif
                  </form>
                @endif
              </div>
            </div>
          @endforeach
        </div>

        <div class="small" style="margin-top: 6px; padding: 8px 10px; background: #ffffff; border: 1px dashed #94a3b8; border-radius: 5px; color: #334155;">
          <strong>Live Pricing Readout ({{ $disposableRates['source'] ?? 'DisposableBasic' }}):</strong>
          Pilot JumpSeat: <code>${{ number_format((float) ($disposableRates['pilot_per_nm'] ?? 0.25), 2) }}/NM</code> &bull;
          Aircraft Transfer: <code>${{ number_format((float) ($disposableRates['aircraft_per_nm'] ?? 0.50), 2) }}/NM</code> &bull;
          Minimum Fee: <code>${{ number_format((float) ($disposableRates['min_fee'] ?? 25.00), 2) }}</code>
          <span style="color: #64748b;">(Edit inside DisposableBasic settings to change)</span>
        </div>

        <div class="small" style="margin-top: 6px; color: #334155;">
          <strong>Guided Travel Status:</strong>
          @if(!empty($travelFeatureActive))
            <span class="label label-success">&#10003; ACTIVE (Flagship + PaxStudio + DisposableBasic + Manual PIREP Disabled)</span>
          @else
            <span class="label label-default">Standby — Requires Tier 3 Flagship, PaxStudio, DisposableBasic &amp; Manual PIREP Disabled</span>
          @endif
        </div>
      </div>

      {{-- Admin Allow / Deny Queue for Pilot Move Requests --}}
      <div class="content" style="margin: 0 15px 15px 15px; background: #fff7ed; padding: 16px; border: 1px solid #ea580c; border-radius: 6px;">
        <h5 style="margin: 0 0 6px 0; font-weight: bold; color: #9a3412;">
          &#128232; Pilot Move Requests Queue (Insufficient Pilot Pay — Allow or Deny)
        </h5>
        @if(empty($moveRequests))
          <div class="small" style="color: #64748b;">No pilot move requests have been submitted yet.</div>
        @else
          <div class="table-responsive">
            <table class="table table-condensed table-striped small" style="background: #ffffff; margin-bottom: 0;">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Pilot</th>
                  <th>Aircraft &amp; Route Move</th>
                  <th>Cost vs. Balance</th>
                  <th>Status</th>
                  <th class="text-right">Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach($moveRequests as $mr)
                  <tr>
                    <td>#{{ $mr->id }}</td>
                    <td><strong>{{ $mr->pilot_name ?: ('User #' . $mr->user_id) }}</strong></td>
                    <td>
                      Pilot: <code>{{ $mr->pilot_from_airport ?: '?' }}</code> &rarr; <strong><code>{{ $mr->to_airport_id }}</code></strong><br>
                      Aircraft: <strong>{{ $mr->ac_registration ?: ('#' . $mr->aircraft_id) }}</strong> (<code>{{ $mr->aircraft_from_airport ?: '?' }}</code> &rarr; <code>{{ $mr->to_airport_id }}</code>)
                    </td>
                    <td>
                      Needed: <strong class="text-danger">${{ number_format((float) $mr->total_cost, 2) }}</strong><br>
                      Balance: <strong>${{ number_format((float) $mr->pilot_balance, 2) }}</strong>
                    </td>
                    <td>
                      @if($mr->status === 'approved')
                        <span class="label label-success">Allowed</span>
                      @elseif($mr->status === 'denied')
                        <span class="label label-danger">Denied</span>
                      @else
                        <span class="label label-warning">Pending</span>
                      @endif
                    </td>
                    <td class="text-right">
                      @if($mr->status === 'pending')
                        <form method="POST" action="{{ route('afterburner.admin.move_requests.resolve', $mr->id) }}" style="display: inline-flex; gap: 4px; margin: 0;">
                          @csrf
                          <input type="text" name="admin_notes" class="form-control input-sm" style="height: 24px; width: 130px; font-size: 11px;" placeholder="Optional note">
                          <button type="submit" name="decision" value="allow" class="btn btn-xs btn-success btn-fill" onclick="return confirm('Allow this move and relocate both pilot and aircraft to {{ $mr->to_airport_id }}?');">&#10003; Allow</button>
                          <button type="submit" name="decision" value="deny" class="btn btn-xs btn-danger btn-fill">&#10005; Deny</button>
                        </form>
                      @else
                        <span class="text-muted">{{ $mr->admin_notes ?: 'Resolved' }}</span>
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

    {{-- Tab 4: License & Storefront Center --}}
    <div id="rr_tab_pane_adm_license" class="rr-tab-pane">
      @if(!class_exists(\Modules\AfterburnerDispatch\Support\VaultLoader::class, false))
        <div style="border:1px solid #cbd5e1;border-radius:6px;padding:12px;margin:10px 0;background:#f8fafc;font-size:12px;">
          <h5 style="margin-top:0;font-weight:700;">&#127760; Authorized Live &amp; Testing Domains (URL Transfer / Testing Slot Request)</h5>
          <p class="text-muted" style="margin-bottom:8px;">Each license supports <strong>1 Live</strong> + <strong>1 Testing</strong> exact host. Approved transfers include a <strong>14-day grace period</strong> and automatically retire the old host once the new host is active for <strong>48 hours</strong>.</p>
          @php $dSlots = $drmEntitlements['domain_slots']['slots'] ?? []; $dReqs = $drmEntitlements['domain_slots']['requests'] ?? []; @endphp
          @if(!empty($dSlots))
            <div style="margin-bottom:8px;">
              @foreach($dSlots as $sl)
                <span class="label {{ ($sl['slot'] ?? '') === 'live' ? 'label-primary' : 'label-info' }}">{{ strtoupper($sl['slot'] ?? '') }}</span>
                <code>{{ $sl['host'] ?? '' }}</code>
                <span class="label {{ ($sl['status'] ?? '') === 'active' ? 'label-success' : 'label-warning' }}">{{ strtoupper($sl['status'] ?? '') }}</span>
                &nbsp;
              @endforeach
            </div>
          @endif
          <form method="POST" action="{{ route('afterburner.admin.license.request_domain_change') }}" class="row">
            @csrf
            <div class="col-md-2">
              <select name="request_type" class="form-control input-sm">
                <option value="transfer">Transfer Live URL</option>
                <option value="add_testing">Add / Transfer Testing URL</option>
              </select>
            </div>
            <div class="col-md-2">
              <select name="slot" class="form-control input-sm">
                <option value="live">Live Slot</option>
                <option value="testing">Testing Slot</option>
              </select>
            </div>
            <div class="col-md-3"><input type="text" name="requested_host" class="form-control input-sm" placeholder="new-crew.yourva.com" required></div>
            <div class="col-md-3"><input type="text" name="reason" class="form-control input-sm" placeholder="Reason (e.g. migration / staging)"></div>
            <div class="col-md-2"><button type="submit" class="btn btn-sm btn-warning btn-fill">Request Approval</button></div>
          </form>
        </div>
      @endif

    <div id="rr_license_storefront_box" class="content" style="margin: 0 15px 15px 15px; background: #f8fafc; padding: 16px; border: 1px solid #cbd5e1; border-radius: 6px;">
      <h5 style="margin-top: 0; font-weight: bold; color: #0f172a;">
        &#128273; Afterburner Dispatch License &amp; Storefront Center (v4.1.3)
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
            <form method="POST" action="{{ route('afterburner.admin.license.save') }}" style="margin-bottom: 10px;">
              @csrf
              <div class="form-group" style="margin-bottom: 6px;">
                <label class="small fw-bold">Paste / Save License Key</label>
                <input type="text" name="license_key" class="form-control input-sm" value="{{ $licenseKey }}" placeholder="RR-BSC-... / RR-EXEC-... / RR-FLG-...">
              </div>
              <button type="submit" class="btn btn-xs btn-info btn-fill">Save &amp; Verify Key</button>
            </form>
            @if($activeTier === 'basic')
              <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px;">
                <form method="POST" action="{{ route('afterburner.admin.license.claim_free') }}" style="margin: 0;" onsubmit="return confirm('Claim your Free Tier 1 (Basic Dispatch Edition) License Key instantly?');">
                  @csrf
                  <input type="hidden" name="va_name" value="{{ config('app.name', 'Virtual Airline') }}">
                  <input type="hidden" name="admin_email" value="{{ optional(auth()->user())->email ?? '' }}">
                  <button type="submit" class="btn btn-xs btn-success btn-fill">
                    &#10003; 1-Click Free Tier 1 Key
                  </button>
                </form>
                <form method="POST" action="{{ route('afterburner.admin.license.claim_trial') }}" style="margin: 0;" onsubmit="return confirm('Start your 7-Day Free Executive Edition Trial (1 per domain)? Automatically returns to Basic after 7 days unless upgraded.');">
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
              <a href="https://crew.flyfantasyair.net/afterburner-portal" target="_blank" style="font-weight: bold; color: #0284c7;">
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
                    <form method="POST" action="{{ route('afterburner.admin.license.checkout') }}">
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
                  <form method="POST" action="{{ route('afterburner.admin.license.checkout') }}">
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

      {{-- Read-Only Baked-In Feature Drop Timetable (v4.1.3 — Immutable until Module Update) --}}
      @php
        $rrServerTs = !empty($ent['signed_at']) ? (int) $ent['signed_at'] : time();
        $rrDropRows = \Modules\AfterburnerDispatch\Support\DropSchedule::getTimetable($activeTier, $rrServerTs);
      @endphp
      <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid #cbd5e1;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px; margin-bottom: 6px;">
          <h6 style="margin: 0; font-weight: bold; color: #0f172a;">
            &#128197; Baked-In Feature Drop Timetable (Auto-Unlocks Automatically on UTC Schedule)
          </h6>
          <span class="label label-default">Schedule: {{ \Modules\AfterburnerDispatch\Support\DropSchedule::SCHEDULE_VERSION }} (Read-Only)</span>
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
                    @if($isStandaloneBuild || $dr['unlocked'])
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
    @if(!empty($drmEntitlements['features']['dispatch_analytics_panel']) && !empty($dispatchAnalytics))
      @php
        $a30 = $dispatchAnalytics['30d'] ?? [];
        $a90 = $dispatchAnalytics['90d'] ?? [];
      @endphp
      <div id="rr_dispatch_analytics_strip" style="margin: 10px 15px 6px 15px; padding: 10px 14px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px; margin-bottom: 6px;">
          <strong style="color: #0f172a;">&#128202; Dispatcher Analytics &amp; Route Demand Snapshot</strong>
          <div style="display: flex; gap: 4px;">
            <button type="button" class="btn btn-xs btn-primary btn-fill" id="rr_an_btn_30" onclick="rrSwitchAnalyticsWindow('30d')">Last 30 Days</button>
            <button type="button" class="btn btn-xs btn-default" id="rr_an_btn_90" onclick="rrSwitchAnalyticsWindow('90d')">Last 90 Days</button>
          </div>
        </div>
        @foreach(['30d' => $a30, '90d' => $a90] as $winKey => $winData)
          <div id="rr_an_win_{{ $winKey }}" style="{{ $winKey === '30d' ? '' : 'display: none;' }}">
            <div style="display: flex; flex-wrap: wrap; gap: 14px; align-items: center;">
              <span><strong>Requests:</strong> {{ (int) ($winData['total'] ?? 0) }} ({{ (int) ($winData['pending'] ?? 0) }} open)</span>
              <span><strong>Completion Rate:</strong> <span class="label label-success">{{ (int) ($winData['completion_rate'] ?? 0) }}%</span> ({{ (int) ($winData['completed'] ?? 0) }} done)</span>
              <span><strong>Avg Turnaround:</strong> {{ $winData['avg_turnaround_hrs'] !== null ? ($winData['avg_turnaround_hrs'] . ' hrs') : '—' }}</span>
              <span>
                <strong>Top Airports:</strong>
                @if(!empty($winData['top_airports']))
                  @foreach($winData['top_airports'] as $ap)
                    <span class="label label-info" style="margin-right: 2px;">{{ $ap['icao'] }} ({{ $ap['count'] }})</span>
                  @endforeach
                @else
                  <span class="text-muted">None</span>
                @endif
              </span>
              <span>
                <strong>Top Type Ratings:</strong>
                @if(!empty($winData['top_typeratings']))
                  @foreach($winData['top_typeratings'] as $tr)
                    <span class="label label-default" style="margin-right: 2px;">{{ $tr['label'] }} ({{ $tr['count'] }})</span>
                  @endforeach
                @else
                  <span class="text-muted">None</span>
                @endif
              </span>
            </div>
          </div>
        @endforeach
      </div>
    @endif

    @if(!empty($drmEntitlements['version_blocked']))
      <div style="margin: 10px 15px; padding: 12px 16px; background: #fef2f2; border: 2px solid #dc2626; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
          <strong style="color: #991b1b; font-size: 14px;">&#9940; Upgrade Required — AfterburnerDispatch v{{ $drmEntitlements['min_rr_version'] ?? '' }}+ Needed</strong>
          <div class="small" style="color: #7f1d1d;">
            {{ $drmEntitlements['error'] ?? 'Your license key requires a newer version of Afterburner Dispatch before gated features can unlock.' }}
            @if(!empty($drmEntitlements['force_auto_update']))
              (Automatic background update is also scheduled via phpVMS <code>CronHourly</code> with zero ACARS impact.)
            @endif
          </div>
        </div>
        <form method="POST" action="{{ route('afterburner.admin.install_update') }}" style="margin: 0;">
          @csrf
          <button type="submit" class="btn btn-sm btn-danger btn-fill">&#9889; Update AfterburnerDispatch Now</button>
        </form>
      </div>
    @endif
    {{-- PaxStudio Compatibility Floor & 1-Click Minor/Major Auto-Updater Banner (v4.0.0) --}}
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
              <form method="POST" action="{{ route('afterburner.admin.install_paxstudio_update') }}" style="margin: 0;" onsubmit="return confirm('Automatically download and install PaxStudio v{{ $paxUpd['latest_version'] ?? '' }} over /modules/PaxStudio now?');">
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
              &#128640; Update Available: Afterburner Dispatch v{{ $updateInfo['latest_version'] }}
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
            <form method="POST" action="{{ route('afterburner.admin.install_update') }}" style="display: inline-block; margin: 0;" onsubmit="return confirm('Download and install RouteRequest v{{ $updateInfo['latest_version'] }} directly over /modules/AfterburnerDispatch now?');">
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

    {{-- v4.0.0: Admin Queue Filters, Live Search & Bulk Cleanup --}}
    <div style="margin: 10px 15px 6px 15px; padding: 10px 12px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
      <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 6px;">
        <strong class="small" style="color: #334155;">Filter Queue:</strong>
        <button type="button" class="btn btn-xs btn-primary btn-fill rr-q-filter" onclick="rrFilterAdminQueue('all', this)">All</button>
        <button type="button" class="btn btn-xs btn-default rr-q-filter" onclick="rrFilterAdminQueue('pending', this)">Pending</button>
        <button type="button" class="btn btn-xs btn-default rr-q-filter" onclick="rrFilterAdminQueue('completed', this)">Completed</button>
        <button type="button" class="btn btn-xs btn-default rr-q-filter" onclick="rrFilterAdminQueue('round-trip', this)">Round-Trip</button>
        <button type="button" class="btn btn-xs btn-default" id="rr_sort_upvotes_btn" onclick="rrToggleSortByUpvotes(this)" title="Sort open requests by highest pilot community upvotes">&#128293; Top Upvoted</button>
        <input type="text" id="rr_admin_queue_search" class="form-control input-sm" style="height: 24px; width: 200px; font-size: 11px;" placeholder="Search pilot, ICAO, operator..." oninput="rrFilterAdminQueue(window.rrCurrQFilter || 'all')">
      </div>
      <form method="POST" action="{{ route('afterburner.admin.bulk_cleanup') }}" style="margin: 0; display: inline-flex; align-items: center; gap: 4px;" onsubmit="return confirm('Delete resolved (completed/cancelled/rejected) route requests older than 30 days?');">
        @csrf
        <input type="hidden" name="older_than_days" value="30">
        <button type="submit" class="btn btn-xs btn-danger">&#128465; Clean Resolved &gt; 30d</button>
      </form>
    </div>

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
              @php $reqUpvotes = (int) ($upvoteCounts[$req->id] ?? 0); @endphp
              <tr class="rr-req-row" data-upvotes="{{ $reqUpvotes }}">
                <td data-label="Request ID">
                  <strong>#{{ $req->id }}</strong>
                  @if($reqUpvotes > 0)
                    <br><span class="rr-upvote-pill" title="Community Pilot Upvotes">&#9650; {{ $reqUpvotes }}</span>
                  @endif
                </td>
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
                      <form method="POST" action="{{ route('afterburner.admin.quick_generate', $req->id) }}" style="margin-top: 5px; background: #f8fafc; padding: 4px 6px; border: 1px solid #cbd5e1; border-radius: 4px;">
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
                  <form method="POST" action="{{ route('afterburner.admin.status', $req->id) }}" class="form-inline rr-status-form">
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
                    @if(!empty($drmEntitlements['features']['dispatch_canned_notes']))
                      <div class="form-group mb-1">
                        <select class="form-control input-sm no-select2" style="max-width: 210px; font-size: 11px;" onchange="rrApplyCannedNote({{ $req->id }}, this)">
                          <option value="">&#9889; Canned Response Preset...</option>
                          <option value="completed|Added to Schedule — Cleared for SimBrief &amp; Bidding!">✓ Added — Cleared for SimBrief</option>
                          <option value="completed|Added Round-Trip Pair to Active Schedule!">✓ Added Round-Trip Pair</option>
                          <option value="approved|Dispatch Accepted — Building Route Schedule Now.">⏳ Accepted — Building Route</option>
                          <option value="rejected|Unable to Fulfill — Runway Too Short for Selected Type.">✕ Runway Too Short</option>
                          <option value="rejected|Unable to Fulfill — Exceeds Subfleet Range Limits.">✕ Exceeds Subfleet Range</option>
                          <option value="rejected|Duplicate — Route Already Exists in Active Schedule.">✕ Duplicate of Existing Route</option>
                        </select>
                      </div>
                    @endif
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
                  <form method="POST" action="{{ route('afterburner.admin.destroy', $req->id) }}" onsubmit="return confirm('Delete this route request?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                  </form>
                </td>
              </tr>

              <!-- Built-In Automated Flight Schedule Generator Drawer (v4.1.3 Afterburner 3-Tier DRM Edition) for Request #{{ $req->id }} -->
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

                  <form method="POST" action="{{ route('afterburner.admin.generate_flight', $req->id) }}" id="gen_flight_form_{{ $req->id }}">
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

                      <!-- 12. Duplicate Bypass + Afterburner Instant AJAX & Mark Completed Buttons (v4.0.0) -->
                      <div class="col-md-4 text-right" style="padding-top: 4px;">
                        <div id="dup_warning_box_{{ $req->id }}" class="alert alert-warning text-left" style="display: none; padding: 6px 8px; margin-bottom: 6px; font-size: 11px; border-left: 4px solid #d97706;">
                          <div id="dup_warning_text_{{ $req->id }}" style="margin-bottom: 4px; font-weight: bold; color: #92400e;"></div>
                        </div>
                        @if(!empty($drmEntitlements['features']['subfleet_dispatch_guard']))
                          <div class="text-left" style="margin-bottom: 6px;">
                            <button type="button" class="btn btn-xs btn-default" style="border-color: #0284c7; color: #0369a1;" onclick="rrRunDispatchGuard({{ $req->id }})">
                              &#128737; Check Subfleet Range &amp; Runway
                            </button>
                            <div id="rr_dg_box_{{ $req->id }}" style="display: none; margin-top: 5px; padding: 6px 8px; border-radius: 4px; font-size: 11px; line-height: 1.35;"></div>
                          </div>
                        @endif
                        @if(!empty($drmEntitlements['features']['auto_expiring_charters']))
                          <div class="text-left" style="margin-bottom: 6px;">
                            <label class="small" style="font-weight: 600; color: #334155; margin-bottom: 2px; display: block;">
                              &#9203; Auto-Expire Charter (Optional)
                            </label>
                            <select name="expire_days" class="form-control input-sm" style="height: 26px; font-size: 11px;">
                              <option value="0" selected>Never (Permanent Schedule)</option>
                              <option value="7">Expire After 7 Days</option>
                              <option value="14">Expire After 14 Days</option>
                              <option value="30">Expire After 30 Days</option>
                              <option value="60">Expire After 60 Days</option>
                              <option value="90">Expire After 90 Days</option>
                            </select>
                          </div>
                        @endif
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
      Afterburner Dispatch Suite v4.1.3 ({{ $tierLabel }}) &bull; Made by Alexander Leard of Fantasy Air VA &amp; Regal Artistic Avenue
    </div>
  </div>
</div>
@endsection

@section('scripts')
@parent
<script>
  var RR_MODULE_VERSION      = '4.1.3';
  var RR_AIRPORTS_HASH       = '{{ $cacheHash ?? "v4.0.0_default" }}';
  var RR_ADMIN_BUNDLE_URL    = '{{ route("afterburner.admin.cache.bundle") }}';
  var RR_ADMIN_SEARCH_URL    = '{{ route("afterburner.admin.airports.search") }}';
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
      url: '{{ route("afterburner.admin.subfleet_sync.presets.save") }}',
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
      url: '{{ url("/admin/afterburner/subfleet-sync/presets") }}/' + id,
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
      url: '{{ route("afterburner.admin.subfleet_sync.undo") }}',
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
      url: '{{ route("afterburner.admin.time_fix.preview") }}',
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
      url: '{{ route("afterburner.admin.time_fix.execute") }}',
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
      url: '{{ route("afterburner.admin.subfleet_sync.preview") }}',
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
      url: '{{ route("afterburner.admin.subfleet_sync.execute") }}',
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
      url: '{{ url("/admin/afterburner") }}/' + reqId + '/mark-completed',
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
      url: '{{ url("/admin/afterburner") }}/' + reqId + '/parse-fa',
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

<script>
  window.rrCurrQFilter = 'all';
  window.rrFilterAdminQueue = function (mode, btnEl) {
    window.rrCurrQFilter = mode || 'all';
    if (btnEl) {
      document.querySelectorAll('.rr-q-filter').forEach(function (b) {
        b.classList.remove('btn-primary', 'btn-fill');
        b.classList.add('btn-default');
      });
      btnEl.classList.remove('btn-default');
      btnEl.classList.add('btn-primary', 'btn-fill');
    }
    var q = (document.getElementById('rr_admin_queue_search')?.value || '').trim().toLowerCase();
    var pane = document.getElementById('rr_tab_pane_adm_requests');
    if (!pane) return;
    pane.querySelectorAll('table tbody tr').forEach(function (tr) {
      if (tr.id && tr.id.indexOf('gen_row_') === 0) return;
      var txt = (tr.textContent || '').toLowerCase();
      var matchMode = (mode === 'all') || (txt.indexOf(mode) !== -1);
      var matchQ = (q === '') || (txt.indexOf(q) !== -1);
      tr.style.display = (matchMode && matchQ) ? '' : 'none';
    });
  };
</script>

<script>
  window.rrRunDispatchGuard = function (reqId) {
    var box = document.getElementById('rr_dg_box_' + reqId);
    var depEl = document.getElementById('admin_dep_airport_' + reqId);
    var arrEl = document.getElementById('admin_arr_airport_' + reqId);
    var form = document.getElementById('gen_flight_form_' + reqId);
    if (!box || !form) return;

    var sfSelect = form.querySelector('select[name="subfleet_ids[]"]');
    var sfIds = sfSelect ? Array.from(sfSelect.selectedOptions).map(function (o) { return o.value; }) : [];

    box.style.display = 'block';
    box.style.background = '#f8fafc';
    box.style.border = '1px solid #cbd5e1';
    box.style.color = '#334155';
    box.innerHTML = 'Checking subfleet range &amp; runway suitability...';

    fetch('{{ route("afterburner.admin.dispatch_guard.check") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        dep_airport_id: depEl ? depEl.value : '',
        arr_airport_id: arrEl ? arrEl.value : '',
        subfleet_ids: sfIds
      })
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
      if (!res || !res.success) {
        box.style.background = '#fef2f2';
        box.style.border = '1px solid #dc2626';
        box.style.color = '#991b1b';
        box.innerHTML = (res && res.message) ? res.message : 'Unable to run dispatch guard check.';
        return;
      }
      if (res.severity === 'danger') {
        box.style.background = '#fef2f2';
        box.style.border = '1px solid #dc2626';
        box.style.color = '#7f1d1d';
      } else if (res.severity === 'warn') {
        box.style.background = '#fffbeb';
        box.style.border = '1px solid #d97706';
        box.style.color = '#78350f';
      } else {
        box.style.background = '#f0fdf4';
        box.style.border = '1px solid #16a34a';
        box.style.color = '#14532d';
      }
      var lines = ['<strong>&#128737; ' + res.dep + ' &rarr; ' + res.arr + ' (' + res.distance_nm + ' NM)</strong>'];
      if (!res.items || !res.items.length) {
        lines.push('Select at least one subfleet to evaluate range &amp; runway.');
      } else {
        res.items.forEach(function (it) {
          var badge = it.range_status === 'danger' ? '&#10060; EXCEEDS RANGE' : (it.range_status === 'warn' ? '&#9888; Marginal (>85%)' : '&#10003; Range OK');
          lines.push('&bull; <strong>' + it.subfleet + '</strong>: ' + badge + ' (Max ' + it.max_range_nm + ' NM) &mdash; ' + it.rwy_note);
        });
      }
      box.innerHTML = lines.join('<br>');
    })
    .catch(function () {
      box.innerHTML = 'Dispatch guard check failed.';
    });
  };
</script>

<script>
  window.rrPreviewBatchDigest = function () {
    var box = document.getElementById('rr_digest_preview_box');
    if (!box) return;
    box.style.display = 'block';
    box.textContent = 'Compiling live Batch Route Digest preview...';
    fetch('{{ route("afterburner.admin.digest.preview") }}', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
      body: JSON.stringify({ hours: 24 })
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
      box.textContent = (res && res.success && res.summary) ? res.summary.plain_text : ((res && res.message) ? res.message : 'Unable to preview digest.');
    })
    .catch(function () { box.textContent = 'Failed to load digest preview.'; });
  };

  window.rrToggleSortByUpvotes = function (btn) {
    var tbody = document.querySelector('table.rr-admin-table > tbody');
    if (!tbody) return;
    var active = btn.classList.toggle('btn-warning');
    btn.classList.toggle('btn-default', !active);
    var pairs = [];
    var rows = Array.from(tbody.children);
    for (var i = 0; i < rows.length; i++) {
      if (rows[i].classList.contains('rr-req-row')) {
        var next = rows[i + 1] && !rows[i + 1].classList.contains('rr-req-row') ? rows[i + 1] : null;
        pairs.push({
          main: rows[i],
          drawer: next,
          upvotes: parseInt(rows[i].getAttribute('data-upvotes') || '0', 10),
          idx: i
        });
      }
    }
    pairs.sort(function (a, b) {
      return active ? (b.upvotes - a.upvotes || a.idx - b.idx) : (a.idx - b.idx);
    });
    pairs.forEach(function (p) {
      tbody.appendChild(p.main);
      if (p.drawer) tbody.appendChild(p.drawer);
    });
  };
</script>

<script>
  window.rrApplyCannedNote = function (reqId, sel) {
    var val = sel.value || '';
    if (!val || val.indexOf('|') === -1) return;
    var parts = val.split('|');
    var targetStatus = parts[0];
    var noteText = parts.slice(1).join('|');
    var noteInput = document.getElementById('row_admin_notes_' + reqId);
    if (noteInput) noteInput.value = noteText;
    var form = sel.closest('form');
    if (form) {
      var stSel = form.querySelector('select[name="status"]');
      if (stSel) {
        var opt = stSel.querySelector('option[value="' + targetStatus + '"]');
        if (opt && !opt.disabled) stSel.value = targetStatus;
      }
    }
    sel.selectedIndex = 0;
  };
</script>

<script>
  (function () {
    var boot = @json($subfleetCreatorData ?? null);
    if (!boot || !document.getElementById('rr_sfc_rows_tbody')) return;

    var canBulk = {{ !empty($drmEntitlements['features']['subfleet_creator_bulk']) ? 'true' : 'false' }};
    var rows = [];
    var selectedIndices = [];
    var stepPos = 0;

    function renderSyntax(tpl, tok, isCode) {
      var out = String(tpl || '').replace(/\{([A-Z0-9_]+)(?::(upper|lower))?\}/gi, function (_, k, mod) {
        var v = String(tok[k.toUpperCase()] || '').trim();
        if (!v) return '';
        if (mod && mod.toLowerCase() === 'upper') return v.toUpperCase();
        if (mod && mod.toLowerCase() === 'lower') return v.toLowerCase();
        return v;
      });
      out = out.replace(/\(\s*\)|\[\s*\]/g, '');
      if (isCode) {
        out = out.replace(/\.{2,}/g, '.').replace(/-{2,}/g, '-').replace(/\s+/g, '').replace(/^[.\-_]+|[.\-_]+$/g, '');
      } else {
        out = out.replace(/\s{2,}/g, ' ').replace(/^[.\-\s]+|[.\-\s]+$/g, '');
      }
      return out;
    }

    function autoMatchTypeRating(icao) {
      var up = String(icao || '').toUpperCase().trim();
      if (!up) return 0;
      for (var i = 0; i < (boot.typeratings || []).length; i++) {
        var tr = boot.typeratings[i];
        if (String(tr.type || '').toUpperCase() === up || String(tr.name || '').toUpperCase().indexOf(up) !== -1) {
          return tr.id;
        }
      }
      return 0;
    }

    function calcPax(cabins) {
      var sum = 0;
      (cabins || []).forEach(function (c) {
        var n = parseInt(c.seats || 0, 10);
        if (n > 0) sum += n;
      });
      return sum;
    }

    function getTokens(r) {
      var vaSel = document.getElementById('rr_sfc_va_airline');
      var vaOpt = vaSel && vaSel.options[vaSel.selectedIndex];
      return {
        OP_ICAO: (document.getElementById('rr_sfc_op_icao').value || '').trim().toUpperCase(),
        OP_IATA: (document.getElementById('rr_sfc_op_iata').value || '').trim().toUpperCase(),
        OP_NAME: (document.getElementById('rr_sfc_op_name').value || '').trim(),
        AIRLINE_ICAO: vaOpt ? (vaOpt.getAttribute('data-icao') || '') : '',
        AIRLINE_NAME: vaOpt ? (vaOpt.getAttribute('data-name') || '') : '',
        ICAO: (r.icao || '').trim().toUpperCase(),
        IATA: (r.iata || '').trim().toUpperCase(),
        AIRCRAFT: (r.aircraft || '').trim(),
        VARIANT: (r.variant || '').trim().toUpperCase(),
        CONFIG: (r.config || '').trim(),
        PAX: r.pax > 0 ? String(r.pax) : ''
      };
    }

    function previewRow(r) {
      var tok = getTokens(r);
      var tTpl = document.getElementById('rr_sfc_type_syntax').value;
      var nTpl = document.getElementById('rr_sfc_name_syntax').value;
      return {
        type: renderSyntax(tTpl, tok, true),
        name: renderSyntax(nTpl, tok, false)
      };
    }

    window.rrSfcSwitchMode = function (mode) {
      var cw = document.getElementById('rr_sfc_create_wrap');
      var sw = document.getElementById('rr_sfc_std_wrap');
      var cb = document.getElementById('rr_sfc_mode_create_btn');
      var sb = document.getElementById('rr_sfc_mode_std_btn');
      if (cw) cw.style.display = (mode === 'create') ? '' : 'none';
      if (sw) sw.style.display = (mode === 'standardize') ? '' : 'none';
      if (cb) { cb.className = 'btn btn-xs ' + (mode === 'create' ? 'btn-primary btn-fill' : 'btn-default'); }
      if (sb) { sb.className = 'btn btn-xs ' + (mode === 'standardize' ? 'btn-warning btn-fill' : 'btn-default'); }
    };

    var dirMap = {};
    (boot.operator_directory || []).forEach(function (d) { dirMap[d.key] = d; });

    function escHtml(s) {
      return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function rrSfcStatusTag(d) {
      var vVer = String(boot.vault_version || '');
      var rev = parseInt(d.seatmap_rev || 0, 10);
      var isCur = vVer !== '' && String(d.updated_in || '') === vVer;
      var cType = String(d.change_type || '').toLowerCase();
      if (isCur && cType === 'new') return 'NEW' + (rev > 0 ? (' • Rev ' + rev) : '');
      if (isCur && cType === 'updated') return 'UPDATED' + (rev > 0 ? (' • Rev ' + rev) : '');
      if (rev > 0) return 'Rev ' + rev;
      return '';
    }

    window.rrSfcJumpToOperator = function (icaoKey) {
      var filt = document.getElementById('rr_sfc_op_filter');
      if (filt) filt.value = '';
      rrSfcPopulateOperators('');
      var sel = document.getElementById('rr_sfc_operator');
      if (sel && sel.querySelector('option[value="' + String(icaoKey).replace(/"/g, '\\"') + '"]')) {
        sel.value = icaoKey;
        rrSfcLoadOperator();
      }
    };

    window.rrSfcRenderUpdatesBanner = function () {
      var box = document.getElementById('rr_sfc_updates_banner');
      if (!box) return;
      var vVer = String(boot.vault_version || '');
      var ops = boot.operators || {};
      var items = [];
      Object.keys(ops).forEach(function (k) {
        var o = ops[k];
        if (o && vVer !== '' && String(o.updated_in || '') === vVer && (o.change_type === 'new' || o.change_type === 'updated')) {
          items.push(o);
        }
      });
      if (!items.length) { box.style.display = 'none'; return; }
      items.sort(function (a, b) {
        if (a.change_type !== b.change_type) return a.change_type === 'new' ? -1 : 1;
        return String(a.icao || '').localeCompare(String(b.icao || ''));
      });
      var h = '<div style="font-weight:700;color:#0369a1;margin-bottom:4px;">&#9733; FLEET VAULT v' + escHtml(vVer) + ' — NEW &amp; RECENTLY UPDATED SEAT MAPS <span class="text-muted" style="font-weight:normal;">(click an airline to load)</span></div>';
      h += '<div style="display:flex;flex-direction:column;gap:3px;">';
      items.forEach(function (o) {
        var isNew = o.change_type === 'new';
        var lblCls = isNew ? 'label-success' : 'label-info';
        var lblTxt = isNew ? 'NEW' : 'UPDATED';
        var revTxt = o.seatmap_rev ? ('Rev ' + o.seatmap_rev) : '';
        h += '<div>'
          + '<a href="javascript:void(0)" onclick="rrSfcJumpToOperator(\'' + escHtml(o.icao) + '\')" style="text-decoration:none;color:#0f172a;">'
          + '<span class="label ' + lblCls + '" style="font-size:9px;margin-right:5px;">' + lblTxt + '</span>'
          + '<strong>' + escHtml(o.icao) + '</strong>'
          + (revTxt ? ' <span class="text-muted">(' + escHtml(revTxt) + ')</span>' : '')
          + ' &bull; ' + escHtml(o.name)
          + (o.update_note ? (' — <span style="color:#334155;">' + escHtml(o.update_note) + '</span>') : '')
          + '</a></div>';
      });
      h += '</div>';
      box.innerHTML = h;
      box.style.display = '';
    };

    window.rrSfcPopulateOperators = function (filterText) {
      var sel = document.getElementById('rr_sfc_operator');
      if (!sel) return;
      var prevVal = sel.value || 'DAL';
      var q = String(filterText || '').trim().toLowerCase();
      var list = (boot.operator_directory || []).filter(function (d) {
        if (!q) return true;
        var st = rrSfcStatusTag(d).toLowerCase();
        return (d.label || '').toLowerCase().indexOf(q) !== -1
          || (d.icao || '').toLowerCase().indexOf(q) !== -1
          || (d.iata || '').toLowerCase().indexOf(q) !== -1
          || (d.category || '').toLowerCase().indexOf(q) !== -1
          || st.indexOf(q) !== -1;
      });
      var groups = {};
      var groupOrder = [];
      list.forEach(function (d) {
        var cat = d.category || 'Operators';
        if (!groups[cat]) { groups[cat] = []; groupOrder.push(cat); }
        groups[cat].push(d);
      });
      var html = '';
      groupOrder.forEach(function (cat) {
        html += '<optgroup label="' + cat.replace(/"/g, '&quot;') + '">';
        groups[cat].forEach(function (d) {
          var badge = d.has_preset ? '★ ' : '';
          var st = rrSfcStatusTag(d);
          var stTxt = st ? ('[' + st + '] ') : '';
          var iataTag = d.iata ? (' [' + d.iata + ']') : '';
          html += '<option value="' + d.key.replace(/"/g, '&quot;') + '">' + badge + stTxt + d.icao + iataTag + ' — ' + d.name.replace(/</g, '&lt;') + '</option>';
        });
        html += '</optgroup>';
      });
      html += '<optgroup label="Custom"><option value="CUSTOM">CUSTOM — Enter Custom Operator...</option></optgroup>';
      sel.innerHTML = html;

      var cntEl = document.getElementById('rr_sfc_op_count');
      if (cntEl) cntEl.textContent = '(' + list.length + ' matched)';

      if (sel.querySelector('option[value="' + prevVal.replace(/"/g, '\"') + '"]')) {
        sel.value = prevVal;
      } else if (list.length > 0) {
        sel.value = list[0].key;
      } else {
        sel.value = 'CUSTOM';
      }
      if (q) rrSfcLoadOperator();
    };

    window.rrSfcLoadOperator = function () {
      var key = document.getElementById('rr_sfc_operator').value;
      var op = (boot.operators || {})[key];
      var dirEntry = dirMap[key] || null;
      var hintEl = document.getElementById('rr_sfc_op_hint');

      function mapFleetRows(fleetArr) {
        return (fleetArr || []).map(function (f) {
          var cabs = (f.cabins || []).map(function (c) {
            return { cabin: c.cabin, fare_code: c.code, seats: c.seats };
          });
          cabs.forEach(function (c) {
            var mf = findFareByCode(c.fare_code, c.cabin);
            if (mf && mf.code) c.fare_code = mf.code;
          });
          return {
            selected: false,
            aircraft: f.aircraft,
            icao: f.icao,
            iata: f.iata,
            variant: f.variant || '',
            config: f.config || '',
            tag: f.tag || '',
            pax: calcPax(cabs),
            simbrief_type: f.icao,
            typerating_id: autoMatchTypeRating(f.icao),
            cabins: cabs
          };
        });
      }

      if (op) {
        document.getElementById('rr_sfc_op_icao').value = op.icao;
        document.getElementById('rr_sfc_op_iata').value = op.iata || '';
        document.getElementById('rr_sfc_op_name').value = op.name;
        rows = mapFleetRows(op.fleet);
        if (hintEl) {
          var revS = op.seatmap_rev ? (' • Rev ' + op.seatmap_rev) : '';
          var vTag = op.verified_at ? (' • ✓ ' + op.verified_at) : '';
          hintEl.textContent = '★ Verified Fleet Preset (' + rows.length + ' configs)' + revS + vTag;
        }
        var revBox = document.getElementById('rr_sfc_rev_strip');
        if (revBox) {
          if (op.seatmap_rev || op.verified_at || op.update_note) {
            var vVer = String(boot.vault_version || '');
            var isCur = vVer !== '' && String(op.updated_in || '') === vVer;
            var badgeHtml = '';
            if (isCur && op.change_type === 'new') badgeHtml = ' <span class="label label-success" style="font-size:9px;">NEW in v' + escHtml(vVer) + '</span>';
            else if (isCur && op.change_type === 'updated') badgeHtml = ' <span class="label label-info" style="font-size:9px;">UPDATED in v' + escHtml(vVer) + '</span>';
            var srcs = Array.isArray(op.sources) && op.sources.length ? (' &bull; Sources: ' + escHtml(op.sources.join(', '))) : '';
            var rHtml = '<div><strong>&#9733; ' + escHtml(op.name) + ' (' + escHtml(op.icao) + ')</strong>'
              + (op.seatmap_rev ? (' &mdash; Seat Map <strong>Rev ' + escHtml(op.seatmap_rev) + '</strong>') : '')
              + badgeHtml + '</div>';
            if (op.verified_at || srcs) {
              rHtml += '<div class="text-muted">' + (op.verified_at ? ('&#10003; ' + escHtml(op.verified_at)) : '') + srcs + '</div>';
            }
            if (op.update_note) {
              rHtml += '<div style="color:#0f172a;margin-top:2px;"><strong>Note:</strong> ' + escHtml(op.update_note) + '</div>';
            }
            revBox.innerHTML = rHtml;
            revBox.style.display = '';
          } else {
            revBox.style.display = 'none';
          }
        }
      } else if (dirEntry) {
        var revBox2 = document.getElementById('rr_sfc_rev_strip');
        if (revBox2) revBox2.style.display = 'none';
        document.getElementById('rr_sfc_op_icao').value = dirEntry.icao || '';
        document.getElementById('rr_sfc_op_iata').value = dirEntry.iata || '';
        document.getElementById('rr_sfc_op_name').value = dirEntry.name || '';
        var stType = dirEntry.starter_type || 'commercial';
        var starter = (boot.starter_fleets || {})[stType] || (boot.starter_fleets || {}).commercial || [];
        rows = mapFleetRows(starter);
        if (hintEl) {
          hintEl.textContent = boot.vault_locked
            ? '🔒 Encrypted Fleet Vault locked (connect Cloud DRM fleet_key to unlock verified presets) — starter fleet loaded'
            : 'Synced Operator (' + stType.toUpperCase() + ' starter fleet loaded — edit or add rows below)';
        }
      } else {
        document.getElementById('rr_sfc_op_icao').value = '';
        document.getElementById('rr_sfc_op_iata').value = '';
        document.getElementById('rr_sfc_op_name').value = '';
        rows = [{
          selected: true, aircraft: 'A320-200', icao: 'A320', iata: '320', variant: '', config: '', pax: 150, simbrief_type: 'A320',
          typerating_id: autoMatchTypeRating('A320'),
          cabins: [{ cabin: 'First', fare_code: 'F', seats: 12 }, { cabin: 'Economy', fare_code: 'Y', seats: 138 }]
        }];
        if (hintEl) hintEl.textContent = 'Custom operator mode';
        var revBox3 = document.getElementById('rr_sfc_rev_strip');
        if (revBox3) revBox3.style.display = 'none';
      }
      rrSfcRenderTable();
    };

    window.rrSfcAddCustomRow = function () {
      rows.push({
        selected: true, aircraft: 'Custom Aircraft', icao: 'A320', iata: '320', variant: '', config: '', pax: 150, simbrief_type: 'A320',
        typerating_id: autoMatchTypeRating('A320'),
        cabins: [{ cabin: 'Economy', fare_code: 'Y', seats: 150 }]
      });
      if (!canBulk) {
        rows.forEach(function (r, i) { r.selected = (i === rows.length - 1); });
      }
      rrSfcRenderTable();
    };

    function rrSfcUpdateSelSummary() {
      var selCnt = 0;
      rows.forEach(function (r) { if (r.selected) selCnt++; });
      var sm = document.getElementById('rr_sfc_sel_summary');
      if (sm) sm.textContent = selCnt + ' aircraft/variant(s) selected';
      var allCb = document.getElementById('rr_sfc_sel_all');
      if (allCb && canBulk) {
        allCb.checked = (rows.length > 0 && selCnt === rows.length);
      }
    }

    function rrSfcSyncSelectionFromDom() {
      var tb = document.getElementById('rr_sfc_rows_tbody');
      if (!tb) return;
      var inputs = tb.querySelectorAll('input.rr-sfc-row-cb');
      if (!inputs.length) return;
      inputs.forEach(function (inp) {
        var idx = parseInt(inp.getAttribute('data-row-idx'), 10);
        if (!isNaN(idx) && rows[idx]) {
          rows[idx].selected = !!inp.checked;
        }
      });
      rrSfcUpdateSelSummary();
    }

    window.rrSfcToggleAll = function (checked) {
      if (!canBulk) return;
      var flag = !!checked;
      rows.forEach(function (r) { r.selected = flag; });
      var tb = document.getElementById('rr_sfc_rows_tbody');
      if (tb) {
        tb.querySelectorAll('input.rr-sfc-row-cb').forEach(function (inp) {
          inp.checked = flag;
        });
      }
      rrSfcUpdateSelSummary();
    };

    window.rrSfcToggleRow = function (idx, checked) {
      var flag = !!checked;
      if (!canBulk && flag) {
        rows.forEach(function (r, i) { r.selected = (i === idx); });
        var tb = document.getElementById('rr_sfc_rows_tbody');
        if (tb) {
          tb.querySelectorAll('input.rr-sfc-row-cb').forEach(function (inp) {
            var i = parseInt(inp.getAttribute('data-row-idx'), 10);
            inp.checked = (i === idx);
          });
        }
      } else if (rows[idx]) {
        rows[idx].selected = flag;
      }
      rrSfcUpdateSelSummary();
    };

    window.rrSfcEditField = function (idx, field, val) {
      if (!rows[idx]) return;
      if (field === 'pax') {
        rows[idx].pax = Math.max(0, parseInt(val || 0, 10));
      } else if (field === 'typerating_id') {
        rows[idx].typerating_id = parseInt(val || 0, 10);
      } else {
        rows[idx][field] = val;
        if (field === 'icao') {
          rows[idx].typerating_id = autoMatchTypeRating(val);
        }
      }
      var pv = previewRow(rows[idx]);
      var pvEl = document.getElementById('rr_sfc_pv_' + idx);
      if (pvEl) pvEl.innerHTML = formatPreviewHtml(pv);
    };

    window.rrSfcRenderTable = function () {
      var tb = document.getElementById('rr_sfc_rows_tbody');
      if (!tb) return;
      var html = '';
      var selCnt = 0;
      rows.forEach(function (r, idx) {
        if (r.selected) selCnt++;
        var pv = previewRow(r);
        var trOpts = '<option value="0">— None —</option>';
        (boot.typeratings || []).forEach(function (tr) {
          trOpts += '<option value="' + tr.id + '"' + (tr.id === r.typerating_id ? ' selected' : '') + '>' + tr.type + ' — ' + tr.name + '</option>';
        });
        html += '<tr>'
          + '<td><input type="' + (canBulk ? 'checkbox' : 'radio') + '" class="rr-sfc-row-cb" data-row-idx="' + idx + '" name="rr_sfc_row_sel" ' + (r.selected ? 'checked' : '') + ' onchange="rrSfcToggleRow(' + idx + ', this.checked)" onclick="rrSfcToggleRow(' + idx + ', this.checked)"></td>'
          + '<td><input type="text" class="form-control input-sm" style="height:24px;font-size:11px;" value="' + String(r.aircraft || '').replace(/"/g, '&quot;') + '" oninput="rrSfcEditField(' + idx + ',\'aircraft\',this.value)"></td>'
          + '<td><input type="text" class="form-control input-sm" style="height:24px;font-size:11px;" value="' + String(r.icao || '').replace(/"/g, '&quot;') + '" oninput="rrSfcEditField(' + idx + ',\'icao\',this.value)"></td>'
          + '<td><input type="text" class="form-control input-sm" style="height:24px;font-size:11px;" value="' + String(r.iata || '').replace(/"/g, '&quot;') + '" oninput="rrSfcEditField(' + idx + ',\'iata\',this.value)"></td>'
          + '<td><input type="text" class="form-control input-sm" style="height:24px;font-size:11px;" value="' + String(r.variant || '').replace(/"/g, '&quot;') + '" oninput="rrSfcEditField(' + idx + ',\'variant\',this.value)"></td>'
          + '<td><input type="text" class="form-control input-sm" style="height:24px;font-size:11px;" placeholder="WL/SL/NGX" value="' + String(r.config || '').replace(/"/g, '&quot;') + '" oninput="rrSfcEditField(' + idx + ',\'config\',this.value)"></td>'
          + '<td><input type="number" min="0" class="form-control input-sm" style="height:24px;font-size:11px;" value="' + r.pax + '" oninput="rrSfcEditField(' + idx + ',\'pax\',this.value)"></td>'
          + '<td style="text-align:center;">' + (r.tag ? ('<span class="label ' + (r.tag === 'NEW' ? 'label-success' : (r.tag === '2027' ? 'label-warning' : 'label-info')) + '" style="font-size:9px;">' + escHtml(r.tag) + '</span>') : '') + '</td>'
          + '<td><input type="text" class="form-control input-sm" style="height:24px;font-size:11px;" placeholder="ICAO or Airframe ID" value="' + String(r.simbrief_type || r.icao || '').replace(/"/g, '&quot;') + '" oninput="rrSfcEditField(' + idx + ',\'simbrief_type\',this.value)"></td>'
          + '<td><select class="form-control input-sm no-select2" style="height:24px;font-size:11px;padding:1px 4px;" onchange="rrSfcEditField(' + idx + ',\'typerating_id\',this.value)">' + trOpts + '</select></td>'
          + '<td id="rr_sfc_pv_' + idx + '">' + formatPreviewHtml(pv) + '</td>'
          + '</tr>';
      });
      tb.innerHTML = html;
      var sm = document.getElementById('rr_sfc_sel_summary');
      if (sm) sm.textContent = selCnt + ' aircraft/variant(s) selected';
    };

    function formatPreviewHtml(pv) {
      var tWarn = (pv.type || '').length > 50 ? ' <span class="label label-danger" title="Exceeds phpVMS 50-char DB limit">' + pv.type.length + '/50!</span>' : '';
      var nWarn = (pv.name || '').length > 50 ? ' <span class="label label-danger" title="Exceeds phpVMS 50-char DB limit">' + pv.name.length + '/50!</span>' : '';
      return '<code>' + pv.type + '</code>' + tWarn + ' &rarr; <strong>' + pv.name + '</strong>' + nWarn;
    }

    function findFareByCode(code, cabinName) {
      var fares = boot.fares || [];
      var up = String(code || '').trim().toUpperCase();
      if (up) {
        for (var i = 0; i < fares.length; i++) {
          if (String(fares[i].code || '').toUpperCase() === up) return fares[i];
        }
      }
      // Smart DynamicFares / custom-code cabin-role auto-matching
      var lbl = String(cabinName || '').toLowerCase();
      var role = '';
      if (up === 'CGO' || up === 'C' || lbl.indexOf('cargo') !== -1 || lbl.indexOf('freight') !== -1) role = 'cargo';
      else if (up === 'P' || up === 'O' || lbl.indexOf('premium') !== -1) role = 'prem_econ';
      else if (up === 'W' || lbl.indexOf('comfort') !== -1 || lbl.indexOf('extra') !== -1 || lbl.indexOf('plus') !== -1) role = 'econ_plus';
      else if (up === 'J' || lbl.indexOf('business') !== -1 || lbl.indexOf('polaris') !== -1 || lbl.indexOf('delta one') !== -1 || lbl.indexOf('flagship suite') !== -1 || lbl.indexOf('charter') !== -1) role = 'business';
      else if (up === 'F' || lbl.indexOf('first') !== -1) role = 'first';
      else if (up === 'Y' || lbl.indexOf('econ') !== -1 || lbl.indexOf('main') !== -1) role = 'economy';

      var roleAlts = {
        cargo:     { codes: ['CGO', 'CARGO', 'C', 'FRT'], words: ['cargo', 'freight'], ftype: 1 },
        prem_econ: { codes: ['O', 'P', 'PE', 'W'],        words: ['premium economy', 'premium select', 'premium plus', 'premium'], ftype: 0 },
        econ_plus: { codes: ['W', 'E+', 'MCE', 'Y+'],     words: ['comfort', 'extra', 'economy plus', 'main cabin extra'], ftype: 0 },
        business:  { codes: ['J', 'B', 'BUS', 'C'],       words: ['business', 'polaris', 'delta one', 'club', 'flagship'], ftype: 0 },
        first:     { codes: ['F', 'FC', 'J'],             words: ['first'], ftype: 0 },
        economy:   { codes: ['Y', 'E', 'M', 'EC'],        words: ['economy', 'main cabin', 'coach', 'standard'], ftype: 0 }
      };
      var cfg = roleAlts[role];
      if (!cfg) return null;
      for (var cIdx = 0; cIdx < cfg.codes.length; cIdx++) {
        for (var j = 0; j < fares.length; j++) {
          if (String(fares[j].code || '').toUpperCase() === cfg.codes[cIdx]) return fares[j];
        }
      }
      for (var wIdx = 0; wIdx < cfg.words.length; wIdx++) {
        for (var k = 0; k < fares.length; k++) {
          if (String(fares[k].name || '').toLowerCase().indexOf(cfg.words[wIdx]) !== -1) return fares[k];
        }
      }
      if (role === 'cargo') {
        for (var m = 0; m < fares.length; m++) {
          if (parseInt(fares[m].type || 0, 10) === 1) return fares[m];
        }
      }
      return null;
    }

    window.rrSfcProceedFromScreen1 = function () {
      rrSfcSyncSelectionFromDom();
      selectedIndices = [];
      rows.forEach(function (r, idx) { if (r.selected) selectedIndices.push(idx); });
      if (!selectedIndices.length) {
        alert('Please select at least one aircraft / variant first.');
        return;
      }
      if (!canBulk && selectedIndices.length > 1) {
        alert('Tier 1 allows creating 1 subfleet at a time.');
        return;
      }
      var mapFares = document.getElementById('rr_sfc_map_fares').checked;
      if (!mapFares) {
        rrSfcExecuteCreate();
        return;
      }
      stepPos = 0;
      document.getElementById('rr_sfc_screen1').style.display = 'none';
      document.getElementById('rr_sfc_screen2').style.display = 'block';
      rrSfcRenderStep();
    };

    window.rrSfcBackToScreen1 = function () {
      document.getElementById('rr_sfc_screen2').style.display = 'none';
      document.getElementById('rr_sfc_screen1').style.display = 'block';
      rrSfcRenderTable();
    };

    window.rrSfcStepNav = function (delta) {
      var next = stepPos + delta;
      if (next >= 0 && next < selectedIndices.length) {
        stepPos = next;
        rrSfcRenderStep();
      }
    };

    window.rrSfcRenderStep = function () {
      var r = rows[selectedIndices[stepPos]];
      if (!r) return;
      r.pax = calcPax(r.cabins);
      var pv = previewRow(r);
      document.getElementById('rr_sfc_step_badge').textContent = 'Subfleet ' + (stepPos + 1) + ' of ' + selectedIndices.length;
      document.getElementById('rr_sfc_step_title').textContent = pv.type + ' — ' + pv.name;
      document.getElementById('rr_sfc_step_preview').textContent = 'ICAO: ' + r.icao + ' | IATA: ' + r.iata + (r.variant ? (' | Variant: ' + r.variant) : '') + ' | Total Pre-Filled Seats: ' + r.pax + ' PAX';

      document.getElementById('rr_sfc_prev_btn').disabled = (stepPos === 0);
      document.getElementById('rr_sfc_next_btn').disabled = (stepPos >= selectedIndices.length - 1);

      var tb = document.getElementById('rr_sfc_cabin_tbody');
      var html = '';
      (r.cabins || []).forEach(function (c, ci) {
        var matched = findFareByCode(c.fare_code, c.cabin);
        var fareOpts = '<option value="">-- Pick Existing Fare --</option>';
        (boot.fares || []).forEach(function (f) {
          var sel = (matched && matched.id === f.id) ? ' selected' : '';
          fareOpts += '<option value="' + f.code + '"' + sel + '>' + f.code + ' — ' + f.name + ' ($' + Number(f.price || 0).toFixed(0) + ')</option>';
        });
        var statusHtml = '';
        if (!c.seats || parseInt(c.seats, 10) <= 0) {
          statusHtml = '<span class="text-muted">Skipped (0 seats)</span>';
        } else if (matched) {
          statusHtml = '<span class="label label-success">&#10003; Matched: ' + matched.code + ' (' + matched.name + ')</span>';
        } else {
          var missCode = String(c.fare_code || '').trim().toUpperCase();
          statusHtml = '<span class="label label-danger">&#9888; Not Found in phpVMS Fares</span> '
            + '<button type="button" class="btn btn-xs btn-warning" style="margin-left:4px;" onclick="rrSfcOpenNewFare(\'' + missCode.replace(/'/g, '') + '\', \'' + String(c.cabin || '').replace(/'/g, '') + '\', ' + (parseInt(c.seats, 10) || 0) + ')">+ Create \'' + (missCode || 'New') + '\'</button>';
        }
        html += '<tr>'
          + '<td><input type="text" class="form-control input-sm" style="height:24px;font-size:11px;" value="' + String(c.cabin || '').replace(/"/g, '&quot;') + '" oninput="rrSfcEditCabin(' + ci + ',\'cabin\',this.value)"></td>'
          + '<td><input type="number" min="0" class="form-control input-sm" style="height:24px;font-size:11px;" value="' + (c.seats || 0) + '" oninput="rrSfcEditCabin(' + ci + ',\'seats\',this.value)"></td>'
          + '<td><select class="form-control input-sm no-select2" style="height:24px;font-size:11px;padding:1px 4px;" onchange="rrSfcEditCabin(' + ci + ',\'fare_code\',this.value)">' + fareOpts + '</select></td>'
          + '<td><input type="text" class="form-control input-sm" style="height:24px;font-size:11px;text-transform:uppercase;" value="' + String(c.fare_code || '').replace(/"/g, '&quot;') + '" oninput="rrSfcEditCabin(' + ci + ',\'fare_code\',this.value)"></td>'
          + '<td>' + statusHtml + '</td>'
          + '<td><button type="button" class="btn btn-xs btn-danger" onclick="rrSfcRemoveCabin(' + ci + ')">&times;</button></td>'
          + '</tr>';
      });
      tb.innerHTML = html;
    };

    window.rrSfcEditCabin = function (ci, field, val) {
      var r = rows[selectedIndices[stepPos]];
      if (!r || !r.cabins[ci]) return;
      if (field === 'seats') {
        r.cabins[ci].seats = Math.max(0, parseInt(val || 0, 10));
      } else if (field === 'fare_code') {
        r.cabins[ci].fare_code = String(val || '').trim().toUpperCase();
      } else {
        r.cabins[ci][field] = val;
      }
      rrSfcRenderStep();
    };

    window.rrSfcAddCabinRow = function () {
      var r = rows[selectedIndices[stepPos]];
      if (!r) return;
      r.cabins.push({ cabin: 'Extra Cabin', fare_code: 'Y', seats: 0 });
      rrSfcRenderStep();
    };

    window.rrSfcRemoveCabin = function (ci) {
      var r = rows[selectedIndices[stepPos]];
      if (!r) return;
      r.cabins.splice(ci, 1);
      rrSfcRenderStep();
    };

    window.rrSfcOpenNewFare = function (code, name, cap) {
      document.getElementById('rr_sfc_nf_code').value = code || '';
      document.getElementById('rr_sfc_nf_name').value = name || '';
      document.getElementById('rr_sfc_nf_cap').value = cap || 0;
      document.getElementById('rr_sfc_nf_status').textContent = '';
      document.getElementById('rr_sfc_new_fare_panel').style.display = 'block';
    };

    window.rrSfcSubmitNewFare = function () {
      var st = document.getElementById('rr_sfc_nf_status');
      st.textContent = 'Saving...';
      fetch('{{ route("afterburner.admin.subfleet_creator.create_fare") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify({
          code: document.getElementById('rr_sfc_nf_code').value,
          name: document.getElementById('rr_sfc_nf_name').value,
          price: document.getElementById('rr_sfc_nf_price').value,
          capacity: document.getElementById('rr_sfc_nf_cap').value,
          type: document.getElementById('rr_sfc_nf_type').value
        })
      })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        st.textContent = data.message || '';
        if (data.success && data.fare) {
          boot.fares.push(data.fare);
          document.getElementById('rr_sfc_new_fare_panel').style.display = 'none';
          rrSfcRenderStep();
        }
      })
      .catch(function () { st.textContent = 'Failed to create fare code.'; });
    };

    window.rrSfcExecuteCreate = function () {
      var resBox = document.getElementById('rr_sfc_result_box');
      resBox.style.display = 'block';
      resBox.style.background = '#e0f2fe';
      resBox.style.border = '1px solid #0284c7';
      resBox.textContent = 'Creating subfleet(s)...';

      var rankIds = [];
      document.querySelectorAll('.rr-sfc-rank-cb:checked').forEach(function (cb) { rankIds.push(parseInt(cb.value, 10)); });

      var items = selectedIndices.map(function (idx) {
        var r = rows[idx];
        var pv = previewRow(r);
        return {
          type: pv.type,
          name: pv.name,
          icao: r.icao,
          simbrief_type: (r.simbrief_type || r.icao || '').trim(),
          typerating_id: r.typerating_id || 0,
          cabins: r.cabins || []
        };
      });

      fetch('{{ route("afterburner.admin.subfleet_creator.execute") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify({
          airline_id: parseInt(document.getElementById('rr_sfc_va_airline').value, 10),
          fuel_type: parseInt(document.getElementById('rr_sfc_fuel_type').value, 10),
          rank_ids: rankIds,
          map_fares: document.getElementById('rr_sfc_map_fares').checked,
          type_syntax: document.getElementById('rr_sfc_type_syntax').value,
          name_syntax: document.getElementById('rr_sfc_name_syntax').value,
          items: items
        })
      })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        resBox.style.background = data.success ? '#dcfce7' : '#fee2e2';
        resBox.style.border = data.success ? '1px solid #16a34a' : '1px solid #dc2626';
        var extra = (data.created && data.created.length) ? ('<br><small>' + data.created.join('<br>') + '</small>') : '';
        resBox.innerHTML = '<strong>' + (data.message || 'Done') + '</strong>' + extra;
      })
      .catch(function () {
        resBox.style.background = '#fee2e2';
        resBox.textContent = 'Request failed while creating subfleets.';
      });
    };

    // =========================================================================
    // Mode 2: Existing Subfleet Standardizer & Cabin Layout Fixer
    // =========================================================================
    var stdActiveFleet = [];
    var stdRowState = {}; // { [subfleetId]: { fleetIdx, presetRow, cabins, editing } }

    function rrSfcStdRenderCurrentFares() {
      var fmap = boot.existing_subfleet_fares || {};
      document.querySelectorAll('.rr-sfc-std-cur-fares').forEach(function (el) {
        var id = el.getAttribute('data-id');
        var list = fmap[id] || [];
        if (!list.length) {
          el.innerHTML = '<span style="color:#94a3b8;">Cabins: none attached</span>';
        } else {
          var tot = 0;
          var parts = list.map(function (c) {
            tot += parseInt(c.capacity || 0, 10);
            return '<span class="label label-default" style="font-size:9px;margin-right:2px;background:#e2e8f0;color:#334155;">' + escHtml(c.code) + ':' + c.capacity + '</span>';
          });
          el.innerHTML = parts.join('') + ' <span class="text-muted">(' + tot + ')</span>';
        }
      });
    }

    function rrSfcStdRenderTokens(presetRow, trEl) {
      var opIcao = (document.getElementById('rr_sfc_std_op_icao').value || '').trim().toUpperCase();
      var opIata = (document.getElementById('rr_sfc_std_op_iata').value || '').trim().toUpperCase();
      var opName = (document.getElementById('rr_sfc_std_op_name').value || '').trim();
      var alIcao = trEl ? (trEl.getAttribute('data-airline-icao') || '') : '';
      var alName = trEl ? (trEl.getAttribute('data-airline-name') || '') : '';
      var pax = calcPax(presetRow.cabins || []);
      var tokens = {
        OP_ICAO: opIcao, OP_IATA: opIata, OP_NAME: opName,
        AIRLINE_ICAO: alIcao, AIRLINE_NAME: alName,
        ICAO: (presetRow.icao || '').toUpperCase(),
        IATA: (presetRow.iata || '').toUpperCase(),
        AIRCRAFT: presetRow.aircraft || '',
        VARIANT: presetRow.variant || '',
        CONFIG: presetRow.config || '',
        PAX: pax > 0 ? String(pax) : ''
      };
      var tSyn = (document.getElementById('rr_sfc_std_type_syntax').value || '{OP_ICAO}.{ICAO}.{VARIANT}');
      var nSyn = (document.getElementById('rr_sfc_std_name_syntax').value || '{OP_ICAO} {AIRCRAFT} {VARIANT} {CONFIG} ({PAX})');
      return {
        type: renderSyntax(tSyn, tokens, true),
        name: renderSyntax(nSyn, tokens, false)
      };
    }

    window.rrSfcStdCheckLen = function (id) {
      var tEl = document.querySelector('.rr-sfc-std-type[data-id="' + id + '"]');
      var nEl = document.querySelector('.rr-sfc-std-name[data-id="' + id + '"]');
      var tl = document.querySelector('.rr-sfc-std-tlen[data-id="' + id + '"]');
      var nl = document.querySelector('.rr-sfc-std-nlen[data-id="' + id + '"]');
      if (tEl && tl) {
        tl.innerHTML = tEl.value.length > 50 ? '<span style="color:#dc2626;font-weight:bold;">&#9888; ' + tEl.value.length + '/50 (trimmed)</span>' : '';
      }
      if (nEl && nl) {
        nl.innerHTML = nEl.value.length > 50 ? '<span style="color:#dc2626;font-weight:bold;">&#9888; ' + nEl.value.length + '/50 (trimmed)</span>' : '';
      }
    };

    window.rrSfcStdPopulateOperators = function (filterText) {
      var sel = document.getElementById('rr_sfc_std_operator');
      if (!sel) return;
      var prevVal = sel.value || 'DAL';
      var q = String(filterText || '').trim().toLowerCase();
      var list = (boot.operator_directory || []).filter(function (d) {
        if (!q) return true;
        var st = rrSfcStatusTag(d).toLowerCase();
        return (d.label || '').toLowerCase().indexOf(q) !== -1
          || (d.icao || '').toLowerCase().indexOf(q) !== -1
          || (d.iata || '').toLowerCase().indexOf(q) !== -1
          || (d.category || '').toLowerCase().indexOf(q) !== -1
          || st.indexOf(q) !== -1;
      });
      var groups = {};
      var groupOrder = [];
      list.forEach(function (d) {
        var cat = d.category || 'Operators';
        if (!groups[cat]) { groups[cat] = []; groupOrder.push(cat); }
        groups[cat].push(d);
      });
      var html = '';
      groupOrder.forEach(function (cat) {
        html += '<optgroup label="' + cat.replace(/"/g, '&quot;') + '">';
        groups[cat].forEach(function (d) {
          var badge = d.has_preset ? '★ ' : '';
          var st = rrSfcStatusTag(d);
          var stTxt = st ? ('[' + st + '] ') : '';
          var iataTag = d.iata ? (' [' + d.iata + ']') : '';
          html += '<option value="' + d.key.replace(/"/g, '&quot;') + '">' + badge + stTxt + d.icao + iataTag + ' — ' + d.name.replace(/</g, '&lt;') + '</option>';
        });
        html += '</optgroup>';
      });
      sel.innerHTML = html;
      var cntEl = document.getElementById('rr_sfc_std_op_count');
      if (cntEl) cntEl.textContent = '(' + list.length + ' matched)';
      if (sel.querySelector('option[value="' + prevVal.replace(/"/g, '\\"') + '"]')) {
        sel.value = prevVal;
      } else if (list.length > 0) {
        sel.value = list[0].key;
      }
      if (q) rrSfcStdLoadOperator();
    };

    window.rrSfcStdLoadOperator = function () {
      var sel = document.getElementById('rr_sfc_std_operator');
      if (!sel) return;
      var key = sel.value;
      var op = (boot.operators || {})[key];
      var dirEntry = dirMap[key] || null;
      var hintEl = document.getElementById('rr_sfc_std_op_hint');
      var revBox = document.getElementById('rr_sfc_std_rev_strip');

      function mapPresetFleet(fleetArr) {
        return (fleetArr || []).map(function (f) {
          var cabs = (f.cabins || []).map(function (c) {
            var fc = c.code;
            var mf = findFareByCode(fc, c.cabin);
            if (mf && mf.code) fc = mf.code;
            return { cabin: c.cabin, fare_code: fc, seats: c.seats };
          });
          return {
            aircraft: f.aircraft, icao: f.icao, iata: f.iata || '',
            variant: f.variant || '', config: f.config || '',
            pax: calcPax(cabs), cabins: cabs
          };
        });
      }

      if (op) {
        document.getElementById('rr_sfc_std_op_icao').value = op.icao;
        document.getElementById('rr_sfc_std_op_iata').value = op.iata || '';
        document.getElementById('rr_sfc_std_op_name').value = op.name;
        stdActiveFleet = mapPresetFleet(op.fleet);
        if (hintEl) {
          var revS = op.seatmap_rev ? (' • Rev ' + op.seatmap_rev) : '';
          var vTag = op.verified_at ? (' • ✓ ' + op.verified_at) : '';
          hintEl.textContent = '★ Verified Fleet Preset (' + stdActiveFleet.length + ' configs)' + revS + vTag;
        }
        if (revBox) {
          if (op.seatmap_rev || op.verified_at || op.update_note) {
            var vVer = String(boot.vault_version || '');
            var isCur = vVer !== '' && String(op.updated_in || '') === vVer;
            var badgeHtml = '';
            if (isCur && op.change_type === 'new') badgeHtml = ' <span class="label label-success" style="font-size:9px;">NEW in v' + escHtml(vVer) + '</span>';
            else if (isCur && op.change_type === 'updated') badgeHtml = ' <span class="label label-info" style="font-size:9px;">UPDATED in v' + escHtml(vVer) + '</span>';
            var srcs = Array.isArray(op.sources) && op.sources.length ? (' &bull; Sources: ' + escHtml(op.sources.join(', '))) : '';
            var rHtml = '<div><strong>&#9733; ' + escHtml(op.name) + ' (' + escHtml(op.icao) + ')</strong>'
              + (op.seatmap_rev ? (' &mdash; Seat Map <strong>Rev ' + escHtml(op.seatmap_rev) + '</strong>') : '')
              + badgeHtml + '</div>';
            if (op.verified_at || srcs) rHtml += '<div class="text-muted">' + (op.verified_at ? ('&#10003; ' + escHtml(op.verified_at)) : '') + srcs + '</div>';
            if (op.update_note) rHtml += '<div style="color:#0f172a;margin-top:2px;"><strong>Note:</strong> ' + escHtml(op.update_note) + '</div>';
            revBox.innerHTML = rHtml;
            revBox.style.display = '';
          } else {
            revBox.style.display = 'none';
          }
        }
      } else if (dirEntry) {
        document.getElementById('rr_sfc_std_op_icao').value = dirEntry.icao || '';
        document.getElementById('rr_sfc_std_op_iata').value = dirEntry.iata || '';
        document.getElementById('rr_sfc_std_op_name').value = dirEntry.name || '';
        var stType = dirEntry.starter_type || 'commercial';
        stdActiveFleet = mapPresetFleet((boot.starter_fleets || {})[stType] || (boot.starter_fleets || {}).commercial || []);
        if (hintEl) hintEl.textContent = 'Synced Operator (' + stType.toUpperCase() + ' starter fleet loaded)';
        if (revBox) revBox.style.display = 'none';
      } else {
        stdActiveFleet = [];
        if (hintEl) hintEl.textContent = '';
        if (revBox) revBox.style.display = 'none';
      }

      // Populate every row's "Match Preset Aircraft" dropdown
      var opts = '<option value="">— Keep / Manual —</option>';
      stdActiveFleet.forEach(function (f, i) {
        var varTag = [f.variant, f.config].filter(Boolean).join(' ');
        var paxTag = f.pax > 0 ? (' (' + f.pax + ' PAX)') : ' (Cargo)';
        opts += '<option value="' + i + '">' + escHtml(f.aircraft) + (varTag ? (' ' + escHtml(varTag)) : '') + ' [' + escHtml(f.icao) + ']' + paxTag + '</option>';
      });
      document.querySelectorAll('.rr-sfc-std-preset-sel').forEach(function (s) {
        s.innerHTML = opts;
      });
    };

    window.rrSfcStdRenderCabinsCell = function (id) {
      var cell = document.querySelector('.rr-sfc-std-cabins-cell[data-id="' + id + '"]');
      if (!cell) return;
      var st = stdRowState[id];
      if (!st || !st.cabins || !st.cabins.length) {
        cell.innerHTML = '<span class="text-muted">Unchanged</span>';
        return;
      }
      var tot = calcPax(st.cabins);
      var missing = [];
      var pills = st.cabins.map(function (c) {
        var ok = !!findFareByCode(c.fare_code, '');
        if (!ok && parseInt(c.seats || 0, 10) > 0) missing.push(c);
        var bg = ok ? '#dcfce7' : '#fee2e2';
        var col = ok ? '#166534' : '#991b1b';
        return '<span style="display:inline-block;padding:1px 4px;border-radius:3px;font-size:10px;margin:1px 2px 1px 0;background:' + bg + ';color:' + col + ';font-weight:600;">' + escHtml(c.fare_code) + ':' + c.seats + '</span>';
      }).join('');

      var html = '<div>' + pills + ' <small class="text-muted">(' + tot + ')</small> '
        + '<a href="javascript:void(0)" onclick="rrSfcStdToggleEditCabins(' + id + ')" style="font-size:10px;">' + (st.editing ? 'Done' : 'Edit') + '</a></div>';

      if (missing.length) {
        html += '<div style="color:#dc2626;font-size:10px;margin-top:2px;">&#9888; Missing fare: '
          + missing.map(function (m) {
              return '<code>' + escHtml(m.fare_code) + '</code> <a href="javascript:void(0)" onclick="rrSfcStdOpenNewFare(\'' + escHtml(m.fare_code) + '\',\'' + escHtml(m.cabin) + '\',' + m.seats + ')">+Create</a>';
            }).join(', ')
          + '</div>';
      }

      if (st.editing) {
        html += '<div style="margin-top:4px;padding:4px;background:#f8fafc;border:1px solid #cbd5e1;border-radius:4px;">';
        st.cabins.forEach(function (c, ci) {
          html += '<div style="display:flex;gap:3px;align-items:center;margin-bottom:3px;">'
            + '<input type="text" class="form-control input-sm" style="width:46px;height:20px;font-size:10px;padding:1px 3px;" value="' + escHtml(c.fare_code) + '" oninput="rrSfcStdEditCabin(' + id + ',' + ci + ',\'fare_code\',this.value)" title="Fare Code">'
            + '<input type="number" min="0" class="form-control input-sm" style="width:52px;height:20px;font-size:10px;padding:1px 3px;" value="' + c.seats + '" oninput="rrSfcStdEditCabin(' + id + ',' + ci + ',\'seats\',this.value)" title="Seats">'
            + '<span style="font-size:10px;color:#475569;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escHtml(c.cabin) + '</span>'
            + '<button type="button" class="btn btn-xs btn-danger" style="padding:0 4px;font-size:10px;" onclick="rrSfcStdRemoveCabin(' + id + ',' + ci + ')">&times;</button>'
            + '</div>';
        });
        html += '<button type="button" class="btn btn-xs btn-default" style="padding:1px 5px;font-size:10px;" onclick="rrSfcStdAddCabin(' + id + ')">+ Cabin</button></div>';
      }
      cell.innerHTML = html;
    };

    window.rrSfcStdToggleEditCabins = function (id) {
      if (!stdRowState[id]) return;
      stdRowState[id].editing = !stdRowState[id].editing;
      rrSfcStdRenderCabinsCell(id);
    };

    window.rrSfcStdEditCabin = function (id, ci, field, val) {
      var st = stdRowState[id];
      if (!st || !st.cabins[ci]) return;
      if (field === 'seats') {
        st.cabins[ci].seats = Math.max(0, parseInt(val || 0, 10));
        if (st.presetRow) {
          st.presetRow.cabins = st.cabins;
          var trEl = document.querySelector('.rr-sfc-std-row[data-id="' + id + '"]');
          var pv = rrSfcStdRenderTokens(st.presetRow, trEl);
          var tEl = document.querySelector('.rr-sfc-std-type[data-id="' + id + '"]');
          var nEl = document.querySelector('.rr-sfc-std-name[data-id="' + id + '"]');
          if (tEl) tEl.value = pv.type;
          if (nEl) nEl.value = pv.name;
          rrSfcStdCheckLen(id);
        }
      } else {
        st.cabins[ci][field] = String(val || '').toUpperCase().trim();
      }
      rrSfcStdRenderCabinsCell(id);
    };

    window.rrSfcStdAddCabin = function (id) {
      if (!stdRowState[id]) return;
      stdRowState[id].cabins.push({ cabin: 'Economy', fare_code: 'Y', seats: 0 });
      rrSfcStdRenderCabinsCell(id);
    };

    window.rrSfcStdRemoveCabin = function (id, ci) {
      if (!stdRowState[id]) return;
      stdRowState[id].cabins.splice(ci, 1);
      rrSfcStdRenderCabinsCell(id);
    };

    window.rrSfcStdApplyPresetToRow = function (id, fleetIdxStr) {
      var trEl = document.querySelector('.rr-sfc-std-row[data-id="' + id + '"]');
      var tEl = document.querySelector('.rr-sfc-std-type[data-id="' + id + '"]');
      var nEl = document.querySelector('.rr-sfc-std-name[data-id="' + id + '"]');
      var sEl = document.querySelector('.rr-sfc-std-simbrief[data-id="' + id + '"]');
      var cb  = document.querySelector('.rr-sfc-std-cb[value="' + id + '"]');

      if (fleetIdxStr === '' || fleetIdxStr === null || fleetIdxStr === undefined) {
        delete stdRowState[id];
        if (trEl) {
          if (tEl) tEl.value = trEl.getAttribute('data-orig-type') || '';
          if (nEl) nEl.value = trEl.getAttribute('data-orig-name') || '';
          if (sEl) sEl.value = trEl.getAttribute('data-orig-sb') || '';
        }
        rrSfcStdCheckLen(id);
        rrSfcStdRenderCabinsCell(id);
        return;
      }

      var idx = parseInt(fleetIdxStr, 10);
      var f = stdActiveFleet[idx];
      if (!f) return;

      var clonedCabins = (f.cabins || []).map(function (c) {
        return { cabin: c.cabin, fare_code: c.fare_code, seats: c.seats };
      });
      var presetCopy = {
        aircraft: f.aircraft, icao: f.icao, iata: f.iata,
        variant: f.variant, config: f.config, cabins: clonedCabins
      };
      stdRowState[id] = { fleetIdx: idx, presetRow: presetCopy, cabins: clonedCabins, editing: false };

      var pv = rrSfcStdRenderTokens(presetCopy, trEl);
      if (tEl) tEl.value = pv.type;
      if (nEl) nEl.value = pv.name;
      if (sEl) sEl.value = f.icao || '';
      if (cb) cb.checked = true;
      rrSfcStdCheckLen(id);
      rrSfcStdRenderCabinsCell(id);
    };

    window.rrSfcStdReapplySyntax = function () {
      Object.keys(stdRowState).forEach(function (idStr) {
        var id = parseInt(idStr, 10);
        var st = stdRowState[id];
        if (!st || !st.presetRow) return;
        var trEl = document.querySelector('.rr-sfc-std-row[data-id="' + id + '"]');
        var pv = rrSfcStdRenderTokens(st.presetRow, trEl);
        var tEl = document.querySelector('.rr-sfc-std-type[data-id="' + id + '"]');
        var nEl = document.querySelector('.rr-sfc-std-name[data-id="' + id + '"]');
        if (tEl) tEl.value = pv.type;
        if (nEl) nEl.value = pv.name;
        rrSfcStdCheckLen(id);
      });
    };

    window.rrSfcStdFilterRows = function (q) {
      var needle = String(q || '').trim().toLowerCase();
      document.querySelectorAll('.rr-sfc-std-row').forEach(function (tr) {
        if (!needle) { tr.style.display = ''; return; }
        var hay = (tr.getAttribute('data-id') + ' ' + tr.getAttribute('data-orig-type') + ' ' + tr.getAttribute('data-orig-name')).toLowerCase();
        tr.style.display = hay.indexOf(needle) !== -1 ? '' : 'none';
      });
    };

    window.rrSfcStdToggleAll = function (checked) {
      document.querySelectorAll('.rr-sfc-std-row').forEach(function (tr) {
        if (tr.style.display === 'none') return;
        var cb = tr.querySelector('.rr-sfc-std-cb');
        if (cb) cb.checked = !!checked;
      });
    };

    window.rrSfcStdAutoMatchVisible = function () {
      if (!stdActiveFleet.length) {
        alert('Select a Real-World Operator Preset first.');
        return;
      }
      var matched = 0;
      document.querySelectorAll('.rr-sfc-std-row').forEach(function (tr) {
        if (tr.style.display === 'none') return;
        var id = parseInt(tr.getAttribute('data-id'), 10);
        var hay = ((tr.getAttribute('data-orig-type') || '') + ' ' + (tr.getAttribute('data-orig-name') || '') + ' ' + (tr.getAttribute('data-orig-sb') || '')).toUpperCase();
        var bestIdx = -1;
        var bestScore = 0;
        stdActiveFleet.forEach(function (f, i) {
          var score = 0;
          var icao = (f.icao || '').toUpperCase();
          var iata = (f.iata || '').toUpperCase();
          var acU  = (f.aircraft || '').toUpperCase();
          var varU = (f.variant || '').toUpperCase();
          if (icao && hay.indexOf(icao) !== -1) score += 10;
          if (acU && hay.indexOf(acU) !== -1) score += 12;
          if (acU && hay.indexOf(acU.replace(/^B/, '')) !== -1) score += 8;
          if (iata && iata.length === 3 && hay.indexOf(iata) !== -1) score += 5;
          if (varU && varU.length >= 2 && hay.indexOf(varU) !== -1) score += 6;
          if (f.pax > 0 && hay.indexOf(String(f.pax)) !== -1) score += 5;
          if (score > bestScore) { bestScore = score; bestIdx = i; }
        });
        if (bestIdx !== -1 && bestScore >= 8) {
          var sel = tr.querySelector('.rr-sfc-std-preset-sel');
          if (sel) sel.value = String(bestIdx);
          rrSfcStdApplyPresetToRow(id, String(bestIdx));
          matched++;
        }
      });
      var sm = document.getElementById('rr_sfc_std_sel_summary');
      if (sm) sm.textContent = matched > 0
        ? ('Auto-matched ' + matched + ' visible subfleet(s) to ' + (document.getElementById('rr_sfc_std_op_name').value || 'preset') + ' — review & click Apply!')
        : 'No visible subfleets matched the selected operator preset. Pick a matching operator or select rows manually.';
    };

    window.rrSfcStdOpenNewFare = function (code, name, cap) {
      document.getElementById('rr_sfc_std_nf_code').value = code || '';
      document.getElementById('rr_sfc_std_nf_name').value = name || '';
      document.getElementById('rr_sfc_std_nf_cap').value = cap || 0;
      document.getElementById('rr_sfc_std_nf_status').textContent = '';
      document.getElementById('rr_sfc_std_new_fare_panel').style.display = 'block';
    };

    window.rrSfcStdSubmitNewFare = function () {
      var st = document.getElementById('rr_sfc_std_nf_status');
      st.textContent = 'Saving...';
      fetch('{{ route("afterburner.admin.subfleet_creator.create_fare") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify({
          code: document.getElementById('rr_sfc_std_nf_code').value,
          name: document.getElementById('rr_sfc_std_nf_name').value,
          price: document.getElementById('rr_sfc_std_nf_price').value,
          capacity: document.getElementById('rr_sfc_std_nf_cap').value,
          type: document.getElementById('rr_sfc_std_nf_type').value
        })
      })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        st.textContent = data.message || '';
        if (data.success && data.fare) {
          boot.fares.push(data.fare);
          document.getElementById('rr_sfc_std_new_fare_panel').style.display = 'none';
          Object.keys(stdRowState).forEach(function (idStr) { rrSfcStdRenderCabinsCell(parseInt(idStr, 10)); });
        }
      })
      .catch(function () { st.textContent = 'Failed to create fare code.'; });
    };

    window.rrSfcExecuteStandardize = function () {
      var fixCabins = document.getElementById('rr_sfc_std_fix_cabins') ? document.getElementById('rr_sfc_std_fix_cabins').checked : false;
      var updates = [];
      document.querySelectorAll('.rr-sfc-std-cb:checked').forEach(function (cb) {
        var id = parseInt(cb.value, 10);
        var tEl = document.querySelector('.rr-sfc-std-type[data-id="' + id + '"]');
        var nEl = document.querySelector('.rr-sfc-std-name[data-id="' + id + '"]');
        var sEl = document.querySelector('.rr-sfc-std-simbrief[data-id="' + id + '"]');
        var st  = stdRowState[id] || null;
        if (tEl && nEl) {
          updates.push({
            id: id,
            new_type: tEl.value,
            new_name: nEl.value,
            simbrief_type: sEl ? sEl.value : '',
            fix_cabins: fixCabins && !!(st && st.cabins && st.cabins.length),
            cabins: (st && st.cabins) ? st.cabins : []
          });
        }
      });
      if (!updates.length) {
        alert('Check at least one existing subfleet row to fix.');
        return;
      }
      var resBox = document.getElementById('rr_sfc_std_result');
      resBox.style.display = 'block';
      resBox.style.background = '#e0f2fe';
      resBox.style.border = '1px solid #0284c7';
      resBox.textContent = 'Applying subfleet & cabin fixes...';

      fetch('{{ route("afterburner.admin.subfleet_creator.standardize") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify({
          updates: updates,
          fix_cabins: fixCabins,
          type_syntax: document.getElementById('rr_sfc_std_type_syntax') ? document.getElementById('rr_sfc_std_type_syntax').value : '',
          name_syntax: document.getElementById('rr_sfc_std_name_syntax') ? document.getElementById('rr_sfc_std_name_syntax').value : ''
        })
      })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        resBox.style.background = data.success ? '#dcfce7' : '#fee2e2';
        resBox.style.border = data.success ? '1px solid #16a34a' : '1px solid #dc2626';
        resBox.textContent = data.message || 'Done';
      })
      .catch(function () {
        resBox.style.background = '#fee2e2';
        resBox.textContent = 'Failed to apply subfleet fixes.';
      });
    };

    var _sfcTbodyEl = document.getElementById('rr_sfc_rows_tbody');
    if (_sfcTbodyEl) {
      _sfcTbodyEl.addEventListener('change', function (e) {
        if (e.target && e.target.classList && e.target.classList.contains('rr-sfc-row-cb')) {
          rrSfcSyncSelectionFromDom();
        }
      });
    }
    rrSfcRenderUpdatesBanner();
    rrSfcPopulateOperators('');
    rrSfcLoadOperator();
    rrSfcStdRenderCurrentFares();
    rrSfcStdPopulateOperators('');
    rrSfcStdLoadOperator();
  })();
</script>

<script>
  window.rrSwitchAnalyticsWindow = function (winKey) {
    var w30 = document.getElementById('rr_an_win_30d');
    var w90 = document.getElementById('rr_an_win_90d');
    var b30 = document.getElementById('rr_an_btn_30');
    var b90 = document.getElementById('rr_an_btn_90');
    if (w30) w30.style.display = (winKey === '30d') ? '' : 'none';
    if (w90) w90.style.display = (winKey === '90d') ? '' : 'none';
    if (b30) b30.className = 'btn btn-xs ' + (winKey === '30d' ? 'btn-primary btn-fill' : 'btn-default');
    if (b90) b90.className = 'btn btn-xs ' + (winKey === '90d' ? 'btn-primary btn-fill' : 'btn-default');
  };

  window.rrRunSystemSelfTest = function () {
    var box = document.getElementById('rr_self_test_result_box');
    if (!box) return;
    box.style.display = 'block';
    box.style.borderColor = '#0ea5e9';
    box.innerHTML = '<strong>&#129658; Running Afterburner Dispatch System Self-Test...</strong>';

    fetch('{{ route("afterburner.admin.self_test") }}', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
      body: JSON.stringify({})
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
      var border = res.overall === 'pass' ? '#16a34a' : (res.overall === 'warn' ? '#d97706' : '#dc2626');
      box.style.borderColor = border;
      var html = '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">'
        + '<strong>&#129658; System Self-Test (' + (res.edition || '') + ' v' + (res.version || '') + ') — ' + String(res.overall || '').toUpperCase() + '</strong>'
        + '<div><small class="text-muted" style="margin-right:8px;">' + (res.checked_at || '') + '</small>'
        + '<button type="button" class="btn btn-xs btn-default" onclick="document.getElementById(\'rr_self_test_result_box\').style.display=\'none\'">&times;</button></div>'
        + '</div>';
      html += '<table class="table table-condensed" style="margin-bottom:0;background:#fff;"><tbody>';
      (res.checks || []).forEach(function (c) {
        var lbl = c.status === 'pass' ? 'label-success' : (c.status === 'warn' ? 'label-warning' : 'label-danger');
        html += '<tr><td style="width:220px;"><strong>' + c.name + '</strong></td>'
          + '<td style="width:70px;"><span class="label ' + lbl + '">' + String(c.status || '').toUpperCase() + '</span></td>'
          + '<td>' + c.detail + '</td></tr>';
      });
      html += '</tbody></table>';
      box.innerHTML = html;
    })
    .catch(function () {
      box.style.borderColor = '#dc2626';
      box.innerHTML = '<strong class="text-danger">Self-Test request failed.</strong>';
    });
  };
</script>
@endsection