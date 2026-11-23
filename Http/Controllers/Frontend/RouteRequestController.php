<?php

declare(strict_types=1);

namespace Modules\RouteRequest\Http\Controllers\Frontend;

use App\Contracts\Controller;
use App\Models\Airport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Modules\RouteRequest\Http\Requests\CreateRouteRequest;
use Modules\RouteRequest\Mail\RouteRequestSubmittedMail;
use Modules\RouteRequest\Models\RouteRequest;
use Modules\RouteRequest\Support\RealWorldAirlines;
use Modules\RouteRequest\Support\DrmEntitlements;
use Modules\RouteRequest\Support\ClientSettings;
use Modules\RouteRequest\Support\DiscordNotifier;
use Modules\RouteRequest\Support\RouteEstimatorService;
use Modules\RouteRequest\Support\TravelDispatchService;
use Modules\RouteRequest\Support\VatsimOverlayService;
use Modules\RouteRequest\Support\SchemaHelper;
use Nwidart\Modules\Facades\Module;

class RouteRequestController extends Controller
{
    public function __construct()
    {
        if (version_compare(PHP_VERSION, '8.3.36', '<')) {
            abort(503, 'RouteRequest v3.2.0-beta.2 requires PHP 8.3.36 or newer (PHP 8.3.37+ recommended). Detected PHP ' . PHP_VERSION . '.');
        }
    }

    protected function ensureColumnsExist(): void
    {
        SchemaHelper::ensureRouteRequestsColumns();
    }

    public function index(Request $request): View
    {
        $this->ensureColumnsExist();

        $user = Auth::user();
        $user->loadMissing('typeratings');

        $pilotTypeRatings = $user->typeratings
            ? $user->typeratings->where('active', true)->sortBy('type')
            : collect();

        $realworldAirlines = RealWorldAirlines::all();

        $hasDispoBasic    = Module::find('DisposableBasic') && Module::find('DisposableBasic')->isEnabled();
        $hasDispoSpecial  = Module::find('DisposableSpecial') && Module::find('DisposableSpecial')->isEnabled();
        $hasDispoAirports = Module::find('DisposableAirports') && Module::find('DisposableAirports')->isEnabled();

        $defaultDep = strtoupper((string) old('dep_airport_id', $request->query('dep', $user->curr_airport_id ?? '')));
        $defaultArr = strtoupper((string) old('arr_airport_id', $request->query('arr', '')));

        $defaultTr = $request->query('typerating_id', '');
        if ($defaultTr === '' && $pilotTypeRatings->count() === 1) {
            $defaultTr = (string) $pilotTypeRatings->first()->id;
        }

        $defaultDepAirport = $defaultDep ? Airport::select('id', 'name', 'icao', 'iata', 'location', 'lat', 'lon')->find($defaultDep) : null;
        $defaultArrAirport = $defaultArr ? Airport::select('id', 'name', 'icao', 'iata', 'location', 'lat', 'lon')->find($defaultArr) : null;

        $myRequests = collect();
        try {
            $myRequests = RouteRequest::with(['dep_airport', 'arr_airport'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(25)
                ->get();

            // v2.0.0 Afterburner: Batch-load all generated flights across all 25 rows in 1 single query
            RouteRequest::hydrateGeneratedFlights($myRequests);
        } catch (\Exception $e) {
            // Table may not be migrated yet
        }
        $drmEntitlements = DrmEntitlements::current(false);
        $defaultEmail = config('routerequest.recipient_email', 'admin@flyfantasyair.net') ?: 'admin@flyfantasyair.net';
        $cacheHash = SchemaHelper::getAirportsCacheHash();

        return view('routerequest::frontend.index', [
            'user'              => $user,
            'pilotTypeRatings'  => $pilotTypeRatings,
            'realworldAirlines' => $realworldAirlines,
            'myRequests'        => $myRequests,
            'defaultEmail'      => $defaultEmail,
            'defaultDep'        => $defaultDep,
            'defaultArr'        => $defaultArr,
            'defaultDepAirport' => $defaultDepAirport,
            'defaultArrAirport' => $defaultArrAirport,
            'defaultTr'         => $defaultTr,
            'hasDispoBasic'     => $hasDispoBasic,
            'hasDispoSpecial'   => $hasDispoSpecial,
            'hasDispoAirports'  => $hasDispoAirports,
            'cacheHash'         => $cacheHash,
            'drmEntitlements'   => $drmEntitlements,
            'rangeTable'          => RouteEstimatorService::getRangeTableForFrontend(),
            'travelFeatureActive' => TravelDispatchService::isFeatureActive($drmEntitlements),
            'myMoveRequests'      => (function () use ($user) {
                TravelDispatchService::ensureMoveRequestsTableExists();
                return \Illuminate\Support\Facades\DB::table('routerequest_move_requests')
                    ->where('user_id', $user->id)
                    ->orderBy('id', 'desc')
                    ->limit(5)
                    ->get();
            })(),
        ]);
    }

    /**
     * v2.0.0 Afterburner Edition:
     * Returns the full phpVMS `airports` snapshot + `realworld_airlines` list + version/hash
     * so the browser can store them in persistent `IndexedDB` (`RouteRequestAfterburnerDB`).
     */
    public function localCacheBundle(Request $request): JsonResponse
    {

        $airports = Airport::select('id', 'icao', 'iata', 'name', 'location', 'country', 'hub', 'lat', 'lon')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($a) {
                $iata = !empty($a->iata) ? ' / ' . $a->iata : '';
                $loc  = !empty($a->location) ? ' (' . $a->location . ')' : '';
                $hub  = $a->hub ? ' [HUB]' : '';
                $text = $a->id . $iata . ' — ' . $a->name . $loc . $hub;
                return [
                    'id'     => strtoupper((string) $a->id),
                    'icao'   => strtoupper((string) ($a->icao ?? $a->id)),
                    'iata'   => strtoupper((string) ($a->iata ?? '')),
                    'name'   => (string) $a->name,
                    'loc'    => (string) ($a->location ?? ''),
                    'lat'    => (float) ($a->lat ?? 0),
                    'lon'    => (float) ($a->lon ?? 0),
                    'text'   => $text,
                    'search' => strtoupper($a->id . ' ' . ($a->icao ?? '') . ' ' . ($a->iata ?? '') . ' ' . $a->name . ' ' . ($a->location ?? '')),
                ];
            })
            ->values();

        return response()->json([
            'version'   => '3.2.0-beta.2',
            'edition'   => 'Afterburner Edition',
            'hash'      => SchemaHelper::getAirportsCacheHash(),
            'count'     => $airports->count(),
            'airports'  => $airports,
            'airlines'  => RealWorldAirlines::all(),
        ])->header('Cache-Control', 'private, max-age=3600');
    }

    public function searchAirports(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', $request->get('term', '')));

        $query = Airport::select('id', 'icao', 'iata', 'name', 'location', 'country', 'hub', 'lat', 'lon');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('id', 'LIKE', '%' . $q . '%')
                    ->orWhere('icao', 'LIKE', '%' . $q . '%')
                    ->orWhere('iata', 'LIKE', '%' . $q . '%')
                    ->orWhere('name', 'LIKE', '%' . $q . '%')
                    ->orWhere('location', 'LIKE', '%' . $q . '%');
            });
        }

        $results = $query->orderByRaw(
            "CASE
                WHEN id = ? THEN 0
                WHEN iata = ? THEN 1
                WHEN id LIKE ? THEN 2
                WHEN iata LIKE ? THEN 3
                ELSE 4
            END, id ASC",
            [strtoupper($q), strtoupper($q), strtoupper($q) . '%', strtoupper($q) . '%']
        )
        ->limit(75)
        ->get()
        ->map(function ($a) {
            $iata = !empty($a->iata) ? ' / ' . $a->iata : '';
            $loc  = !empty($a->location) ? ' (' . $a->location . ')' : '';
            $hub  = $a->hub ? ' [HUB]' : '';
            return [
                'id'   => $a->id,
                'lat'  => (float) ($a->lat ?? 0),
                'lon'  => (float) ($a->lon ?? 0),
                'text' => $a->id . $iata . ' — ' . $a->name . $loc . $hub,
            ];
        });

        return response()->json([
            'results' => $results,
            'data'    => $results,
        ]);
    }

    public function store(CreateRouteRequest $request): RedirectResponse
    {
        $this->ensureColumnsExist();

        $user = Auth::user();
        $user->loadMissing('typeratings');

        $validated = $request->validated();

        // v3.2.0-beta.2: Enforce Pilot Max Open Requests & Cooldown Settings
        $clientSettings = ClientSettings::all();
        $maxOpen = (int) ($clientSettings['max_open_requests_per_pilot'] ?? 0);
        $cooldownMins = (int) ($clientSettings['pilot_cooldown_minutes'] ?? 0);

        if ($maxOpen > 0) {
            $openCount = RouteRequest::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'approved', 'in_progress'])
                ->count();
            if ($openCount >= $maxOpen) {
                flash()->warning("Maximum Open Requests Reached: You currently have {$openCount}/{$maxOpen} open route request(s). Please wait for Dispatch to complete an existing request or cancel a pending one.");
                return redirect()->route('routerequest.index')->withInput();
            }
        }

        if ($cooldownMins > 0) {
            $lastReq = RouteRequest::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->first();
            if ($lastReq && $lastReq->created_at) {
                $nextAllowed = $lastReq->created_at->copy()->addMinutes($cooldownMins);
                if ($nextAllowed->isFuture()) {
                    $minsLeft = max(1, (int) now()->diffInMinutes($nextAllowed, false));
                    flash()->warning("Request Cooldown Active: Please wait {$minsLeft} more minute(s) before submitting another route request.");
                    return redirect()->route('routerequest.index')->withInput();
                }
            }
        }

        $selectedRating = $user->typeratings
            ->where('active', true)
            ->firstWhere('id', (int) $validated['typerating_id']);

        if (!$selectedRating) {
            flash()->error('You are not signed off on the selected Type Rating.');
            return redirect()->route('routerequest.index');
        }

        $formattedTypeRating = trim($selectedRating->type . ' - ' . $selectedRating->name);

        $airlineName = $validated['realworld_airline'] === 'OTHER'
            ? trim($validated['realworld_airline_other'])
            : trim($validated['realworld_airline']);

        $multipleDest  = filter_var($request->input('multiple_destinations', false), FILTER_VALIDATE_BOOLEAN);
        $requestReturn = filter_var($request->input('request_return', false), FILTER_VALIDATE_BOOLEAN);
        $depAirport    = strtoupper(trim($validated['dep_airport_id']));
        $arrAirport    = (!$multipleDest && !empty($validated['arr_airport_id']))
            ? strtoupper(trim($validated['arr_airport_id']))
            : null;

        $duplicateQuery = RouteRequest::whereIn('status', ['pending', 'approved', 'in_progress'])
            ->where('dep_airport_id', $depAirport)
            ->where('realworld_airline', $airlineName)
            ->where(function ($q) use ($selectedRating, $formattedTypeRating) {
                $q->where('typerating_id', $selectedRating->id)
                  ->orWhere('aircraft_type', $formattedTypeRating);
            });

        if ($arrAirport === null) {
            $duplicateQuery->whereNull('arr_airport_id');
        } else {
            $duplicateQuery->where('arr_airport_id', $arrAirport);
        }

        $existingDuplicate = $duplicateQuery->first();
        if ($existingDuplicate) {
            flash()->warning(
                'Duplicate Request Prevented: Request #' . $existingDuplicate->id .
                ' for ' . $depAirport . ' -> ' . ($arrAirport ?: 'ANY') .
                ' (' . $airlineName . ' / ' . $formattedTypeRating . ') is already ' .
                $existingDuplicate->status_label . '!'
            );
            return redirect()->route('routerequest.index')->withInput();
        }

        $defaultEmail = config('routerequest.recipient_email', 'admin@flyfantasyair.net') ?: 'admin@flyfantasyair.net';
        $targetEmail = !empty($validated['recipient_email'])
            ? trim($validated['recipient_email'])
            : $defaultEmail;

        $createData = [
            'user_id'               => $user->id,
            'typerating_id'         => $selectedRating->id,
            'aircraft_type'         => $formattedTypeRating,
            'realworld_airline'     => $airlineName,
            'dep_airport_id'        => $depAirport,
            'arr_airport_id'        => $arrAirport,
            'multiple_destinations' => $multipleDest,
            'request_return'        => $requestReturn,
            'recipient_email'       => $targetEmail,
            'notes'                 => $validated['notes'] ?? null,
            'status'                => 'pending',
        ];

        try {
            $routeRequest = RouteRequest::create($createData);
        } catch (\Throwable $insertErr) {
            // Self-heal schema if cache was stale and retry
            SchemaHelper::ensureRouteRequestsColumns(true);
            $routeRequest = RouteRequest::create($createData);
        }

        $routeRequest->multiple_destinations = $multipleDest;
        $routeRequest->request_return        = $requestReturn;
        $routeRequest->load(['user', 'dep_airport', 'arr_airport']);

        $destText = $arrAirport ?: ($multipleDest ? 'MULTIPLE DESTINATIONS (ANY)' : 'OPEN');
        $tripType = $requestReturn ? 'Round-Trip' : 'One-Way';
        flash()->success('Route request (' . $depAirport . ' -> ' . $destText . ' [' . $tripType . '] | ' . $formattedTypeRating . ') submitted and dispatched to ' . $targetEmail . '!');

        // v2.0.0 Afterburner: Send email after HTTP response so redirect is instant!
        $userEmail = $user->email;
        app()->terminating(function () use ($routeRequest, $targetEmail, $defaultEmail, $userEmail) {
            try {
                $mail = Mail::to($targetEmail);
                if ($defaultEmail && strtolower($defaultEmail) !== strtolower($targetEmail)) {
                    $mail->cc($defaultEmail);
                }
                if (config('routerequest.cc_pilot', true) && !empty($userEmail) && strtolower($userEmail) !== strtolower($targetEmail)) {
                    $mail->bcc($userEmail);
                }
                $mail->send(new RouteRequestSubmittedMail($routeRequest, false));
            } catch (\Throwable $e) {
                Log::error('RouteRequest Background Email Error: ' . $e->getMessage());
            }
            try {
                DiscordNotifier::notifyStaffNewRequest($routeRequest);
            } catch (\Throwable $e2) {}
        });

        return redirect()->route('routerequest.index');
    }

    public function cancel(int $id): RedirectResponse
    {
        $user = Auth::user();
        $routeRequest = RouteRequest::with(['user', 'dep_airport', 'arr_airport'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($routeRequest->status !== 'pending') {
            flash()->warning('Request #' . $routeRequest->id . ' can no longer be cancelled because it is already ' . $routeRequest->status_label . '.');
            return redirect()->route('routerequest.index');
        }

        $routeRequest->status = 'cancelled';
        $routeRequest->save();

        $defaultEmail = config('routerequest.recipient_email', 'admin@flyfantasyair.net') ?: 'admin@flyfantasyair.net';
        $recipients = array_unique(array_filter([
            $routeRequest->recipient_email,
            $defaultEmail,
            $user->email,
        ]));

        flash()->info('Route request #' . $routeRequest->id . ' has been marked as Cancelled by Pilot and dispatch has been notified.');

        app()->terminating(function () use ($routeRequest, $recipients) {
            try {
                if (!empty($recipients)) {
                    Mail::to($recipients)->send(new RouteRequestSubmittedMail($routeRequest, true));
                }
            } catch (\Throwable $e) {
                Log::error('RouteRequest Background Cancel Email Error: ' . $e->getMessage());
            }
        });

        return redirect()->route('routerequest.index');
    }

    /**
     * ONLY called when a pilot's route exceeds the Aircraft Range Guardrail.
     * Quickly queries FlightAware & Flightradar24 to verify if the route is flown in the real world.
     */
    public function verifyRangeOverride(Request $request): JsonResponse
    {
        $ent = DrmEntitlements::current(false);
        if (empty($ent['features']['range_guardrail_live_check'])) {
            return response()->json([
                'verified' => false,
                'locked'   => true,
                'message'  => 'On-demand FlightAware/FR24 live range verification unlocks for Executive on Jan 15, 2027 (or is available immediately on Tier 3 Flagship).',
            ]);
        }
        $dep = strtoupper(trim((string) $request->input('dep_airport_id', '')));
        $arr = strtoupper(trim((string) $request->input('arr_airport_id', '')));

        $result = RouteEstimatorService::verifyRealWorldRoute($dep, $arr);
        return response()->json($result);
    }

    public function travelOptions(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }
        if (!TravelDispatchService::isFeatureActive()) {
            return response()->json(['success' => true, 'needs_reposition' => false]);
        }
        $flightId = trim((string) $request->input('flight_id', ''));
        $sortBy = (string) $request->input('sort_by', 'cheapest');
        $res = TravelDispatchService::buildTravelOptions($user, $flightId, $sortBy);
        return response()->json($res, !empty($res['success']) ? 200 : 422);
    }

    public function executeTravel(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }
        if (!TravelDispatchService::isFeatureActive()) {
            return response()->json(['success' => false, 'message' => 'Guided travel is not active on this VA.'], 403);
        }
        $flightId = trim((string) $request->input('flight_id', ''));
        $aircraftId = (int) $request->input('aircraft_id', 0);
        $res = TravelDispatchService::executeSelfTravel($user, $flightId, $aircraftId);
        return response()->json($res, !empty($res['success']) ? 200 : 422);
    }

    public function requestAdminMove(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }
        if (!TravelDispatchService::isFeatureActive()) {
            return response()->json(['success' => false, 'message' => 'Guided travel is not active on this VA.'], 403);
        }
        $flightId = trim((string) $request->input('flight_id', ''));
        $aircraftId = (int) $request->input('aircraft_id', 0);
        $notes = (string) $request->input('notes', '');
        $res = TravelDispatchService::requestAdminMove($user, $flightId, $aircraftId, $notes);
        return response()->json($res, !empty($res['success']) ? 200 : 422);
    }

    public function vatsimAtcOverlay(): JsonResponse
    {
        $ent = DrmEntitlements::current(false);
        if (empty($ent['features']['vatsim_atc_overlay'])) {
            return response()->json(['success' => true, 'airports' => []]);
        }
        return response()->json([
            'success'  => true,
            'airports' => VatsimOverlayService::getActiveAtcAirports(),
        ]);
    }
}
