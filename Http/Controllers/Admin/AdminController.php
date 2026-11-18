<?php

namespace Modules\RouteRequest\Http\Controllers\Admin;

use App\Contracts\Controller;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Enums\Days;
use App\Models\Enums\FlightType;
use App\Models\Flight;
use App\Models\Subfleet;
use App\Services\AirportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Modules\RouteRequest\Mail\RouteRequestSubmittedMail;
use Modules\RouteRequest\Models\RouteRequest;
use Modules\RouteRequest\Support\DrmEntitlements;
use Modules\RouteRequest\Support\FlightAwareParser;
use Modules\RouteRequest\Support\RealWorldAirlines;
use Modules\RouteRequest\Support\SchemaHelper;
use Modules\RouteRequest\Support\UpdateManager;

class AdminController extends Controller
{
    public const MAX_MULTI_RETURN_PAIRS = 4;
    public const MAX_ROUTES_PER_REQUEST = 15;

    protected function ensureColumnsExist(): void
    {
        if (!class_exists(SchemaHelper::class, false)) {
            require_once __DIR__ . '/../../../Support/SchemaHelper.php';
        }
        SchemaHelper::ensureRouteRequestsColumns();
    }

    protected function getEntitlements(bool $forceRefresh = false): array
    {
        if (!class_exists(DrmEntitlements::class, false)) {
            require_once __DIR__ . '/../../../Support/DrmEntitlements.php';
        }
        return DrmEntitlements::current($forceRefresh);
    }

    protected function computeNextFlightNumbers($airlines): array
    {
        $map = [];
        foreach ($airlines as $airline) {
            $map[$airline->id] = 1;
        }

        try {
            $rows = DB::table('flights')
                ->selectRaw('airline_id, MAX(CAST(flight_number AS UNSIGNED)) as max_num')
                ->groupBy('airline_id')
                ->get();

            foreach ($rows as $r) {
                $max = (int) ($r->max_num ?? 0);
                $map[(int) $r->airline_id] = $max > 0 ? ($max + 1) : 1;
            }
        } catch (\Throwable $e) {
            // Fallback if SQL dialect differs
        }

        return $map;
    }

    public function index(Request $request): View
    {
        $this->ensureColumnsExist();
        $this->repairPreviouslyGeneratedFlightsOnce();

        $drmEntitlements = $this->getEntitlements(false);
        $maxReturnPairs = (int) ($drmEntitlements['max_return_pairs'] ?? self::MAX_MULTI_RETURN_PAIRS);
        $maxRoutesPerReq = (int) ($drmEntitlements['max_routes_per_req'] ?? self::MAX_ROUTES_PER_REQUEST);

        $requests = RouteRequest::with(['user', 'typerating.subfleets.aircraft', 'typerating.subfleets.airline', 'dep_airport', 'arr_airport'])
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        RouteRequest::hydrateGeneratedFlights($requests);

        $airlines = Airline::where('active', true)->orderBy('name', 'asc')->get();
        if ($airlines->isEmpty()) {
            $airlines = Airline::orderBy('name', 'asc')->get();
        }

        $nextFlightNumbers = $this->computeNextFlightNumbers($airlines);

        $subfleets = Subfleet::with(['airline', 'aircraft'])->orderBy('name', 'asc')->get();

        $flightTypes = [
            'J' => 'J — Scheduled Pax (Passenger Normal)',
            'C' => 'C — Charter Pax Only',
            'F' => 'F — Scheduled Cargo / Freighter',
            'A' => 'A — Additional Cargo / Mail',
            'H' => 'H — Charter Cargo / Mail',
            'E' => 'E — Special VIP Flight',
            'G' => 'G — Additional Pax (Shuttle)',
            'B' => 'B — Charter Special (Bus/Shuttle)',
            'M' => 'M — Mail Service',
            'O' => 'O — Charter Requiring Special Handling',
            'P' => 'P — Non-Revenue (Positioning / Ferry)',
            'T' => 'T — Technical / Training Test Flight',
            'W' => 'W — Military Flight',
            'K' => 'K — Crew Training Flight',
        ];
        if (class_exists(FlightType::class) && method_exists(FlightType::class, 'select')) {
            try {
                $enumTypes = FlightType::select();
                if (!empty($enumTypes)) {
                    $flightTypes = $enumTypes;
                }
            } catch (\Throwable $e) {}
        }

        $recipientEmail = config('routerequest.recipient_email', 'admin@flyfantasyair.net') ?: 'admin@flyfantasyair.net';
        $openGenId = (int) $request->query('open_gen', 0);
        $cacheHash = SchemaHelper::getAirportsCacheHash();

        if (!class_exists(UpdateManager::class, false)) {
            require_once __DIR__ . '/../../../Support/UpdateManager.php';
        }
        $updateInfo = UpdateManager::check(false);

        return view('routerequest::admin.index', [
            'requests'          => $requests,
            'airlines'          => $airlines,
            'nextFlightNumbers' => $nextFlightNumbers,
            'subfleets'         => $subfleets,
            'flightTypes'       => $flightTypes,
            'recipientEmail'    => $recipientEmail,
            'openGenId'         => $openGenId,
            'maxReturnPairs'    => $maxReturnPairs,
            'maxRoutesPerReq'   => $maxRoutesPerReq,
            'drmEntitlements'   => $drmEntitlements,
            'cacheHash'         => $cacheHash,
            'updateInfo'        => $updateInfo,
        ]);
    }

    public function localCacheBundle(Request $request): JsonResponse
    {
        if (!class_exists(SchemaHelper::class, false)) {
            require_once __DIR__ . '/../../../Support/SchemaHelper.php';
        }

        $airports = Airport::select('id', 'icao', 'iata', 'name', 'location', 'country', 'hub')
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
                    'text'   => $text,
                    'search' => strtoupper($a->id . ' ' . ($a->icao ?? '') . ' ' . ($a->iata ?? '') . ' ' . $a->name . ' ' . ($a->location ?? '')),
                ];
            })
            ->values();

        return response()->json([
            'version'   => '2.2.0',
            'edition'   => 'Afterburner Edition',
            'hash'      => SchemaHelper::getAirportsCacheHash(),
            'count'     => $airports->count(),
            'airports'  => $airports,
            'airlines'  => RealWorldAirlines::all(),
        ])->header('Cache-Control', 'private, max-age=3600');
    }

    public function parseFlightAware(Request $request, int $id): JsonResponse
    {
        $drmEntitlements = $this->getEntitlements(false);
        if (empty($drmEntitlements['features']['fa_auto_matcher'])) {
            return response()->json([
                'success' => false,
                'error'   => 'FlightAware Route & Altitude Auto-Matcher requires Tier 2 (Executive Dispatch Edition) or Tier 3 (Flagship Edition).',
            ], 403);
        }

        if (!class_exists(FlightAwareParser::class, false)) {
            require_once __DIR__ . '/../../../Support/FlightAwareParser.php';
        }

        $routeRequest = RouteRequest::findOrFail($id);
        $depIcao = strtoupper(trim((string) $request->input('dep_airport_id', $routeRequest->dep_airport_id)));
        $arrIcao = strtoupper(trim((string) $request->input('arr_airport_id', $routeRequest->arr_airport_id ?? '')));

        $notesToParse = (string) $request->input('custom_urls', $routeRequest->notes ?? '');
        $matched = FlightAwareParser::parseAndMatchFromNotes($notesToParse, $depIcao, $arrIcao ?: null);

        $detectedArrAirport = null;
        if (!empty($matched['outbound']['destination'])) {
            $ap = $this->resolveAndVerifyAirport($matched['outbound']['destination']);
            if ($ap) {
                $detectedArrAirport = [
                    'id'   => $ap->id,
                    'text' => $ap->id . (!empty($ap->iata) ? ' / ' . $ap->iata : '') . ' — ' . $ap->name,
                ];
            }
        }

        return response()->json([
            'success'              => !empty($matched['urls']),
            'urls'                 => $matched['urls'],
            'outbound'             => $matched['outbound'],
            'return'               => $matched['return'],
            'swapped_order'        => $matched['swapped_order'],
            'detected_arr_airport' => $detectedArrAirport,
        ]);
    }

    public function quickGenerate(Request $request, int $id)
    {
        $this->ensureColumnsExist();
        $drmEntitlements = $this->getEntitlements(false);

        if (empty($drmEntitlements['features']['one_click_create'])) {
            flash()->error('1-Click Auto-Create requires Tier 2 (Executive Dispatch Edition) or Tier 3 (Flagship Edition). Please use the standard "+ Generate Flight" drawer.');
            return redirect()->route('routerequest.admin.index', ['open_gen' => $id]);
        }

        if (!class_exists(FlightAwareParser::class, false)) {
            require_once __DIR__ . '/../../../Support/FlightAwareParser.php';
        }

        $routeRequest = RouteRequest::with(['user', 'typerating.subfleets.aircraft', 'typerating.subfleets.airline', 'dep_airport', 'arr_airport'])->findOrFail($id);

        $faImportMode = (string) $request->input('fa_import_mode', 'route_only');

        $depIcao = strtoupper(trim((string) $routeRequest->dep_airport_id));
        $arrIcao = strtoupper(trim((string) ($routeRequest->arr_airport_id ?? '')));

        $faMatched = FlightAwareParser::parseAndMatchFromNotes($routeRequest->notes, $depIcao, $arrIcao ?: null);

        if (($arrIcao === '' || $arrIcao === 'ANY' || $arrIcao === 'OPEN') && !empty($faMatched['outbound']['destination'])) {
            $arrIcao = strtoupper(trim((string) $faMatched['outbound']['destination']));
        }

        if ($arrIcao === '' || $arrIcao === 'ANY' || $arrIcao === 'OPEN') {
            flash()->warning('Request #' . $routeRequest->id . ' has an open (ANY) destination. Please select an Arrival Airport in the generator below.');
            return redirect()->route('routerequest.admin.index', ['open_gen' => $routeRequest->id]);
        }

        $linkedSubfleets = ($routeRequest->typerating && $routeRequest->typerating->subfleets)
            ? $routeRequest->typerating->subfleets
            : collect();

        $airlineId = null;
        foreach ($linkedSubfleets as $lsf) {
            if (!empty($lsf->airline_id)) {
                $airlineId = (int) $lsf->airline_id;
                break;
            }
        }
        if (!$airlineId) {
            $firstAirline = Airline::where('active', true)->orderBy('name', 'asc')->first() ?? Airline::orderBy('name', 'asc')->first();
            $airlineId = $firstAirline ? (int) $firstAirline->id : null;
        }

        if (!$airlineId) {
            flash()->error('No active VA Airline found in phpVMS.');
            return redirect()->route('routerequest.admin.index', ['open_gen' => $routeRequest->id]);
        }

        $airlines = Airline::where('id', $airlineId)->get();
        $nextNums = $this->computeNextFlightNumbers($airlines);
        $outboundNum = $nextNums[$airlineId] ?? 100;

        $rwLower = strtolower($routeRequest->realworld_airline ?? '');
        $isCharter = str_contains($rwLower, 'charter') || str_contains($rwLower, 'cobalt') || str_contains($rwLower, 'xo') || str_contains($rwLower, 'netjets') || str_contains($rwLower, 'vistajet') || str_contains($rwLower, 'flexjet') || str_contains($rwLower, 'wheels up') || str_contains($rwLower, 'jsx') || str_contains($rwLower, 'planesense');
        $flightType = $isCharter ? 'C' : 'J';

        $outRoute = null;
        $outLevel = null;
        $retRoute = null;
        $retLevel = null;

        if ($faImportMode !== 'none') {
            if (!empty($faMatched['outbound']['route'])) {
                $outRoute = $faMatched['outbound']['route'];
            }
            if (!empty($faMatched['return']['route'])) {
                $retRoute = $faMatched['return']['route'];
            }
            if ($faImportMode === 'route_and_altitude') {
                if (!empty($faMatched['outbound']['altitude_ft'])) {
                    $outLevel = (int) $faMatched['outbound']['altitude_ft'];
                }
                if (!empty($faMatched['return']['altitude_ft'])) {
                    $retLevel = (int) $faMatched['return']['altitude_ft'];
                }
            }
        }

        $synthRequest = Request::create(
            route('routerequest.admin.generate_flight', $routeRequest->id),
            'POST',
            [
                'airline_id'        => $airlineId,
                'flight_number'     => $outboundNum,
                'dep_airport_id'    => $depIcao,
                'arr_airport_id'    => $arrIcao,
                'flight_type'       => $flightType,
                'flight_hours'      => 0,
                'flight_minutes'    => 0,
                'level'             => $outLevel,
                'route'             => $outRoute,
                'create_return_leg' => $routeRequest->request_return ? 1 : 0,
                'return_flight_num' => $outboundNum + 1,
                'return_level'      => $retLevel,
                'return_route'      => $retRoute,
                'reject_return_leg' => 0,
                'subfleet_ids'      => $linkedSubfleets->pluck('id')->toArray(),
                'submit_action'     => 'complete',
            ]
        );

        return $this->generateFlight($synthRequest, $routeRequest->id);
    }

    protected function repairPreviouslyGeneratedFlightsOnce(): void
    {
        try {
            if (Cache::get('routerequest_legacy_repaired_v2') === true) {
                return;
            }
            $linkedRequests = RouteRequest::whereNotNull('flight_id')->get();
            foreach ($linkedRequests as $req) {
                $flight = Flight::find($req->flight_id);
                if (!$flight) {
                    continue;
                }
                $needsSave = false;
                if (empty($flight->dpt_airport_id) && !empty($req->dep_airport_id)) {
                    $flight->dpt_airport_id = strtoupper(trim($req->dep_airport_id));
                    $needsSave = true;
                }
                if ((empty($flight->distance) || (float) $flight->distance == 0) && !empty($flight->dpt_airport_id) && !empty($flight->arr_airport_id)) {
                    $depAp = Airport::find($flight->dpt_airport_id);
                    $arrAp = Airport::find($flight->arr_airport_id);
                    if ($depAp && $arrAp) {
                        $dist = $this->calculateDistanceNmi($depAp, $arrAp);
                        if ($dist > 0) {
                            $flight->distance = $dist;
                            $needsSave = true;
                        }
                    }
                }
                if ($needsSave) {
                    $flight->save();
                }
            }
            Cache::forever('routerequest_legacy_repaired_v2', true);
        } catch (\Throwable $e) {}
    }

    public function searchAirports(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', $request->get('term', '')));

        $query = Airport::select('id', 'icao', 'iata', 'name', 'location', 'country', 'hub');

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
                'text' => $a->id . $iata . ' — ' . $a->name . $loc . $hub,
            ];
        });

        return response()->json([
            'results' => $results,
            'data'    => $results,
        ]);
    }

    protected function resolveAndVerifyAirport(string $icao): ?Airport
    {
        $icao = strtoupper(trim($icao));
        if ($icao === '' || $icao === 'ANY' || $icao === 'OPEN') {
            return null;
        }

        $airport = Airport::find($icao);
        if ($airport) {
            return $airport;
        }

        try {
            if (class_exists(AirportService::class)) {
                $airportSvc = app(AirportService::class);
                if (method_exists($airportSvc, 'lookupAirportIfNotFound')) {
                    return $airportSvc->lookupAirportIfNotFound($icao);
                }
                if (method_exists($airportSvc, 'lookupAirport')) {
                    $data = $airportSvc->lookupAirport($icao);
                    if (!empty($data) && is_array($data)) {
                        return Airport::create($data);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('RouteRequest Airport Auto-Lookup failed for ' . $icao . ': ' . $e->getMessage());
        }

        return Airport::find($icao);
    }

    protected function calculateDistanceNmi(?Airport $dep, ?Airport $arr): int
    {
        if (!$dep || !$arr || $dep->lat === null || $dep->lon === null || $arr->lat === null || $arr->lon === null) {
            return 0;
        }

        $lat1 = deg2rad((float) $dep->lat);
        $lon1 = deg2rad((float) $dep->lon);
        $lat2 = deg2rad((float) $arr->lat);
        $lon2 = deg2rad((float) $arr->lon);

        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;

        $a = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * (sin($dLon / 2) ** 2);
        $c = 2 * asin(min(1.0, sqrt($a)));

        return (int) round(3440.065 * $c);
    }

    /**
     * v2.2.0 Afterburner 3-Tier DRM Schedule Generator:
     * Enforces:
     * - Tier 1 (Basic Dispatch Edition): Max 12 routes per request, max 3 round-trip pairs per request, max 28 routes per rolling 7-day week (each round-trip counts as 2).
     * - Tier 2 (Executive Dispatch Edition) & Tier 3 (Flagship Edition): Max 15 routes per request, max 4 round-trip pairs per request, unlimited weekly routes.
     */
    public function generateFlight(Request $request, int $id)
    {
        $this->ensureColumnsExist();
        $isAjax = $request->expectsJson() || $request->ajax() || $request->boolean('ajax_mode');

        $drmEntitlements = $this->getEntitlements(false);
        $maxReturnPairs = (int) ($drmEntitlements['max_return_pairs'] ?? self::MAX_MULTI_RETURN_PAIRS);
        $maxRoutesPerReq = (int) ($drmEntitlements['max_routes_per_req'] ?? self::MAX_ROUTES_PER_REQUEST);
        $tierLabel = $drmEntitlements['tier_label'] ?? 'Tier 1: Basic Dispatch Edition';

        $routeRequest = RouteRequest::with(['user', 'typerating.subfleets.aircraft', 'dep_airport', 'arr_airport'])->findOrFail($id);

        $depIcao = strtoupper(trim((string) $request->input('dep_airport_id', '')));
        $arrIcao = strtoupper(trim((string) $request->input('arr_airport_id', '')));
        $submitAction = $request->input('submit_action', 'complete');

        if ($arrIcao === '' || $arrIcao === 'ANY' || $arrIcao === 'OPEN') {
            $errMsg = 'Cannot generate a flight without a valid Destination (Arrival) Airport. Please select an Arrival Airport from the autocomplete list.';
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => $errMsg], 422);
            }
            flash()->error($errMsg);
            return redirect()->route('routerequest.admin.index', ['open_gen' => $routeRequest->id]);
        }

        $depAirportModel = $this->resolveAndVerifyAirport($depIcao);
        if (!$depAirportModel) {
            $errMsg = 'Departure Airport "' . $depIcao . '" does not exist in your phpVMS Airports table!';
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => $errMsg], 422);
            }
            flash()->error($errMsg);
            return redirect()->route('routerequest.admin.index', ['open_gen' => $routeRequest->id]);
        }

        $arrAirportModel = $this->resolveAndVerifyAirport($arrIcao);
        if (!$arrAirportModel) {
            $errMsg = 'Arrival Airport "' . $arrIcao . '" does not exist in your phpVMS Airports table!';
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => $errMsg], 422);
            }
            flash()->error($errMsg);
            return redirect()->route('routerequest.admin.index', ['open_gen' => $routeRequest->id]);
        }

        $validated = $request->validate([
            'airline_id'          => ['required', 'integer', 'exists:airlines,id'],
            'flight_number'       => ['required', 'numeric', 'min:1', 'max:99999'],
            'dep_airport_id'      => ['required', 'string', 'min:3', 'max:5'],
            'arr_airport_id'      => ['required', 'string', 'min:3', 'max:5'],
            'flight_type'         => ['required', 'string', 'max:5'],
            'flight_hours'        => ['nullable', 'integer', 'min:0', 'max:48'],
            'flight_minutes'      => ['nullable', 'integer', 'min:0', 'max:59'],
            'flight_time'         => ['nullable', 'numeric', 'min:0', 'max:3000'],
            'level'               => ['nullable', 'numeric', 'min:0', 'max:60000'],
            'days'                => ['nullable', 'array'],
            'days.*'              => ['integer'],
            'dpt_time'            => ['nullable', 'string', 'max:10'],
            'arr_time'            => ['nullable', 'string', 'max:10'],
            'route_code'          => ['nullable', 'string', 'max:25'],
            'callsign'            => ['nullable', 'string', 'max:25'],
            'route'               => ['nullable', 'string', 'max:2000'],
            'notes'               => ['nullable', 'string', 'max:2000'],
            'subfleet_ids'        => ['nullable', 'array'],
            'subfleet_ids.*'      => ['integer'],
            'create_return_leg'   => ['nullable', 'boolean'],
            'return_flight_num'   => ['nullable', 'numeric', 'min:1', 'max:99999'],
            'return_level'        => ['nullable', 'numeric', 'min:0', 'max:60000'],
            'return_route'        => ['nullable', 'string', 'max:2000'],
            'reject_return_leg'   => ['nullable', 'boolean'],
        ]);

        $existingFlightModels = $routeRequest->generated_flight_models;
        $existingTotalRoutes = $existingFlightModels->count();
        $existingReturnCount = $existingFlightModels->filter(function ($gf) use ($routeRequest) {
            return strtoupper((string) $gf->arr_airport_id) === strtoupper((string) $routeRequest->dep_airport_id);
        })->count();

        $createReturn = filter_var($request->input('create_return_leg', false), FILTER_VALIDATE_BOOLEAN);
        $rejectReturn = filter_var($request->input('reject_return_leg', false), FILTER_VALIDATE_BOOLEAN);

        if ($createReturn && !$rejectReturn && $existingReturnCount >= $maxReturnPairs) {
            $createReturn = false;
        }

        // How many routes will this submission create? (Outbound = 1, Round-Trip = 2)
        $routesToCreateCount = ($createReturn && !$rejectReturn) ? 2 : 1;

        // 1. Enforce Per-Request Route Limit (12 on Tier 1 Basic, 15 on Tier 2 Executive & Tier 3 Flagship)
        if ($existingTotalRoutes >= $maxRoutesPerReq) {
            $errMsg = sprintf(
                'Per-Request Route Limit Reached (%d/%d routes already created on Request #%d under %s). Cannot create more routes on this request.',
                $existingTotalRoutes,
                $maxRoutesPerReq,
                $routeRequest->id,
                $tierLabel
            );
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => $errMsg], 422);
            }
            flash()->error($errMsg);
            return redirect()->route('routerequest.admin.index', ['open_gen' => $routeRequest->id]);
        }

        if (($existingTotalRoutes + $routesToCreateCount) > $maxRoutesPerReq) {
            // Only 1 slot left on this request, so downgrade round-trip to outbound-only
            $createReturn = false;
            $routesToCreateCount = 1;
        }

        // 2. Enforce Weekly Route Creation Quota (28/week on Tier 1 Basic, Unlimited on Tier 2/3)
        if (!class_exists(DrmEntitlements::class, false)) {
            require_once __DIR__ . '/../../../Support/DrmEntitlements.php';
        }
        $quotaCheck = DrmEntitlements::verifyAndRecordRouteCreation($routesToCreateCount);
        if (empty($quotaCheck['allowed'])) {
            $errMsg = $quotaCheck['message'] ?? 'Weekly route quota exceeded for your current license tier.';
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => $errMsg], 429);
            }
            flash()->error($errMsg);
            return redirect()->route('routerequest.admin.index', ['open_gen' => $routeRequest->id]);
        }

        $hours   = (int) ($validated['flight_hours'] ?? 0);
        $minutes = (int) ($validated['flight_minutes'] ?? 0);
        $totalMinutes = ($hours * 60) + $minutes;

        if ($totalMinutes === 0 && isset($validated['flight_time']) && (int) $validated['flight_time'] > 0) {
            $totalMinutes = (int) $validated['flight_time'];
        }

        $daysMask = 0;
        if (!empty($validated['days']) && is_array($validated['days'])) {
            if (class_exists(Days::class) && method_exists(Days::class, 'getDaysMask')) {
                $daysMask = (int) Days::getDaysMask($validated['days']);
            } else {
                foreach ($validated['days'] as $dayBit) {
                    $daysMask |= (int) $dayBit;
                }
            }
        }

        $flightLevel = !empty($validated['level']) ? (int) $validated['level'] : null;
        $distanceNmi = $this->calculateDistanceNmi($depAirportModel, $arrAirportModel);
        $routeText = !empty($validated['route']) ? strtoupper(trim($validated['route'])) : null;

        $createdFlightIds = [];
        $createdBadges = [];

        $flightData = [
            'airline_id'     => (int) $validated['airline_id'],
            'flight_number'  => (int) $validated['flight_number'],
            'dpt_airport_id' => $depAirportModel->id,
            'arr_airport_id' => $arrAirportModel->id,
            'flight_type'    => $validated['flight_type'],
            'flight_time'    => $totalMinutes,
            'level'          => $flightLevel,
            'distance'       => $distanceNmi,
            'dpt_time'       => $validated['dpt_time'] ?? null,
            'arr_time'       => $validated['arr_time'] ?? null,
            'route'          => $routeText,
            'notes'          => $validated['notes'] ?? ('Simulating ' . $routeRequest->realworld_airline),
            'days'           => $daysMask,
            'active'         => true,
            'visible'        => true,
        ];

        if (Schema::hasColumn('flights', 'route_code') && !empty($validated['route_code'])) {
            $flightData['route_code'] = strtoupper(trim($validated['route_code']));
        }
        if (Schema::hasColumn('flights', 'callsign') && !empty($validated['callsign'])) {
            $flightData['callsign'] = strtoupper(trim($validated['callsign']));
        }

        $outboundFlight = Flight::create($flightData);
        $createdFlightIds[] = $outboundFlight->id;

        $subfleetIds = !empty($validated['subfleet_ids']) ? $validated['subfleet_ids'] : [];
        if (empty($subfleetIds) && $routeRequest->typerating && $routeRequest->typerating->subfleets) {
            $subfleetIds = $routeRequest->typerating->subfleets->pluck('id')->toArray();
        }
        if (!empty($subfleetIds)) {
            $outboundFlight->subfleets()->syncWithoutDetaching($subfleetIds);
        }

        $airlineCode = optional($outboundFlight->airline)->icao ?? optional($outboundFlight->airline)->code ?? '';
        $outboundIdent = $airlineCode . $outboundFlight->flight_number;
        $notesSummary = sprintf('Flight %s (%s -> %s)', $outboundIdent, $depAirportModel->id, $arrAirportModel->id);
        $createdBadges[] = [
            'id'             => $outboundFlight->id,
            'ident'          => $outboundIdent,
            'dpt'            => $depAirportModel->id,
            'arr'            => $arrAirportModel->id,
            'edit_url'       => url('/admin/flights/' . $outboundFlight->id . '/edit'),
            'paxstudio_url'  => url('/paxstudio/loadsheet?flight_id=' . $outboundFlight->id),
        ];

        $lastFlightNumUsed = (int) $outboundFlight->flight_number;

        if ($createReturn && !$rejectReturn) {
            $returnFlightNum = !empty($validated['return_flight_num'])
                ? (int) $validated['return_flight_num']
                : ((int) $validated['flight_number'] + 1);

            $returnLevel = !empty($validated['return_level']) ? (int) $validated['return_level'] : null;
            $returnRoute = !empty($validated['return_route']) ? strtoupper(trim($validated['return_route'])) : null;

            $returnFlightData = [
                'airline_id'     => (int) $validated['airline_id'],
                'flight_number'  => $returnFlightNum,
                'dpt_airport_id' => $arrAirportModel->id,
                'arr_airport_id' => $depAirportModel->id,
                'flight_type'    => $validated['flight_type'],
                'flight_time'    => $totalMinutes,
                'level'          => $returnLevel,
                'distance'       => $distanceNmi,
                'route'          => $returnRoute,
                'notes'          => 'Return leg for ' . $routeRequest->realworld_airline,
                'days'           => $daysMask,
                'active'         => true,
                'visible'        => true,
            ];

            if (Schema::hasColumn('flights', 'route_code') && !empty($validated['route_code'])) {
                $returnFlightData['route_code'] = strtoupper(trim($validated['route_code']));
            }
            if (Schema::hasColumn('flights', 'callsign') && !empty($validated['callsign'])) {
                $returnFlightData['callsign'] = strtoupper(trim($validated['callsign']));
            }

            $returnFlight = Flight::create($returnFlightData);
            $createdFlightIds[] = $returnFlight->id;
            $existingReturnCount++;
            $lastFlightNumUsed = max($lastFlightNumUsed, (int) $returnFlight->flight_number);

            if (!empty($subfleetIds)) {
                $returnFlight->subfleets()->syncWithoutDetaching($subfleetIds);
            }

            $returnIdent = $airlineCode . $returnFlight->flight_number;
            $notesSummary .= sprintf(' & Return Flight %s (%s -> %s)', $returnIdent, $arrAirportModel->id, $depAirportModel->id);
            $createdBadges[] = [
                'id'             => $returnFlight->id,
                'ident'          => $returnIdent,
                'dpt'            => $arrAirportModel->id,
                'arr'            => $depAirportModel->id,
                'edit_url'       => url('/admin/flights/' . $returnFlight->id . '/edit'),
                'paxstudio_url'  => url('/paxstudio/loadsheet?flight_id=' . $returnFlight->id),
            ];
        } elseif ($rejectReturn) {
            $notesSummary .= ' [Return Leg Rejected / Outbound Only]';
        }

        $existingFlights = is_array($routeRequest->generated_flights) ? $routeRequest->generated_flights : [];
        if (!empty($routeRequest->flight_id) && !in_array($routeRequest->flight_id, $existingFlights, true)) {
            $existingFlights[] = $routeRequest->flight_id;
        }
        foreach ($createdFlightIds as $newFId) {
            $existingFlights[] = $newFId;
        }
        $existingFlights = array_values(array_unique(array_filter($existingFlights)));

        $routeRequest->flight_id = $outboundFlight->id;
        $routeRequest->generated_flights = $existingFlights;

        if (count($existingFlights) > 2) {
            $routeRequest->multiple_destinations = true;
        }

        $routeRequest->admin_notes = !empty($routeRequest->admin_notes)
            ? $routeRequest->admin_notes . ' | ' . $notesSummary
            : $notesSummary;

        if ($submitAction === 'add_more') {
            $routeRequest->multiple_destinations = true;
            if ($routeRequest->status === 'pending') {
                $routeRequest->status = 'approved';
            }
            $routeRequest->save();

            $nextSuggestedOutbound = $lastFlightNumUsed + 1;
            $capReached = ($existingReturnCount >= $maxReturnPairs);
            $routeCapReached = (count($existingFlights) >= $maxRoutesPerReq);
            $updatedEntitlements = $this->getEntitlements(false);

            if ($isAjax) {
                return response()->json([
                    'success'                  => true,
                    'action'                   => 'add_more',
                    'message'                  => $notesSummary . ' created! (' . count($existingFlights) . '/' . $maxRoutesPerReq . ' routes on this request).',
                    'created_flights'          => $createdBadges,
                    'total_flights'            => count($existingFlights),
                    'max_routes_per_req'       => $maxRoutesPerReq,
                    'route_cap_reached'        => $routeCapReached,
                    'return_pairs_used'        => $existingReturnCount,
                    'max_return_pairs'         => $maxReturnPairs,
                    'return_cap_reached'       => $capReached,
                    'weekly_routes_used'       => $updatedEntitlements['weekly_routes_used'] ?? 0,
                    'weekly_route_cap'         => $updatedEntitlements['weekly_route_cap'] ?? null,
                    'next_outbound_flight_num' => $nextSuggestedOutbound,
                    'next_return_flight_num'   => $nextSuggestedOutbound + 1,
                    'admin_notes'              => $routeRequest->admin_notes,
                ]);
            }

            flash()->success(
                $notesSummary . ' created! Total flights for Request #' . $routeRequest->id . ': ' . count($existingFlights) . '/' . $maxRoutesPerReq . '. ' .
                'The generator below is ready for your next leg.'
            );

            return redirect()->route('routerequest.admin.index', ['open_gen' => $routeRequest->id]);
        }

        if (!$routeRequest->multiple_destinations || empty($routeRequest->arr_airport_id)) {
            $routeRequest->arr_airport_id = $arrAirportModel->id;
        }

        if (in_array($routeRequest->status, ['pending', 'approved', 'in_progress'], true)) {
            $routeRequest->status = 'completed';
        }

        $routeRequest->save();
        $routeRequest->load(['user', 'dep_airport', 'arr_airport', 'flight.airline']);

        $recipients = array_unique(array_filter([
            $routeRequest->recipient_email,
            config('routerequest.recipient_email', 'admin@flyfantasyair.net') ?: 'admin@flyfantasyair.net',
            optional($routeRequest->user)->email,
        ]));

        flash()->success(
            $notesSummary . ' created in phpVMS! Request #' . $routeRequest->id . ' (' . count($existingFlights) . '/' . $maxRoutesPerReq . ' route[s]) automatically marked "' . $routeRequest->status_label . '" and emailed.'
        );

        app()->terminating(function () use ($routeRequest, $recipients) {
            try {
                if (!empty($recipients)) {
                    Mail::to($recipients)->send(new RouteRequestSubmittedMail($routeRequest, true));
                }
            } catch (\Throwable $e) {
                Log::error('RouteRequest Background GenerateFlight Email Error: ' . $e->getMessage());
            }
        });

        if ($isAjax) {
            return response()->json([
                'success'  => true,
                'action'   => 'complete',
                'redirect' => route('routerequest.admin.index'),
            ]);
        }

        return redirect()->route('routerequest.admin.index');
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $this->ensureColumnsExist();

        $routeRequest = RouteRequest::with(['user', 'dep_airport', 'arr_airport'])->findOrFail($id);

        $validated = $request->validate([
            'status'          => ['required', 'in:pending,approved,in_progress,completed,rejected,cancelled'],
            'admin_notes'     => ['nullable', 'string', 'max:2000'],
            'recipient_email' => ['nullable', 'email', 'max:191'],
        ]);

        $hasGeneratedFlights = !empty($routeRequest->flight_id) || (!empty($routeRequest->generated_flights) && count($routeRequest->generated_flights) > 0);

        if ($validated['status'] === 'completed' && empty($routeRequest->arr_airport_id) && !$hasGeneratedFlights) {
            flash()->error(
                'Cannot mark Request #' . $routeRequest->id .
                ' as Completed because it has no Destination (Arrival) Airport or generated flights yet. Use "+ Generate Flight" to create the schedule first.'
            );
            return redirect()->route('routerequest.admin.index');
        }

        $routeRequest->status = $validated['status'];
        $routeRequest->admin_notes = $validated['admin_notes'] ?? $routeRequest->admin_notes;

        if (!empty($validated['recipient_email'])) {
            $routeRequest->recipient_email = trim($validated['recipient_email']);
        }

        $routeRequest->save();

        $recipients = array_unique(array_filter([
            $routeRequest->recipient_email,
            config('routerequest.recipient_email', 'admin@flyfantasyair.net') ?: 'admin@flyfantasyair.net',
            optional($routeRequest->user)->email,
        ]));

        flash()->success('Route request #' . $routeRequest->id . ' updated to "' . $routeRequest->status_label . '" and notification email dispatched.');

        app()->terminating(function () use ($routeRequest, $recipients) {
            try {
                if (!empty($recipients)) {
                    Mail::to($recipients)->send(new RouteRequestSubmittedMail($routeRequest, true));
                }
            } catch (\Throwable $e) {
                Log::error('RouteRequest Background Status Email Error: ' . $e->getMessage());
            }
        });

        return redirect()->route('routerequest.admin.index');
    }

    public function checkUpdate(Request $request): RedirectResponse
    {
        if (!class_exists(UpdateManager::class, false)) {
            require_once __DIR__ . '/../../../Support/UpdateManager.php';
        }
        $info = UpdateManager::check(true);

        if (!empty($info['error'])) {
            flash()->warning('GitHub Update Check (' . $info['repo'] . '): ' . $info['error']);
        } elseif (!empty($info['update_available'])) {
            flash()->info(
                'Update Available! Version v' . $info['latest_version'] .
                (!empty($info['is_prerelease']) ? ' [PRE-RELEASE]' : '') .
                ' is ready on GitHub (' . $info['repo'] . ' — Channel: ' . ($info['channel'] ?? 'Stable') . ').'
            );
        } else {
            flash()->success(
                'RouteRequest is up to date! Installed Version: v' . $info['current_version'] .
                ' (Channel: ' . ($info['channel'] ?? 'Stable') . ').'
            );
        }

        return redirect()->route('routerequest.admin.index');
    }

    public function installUpdate(Request $request): RedirectResponse
    {
        if (!class_exists(UpdateManager::class, false)) {
            require_once __DIR__ . '/../../../Support/UpdateManager.php';
        }
        $result = UpdateManager::installLatestUpdate();

        if (!empty($result['success'])) {
            flash()->success($result['message']);
        } else {
            flash()->error($result['message']);
        }

        return redirect()->route('routerequest.admin.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        $routeRequest = RouteRequest::findOrFail($id);
        $routeRequest->delete();

        flash()->success('Route request deleted.');
        return redirect()->route('routerequest.admin.index');
    }
}
