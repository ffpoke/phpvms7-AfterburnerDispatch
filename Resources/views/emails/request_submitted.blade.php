<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #1f2937; line-height: 1.5; background-color: #f3f4f6; padding: 15px; }
    .container { max-width: 640px; margin: 0 auto; border: 1px solid #d1d5db; border-radius: 6px; overflow: hidden; background-color: #ffffff; }
    .header { background-color: #1e293b; color: #ffffff; padding: 16px 22px; }
    .header h2 { margin: 0; font-size: 18px; }
    .content { padding: 22px; }
    .details-table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 16px; }
    .details-table th, .details-table td { text-align: left; padding: 9px 12px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
    .details-table th { width: 38%; color: #374151; font-weight: 600; background-color: #f9fafb; }
    .badge { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; background-color: #dbeafe; color: #1e40af; }
    .badge-multi { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; background-color: #ede9fe; color: #5b21b6; }
    .badge-rt { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; background-color: #dcfce7; color: #166534; }
    .btn { display: inline-block; background-color: #2563eb; color: #ffffff !important; text-decoration: none; padding: 8px 16px; border-radius: 4px; font-size: 13px; font-weight: 600; margin: 4px; }
    .btn-success { background-color: #16a34a; }
    .footer { background-color: #f8fafc; color: #64748b; padding: 12px 22px; font-size: 12px; text-align: center; border-top: 1px solid #e5e7eb; }
  </style>
</head>
<body>
  @php
    $genFlights = $routeRequest->generated_flight_models;
    $isMultiDest = (bool) $routeRequest->multiple_destinations || $genFlights->count() > 2;
  @endphp
  <div class="container">
    <div class="header">
      <h2>
        @if($isStatusUpdate)
          Fly Fantasy Air — Route Request #{{ $routeRequest->id }} ({{ $routeRequest->status_label }})
        @else
          Fly Fantasy Air — New Pilot Route Request (#{{ $routeRequest->id }})
        @endif
      </h2>
    </div>
    <div class="content">
      @if($isStatusUpdate)
        <p>
          The status of Pilot Route Request <strong>#{{ $routeRequest->id }}</strong> has been updated to:
          <span class="badge">{{ $routeRequest->status_label }}</span>
        </p>
        @if($routeRequest->status === 'approved' || $routeRequest->status === 'in_progress')
          <p>Our dispatch team has accepted your request and is currently building and testing the route(s) in the system. You will receive another notification once the route(s) are completed and active.</p>
        @elseif($routeRequest->status === 'completed')
          <p>Your requested route(s) have been built and are now active in the Fly Fantasy Air flight schedules!</p>
        @elseif($routeRequest->status === 'cancelled')
          <p>This pending route request was marked as <strong>Cancelled by Pilot</strong>.</p>
        @endif
      @else
        <p>A new pilot route request has been submitted on <strong>Fly Fantasy Air</strong> and dispatched to <code>admin@flyfantasyair.net</code>.</p>
      @endif

      <table class="details-table">
        <tr>
          <th>Request ID</th>
          <td>#{{ $routeRequest->id }}</td>
        </tr>
        <tr>
          <th>Requesting Pilot</th>
          <td>
            @if($routeRequest->user)
              <strong>{{ $routeRequest->user->ident }}</strong> — {{ $routeRequest->user->name }} ({{ $routeRequest->user->email }})
            @else
              Pilot ID #{{ $routeRequest->user_id }}
            @endif
          </td>
        </tr>
        <tr>
          <th>Pilot Signed-Off Type Rating</th>
          <td><strong>{{ $routeRequest->aircraft_type }}</strong></td>
        </tr>
        <tr>
          <th>Real-World / Charter Counterpart</th>
          <td><strong>{{ $routeRequest->realworld_airline }}</strong></td>
        </tr>
        <tr>
          <th>Starting (Departure) Airport</th>
          <td>
            <strong>{{ $routeRequest->dep_airport_id }}</strong>
            @if($routeRequest->dep_airport)
              — {{ $routeRequest->dep_airport->name }}
            @endif
          </td>
        </tr>
        <tr>
          <th>Ending (Arrival) Airport</th>
          <td>
            @if($isMultiDest)
              <span class="badge-multi">MULTIPLE DESTINATIONS (ANY / OPEN)</span>
            @elseif($routeRequest->arr_airport_id)
              <strong>{{ $routeRequest->arr_airport_id }}</strong>
              @if($routeRequest->arr_airport)
                — {{ $routeRequest->arr_airport->name }}
              @endif
            @else
              <em>Not Specified / Open Destination</em>
            @endif
          </td>
        </tr>
        <tr>
          <th>Requested Trip Type</th>
          <td>
            @if($routeRequest->request_return)
              <span class="badge-rt">ROUND-TRIP (Outbound + Return Flight Requested)</span>
            @else
              <span class="badge">ONE-WAY ONLY (No Return Flight Requested)</span>
            @endif
          </td>
        </tr>
        <tr>
          <th>Multiple Destinations Checkbox</th>
          <td>
            @if($isMultiDest)
              <span class="badge-multi">YES — Pilot Requested Multiple Destinations (Max 4 Return Pairs)</span>
            @else
              No (Single Destination Request)
            @endif
          </td>
        </tr>
        <tr>
          <th>Current Status</th>
          <td><span class="badge">{{ $routeRequest->status_label }}</span></td>
        </tr>
        @if($genFlights->count() > 0)
          <tr>
            <th>Generated Flight Schedule(s)</th>
            <td>
              @foreach($genFlights as $gf)
                @php $gfCode = optional($gf->airline)->icao ?? optional($gf->airline)->code ?? ''; @endphp
                <div>
                  <strong>{{ $gfCode }}{{ $gf->flight_number }}</strong>: {{ $gf->dpt_airport_id }} &rarr; {{ $gf->arr_airport_id }}
                  @if(!empty($gf->level))
                    (Alt: {{ $gf->level }} ft)
                  @endif
                </div>
              @endforeach
            </td>
          </tr>
        @endif
        @if($routeRequest->notes)
          <tr>
            <th>Pilot Remarks</th>
            <td>{{ $routeRequest->notes }}</td>
          </tr>
        @endif
        @if($routeRequest->admin_notes)
          <tr>
            <th>Dispatch / Staff Notes</th>
            <td><strong>{{ $routeRequest->admin_notes }}</strong></td>
          </tr>
        @endif
        <tr>
          <th>Submitted At</th>
          <td>{{ $routeRequest->created_at }}</td>
        </tr>
      </table>

      <p style="text-align: center; margin-top: 18px;">
        @if($genFlights->count() > 0)
          @foreach($genFlights as $gf)
            @php $gfCode = optional($gf->airline)->icao ?? optional($gf->airline)->code ?? ''; @endphp
            <a href="{{ url('/flights/' . $gf->id) }}" class="btn btn-success">
              Fly / Bid: {{ $gfCode }}{{ $gf->flight_number }} ({{ $gf->dpt_airport_id }} &rarr; {{ $gf->arr_airport_id }})
            </a>
          @endforeach
        @endif
        <a href="{{ url('/admin/routerequest') }}" class="btn">Manage in phpVMS Admin Panel</a>
      </p>
    </div>
    <div class="footer">
      Fly Fantasy Air &bull; phpVMS v7 RouteRequest Module v3.0.7 — Afterburner Edition (admin@flyfantasyair.net)<br>
      Made by Alexander Leard of Fantasy Air VA &amp; Regal Artistic Avenue
    </div>
  </div>
</body>
</html>
