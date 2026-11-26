<?php

declare(strict_types=1);

namespace Modules\AfterburnerDispatch\Support;

use App\Models\Flight;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

if (!class_exists(RouteEstimatorService::class, false)) {
    class RouteEstimatorService
    {
        /**
         * Built-in typical max operational ranges (NM) by ICAO / aircraft type prefix.
         */
        private const array DEFAULT_RANGES_NM = [
            'A318' => 3100, 'A319' => 3750, 'A320' => 3300, 'A20N' => 3400,
            'A321' => 3200, 'A21N' => 4000, 'A21X' => 4700, 'BCS1' => 3450, 'BCS3' => 3600, 'A220' => 3450,
            'A332' => 7250, 'A333' => 6350, 'A338' => 8150, 'A339' => 7200,
            'A343' => 7300, 'A346' => 7800, 'A359' => 8100, 'A35K' => 8700, 'A388' => 8000,
            'B712' => 2060, 'B737' => 3010, 'B738' => 2935, 'B739' => 2950,
            'B38M' => 3550, 'B39M' => 3550, 'B37M' => 3850,
            'B744' => 7260, 'B748' => 7730, 'B752' => 3915, 'B753' => 3400,
            'B763' => 5980, 'B764' => 5625, 'B772' => 5240, 'B77L' => 8555, 'B77W' => 7370,
            'B788' => 7305, 'B789' => 7565, 'B78X' => 6330,
            'E135' => 1750, 'E145' => 1550, 'E170' => 2150, 'E175' => 2200, 'E190' => 2450, 'E195' => 2300, 'E295' => 2600,
            'CRJ2' => 1700, 'CRJ7' => 1378, 'CRJ9' => 1553, 'CRJX' => 1622,
            'DH8D' => 1100, 'Q400' => 1100, 'AT72' => 825, 'AT76' => 825, 'AT45' => 716,
            'MD11' => 6725, 'MD80' => 2050, 'MD82' => 2050, 'MD88' => 2050, 'DC10' => 5400,
            'C208' => 960, 'PC12' => 1800, 'B350' => 1800, 'C172' => 640, 'TBM9' => 1730,
            'C56X' => 2100, 'C700' => 3500, 'GLF5' => 6500, 'GLEX' => 6000, 'CL60' => 4000,
        ];

        /**
         * Haversine great-circle distance in nautical miles.
         */
        public static function calculateGreatCircleNm(float $lat1, float $lon1, float $lat2, float $lon2): int
        {
            $earthRadiusNm = 3440.065;
            $dLat = deg2rad($lat2 - $lat1);
            $dLon = deg2rad($lon2 - $lon1);
            $a = sin($dLat / 2) * sin($dLat / 2)
               + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
            $c = 2 * atan2(sqrt($a), sqrt(max(0.0, 1.0 - $a)));
            return (int) round($earthRadiusNm * $c);
        }

        /**
         * Estimates realistic block time (cruise + 25 min taxi/climb/descent buffer).
         */
        public static function estimateBlockTime(int $distanceNm, string $aircraftHint = ''): array
        {
            $hint = strtoupper($aircraftHint);
            $cruiseKts = 450; // default narrowbody jet

            if (preg_match('/(C172|C208|PA28|SR22)/', $hint)) {
                $cruiseKts = 145;
            } elseif (preg_match('/(DH8|Q400|AT4|AT7|PC12|B350|TBM|SF34)/', $hint)) {
                $cruiseKts = 285;
            } elseif (preg_match('/(CRJ|E135|E145|E170|E175|E190|E195)/', $hint)) {
                $cruiseKts = 430;
            } elseif (preg_match('/(B74|B77|B78|A33|A34|A35|A38|MD11)/', $hint)) {
                $cruiseKts = 485;
            }

            $bufferMins = $distanceNm < 120 ? 18 : 25;
            $totalMins = (int) max(20, round(($distanceNm / $cruiseKts) * 60) + $bufferMins);

            return [
                'minutes'    => $totalMins,
                'hours'      => intdiv($totalMins, 60),
                'mins'       => $totalMins % 60,
                'cruise_kts' => $cruiseKts,
            ];
        }

        /**
         * Resolves max operational range (NM) for a given Type Rating string/ID.
         * Supports optional custom overrides stored in ClientSettings (`range_overrides_json`).
         */
        public static function getMaxRangeNm(string $typeRatingText, ?int $typeratingId = null): int
        {
            $settings = ClientSettings::all();
            $rawOverrides = trim((string) ($settings['range_overrides_json'] ?? ''));
            if ($rawOverrides !== '' && json_validate($rawOverrides)) {
                $map = json_decode($rawOverrides, true);
                if (is_array($map)) {
                    if ($typeratingId !== null && isset($map[(string) $typeratingId]) && (int) $map[(string) $typeratingId] > 0) {
                        return (int) $map[(string) $typeratingId];
                    }
                    $upper = strtoupper(trim($typeRatingText));
                    foreach ($map as $k => $v) {
                        if ($k !== '' && str_contains($upper, strtoupper((string) $k)) && (int) $v > 0) {
                            return (int) $v;
                        }
                    }
                }
            }

            $upper = strtoupper($typeRatingText);
            foreach (self::DEFAULT_RANGES_NM as $code => $nm) {
                if (str_contains($upper, $code)) {
                    return $nm;
                }
            }

            // General family fallbacks
            if (str_contains($upper, '737')) return 3000;
            if (str_contains($upper, '747')) return 7400;
            if (str_contains($upper, '757')) return 3900;
            if (str_contains($upper, '767')) return 5800;
            if (str_contains($upper, '777')) return 7370;
            if (str_contains($upper, '787')) return 7500;
            if (str_contains($upper, '320') || str_contains($upper, '321') || str_contains($upper, '319')) return 3400;
            if (str_contains($upper, '330')) return 6800;
            if (str_contains($upper, '350')) return 8100;
            if (str_contains($upper, '380')) return 8000;

            return 3500;
        }

        /**
         * Returns the full default range table for client-side JS instant range checks.
         */
        public static function getRangeTableForFrontend(): array
        {
            $ranges = self::DEFAULT_RANGES_NM;
            $settings = ClientSettings::all();
            $rawOverrides = trim((string) ($settings['range_overrides_json'] ?? ''));
            if ($rawOverrides !== '' && json_validate($rawOverrides)) {
                $map = json_decode($rawOverrides, true);
                if (is_array($map)) {
                    foreach ($map as $k => $v) {
                        if ((int) $v > 0) {
                            $ranges[strtoupper(trim((string) $k))] = (int) $v;
                        }
                    }
                }
            }
            return $ranges;
        }

        /**
         * ONLY invoked when a pilot's route exceeds the Aircraft Range Guardrail.
         * Quickly checks FlightAware and Flightradar24 to verify if real-world flights operate DEP -> ARR.
         */
        public static function verifyRealWorldRoute(string $depIcao, string $arrIcao): array
        {
            $dep = strtoupper(trim($depIcao));
            $arr = strtoupper(trim($arrIcao));

            if (strlen($dep) < 3 || strlen($arr) < 3) {
                return ['verified' => false, 'sources' => [], 'sample_flights' => [], 'message' => 'Invalid airport ICAO codes.'];
            }

            $sources = [];
            $samples = [];

            // 1. Quick FlightAware FindFlight check (5s timeout)
            try {
                $faUrl = "https://www.flightaware.com/live/findflight?origin={$dep}&destination={$arr}";
                $faRes = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/126.0 Safari/537.36',
                    'Accept'     => 'text/html',
                ])->timeout(5)->get($faUrl);

                if ($faRes->successful()) {
                    $body = $faRes->body();
                    // Look for active/scheduled flight idents linked on FlightAware results table
                    if (preg_match_all('/\/live\/flight\/([A-Z]{2,3}[0-9]{1,4})/i', $body, $m)) {
                        $found = array_values(array_unique(array_map('strtoupper', $m[1])));
                        $found = array_slice($found, 0, 5);
                        if (!empty($found)) {
                            $sources[] = 'FlightAware';
                            $samples = array_merge($samples, $found);
                        }
                    }
                }
            } catch (\Throwable $e) {}

            // 2. Quick Flightradar24 public search/route check (5s timeout)
            try {
                $frUrl = "https://www.flightradar24.com/v1/search/web/find?query=" . urlencode("{$dep}-{$arr}") . "&limit=5";
                $frRes = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/126.0 Safari/537.36',
                    'Accept'     => 'application/json',
                ])->timeout(5)->get($frUrl);

                $frRaw = $frRes->body();
                if ($frRes->successful() && $frRaw !== '' && json_validate($frRaw)) {
                    $frJson = json_decode($frRaw, true);
                    $results = $frJson['results'] ?? [];
                    foreach ($results as $r) {
                        $type = strtolower((string) ($r['type'] ?? ''));
                        $label = strtoupper((string) ($r['label'] ?? $r['id'] ?? ''));
                        if (in_array($type, ['live', 'schedule', 'flight'], true) && $label !== '') {
                            if (!in_array('Flightradar24', $sources, true)) {
                                $sources[] = 'Flightradar24';
                            }
                            $samples[] = $label;
                        }
                    }
                }
            } catch (\Throwable $e) {}

            $samples = array_values(array_unique(array_slice($samples, 0, 6)));
            $verified = !empty($sources);

            return [
                'verified'       => $verified,
                'sources'        => $sources,
                'sample_flights' => $samples,
                'fa_url'         => "https://www.flightaware.com/live/findflight?origin={$dep}&destination={$arr}",
                'fr24_url'       => "https://www.flightradar24.com/data/flights",
                'message'        => $verified
                    ? ('Real-world route verified via ' . implode(' & ', $sources) . (!empty($samples) ? (' (e.g. ' . implode(', ', $samples) . ')') : '') . '. Range override approved!')
                    : "No scheduled real-world flights were automatically detected on FlightAware or Flightradar24 for {$dep} → {$arr}. You may still confirm an extended-range/charter override below.",
            ];
        }

        /**
         * Dry-Run Preview for fixing old phpVMS routes with missing (0 / NULL) or unrealistic (< 15 min) flight times.
         */
        public static function previewFlightTimeFix(string $mode = 'missing_only', bool $activeOnly = true): array
        {
            try {
                $query = DB::table('flights as f')
                    ->join('airports as dep', 'dep.id', '=', 'f.dpt_airport_id')
                    ->join('airports as arr', 'arr.id', '=', 'f.arr_airport_id')
                    ->leftJoin('airlines as al', 'al.id', '=', 'f.airline_id')
                    ->select(
                        'f.id',
                        'f.flight_number',
                        'f.dpt_airport_id',
                        'f.arr_airport_id',
                        'f.flight_time',
                        'f.distance',
                        'al.icao as airline_icao',
                        'al.iata as airline_iata',
                        'dep.lat as dep_lat',
                        'dep.lon as dep_lon',
                        'arr.lat as arr_lat',
                        'arr.lon as arr_lon'
                    )
                    ->whereNotNull('dep.lat')
                    ->whereNotNull('dep.lon')
                    ->whereNotNull('arr.lat')
                    ->whereNotNull('arr.lon');

                if (Schema::hasColumn('flights', 'deleted_at')) {
                    $query->whereNull('f.deleted_at');
                }

                if ($activeOnly) {
                    $query->where('f.active', 1);
                }

            if ($mode === 'missing_only') {
                $query->where(function ($q) {
                    $q->whereNull('f.flight_time')
                      ->orWhere('f.flight_time', '<', 15);
                });
            }

            $rows = $query->get();
            $toFix = [];
            $samples = [];

            foreach ($rows as $row) {
                $depLat = (float) $row->dep_lat;
                $depLon = (float) $row->dep_lon;
                $arrLat = (float) $row->arr_lat;
                $arrLon = (float) $row->arr_lon;

                if ($depLat === 0.0 && $depLon === 0.0) {
                    continue;
                }

                $distNm = self::calculateGreatCircleNm($depLat, $depLon, $arrLat, $arrLon);
                $est = self::estimateBlockTime($distNm);
                $oldTime = (int) ($row->flight_time ?? 0);
                $newTime = (int) $est['minutes'];
                $oldDist = (int) round((float) ($row->distance ?? 0));

                // In `all_unrealistic` mode, also fix routes where time differs drastically from realistic cruise
                if ($mode === 'unrealistic' && $oldTime >= 15 && abs($oldTime - $newTime) <= max(45, (int) round($newTime * 0.35))) {
                    continue;
                }

                $toFix[] = [
                    'id'          => $row->id,
                    'flight_time' => $newTime,
                    'distance'    => $oldDist > 0 ? $oldDist : $distNm,
                ];

                if (count($samples) < 25) {
                    $code = $row->airline_icao ?: ($row->airline_iata ?: '');
                    $samples[] = [
                        'ident'    => $code . $row->flight_number,
                        'route'    => $row->dpt_airport_id . ' → ' . $row->arr_airport_id,
                        'dist_nm'  => $distNm,
                        'old_time' => sprintf('%dh %02dm', intdiv($oldTime, 60), $oldTime % 60),
                        'new_time' => sprintf('%dh %02dm', $est['hours'], $est['mins']),
                    ];
                }
            }

                return [
                    'success'     => true,
                    'total_count' => count($toFix),
                    'samples'     => $samples,
                    'updates'     => $toFix,
                ];
            } catch (\Throwable $e) {
                return [
                    'success'     => false,
                    'total_count' => 0,
                    'samples'     => [],
                    'updates'     => [],
                    'message'     => 'Flight time scan failed: ' . $e->getMessage(),
                ];
            }
        }

        /**
         * 1-Click Execute to update broken/missing `flight_time` (and missing `distance`) on old phpVMS flights.
         */
        public static function executeFlightTimeFix(string $mode = 'missing_only', bool $activeOnly = true): array
        {
            $preview = self::previewFlightTimeFix($mode, $activeOnly);
            if (empty($preview['success'])) {
                return $preview;
            }
            $updates = $preview['updates'] ?? [];

            if (empty($updates)) {
                return [
                    'success'       => true,
                    'updated_count' => 0,
                    'message'       => 'All scanned phpVMS flights already have valid flight times — 0 changes needed.',
                ];
            }

            $updated = 0;
            foreach ($updates as $u) {
                DB::table('flights')
                    ->where('id', $u['id'])
                    ->update([
                        'flight_time' => $u['flight_time'],
                        'distance'    => $u['distance'],
                    ]);
                $updated++;
            }

            return [
                'success'       => true,
                'updated_count' => $updated,
                'message'       => "Route Time Estimator Fix Complete! Updated flight_time & distance on {$updated} flight(s).",
            ];
        }

        /**
         * Returns minimum required runway length (feet) for common aircraft ICAOs/hints.
         */
        public static function getMinRunwayFt(string $aircraftHint): int
        {
            $u = strtoupper(trim($aircraftHint));
            if ($u === '') {
                return 6000;
            }
            $map = [
                'A388' => 10000, 'A380' => 10000, 'B748' => 10000, 'B744' => 9800, 'B74' => 9500,
                'B77W' => 9500,  'B77L' => 9200,  'B772' => 8500,  'B773' => 9200, 'B77' => 9000,
                'A346' => 9500,  'A343' => 9200,  'MD11' => 9500,  'B78X' => 8800, 'B789' => 8500,
                'B788' => 8000,  'B78'  => 8500,  'A35K' => 9000,  'A359' => 8500, 'A35' => 8500,
                'A339' => 8500,  'A333' => 8500,  'A332' => 8000,  'A33'  => 8200, 'B764' => 8500,
                'B763' => 8000,  'B762' => 7500,  'B76'  => 8000,  'B753' => 7500, 'B752' => 6500,
                'A321' => 7000,  'A21N' => 7000,  'B739' => 7000,  'B39M' => 7000, 'B738' => 6500,
                'B38M' => 6500,  'A320' => 6200,  'A20N' => 6200,  'A319' => 5800, 'A19N' => 5800,
                'B737' => 6000,  'B37M' => 6000,  'B736' => 5500,  'B712' => 5500, 'BCS3' => 5500,
                'BCS1' => 5000,  'A220' => 5200,  'E195' => 5500,  'E190' => 5200, 'E175' => 5000,
                'E170' => 4800,  'CRJ9' => 5500,  'CRJ7' => 5200,  'CRJ2' => 5000, 'E145' => 5000,
                'DH8D' => 4200,  'Q400' => 4200,  'AT76' => 4200,  'AT72' => 4200, 'AT45' => 3800,
                'SF34' => 4000,  'PC12' => 3000,  'TBM9' => 2800,  'C208' => 2500, 'DHC6' => 2000,
                'C172' => 1800,  'SR22' => 2000,  'BE36' => 2200,  'C56X' => 4200, 'CL60' => 5200,
                'GLEX' => 6000,  'GLF6' => 6200,  'LJ45' => 4500,  'C700' => 4800,
            ];
            foreach ($map as $code => $ft) {
                if (str_contains($u, $code)) {
                    return $ft;
                }
            }
            return 6000;
        }

        /**
         * Evaluates selected subfleets against route Great-Circle distance and airport runway lengths.
         */
        public static function evaluateDispatchGuard(string $depIcao, string $arrIcao, array $subfleetIds): array
        {
            $depId = strtoupper(trim($depIcao));
            $arrId = strtoupper(trim($arrIcao));
            if ($depId === '' || $arrId === '') {
                return ['success' => false, 'message' => 'Select both Departure and Arrival airports first.'];
            }

            $dep = \App\Models\Airport::find($depId);
            $arr = \App\Models\Airport::find($arrId);
            if (!$dep || !$arr) {
                return ['success' => false, 'message' => 'Could not locate Departure or Arrival airport in phpVMS database.'];
            }

            $distNm = 0;
            if (($dep->lat || $dep->lon) && ($arr->lat || $arr->lon)) {
                $distNm = self::calculateGreatCircleNm((float) $dep->lat, (float) $dep->lon, (float) $arr->lat, (float) $arr->lon);
            }

            // Optional runway length lookup if a runways table exists (e.g., Disposable/custom addon)
            $depMaxRwy = null;
            $arrMaxRwy = null;
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('runways')) {
                    $depMaxRwy = (int) (\Illuminate\Support\Facades\DB::table('runways')->where('airport_id', $depId)->max('length') ?? 0) ?: null;
                    $arrMaxRwy = (int) (\Illuminate\Support\Facades\DB::table('runways')->where('airport_id', $arrId)->max('length') ?? 0) ?: null;
                } elseif (\Illuminate\Support\Facades\Schema::hasTable('disposable_runways')) {
                    $depMaxRwy = (int) (\Illuminate\Support\Facades\DB::table('disposable_runways')->where('airport_id', $depId)->max('length') ?? 0) ?: null;
                    $arrMaxRwy = (int) (\Illuminate\Support\Facades\DB::table('disposable_runways')->where('airport_id', $arrId)->max('length') ?? 0) ?: null;
                }
            } catch (\Throwable $e) {}

            $ids = array_values(array_filter(array_map('intval', $subfleetIds)));
            $subfleets = empty($ids)
                ? collect()
                : \App\Models\Subfleet::whereIn('id', $ids)->get();

            $items = [];
            $hasDanger = false;
            $hasWarn = false;

            foreach ($subfleets as $sf) {
                $hint = trim((string) ($sf->type . ' ' . $sf->name));
                $maxRange = self::getMaxRangeNm($hint, null);
                $minRwy = self::getMinRunwayFt($hint);

                $rangeStatus = 'ok';
                if ($distNm > 0 && $maxRange > 0) {
                    if ($distNm > $maxRange) {
                        $rangeStatus = 'danger';
                        $hasDanger = true;
                    } elseif ($distNm > (int) round($maxRange * 0.85)) {
                        $rangeStatus = 'warn';
                        $hasWarn = true;
                    }
                }

                $rwyStatus = 'advisory';
                $rwyNote = 'Min Runway Req: ' . number_format($minRwy) . ' ft';
                if ($depMaxRwy !== null || $arrMaxRwy !== null) {
                    $shortDep = ($depMaxRwy !== null && $depMaxRwy < $minRwy);
                    $shortArr = ($arrMaxRwy !== null && $arrMaxRwy < $minRwy);
                    if ($shortDep || $shortArr) {
                        $rwyStatus = 'danger';
                        $hasDanger = true;
                        $rwyNote = 'RUNWAY SHORT! Req ' . number_format($minRwy) . ' ft (DEP: ' . ($depMaxRwy ? number_format($depMaxRwy) . ' ft' : 'N/A') . ', ARR: ' . ($arrMaxRwy ? number_format($arrMaxRwy) . ' ft' : 'N/A') . ')';
                    } else {
                        $rwyStatus = 'ok';
                        $rwyNote = 'Runways OK (Req ' . number_format($minRwy) . ' ft • DEP ' . ($depMaxRwy ? number_format($depMaxRwy) . ' ft' : 'N/A') . ', ARR ' . ($arrMaxRwy ? number_format($arrMaxRwy) . ' ft' : 'N/A') . ')';
                    }
                }

                $items[] = [
                    'subfleet'     => $sf->type . ' (' . $sf->name . ')',
                    'max_range_nm' => $maxRange,
                    'range_status' => $rangeStatus,
                    'min_rwy_ft'   => $minRwy,
                    'rwy_status'   => $rwyStatus,
                    'rwy_note'     => $rwyNote,
                ];
            }

            return [
                'success'     => true,
                'dep'         => $depId,
                'arr'         => $arrId,
                'distance_nm' => $distNm,
                'severity'    => $hasDanger ? 'danger' : ($hasWarn ? 'warn' : 'ok'),
                'items'       => $items,
            ];
        }
    }
}
