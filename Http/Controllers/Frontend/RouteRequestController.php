<?php

declare(strict_types=1);

namespace Modules\AfterburnerDispatch\Http\Controllers\Frontend;

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
use Modules\AfterburnerDispatch\Http\Requests\CreateRouteRequest;
use Modules\AfterburnerDispatch\Mail\RouteRequestSubmittedMail;
use Modules\AfterburnerDispatch\Models\RouteRequest;
use Modules\AfterburnerDispatch\Support\RealWorldAirlines;
use Modules\AfterburnerDispatch\Support\DrmEntitlements;
use Modules\AfterburnerDispatch\Support\ClientSettings;
use Modules\AfterburnerDispatch\Support\DiscordNotifier;
use Modules\AfterburnerDispatch\Support\RouteEstimatorService;
use Modules\AfterburnerDispatch\Support\TravelDispatchService;
use Modules\AfterburnerDispatch\Support\VatsimOverlayService;
use Modules\AfterburnerDispatch\Support\SchemaHelper;
use Nwidart\Modules\Facades\Module;

class RouteRequestController extends Controller
{
    public function __construct()
    {
        if (is_file(__DIR__ . '/../../../Support/VaultLoader.php')) {
            if (!class_exists(\Modules\AfterburnerDispatch\Support\VaultLoader::class, false)) {
                require_once __DIR__ . '/../../../Support/VaultLoader.php';
            }
            \Modules\AfterburnerDispatch\Support\VaultLoader::loadAll();
        }

        if (version_compare(PHP_VERSION, '8.3.36', '<')) {
            abort(503, 'RouteRequest v4.0.0 requires PHP 8.3.36 or newer (PHP 8.3.37+ recommended). Detected PHP ' . PHP_VERSION . '.');
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
        $defaultEmail = config('afterburner.recipient_email', 'admin@flyfantasyair.net') ?: 'admin@flyfantasyair.net';
        $cacheHash = SchemaHelper::getAirportsCacheHash();

        $pilotPrefs = \Modules\AfterburnerDispatch\Support\SceneryService::getPilotPrefs((int) $user->id);
        $skyAdventuresLegs = \Modules\AfterburnerDispatch\Support\SkyAdventuresBridge::getPilotNextLegs((int) $user->id);

        return view('afterburner::frontend.index', [
            'pilotPrefs'          => $pilotPrefs,
            'clientSettings'      => ClientSettings::all(),
            'supportedSimulators' => \Modules\AfterburnerDispatch\Support\SceneryService::SIMULATORS,
            'sceneryPrefsList'    => \Modules\AfterburnerDispatch\Support\SceneryService::SCENERY_PREFS,
            'skyAdventuresLegs'   => $skyAdventuresLegs,
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
            'communityQueue'      => (function () use ($drmEntitlements) {
                if (empty($drmEntitlements['features']['community_queue'])) {
                    return collect();
                }
                $upvMap = [];
                try {
                    if (\Illuminate\Support\Facades\Schema::hasTable('routerequest_upvotes')) {
                        foreach (\Illuminate\Support\Facades\DB::table('routerequest_upvotes')->select('route_request_id', \Illuminate\Support\Facades\DB::raw('COUNT(*) as cnt'))->groupBy('route_request_id')->get() as $r) {
                            $upvMap[(int) $r->route_request_id] = (int) $r->cnt;
                        }
                    }
                } catch (\Throwable $e) {}
                return RouteRequest::with(['user', 'dep_airport', 'arr_airport', 'typerating'])
                    ->whereIn('status', ['pending', 'in_progress', 'approved'])
                    ->orderBy('id', 'desc')
                    ->limit(50)
                    ->get()
                    ->each(static function ($item) use ($upvMap) {
                        $item->upvotes_count = $upvMap[(int) $item->id] ?? 0;
                    })
                    ->sortByDesc(static function ($item) {
                        return ((int) $item->upvotes_count) * 1000000 + (int) $item->id;
                    })
                    ->values();
            })(),
            'myUpvotedIds'        => (function () use ($user, $drmEntitlements) {
                if (empty($drmEntitlements['features']['community_queue'])) {
                    return [];
                }
                try {
                    if (\Illuminate\Support\Facades\Schema::hasTable('routerequest_upvotes')) {
                        return \Illuminate\Support\Facades\DB::table('routerequest_upvotes')
                            ->where('user_id', $user->id)
                            ->pluck('route_request_id')
                            ->map(static fn($v) => (int) $v)
                            ->all();
                    }
                } catch (\Throwable $e) {}
                return [];
            })(),
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
            'version'   => '4.1.0-beta.7',
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

        // v4.0.0: Enforce Pilot Max Open Requests & Cooldown Settings
        $clientSettings = ClientSettings::all();
        $maxOpen = (int) ($clientSettings['max_open_requests_per_pilot'] ?? 0);
        $cooldownMins = (int) ($clientSettings['pilot_cooldown_minutes'] ?? 0);

        if ($maxOpen > 0) {
            $openCount = RouteRequest::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'approved', 'in_progress'])
                ->count();
            if ($openCount >= $maxOpen) {
                flash()->warning("Maximum Open Requests Reached: You currently have {$openCount}/{$maxOpen} open route request(s). Please wait for Dispatch to complete an existing request or cancel a pending one.");
                return redirect()->route('afterburner.index')->withInput();
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
                    return redirect()->route('afterburner.index')->withInput();
                }
            }
        }

        $selectedRating = $user->typeratings
            ->where('active', true)
            ->firstWhere('id', (int) $validated['typerating_id']);

        if (!$selectedRating) {
            flash()->error('You are not signed off on the selected Type Rating.');
            return redirect()->route('afterburner.index');
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
            return redirect()->route('afterburner.index')->withInput();
        }

        $defaultEmail = config('afterburner.recipient_email', 'admin@flyfantasyair.net') ?: 'admin@flyfantasyair.net';
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
                if (config('afterburner.cc_pilot', true) && !empty($userEmail) && strtolower($userEmail) !== strtolower($targetEmail)) {
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

        return redirect()->route('afterburner.index');
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
            return redirect()->route('afterburner.index');
        }

        $routeRequest->status = 'cancelled';
        $routeRequest->save();

        $defaultEmail = config('afterburner.recipient_email', 'admin@flyfantasyair.net') ?: 'admin@flyfantasyair.net';
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

        return redirect()->route('afterburner.index');
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

    public function toggleUpvote(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }
        $ent = DrmEntitlements::current(false);
        if (empty($ent['features']['community_queue'])) {
            return response()->json(['success' => false, 'message' => 'Community Route Queue & Upvoting is not yet unlocked for your tier.'], 403);
        }

        SchemaHelper::ensureRouteRequestsColumns(false);
        $req = RouteRequest::find($id);
        if (!$req || !in_array($req->status, ['pending', 'in_progress', 'approved'], true)) {
            return response()->json(['success' => false, 'message' => 'Only open route requests can be upvoted.'], 404);
        }
        if ((int) $req->user_id === (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'You cannot upvote your own route request.'], 422);
        }

        $exists = \Illuminate\Support\Facades\DB::table('routerequest_upvotes')
            ->where('route_request_id', $req->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            \Illuminate\Support\Facades\DB::table('routerequest_upvotes')
                ->where('route_request_id', $req->id)
                ->where('user_id', $user->id)
                ->delete();
            $upvoted = false;
        } else {
            \Illuminate\Support\Facades\DB::table('routerequest_upvotes')->insert([
                'route_request_id' => $req->id,
                'user_id'          => $user->id,
                'created_at'       => now(),
            ]);
            $upvoted = true;
        }

        $count = (int) \Illuminate\Support\Facades\DB::table('routerequest_upvotes')
            ->where('route_request_id', $req->id)
            ->count();

        return response()->json([
            'success' => true,
            'upvoted' => $upvoted,
            'count'   => $count,
        ]);
    }

    public function checkDuplicate(Request $request): JsonResponse
    {
        $ent = DrmEntitlements::current(false);
        if (empty($ent['features']['duplicate_route_guard'])) {
            return response()->json(['enabled' => false, 'flights' => [], 'open_requests' => []]);
        }

        $dep = strtoupper(trim((string) $request->query('dep', '')));
        $arr = strtoupper(trim((string) $request->query('arr', '')));
        if (strlen($dep) < 3 || strlen($arr) < 3) {
            return response()->json(['enabled' => true, 'flights' => [], 'open_requests' => []]);
        }

        $flights = [];
        try {
            $rows = \App\Models\Flight::with('airline')
                ->where('dpt_airport_id', $dep)
                ->where('arr_airport_id', $arr)
                ->where('active', true)
                ->where('visible', true)
                ->limit(5)
                ->get();
            foreach ($rows as $fl) {
                $ident = ($fl->airline ? $fl->airline->icao : '') . $fl->flight_number;
                $flights[] = [
                    'id'    => $fl->id,
                    'ident' => $ident,
                    'url'   => route('frontend.flights.show', [$fl->id]),
                ];
            }
        } catch (\Throwable $e) {}

        $openRequests = [];
        try {
            $userId = Auth::id();
            $reqs = RouteRequest::with('user')
                ->where('dep_airport_id', $dep)
                ->where('arr_airport_id', $arr)
                ->whereIn('status', ['pending', 'in_progress', 'approved'])
                ->orderBy('id', 'desc')
                ->limit(5)
                ->get();
            $upvCounts = [];
            $reqIds = $reqs->pluck('id')->all();
            if (!empty($reqIds)) {
                try {
                    if (\Illuminate\Support\Facades\Schema::hasTable('routerequest_upvotes')) {
                        $upvCounts = \Illuminate\Support\Facades\DB::table('routerequest_upvotes')
                            ->whereIn('route_request_id', $reqIds)
                            ->select('route_request_id', \Illuminate\Support\Facades\DB::raw('COUNT(*) as cnt'))
                            ->groupBy('route_request_id')
                            ->pluck('cnt', 'route_request_id')
                            ->toArray();
                    }
                } catch (\Throwable $e) {}
            }
            foreach ($reqs as $r) {
                $openRequests[] = [
                    'id'       => (int) $r->id,
                    'pilot'    => $r->user ? $r->user->ident : ('Pilot #' . $r->user_id),
                    'is_own'   => ((int) $r->user_id === (int) $userId),
                    'upvotes'  => (int) ($upvCounts[$r->id] ?? 0),
                    'status'   => $r->status,
                ];
            }
        } catch (\Throwable $e) {}

        return response()->json([
            'enabled'       => true,
            'dep'           => $dep,
            'arr'           => $arr,
            'flights'       => $flights,
            'open_requests' => $openRequests,
        ]);
    }

    public function savePilotPrefs(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }
        $sims = (array) $request->input('simulators', []);
        $pref = (string) $request->input('scenery_pref', 'both');
        $saved = \Modules\AfterburnerDispatch\Support\SceneryService::savePilotPrefs((int) $user->id, $sims, $pref);
        return response()->json(['success' => true, 'prefs' => $saved]);
    }

    public function lookupScenery(Request $request)
    {
        $user = Auth::user();
        $prefs = $user ? \Modules\AfterburnerDispatch\Support\SceneryService::getPilotPrefs((int) $user->id) : ['simulators' => ['msfs2024_pc'], 'scenery_pref' => 'both'];
        $icaos = (array) $request->input('icaos', []);
        $sims = (array) $request->input('simulators', $prefs['simulators'] ?: ['msfs2024_pc']);
        $sceneryPref = (string) $request->input('scenery_pref', $prefs['scenery_pref'] ?? 'both');
        $forceRefresh = (bool) $request->input('force_refresh', false);
        $data = \Modules\AfterburnerDispatch\Support\SceneryService::lookupForAirports($icaos, $sims, $sceneryPref, true, $forceRefresh);
        return response()->json(['success' => true, 'airports' => $data]);
    }

}
