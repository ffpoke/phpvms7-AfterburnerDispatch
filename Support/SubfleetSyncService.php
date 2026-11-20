<?php

declare(strict_types=1);

namespace Modules\RouteRequest\Support;

use App\Models\Flight;
use App\Models\Subfleet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (!class_exists(SubfleetSyncService::class, false)) {
    class SubfleetSyncService
    {
        /**
         * Dry-run analysis across ALL phpVMS flights to find which flights are missing
         * the selected target subfleet(s) under the chosen match mode and airline restrictions.
         *
         * @param int[] $subfleetIds
         * @param string $matchMode   'typerating' | 'subfleet_type' | 'all_flights'
         * @param string $airlineMode 'same_airline' | 'custom_airlines' | 'all_airlines'
         * @param int[] $customAirlineIds
         * @param bool $activeOnly
         */
        public static function analyze(
            array $subfleetIds,
            string $matchMode = 'typerating',
            string $airlineMode = 'same_airline',
            array $customAirlineIds = [],
            bool $activeOnly = true
        ): array {
            $cleanSfIds = array_values(array_unique(array_filter(array_map('intval', $subfleetIds), fn (int $id): bool => $id > 0)));
            if (empty($cleanSfIds)) {
                return [
                    'success' => false,
                    'message' => 'Please select at least one target Subfleet to sync.',
                ];
            }

            if (!in_array($matchMode, ['typerating', 'subfleet_type', 'all_flights'], true)) {
                $matchMode = 'typerating';
            }
            if (!in_array($airlineMode, ['same_airline', 'custom_airlines', 'all_airlines'], true)) {
                $airlineMode = 'same_airline';
            }

            $cleanAirlineIds = array_values(array_unique(array_filter(array_map('intval', $customAirlineIds), fn (int $id): bool => $id > 0)));
            if ($airlineMode === 'custom_airlines' && empty($cleanAirlineIds)) {
                return [
                    'success' => false,
                    'message' => 'Custom Airline Restriction is selected — please choose at least one VA Airline.',
                ];
            }

            $subfleets = Subfleet::with('airline')->whereIn('id', $cleanSfIds)->get();
            if ($subfleets->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Selected subfleet(s) could not be found in phpVMS.',
                ];
            }

            $hasTypeRatingPivot = Schema::hasTable('typerating_subfleet');
            $hasTypeRatingsTable = Schema::hasTable('typeratings');

            $pairsToInsert = [];
            $uniqueFlightIds = [];
            $breakdown = [];
            $sampleFlightIds = [];

            foreach ($subfleets as $sf) {
                $sfId = (int) $sf->id;
                $sfAirlineCode = optional($sf->airline)->icao ?? optional($sf->airline)->code ?? '';
                $sfLabel = trim(($sfAirlineCode ? $sfAirlineCode . ' — ' : '') . $sf->type . ' (' . $sf->name . ')');

                // Determine allowed airline_id filter for this subfleet
                $allowedAirlines = null;
                if ($airlineMode === 'same_airline') {
                    if (!empty($sf->airline_id)) {
                        $allowedAirlines = [(int) $sf->airline_id];
                    }
                } elseif ($airlineMode === 'custom_airlines') {
                    $allowedAirlines = $cleanAirlineIds;
                }

                $candidateFlightIds = null; // null means query all flights directly
                $matchNote = '';

                if ($matchMode === 'typerating') {
                    if (!$hasTypeRatingPivot) {
                        $breakdown[] = [
                            'subfleet_id'   => $sfId,
                            'subfleet_name' => $sfLabel,
                            'matched_count' => 0,
                            'note'          => 'Type Ratings table not available in this phpVMS install.',
                        ];
                        continue;
                    }

                    $trIds = DB::table('typerating_subfleet')
                        ->where('subfleet_id', $sfId)
                        ->pluck('typerating_id')
                        ->map(fn ($v): int => (int) $v)
                        ->unique()
                        ->values()
                        ->all();

                    if (empty($trIds)) {
                        $breakdown[] = [
                            'subfleet_id'   => $sfId,
                            'subfleet_name' => $sfLabel,
                            'matched_count' => 0,
                            'note'          => 'No Type Rating linked to this subfleet yet (link it to a Type Rating or use "Same Subfleet Type").',
                        ];
                        continue;
                    }

                    $trNames = $hasTypeRatingsTable
                        ? DB::table('typeratings')->whereIn('id', $trIds)->pluck('type')->implode(', ')
                        : ('TR #' . implode(', #', $trIds));

                    $siblingSfIds = DB::table('typerating_subfleet')
                        ->whereIn('typerating_id', $trIds)
                        ->where('subfleet_id', '!=', $sfId)
                        ->pluck('subfleet_id')
                        ->map(fn ($v): int => (int) $v)
                        ->unique()
                        ->values()
                        ->all();

                    if (empty($siblingSfIds)) {
                        $breakdown[] = [
                            'subfleet_id'   => $sfId,
                            'subfleet_name' => $sfLabel,
                            'matched_count' => 0,
                            'note'          => "Type Rating [{$trNames}] has no other sibling subfleets to mirror routes from.",
                        ];
                        continue;
                    }

                    $candidateFlightIds = DB::table('flight_subfleet')
                        ->whereIn('subfleet_id', $siblingSfIds)
                        ->distinct()
                        ->pluck('flight_id')
                        ->all();

                    $matchNote = "Matched via Type Rating [{$trNames}]";
                } elseif ($matchMode === 'subfleet_type') {
                    $sfType = strtoupper(trim((string) $sf->type));
                    if ($sfType === '') {
                        $breakdown[] = [
                            'subfleet_id'   => $sfId,
                            'subfleet_name' => $sfLabel,
                            'matched_count' => 0,
                            'note'          => 'Subfleet has no Type code defined.',
                        ];
                        continue;
                    }

                    $siblingSfIds = DB::table('subfleets')
                        ->whereRaw('UPPER(TRIM(type)) = ?', [$sfType])
                        ->where('id', '!=', $sfId)
                        ->pluck('id')
                        ->map(fn ($v): int => (int) $v)
                        ->all();

                    if (empty($siblingSfIds)) {
                        $breakdown[] = [
                            'subfleet_id'   => $sfId,
                            'subfleet_name' => $sfLabel,
                            'matched_count' => 0,
                            'note'          => "No other subfleets share Type [{$sfType}].",
                        ];
                        continue;
                    }

                    $candidateFlightIds = DB::table('flight_subfleet')
                        ->whereIn('subfleet_id', $siblingSfIds)
                        ->distinct()
                        ->pluck('flight_id')
                        ->all();

                    $matchNote = "Matched via Subfleet Type [{$sfType}]";
                } else {
                    $matchNote = 'Matched all flights in selected airline scope';
                }

                if ($candidateFlightIds !== null && empty($candidateFlightIds)) {
                    $breakdown[] = [
                        'subfleet_id'   => $sfId,
                        'subfleet_name' => $sfLabel,
                        'matched_count' => 0,
                        'note'          => $matchNote . ' (0 candidate flights found).',
                    ];
                    continue;
                }

                // Flights already attached to this target subfleet
                $alreadyAttached = DB::table('flight_subfleet')
                    ->where('subfleet_id', $sfId)
                    ->pluck('flight_id')
                    ->flip()
                    ->all();

                $flightQuery = DB::table('flights')->select('id');
                if ($activeOnly) {
                    $flightQuery->where('active', 1);
                }
                if ($allowedAirlines !== null) {
                    $flightQuery->whereIn('airline_id', $allowedAirlines);
                }
                if ($candidateFlightIds !== null) {
                    $flightQuery->whereIn('id', $candidateFlightIds);
                }

                $eligibleIds = $flightQuery->pluck('id')->all();
                $missingIds = [];
                foreach ($eligibleIds as $fid) {
                    if (!isset($alreadyAttached[$fid])) {
                        $missingIds[] = $fid;
                        $pairsToInsert[] = [
                            'flight_id'   => $fid,
                            'subfleet_id' => $sfId,
                        ];
                        $uniqueFlightIds[$fid] = true;
                        if (count($sampleFlightIds) < 20) {
                            $sampleFlightIds[$fid] = true;
                        }
                    }
                }

                $breakdown[] = [
                    'subfleet_id'   => $sfId,
                    'subfleet_name' => $sfLabel,
                    'matched_count' => count($missingIds),
                    'note'          => $matchNote,
                ];
            }

            // Hydrate up to 20 sample flights for preview verification
            $samples = [];
            if (!empty($sampleFlightIds)) {
                $sampleModels = Flight::with('airline')
                    ->whereIn('id', array_keys($sampleFlightIds))
                    ->orderBy('airline_id')
                    ->orderBy('flight_number')
                    ->limit(20)
                    ->get();

                foreach ($sampleModels as $sm) {
                    $code = optional($sm->airline)->icao ?? optional($sm->airline)->code ?? '';
                    $samples[] = [
                        'id'    => (string) $sm->id,
                        'ident' => $code . $sm->flight_number,
                        'route' => $sm->dpt_airport_id . ' → ' . $sm->arr_airport_id,
                    ];
                }
            }

            return [
                'success'              => true,
                'unique_flights_count' => count($uniqueFlightIds),
                'total_links_to_add'   => count($pairsToInsert),
                'breakdown'            => $breakdown,
                'samples'              => $samples,
                'pairs_to_insert'      => $pairsToInsert,
            ];
        }

        /**
         * Executes the bulk `INSERT IGNORE` sync into `flight_subfleet` in chunks of 500.
         * Never removes or alters any existing subfleet assignments.
         */
        public static function execute(
            array $subfleetIds,
            string $matchMode = 'typerating',
            string $airlineMode = 'same_airline',
            array $customAirlineIds = [],
            bool $activeOnly = true
        ): array {
            $analysis = self::analyze($subfleetIds, $matchMode, $airlineMode, $customAirlineIds, $activeOnly);
            if (empty($analysis['success'])) {
                return $analysis;
            }

            $pairs = $analysis['pairs_to_insert'] ?? [];
            if (empty($pairs)) {
                return [
                    'success'              => true,
                    'unique_flights_count' => 0,
                    'inserted_count'       => 0,
                    'breakdown'            => $analysis['breakdown'],
                    'message'              => 'All matching flights already have the selected subfleet(s) attached — 0 changes needed.',
                ];
            }

            $inserted = 0;
            foreach (array_chunk($pairs, 500) as $chunk) {
                $inserted += DB::table('flight_subfleet')->insertOrIgnore($chunk);
            }

            return [
                'success'              => true,
                'unique_flights_count' => $analysis['unique_flights_count'],
                'inserted_count'       => $inserted,
                'breakdown'            => $analysis['breakdown'],
                'message'              => sprintf(
                    'Bulk Sync Complete! Attached %d new subfleet link(s) across %d flight(s) in phpVMS.',
                    $inserted,
                    $analysis['unique_flights_count']
                ),
            ];
        }
    }
}
