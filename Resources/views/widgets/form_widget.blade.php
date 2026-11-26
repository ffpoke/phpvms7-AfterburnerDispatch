@php
  if (empty($realworldAirlines) && class_exists(\Modules\AfterburnerDispatch\Support\RealWorldAirlines::class)) {
    $realworldAirlines = \Modules\AfterburnerDispatch\Support\RealWorldAirlines::all();
  }
  $singleTrId = ($pilotTypeRatings && $pilotTypeRatings->count() === 1) ? $pilotTypeRatings->first()->id : '';
@endphp
<div class="card mb-2">
  <div class="card-header p-1">
    <h5 class="m-1">
      <i class="fas fa-route float-end"></i>
      Quick Route Request
    </h5>
  </div>
  @if($pilotTypeRatings->isEmpty())
    <div class="card-body p-2 small text-muted">
      <i class="fas fa-lock me-1 text-warning"></i> You must hold at least one signed-off Type Rating on your profile to request a route.
    </div>
  @else
    <form method="POST" action="{{ route('afterburner.store') }}">
      @csrf
      <input type="hidden" name="recipient_email" value="{{ $defaultEmail }}">
      <div class="card-body p-2">
        <div class="input-group input-group-sm mb-1">
          <span class="input-group-text col-4">Type Rating <span class="text-danger">*</span></span>
          <select name="typerating_id" class="form-control select2" required>
            <option value="">-- Select Type Rating --</option>
            @foreach($pilotTypeRatings as $tr)
              <option value="{{ $tr->id }}" {{ (string) $singleTrId === (string) $tr->id ? 'selected' : '' }}>{{ $tr->type }} — {{ $tr->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="input-group input-group-sm mb-1">
          <span class="input-group-text col-4">Real Airline <span class="text-danger">*</span></span>
          <select name="realworld_airline" class="form-control select2" required>
            <option value="">-- Select Real/Charter Airline --</option>
            @foreach($realworldAirlines as $groupLabel => $operators)
              <optgroup label="{{ $groupLabel }}">
                @foreach($operators as $val => $label)
                  <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
              </optgroup>
            @endforeach
          </select>
        </div>

        <div class="input-group input-group-sm mb-1">
          <span class="input-group-text col-4">Start ICAO <span class="text-danger">*</span></span>
          <input type="text" name="dep_airport_id" maxlength="5" style="text-transform: uppercase;" class="form-control" value="{{ $defaultDep }}" placeholder="Required ICAO (e.g. KLAX)" required>
        </div>

        <div class="input-group input-group-sm mb-1">
          <span class="input-group-text col-4">End ICAO</span>
          <input type="text" name="arr_airport_id" id="widget_arr_airport_id" maxlength="5" style="text-transform: uppercase;" class="form-control" placeholder="Optional ICAO">
        </div>

        <div class="form-check mb-1 ms-1">
          <input type="hidden" name="request_return" value="0">
          <input class="form-check-input" type="checkbox" name="request_return" id="widget_request_return" value="1">
          <label class="form-check-label small" for="widget_request_return">
            Also Request Return Flight (Round-Trip)
          </label>
        </div>

        <div class="form-check mb-1 ms-1">
          <input type="hidden" name="multiple_destinations" value="0">
          <input class="form-check-input" type="checkbox" name="multiple_destinations" id="widget_multiple_destinations" value="1" onchange="var el=document.getElementById('widget_arr_airport_id'); if(this.checked){el.value=''; el.disabled=true;}else{el.disabled=false;}">
          <label class="form-check-label small" for="widget_multiple_destinations">
            Multiple Destinations (ANY Ending Airport — Max 4 Return Pairs)
          </label>
        </div>
      </div>
      <div class="card-footer p-1 d-flex justify-content-between align-items-center flex-wrap">
        <span class="small text-muted" style="font-size: 10px;">Made by Alexander Leard of Fantasy Air VA &amp; Regal Artistic Avenue</span>
        <button type="submit" class="btn btn-sm bg-primary text-white">
          <i class="fas fa-paper-plane me-1"></i> Send to Dispatch
        </button>
      </div>
    </form>
  @endif
</div>
