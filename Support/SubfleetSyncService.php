<?php

declare(strict_types=1);

namespace Modules\RouteRequest\Support;

use App\Models\Flight;
use App\Models\Subfleet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

if (!class_exists(SubfleetSyncService::class, false)) {
    class SubfleetSyncService
    {
        public static function ensureSyncTablesExist(): void
        {
            try {
                if (!Schema::hasTable('routerequest_sync_batches')) {
                    Schema::create('routerequest_sync_batches', function ($table) {
                        $table->increments('id');
                        $table->string('batch_ref', 40)->unique();
                        $table->string('summary', 255);
                        $table->unsignedInteger('flights_affected')->default(0);
                        $table->unsignedInteger('links_added')->default(0);
                        $table->longText('pairs_json');
                        $table->timestamp('undone_at')->nullable();
                        $table->timestamps();
                    });
                }
                if (!Schema::hasTable('routerequest_sync_presets')) {
                    Schema::create('routerequest_sync_presets', function ($table) {
                        $table->increments('id');
                        $table->string('name', 120);
                        $table->text('subfleet_ids_json');
                        $table->string('match_mode', 30)->default('typerating');
                        $table->string('airline_mode', 30)->default('same_airline');
                        $table->text('airline_ids_json')->nullable();
                        $table->boolean('active_only')->default(true);
                        $table->timestamps();
                    });
                }
            } catch (\Throwable $e) {}
        }

        public static function getPresets(): array
        {
            self::ensureSyncTablesExist();
            try {
                return DB::table('routerequest_sync_presets')
                    ->orderBy('name', 'asc')
                    ->get()
                    ->map(function ($row) {
                        return [
                            'id'           => (int) $row->id,
                            'name'         => (string) $row->name,
                            'subfleet_ids' => json_decode((string) $row->subfleet_ids_json, true) ?: [],
                            'match_mode'   => (string) $row->match_mode,
                            'airline_mode' => (string) $row->airline_mode,
                            'airline_ids'  => json_decode((string) ($row->airline_ids_json ?: '[]'), true) ?: [],
                            'active_only'  => (bool) $row->active_only,
                        ];
                    })
                    ->all();
            } catch (\Throwable $e) {
                return [];
            }
        }

        public static function savePreset(
            string $name,
            array $subfleetIds,
            string $matchMode,
            string $airlineMode,
            array $airlineIds,
            bool $activeOnly
        ): array {
            self::ensureSyncTablesExist();
            $cleanName = trim($name);
            $cleanSf = array_values(array_unique(array_filter(array_map('intval', $subfleetIds), fn (int $i): bool => $i > 0)));
            $cleanAl = array_values(array_unique(array_filter(array_map('intval', $airlineIds), fn (int $i): bool => $i > 0)));

            if ($cleanName === '') {
                return ['success' => false, 'message' => 'Please enter a name for this Sync Preset.'];
            }
            if (empty($cleanSf)) {
                return ['success' => false, 'message' => 'Select at least one Subfleet before saving a preset.'];
            }

            DB::table('routerequest_sync_presets')->insert([
                'name'              => substr($cleanName, 0, 120),
                'subfleet_ids_json' => json_encode($cleanSf),
                'match_mode'        => $matchMode,
                'airline_mode'      => $airlineMode,
                'airline_ids_json'  => json_encode($cleanAl),
                'active_only'       => $activeOnly ? 1 : 0,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            return [
                'success' => true,
                'message' => "Saved Subfleet Sync Preset \"{$cleanName}\"!",
                'presets' => self::getPresets(),
            ];
        }

        public static function deletePreset(int $id): array
        {
            self::ensureSyncTablesExist();
            DB::table('routerequest_sync_presets')->where('id', $id)->delete();
            return [
                'success' => true,
                'message' => 'Sync preset deleted.',
                'presets' => self::getPresets(),
            ];
        }

        public static function getLastActiveBatch(): ?array
        {
            self::ensureSyncTablesExist();
            try {
                $row = DB::table('routerequest_sync_batches')
                    ->whereNull('undone_at')
                    ->orderBy('id', 'desc')
                    ->first();

                if (!$row) {
                    return null;
                }

                return [
                    'id'               => (int) $row->id,
                    'batch_ref'        => (string) $row->batch_ref,
                    'summary'          => (string) $row->summary,
                    'flights_affected' => (int) $row->flights_affected,
                    'links_added'      => (int) $row->links_added,
                    'created_at'       => $row->created_at ? date('d M Y H:i', strtotime((string) $row->created_at)) : '',
                ];
            } catch (\Throwable $e) {
                return null;
            }
        }

        /**
         * Cleanly deletes ONLY the exact `(flight_id, subfleet_id)` rows inserted by the last active sync batch.
         */
        public static function undoLastBatch(): array
        {
            self::ensureSyncTablesExist();
            $row = DB::table('routerequest_sync_batches')
                ->whereNull('undone_at')
                ->orderBy('id', 'desc')
                ->first();

            if (!$row) {
                return ['success' => false, 'message' => 'No active Subfleet Sync batch is available to undo.'];
            }

            $pairs = json_decode((string) $row->pairs_json, true);
            if (!is_array($pairs) || empty($pairs)) {
                DB::table('routerequest_sync_batches')->where('id', $row->id)->update(['undone_at' => now()]);
                return ['success' => true, 'message' => 'Batch marked undone (0 pairs recorded).'];
            }

            // Group by subfleet_id for fast chunked deletes
            $bySubfleet = [];
            foreach ($pairs as $p) {
                $sfId = (int) ($p['subfleet_id'] ?? 0);
                $fid  = (string) ($p['flight_id'] ?? '');
                if ($sfId > 0 && $fid !== '') {
                    $bySubfleet[$sfId][] = $fid;
                }
            }

            $deleted = 0;
            foreach ($bySubfleet as $sfId => $fids) {
                foreach (array_chunk($fids, 500) as $chunk) {
                    $deleted += DB::table('flight_subfleet')
                        ->where('subfleet_id', $sfId)
                        ->whereIn('flight_id', $chunk)
                        ->delete();
                }
            }

            DB::table('routerequest_sync_batches')
                ->where('id', $row->id)
                ->update(['undone_at' => now(), 'updated_at' => now()]);

            return [
                'success'       => true,
                'deleted_count' => $deleted,
                'last_batch'    => self::getLastActiveBatch(),
                'message'       => "Undo Complete! Removed {$deleted} subfleet link(s) from batch {$row->batch_ref} ({$row->summary}).",
            ];
        }

        public static function analyze(
            array $subfleetIds,
            string $matchMode = 'typerating',
            string $airlineMode = 'same_airline',
            array $customAirlineIds = [],
            bool $activeOnly = true
        ): array {
            $cleanSfIds = array_values(array_unique(array_filter(array_map('intval', $subfleetIds), fn (int $id): bool => $id > 0)));
            if (empty($cleanSfIds)) {
                return ['success' => false, 'message' => 'Please select at least one target Subfleet to sync.'];
            }

            if (!in_array($matchMode, ['typerating', 'subfleet_type', 'all_flights'], true)) {
                $matchMode = 'typerating';
            }
            if (!in_array($airlineMode, ['same_airline', 'custom_airlines', 'all_airlines'], true)) {
                $airlineMode = 'same_airline';
            }

            $cleanAirlineIds = array_values(array_unique(array_filter(array_map('intval', $customAirlineIds), fn (int $id): bool => $id > 0)));
            if ($airlineMode === 'custom_airlines' && empty($cleanAirlineIds)) {
                return ['success' => false, 'message' => 'Custom Airline Restriction is selected — please choose at least one VA Airline.'];
            }

            $subfleets = Subfleet::with('airline')->whereIn('id', $cleanSfIds)->get();
            if ($subfleets->isEmpty()) {
                return ['success' => false, 'message' => 'Selected subfleet(s) could not be found in phpVMS.'];
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

                $allowedAirlines = null;
                if ($airlineMode === 'same_airline') {
                    if (!empty($sf->airline_id)) {
                        $allowedAirlines = [(int) $sf->airline_id];
                    }
                } elseif ($airlineMode === 'custom_airlines') {
                    $allowedAirlines = $cleanAirlineIds;
                }

                $candidateFlightIds = null;
                $matchNote = '';

                if ($matchMode === 'typerating') {
                    if (!$hasTypeRatingPivot) {
                        $breakdown[] = ['subfleet_id' => $sfId, 'subfleet_name' => $sfLabel, 'matched_count' => 0, 'note' => 'Type Ratings table not available.'];
                        continue;
                    }
                    $trIds = DB::table('typerating_subfleet')->where('subfleet_id', $sfId)->pluck('typerating_id')->map(fn ($v): int => (int) $v)->unique()->values()->all();
                    if (empty($trIds)) {
                        $breakdown[] = ['subfleet_id' => $sfId, 'subfleet_name' => $sfLabel, 'matched_count' => 0, 'note' => 'No Type Rating linked yet.'];
                        continue;
                    }
                    $trNames = $hasTypeRatingsTable
                        ? DB::table('typeratings')->whereIn('id', $trIds)->pluck('type')->implode(', ')
                        : ('TR #' . implode(', #', $trIds));
                    $siblingSfIds = DB::table('typerating_subfleet')->whereIn('typerating_id', $trIds)->where('subfleet_id', '!=', $sfId)->pluck('subfleet_id')->map(fn ($v): int => (int) $v)->unique()->values()->all();
                    if (empty($siblingSfIds)) {
                        $breakdown[] = ['subfleet_id' => $sfId, 'subfleet_name' => $sfLabel, 'matched_count' => 0, 'note' => "Type Rating [{$trNames}] has no sibling subfleets."];
                        continue;
                    }
                    $candidateFlightIds = DB::table('flight_subfleet')->whereIn('subfleet_id', $siblingSfIds)->distinct()->pluck('flight_id')->all();
                    $matchNote = "Matched via Type Rating [{$trNames}]";
                } elseif ($matchMode === 'subfleet_type') {
                    $sfType = strtoupper(trim((string) $sf->type));
                    if ($sfType === '') {
                        $breakdown[] = ['subfleet_id' => $sfId, 'subfleet_name' => $sfLabel, 'matched_count' => 0, 'note' => 'No Type code defined.'];
                        continue;
                    }
                    $siblingSfIds = DB::table('subfleets')->whereRaw('UPPER(TRIM(type)) = ?', [$sfType])->where('id', '!=', $sfId)->pluck('id')->map(fn ($v): int => (int) $v)->all();
                    if (empty($siblingSfIds)) {
                        $breakdown[] = ['subfleet_id' => $sfId, 'subfleet_name' => $sfLabel, 'matched_count' => 0, 'note' => "No other subfleets share Type [{$sfType}]."];
                        continue;
                    }
                    $candidateFlightIds = DB::table('flight_subfleet')->whereIn('subfleet_id', $siblingSfIds)->distinct()->pluck('flight_id')->all();
                    $matchNote = "Matched via Subfleet Type [{$sfType}]";
                } else {
                    $matchNote = 'Matched all flights in selected airline scope';
                }

                if ($candidateFlightIds !== null && empty($candidateFlightIds)) {
                    $breakdown[] = ['subfleet_id' => $sfId, 'subfleet_name' => $sfLabel, 'matched_count' => 0, 'note' => $matchNote . ' (0 candidate flights).'];
                    continue;
                }

                $alreadyAttached = DB::table('flight_subfleet')->where('subfleet_id', $sfId)->pluck('flight_id')->flip()->all();

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
                        $pairsToInsert[] = ['flight_id' => $fid, 'subfleet_id' => $sfId];
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

        public static function execute(
            array $subfleetIds,
            string $matchMode = 'typerating',
            string $airlineMode = 'same_airline',
            array $customAirlineIds = [],
            bool $activeOnly = true
        ): array {
            self::ensureSyncTablesExist();
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
                    'last_batch'           => self::getLastActiveBatch(),
                    'message'              => 'All matching flights already have the selected subfleet(s) attached — 0 changes needed.',
                ];
            }

            $inserted = 0;
            foreach (array_chunk($pairs, 500) as $chunk) {
                $inserted += DB::table('flight_subfleet')->insertOrIgnore($chunk);
            }

            $batchRef = 'SYNC-' . strtoupper(Str::random(8));
            $sfNames = array_map(fn ($b) => $b['subfleet_name'], array_filter($analysis['breakdown'], fn ($b) => $b['matched_count'] > 0));
            $summary = substr(implode(', ', $sfNames) ?: 'Bulk Subfleet Sync', 0, 250);

            DB::table('routerequest_sync_batches')->insert([
                'batch_ref'        => $batchRef,
                'summary'          => $summary,
                'flights_affected' => (int) $analysis['unique_flights_count'],
                'links_added'      => $inserted,
                'pairs_json'       => json_encode($pairs),
                'undone_at'        => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            return [
                'success'              => true,
                'batch_ref'            => $batchRef,
                'unique_flights_count' => $analysis['unique_flights_count'],
                'inserted_count'       => $inserted,
                'breakdown'            => $analysis['breakdown'],
                'last_batch'           => self::getLastActiveBatch(),
                'message'              => sprintf(
                    'Bulk Sync Complete (%s)! Attached %d new subfleet link(s) across %d flight(s).',
                    $batchRef,
                    $inserted,
                    $analysis['unique_flights_count']
                ),
            ];
        }
    }
}
