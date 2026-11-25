<?php

declare(strict_types=1);

namespace Modules\AfterburnerDispatch\Support;

use App\Models\Aircraft;
use App\Models\Airport;
use App\Models\Flight;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (!class_exists(TravelDispatchService::class, false)) {
    class TravelDispatchService
    {
        public static function ensureMoveRequestsTableExists(): void
        {
            try {
                if (!Schema::hasTable('routerequest_move_requests')) {
                    Schema::create('routerequest_move_requests', function ($table) {
                        $table->increments('id');
                        $table->unsignedInteger('user_id')->index();
                        $table->string('flight_id', 36)->nullable()->index();
                        $table->unsignedInteger('aircraft_id')->nullable()->index();
                        $table->string('pilot_from_airport', 10)->nullable();
                        $table->string('aircraft_from_airport', 10)->nullable();
                        $table->string('to_airport_id', 10)->index();
                        $table->decimal('pilot_cost', 12, 2)->default(0);
                        $table->decimal('aircraft_cost', 12, 2)->default(0);
                        $table->decimal('total_cost', 12, 2)->default(0);
                        $table->decimal('pilot_balance', 12, 2)->default(0);
                        $table->string('status', 20)->default('pending')->index();
                        $table->text('pilot_notes')->nullable();
                        $table->text('admin_notes')->nullable();
                        $table->unsignedInteger('resolved_by')->nullable();
                        $table->timestamp('resolved_at')->nullable();
                        $table->timestamps();
                    });
                }
            } catch (\Throwable $e) {}
        }

        /**
         * Returns true ONLY when all prerequisites are met:
         * 1. Tier 3 Flagship (`pax_studio` feature unlocked)
         * 2. PaxStudio module installed
         * 3. DisposableBasic module installed & enabled
         * 4. ManualPirepControl installed & Manual PIREP filing disabled
         */
        public static function isFeatureActive(?array $entitlements = null): bool
        {
            $ent = $entitlements ?? DrmEntitlements::current(false);
            if (empty($ent['features']['pax_studio']) || empty($ent['paxstudio_installed'])) {
                return false;
            }
            if (!CompanionModuleManager::isModuleEnabled('DisposableBasic')) {
                return false;
            }
            return CompanionModuleManager::isManualPirepDisabled();
        }

        /**
         * Reads JumpSeat & Aircraft Transfer per-NM rates from DisposableBasic's settings table.
         * Never overrides or duplicates DisposableBasic settings so other modules stay intact.
         */
        public static function getDisposableBasicRates(): array
        {
            $pilotPerNm = 0.25;
            $aircraftPerNm = 0.50;
            $minFee = 25.00;
            $source = 'DisposableBasic Default';

            try {
                if (Schema::hasTable('disposable_settings')) {
                    $rows = DB::table('disposable_settings')->get();
                    foreach ($rows as $r) {
                        $k = strtolower((string) ($r->key ?? $r->name ?? ''));
                        $v = (float) ($r->value ?? 0);
                        if ($v > 0) {
                            if (str_contains($k, 'jumpseat') && (str_contains($k, 'base') || str_contains($k, 'price') || str_contains($k, 'rate'))) {
                                $pilotPerNm = $v;
                                $source = 'DisposableBasic';
                            } elseif ((str_contains($k, 'transfer') || str_contains($k, 'aircraft')) && (str_contains($k, 'base') || str_contains($k, 'price') || str_contains($k, 'rate'))) {
                                $aircraftPerNm = $v;
                                $source = 'DisposableBasic';
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {}

            return [
                'pilot_per_nm'    => $pilotPerNm,
                'aircraft_per_nm' => $aircraftPerNm,
                'min_fee'         => $minFee,
                'source'          => $source,
            ];
        }

        /**
         * Reads a pilot's current phpVMS journal balance in major currency units (e.g., USD).
         */
        public static function getPilotBalance(User $user): float
        {
            try {
                $journal = $user->journal;
                if ($journal && isset($journal->balance)) {
                    $bal = $journal->balance;
                    if (is_object($bal) && method_exists($bal, 'toUnit')) {
                        return (float) $bal->toUnit();
                    }
                    if (is_numeric($bal)) {
                        // phpVMS journals store minor units (cents) as integer when uncast
                        return ((float) $bal) / 100.0;
                    }
                }
            } catch (\Throwable $e) {}

            try {
                $row = DB::table('journals')
                    ->where('morphed_type', User::class)
                    ->where('morphed_id', $user->id)
                    ->first();
                if ($row && isset($row->balance)) {
                    return ((float) $row->balance) / 100.0;
                }
            } catch (\Throwable $e) {}

            return 0.0;
        }

        /**
         * Deducts travel/transfer cost from the pilot's phpVMS journal safely.
         */
        private static function deductPilotBalance(User $user, float $amountMajor, string $memo): bool
        {
            if ($amountMajor <= 0.0) {
                return true;
            }
            $cents = (int) round($amountMajor * 100);

            try {
                $journal = $user->journal;
                if ($journal && class_exists(\App\Support\Money::class) && method_exists($journal, 'debit')) {
                    $money = \App\Support\Money::createFromAmount($amountMajor);
                    $journal->debit($money, $memo);
                    return true;
                }
            } catch (\Throwable $e) {}

            // Fallback direct atomic journal update if Money helper signature differs
            try {
                $row = DB::table('journals')
                    ->where('morphed_type', User::class)
                    ->where('morphed_id', $user->id)
                    ->first();
                if ($row) {
                    DB::table('journals')
                        ->where('id', $row->id)
                        ->update([
                            'balance'    => (int) $row->balance - $cents,
                            'updated_at' => now(),
                        ]);
                    return true;
                }
            } catch (\Throwable $e) {}

            return false;
        }

        /**
         * Evaluates whether a pilot needs to travel (or move an aircraft) to fly the target flight,
         * and returns sorted eligible aircraft options + pilot pay affordability.
         */
        public static function buildTravelOptions(User $user, string $flightId, string $sortBy = 'cheapest'): array
        {
            self::ensureMoveRequestsTableExists();

            $flight = Flight::with(['subfleets', 'airline'])->find($flightId);
            if (!$flight) {
                return ['success' => false, 'message' => 'Flight not found.'];
            }

            $depId = strtoupper((string) $flight->dpt_airport_id);
            $pilotCurr = strtoupper((string) ($user->curr_airport_id ?: $user->home_airport_id ?: ''));
            $pilotNeedsTravel = ($pilotCurr !== '' && $pilotCurr !== $depId);

            $subfleetIds = $flight->subfleets ? $flight->subfleets->pluck('id')->all() : [];
            $acQuery = Aircraft::with('subfleet')->where('status', 'A');
            if (!empty($subfleetIds)) {
                $acQuery->whereIn('subfleet_id', $subfleetIds);
            }
            // Exclude aircraft currently in flight (state = 1 or 2)
            $acQuery->where(function ($q) {
                $q->whereNull('state')->orWhere('state', 0);
            });

            $aircraftList = $acQuery->get();
            if ($aircraftList->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No active, idle aircraft are assigned to this flight\'s subfleet(s).',
                ];
            }

            // Check if at least one eligible aircraft is already parked at DEP
            $aircraftAtDep = $aircraftList->first(fn ($ac) => strtoupper((string) $ac->airport_id) === $depId);
            if (!$pilotNeedsTravel && $aircraftAtDep) {
                return [
                    'success'        => true,
                    'needs_reposition' => false,
                    'message'        => 'Both you and an eligible aircraft are already at ' . $depId . '!',
                ];
            }

            // Load airport coordinates for distance & cost calculations
            $neededAirports = array_values(array_unique(array_filter(array_merge(
                [$depId, $pilotCurr],
                $aircraftList->pluck('airport_id')->map(fn ($v) => strtoupper((string) $v))->all()
            ))));
            $airports = Airport::whereIn('id', $neededAirports)->get()->keyBy(fn ($a) => strtoupper((string) $a->id));
            $depApt = $airports->get($depId);

            $rates = self::getDisposableBasicRates();

            // Pilot travel cost
            $pilotDistNm = 0;
            $pilotCost = 0.0;
            if ($pilotNeedsTravel) {
                $pApt = $airports->get($pilotCurr);
                if ($pApt && $depApt && ($pApt->lat || $pApt->lon) && ($depApt->lat || $depApt->lon)) {
                    $pilotDistNm = RouteEstimatorService::calculateGreatCircleNm((float) $pApt->lat, (float) $pApt->lon, (float) $depApt->lat, (float) $depApt->lon);
                } else {
                    $pilotDistNm = 250;
                }
                $pilotCost = round(max($rates['min_fee'], $pilotDistNm * $rates['pilot_per_nm']), 2);
            }

            // Last PIREP timestamps for sorting ("Last Used First" & "Not In Use Recently")
            $acIds = $aircraftList->pluck('id')->all();
            $pilotLastUsedMap = [];
            $globalLastUsedMap = [];
            try {
                if (Schema::hasTable('pireps') && !empty($acIds)) {
                    $pilotLastUsedMap = DB::table('pireps')
                        ->select('aircraft_id', DB::raw('MAX(created_at) as last_used'))
                        ->where('user_id', $user->id)
                        ->whereIn('aircraft_id', $acIds)
                        ->groupBy('aircraft_id')
                        ->pluck('last_used', 'aircraft_id')
                        ->all();

                    $globalLastUsedMap = DB::table('pireps')
                        ->select('aircraft_id', DB::raw('MAX(created_at) as last_used'))
                        ->whereIn('aircraft_id', $acIds)
                        ->groupBy('aircraft_id')
                        ->pluck('last_used', 'aircraft_id')
                        ->all();
                }
            } catch (\Throwable $e) {}

            $pilotBalance = round(self::getPilotBalance($user), 2);
            $options = [];

            foreach ($aircraftList as $ac) {
                $acAptId = strtoupper((string) ($ac->airport_id ?: ''));
                $acNeedsMove = ($acAptId !== $depId);
                $acDistNm = 0;
                $acCost = 0.0;

                if ($acNeedsMove) {
                    $aApt = $airports->get($acAptId);
                    if ($aApt && $depApt && ($aApt->lat || $aApt->lon) && ($depApt->lat || $depApt->lon)) {
                        $acDistNm = RouteEstimatorService::calculateGreatCircleNm((float) $aApt->lat, (float) $aApt->lon, (float) $depApt->lat, (float) $depApt->lon);
                    } else {
                        $acDistNm = 300;
                    }
                    $acCost = round(max($rates['min_fee'], $acDistNm * $rates['aircraft_per_nm']), 2);
                }

                $totalCost = round($pilotCost + $acCost, 2);
                $pilotLast = $pilotLastUsedMap[$ac->id] ?? null;
                $globalLast = $globalLastUsedMap[$ac->id] ?? null;

                $options[] = [
                    'aircraft_id'      => (int) $ac->id,
                    'registration'     => (string) $ac->registration,
                    'name'             => (string) $ac->name,
                    'icao'             => (string) ($ac->icao ?: optional($ac->subfleet)->type ?: ''),
                    'airport_id'       => $acAptId ?: 'N/A',
                    'needs_move'       => $acNeedsMove,
                    'distance_nm'      => $acDistNm,
                    'aircraft_cost'    => $acCost,
                    'pilot_cost'       => $pilotCost,
                    'total_cost'       => $totalCost,
                    'can_afford'       => $pilotBalance >= $totalCost,
                    'pilot_last_used'  => $pilotLast ? date('d M Y', strtotime((string) $pilotLast)) : 'Never',
                    'pilot_last_ts'    => $pilotLast ? strtotime((string) $pilotLast) : 0,
                    'global_last_used' => $globalLast ? date('d M Y', strtotime((string) $globalLast)) : 'Never Used',
                    'global_last_ts'   => $globalLast ? strtotime((string) $globalLast) : 0,
                ];
            }

            if ($sortBy === 'last_used') {
                usort($options, fn ($a, $b) => ($b['pilot_last_ts'] <=> $a['pilot_last_ts']) ?: ($a['total_cost'] <=> $b['total_cost']));
            } elseif ($sortBy === 'least_recent') {
                usort($options, fn ($a, $b) => ($a['global_last_ts'] <=> $b['global_last_ts']) ?: ($a['total_cost'] <=> $b['total_cost']));
            } else {
                // default: cheapest / closest
                usort($options, fn ($a, $b) => ($a['total_cost'] <=> $b['total_cost']) ?: ($a['distance_nm'] <=> $b['distance_nm']));
            }

            return [
                'success'            => true,
                'needs_reposition'   => true,
                'flight_id'          => (string) $flight->id,
                'flight_ident'       => (optional($flight->airline)->icao ?? optional($flight->airline)->iata ?? '') . $flight->flight_number,
                'dep_airport_id'     => $depId,
                'arr_airport_id'     => strtoupper((string) $flight->arr_airport_id),
                'pilot_curr_airport' => $pilotCurr ?: 'N/A',
                'pilot_needs_travel' => $pilotNeedsTravel,
                'pilot_dist_nm'      => $pilotDistNm,
                'pilot_cost'         => $pilotCost,
                'pilot_balance'      => $pilotBalance,
                'rates_source'       => $rates['source'],
                'aircraft_options'   => array_slice($options, 0, 25),
            ];
        }

        /**
         * Executes paid self-travel & aircraft repositioning ONLY if the pilot has enough Pilot Pay.
         */
        public static function executeSelfTravel(User $user, string $flightId, int $aircraftId): array
        {
            $opts = self::buildTravelOptions($user, $flightId, 'cheapest');
            if (empty($opts['success'])) {
                return $opts;
            }
            if (empty($opts['needs_reposition'])) {
                return ['success' => true, 'message' => 'Already positioned at departure airport.'];
            }

            $chosen = null;
            foreach ($opts['aircraft_options'] as $opt) {
                if ((int) $opt['aircraft_id'] === $aircraftId) {
                    $chosen = $opt;
                    break;
                }
            }

            if (!$chosen) {
                return ['success' => false, 'message' => 'Selected aircraft is no longer eligible or available.'];
            }

            $totalCost = (float) $chosen['total_cost'];
            $balance = (float) $opts['pilot_balance'];

            if ($balance < $totalCost) {
                return [
                    'success'            => false,
                    'insufficient_funds' => true,
                    'message'            => sprintf(
                        'Insufficient Pilot Pay! You have $%.2f but need $%.2f to move both yourself ($%.2f) and aircraft %s ($%.2f). Self-travel is blocked — please click "Ask Admin to Move Me".',
                        $balance,
                        $totalCost,
                        $chosen['pilot_cost'],
                        $chosen['registration'],
                        $chosen['aircraft_cost']
                    ),
                ];
            }

            $depId = (string) $opts['dep_airport_id'];
            $memo = "RouteRequest Travel & Aircraft Ferry ({$chosen['registration']}) to {$depId}";
            if (!self::deductPilotBalance($user, $totalCost, $memo)) {
                return ['success' => false, 'message' => 'Could not process Pilot Pay deduction. Please contact an administrator.'];
            }

            if (!empty($opts['pilot_needs_travel'])) {
                $user->curr_airport_id = $depId;
                $user->save();
            }

            if (!empty($chosen['needs_move'])) {
                Aircraft::where('id', $aircraftId)->update(['airport_id' => $depId]);
            }

            return [
                'success' => true,
                'message' => sprintf('Moved to %s with aircraft %s ($%.2f deducted from Pilot Pay).', $depId, $chosen['registration'], $totalCost),
            ];
        }

        /**
         * Submits a Pilot Move Request to Admins when Pilot Pay is insufficient.
         */
        public static function requestAdminMove(User $user, string $flightId, int $aircraftId, string $notes = ''): array
        {
            self::ensureMoveRequestsTableExists();
            $opts = self::buildTravelOptions($user, $flightId, 'cheapest');
            if (empty($opts['success'])) {
                return $opts;
            }

            $chosen = null;
            foreach (($opts['aircraft_options'] ?? []) as $opt) {
                if ((int) $opt['aircraft_id'] === $aircraftId) {
                    $chosen = $opt;
                    break;
                }
            }
            if (!$chosen) {
                return ['success' => false, 'message' => 'Please select an eligible aircraft before requesting an admin move.'];
            }

            $existing = DB::table('routerequest_move_requests')
                ->where('user_id', $user->id)
                ->where('to_airport_id', $opts['dep_airport_id'])
                ->where('status', 'pending')
                ->first();

            if ($existing) {
                return ['success' => false, 'message' => "You already have a pending Admin Move Request to {$opts['dep_airport_id']}."];
            }

            DB::table('routerequest_move_requests')->insert([
                'user_id'               => $user->id,
                'flight_id'             => $flightId,
                'aircraft_id'           => $aircraftId,
                'pilot_from_airport'    => $opts['pilot_curr_airport'],
                'aircraft_from_airport' => $chosen['airport_id'],
                'to_airport_id'         => $opts['dep_airport_id'],
                'pilot_cost'            => $chosen['pilot_cost'],
                'aircraft_cost'         => $chosen['aircraft_cost'],
                'total_cost'            => $chosen['total_cost'],
                'pilot_balance'         => $opts['pilot_balance'],
                'status'                => 'pending',
                'pilot_notes'           => substr(trim($notes), 0, 500) ?: null,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);

            try {
                DiscordNotifier::notifyStaffMoveRequest([
                    'pilot_name'    => trim(($user->ident ?? ('Pilot #' . $user->id)) . ' — ' . ($user->name_private ?? $user->name ?? '')),
                    'pilot_from'    => $opts['pilot_curr_airport'],
                    'aircraft_reg'  => $chosen['registration'],
                    'aircraft_from' => $chosen['airport_id'],
                    'to_airport'    => $opts['dep_airport_id'],
                    'total_cost'    => $chosen['total_cost'],
                    'pilot_balance' => $opts['pilot_balance'],
                ]);
            } catch (\Throwable $e) {}

            return [
                'success' => true,
                'message' => "Admin Move Request submitted for {$opts['dep_airport_id']} with aircraft {$chosen['registration']}! Dispatch has been notified and will Allow or Deny your move shortly.",
            ];
        }

        /**
         * Admin action: Allow or Deny a pending Pilot Move Request.
         */
        public static function resolveMoveRequest(int $moveId, string $decision, int $adminUserId, string $adminNotes = ''): array
        {
            self::ensureMoveRequestsTableExists();
            $req = DB::table('routerequest_move_requests')->where('id', $moveId)->first();
            if (!$req) {
                return ['success' => false, 'message' => 'Move request not found.'];
            }
            if ($req->status !== 'pending') {
                return ['success' => false, 'message' => 'This move request has already been resolved.'];
            }

            $allow = ($decision === 'allow' || $decision === 'approved');

            if ($allow) {
                User::where('id', $req->user_id)->update(['curr_airport_id' => $req->to_airport_id]);
                if (!empty($req->aircraft_id)) {
                    Aircraft::where('id', $req->aircraft_id)->update(['airport_id' => $req->to_airport_id]);
                }
            }

            DB::table('routerequest_move_requests')
                ->where('id', $moveId)
                ->update([
                    'status'      => $allow ? 'approved' : 'denied',
                    'admin_notes' => substr(trim($adminNotes), 0, 500) ?: null,
                    'resolved_by' => $adminUserId,
                    'resolved_at' => now(),
                    'updated_at'  => now(),
                ]);

            return [
                'success' => true,
                'message' => $allow
                    ? "Move Request #{$moveId} ALLOWED — Pilot and Aircraft moved to {$req->to_airport_id}!"
                    : "Move Request #{$moveId} DENIED.",
            ];
        }

        public static function getRecentMoveRequests(): array
        {
            self::ensureMoveRequestsTableExists();
            try {
                $rows = DB::table('routerequest_move_requests as m')
                    ->leftJoin('users as u', 'u.id', '=', 'm.user_id')
                    ->leftJoin('aircraft as a', 'a.id', '=', 'm.aircraft_id')
                    ->select(
                        'm.*',
                        'u.pilot_id as pilot_ident',
                        'u.name as pilot_name',
                        'a.registration as ac_registration',
                        'a.icao as ac_icao'
                    )
                    ->orderByRaw("CASE WHEN m.status = 'pending' THEN 0 ELSE 1 END")
                    ->orderBy('m.id', 'desc')
                    ->limit(30)
                    ->get();

                return $rows->all();
            } catch (\Throwable $e) {
                return [];
            }
        }
    }
}
