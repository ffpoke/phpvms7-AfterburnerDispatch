<?php

declare(strict_types=1);

namespace Modules\AfterburnerDispatch\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

if (!class_exists(DispatchAnalyticsService::class, false)) {
    class DispatchAnalyticsService
    {
        public static function getSnapshot(): array
        {
            return [
                '30d'           => self::computeWindow(30),
                '90d'           => self::computeWindow(90),
                '180d'          => self::computeWindow(180),
                'fleet_health'  => self::computeFleetHealth(),
            ];
        }

        private static function computeFleetHealth(): array
        {
            $out = ['total_subfleets' => 0, 'missing_fares' => 0, 'over_50_names' => 0, 'simbrief_coverage_pct' => 0];
            try {
                if (!Schema::hasTable('subfleets')) {
                    return $out;
                }
                $sfs = DB::table('subfleets')->select('id', 'name', 'type', 'simbrief_type')->get();
                $out['total_subfleets'] = $sfs->count();
                if ($out['total_subfleets'] === 0) {
                    return $out;
                }
                $fareIds = Schema::hasTable('subfleet_fare')
                    ? DB::table('subfleet_fare')->distinct()->pluck('subfleet_id')->flip()->all()
                    : [];
                $sbCount = 0;
                foreach ($sfs as $sf) {
                    if (!isset($fareIds[$sf->id])) {
                        $out['missing_fares']++;
                    }
                    if (strlen((string) ($sf->name ?? '')) > 50 || strlen((string) ($sf->type ?? '')) > 50) {
                        $out['over_50_names']++;
                    }
                    if (trim((string) ($sf->simbrief_type ?? '')) !== '') {
                        $sbCount++;
                    }
                }
                $out['simbrief_coverage_pct'] = (int) round(($sbCount / $out['total_subfleets']) * 100);
            } catch (\Throwable $e) {}
            return $out;
        }

        private static function computeWindow(int $days): array
        {
            $empty = [
                'days'               => $days,
                'total'              => 0,
                'completed'          => 0,
                'pending'            => 0,
                'completion_rate'    => 0,
                'avg_turnaround_hrs' => null,
                'top_airports'       => [],
                'top_typeratings'    => [],
            ];

            try {
                if (!Schema::hasTable('route_requests')) {
                    return $empty;
                }

                $since = Carbon::now()->subDays($days);
                $rows = DB::table('route_requests')
                    ->select('dep_airport_id', 'arr_airport_id', 'typerating_id', 'status', 'created_at', 'updated_at')
                    ->where('created_at', '>=', $since)
                    ->get();

                $total = $rows->count();
                if ($total === 0) {
                    return $empty;
                }

                $completed = 0;
                $pending = 0;
                $turnaroundMins = [];
                $airportCounts = [];
                $trCounts = [];

                foreach ($rows as $r) {
                    $st = strtolower((string) ($r->status ?? ''));
                    if ($st === 'completed') {
                        $completed++;
                        if (!empty($r->created_at) && !empty($r->updated_at)) {
                            $diff = Carbon::parse($r->created_at)->diffInMinutes(Carbon::parse($r->updated_at), false);
                            if ($diff >= 0 && $diff <= 60 * 24 * 60) {
                                $turnaroundMins[] = $diff;
                            }
                        }
                    } elseif (in_array($st, ['pending', 'approved', 'in_progress'], true)) {
                        $pending++;
                    }

                    $dep = strtoupper(trim((string) ($r->dep_airport_id ?? '')));
                    $arr = strtoupper(trim((string) ($r->arr_airport_id ?? '')));
                    if ($dep !== '') {
                        $airportCounts[$dep] = ($airportCounts[$dep] ?? 0) + 1;
                    }
                    if ($arr !== '' && $arr !== $dep) {
                        $airportCounts[$arr] = ($airportCounts[$arr] ?? 0) + 1;
                    }

                    $trId = (int) ($r->typerating_id ?? 0);
                    if ($trId > 0) {
                        $trCounts[$trId] = ($trCounts[$trId] ?? 0) + 1;
                    }
                }

                arsort($airportCounts);
                $topAirports = [];
                foreach (array_slice($airportCounts, 0, 5, true) as $icao => $cnt) {
                    $topAirports[] = ['icao' => $icao, 'count' => $cnt];
                }

                arsort($trCounts);
                $topTrIds = array_slice(array_keys($trCounts), 0, 5);
                $trLabels = [];
                if (!empty($topTrIds) && Schema::hasTable('typeratings')) {
                    try {
                        $trLabels = DB::table('typeratings')
                            ->whereIn('id', $topTrIds)
                            ->pluck('type', 'id')
                            ->toArray();
                    } catch (\Throwable $e) {}
                }
                $topTypeRatings = [];
                foreach ($topTrIds as $tid) {
                    $topTypeRatings[] = [
                        'label' => (string) ($trLabels[$tid] ?? ('TR #' . $tid)),
                        'count' => (int) $trCounts[$tid],
                    ];
                }

                $avgHrs = !empty($turnaroundMins)
                    ? round((array_sum($turnaroundMins) / count($turnaroundMins)) / 60, 1)
                    : null;

                return [
                    'days'               => $days,
                    'total'              => $total,
                    'completed'          => $completed,
                    'pending'            => $pending,
                    'completion_rate'    => (int) round(($completed / $total) * 100),
                    'avg_turnaround_hrs' => $avgHrs,
                    'top_airports'       => $topAirports,
                    'top_typeratings'    => $topTypeRatings,
                ];
            } catch (\Throwable $e) {
                return $empty;
            }
        }

        public static function runSystemSelfTest(): array
        {
            $checks = [];

            // 1. PHP Version & Extensions
            $phpOk = version_compare(PHP_VERSION, '8.3.36', '>=');
            $phpRec = version_compare(PHP_VERSION, '8.3.37', '>=');
            $checks[] = [
                'name'   => 'PHP Runtime',
                'status' => $phpOk ? ($phpRec ? 'pass' : 'warn') : 'fail',
                'detail' => 'PHP ' . PHP_VERSION . ($phpRec ? ' (meets 8.3.37+ recommendation)' : ($phpOk ? ' (meets 8.3.36 minimum; 8.3.37+ recommended)' : ' (below 8.3.36 minimum)')),
            ];

            $hasXchacha = function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_decrypt');
            $hasEd25519 = function_exists('sodium_crypto_sign_verify_detached');
            $hasZip = class_exists(\ZipArchive::class);
            $checks[] = [
                'name'   => 'Crypto & Archive Extensions',
                'status' => ($hasXchacha && $hasEd25519 && $hasZip) ? 'pass' : 'fail',
                'detail' => 'libsodium XChaCha20: ' . ($hasXchacha ? 'OK' : 'MISSING') . ' | Ed25519: ' . ($hasEd25519 ? 'OK' : 'MISSING') . ' | ZipArchive: ' . ($hasZip ? 'OK' : 'MISSING'),
            ];

            // 2. DropSchedule Ed25519 Integrity
            $dsValid = DropSchedule::isIntegrityValid();
            $checks[] = [
                'name'   => 'DropSchedule Ed25519 Signature',
                'status' => $dsValid ? 'pass' : 'fail',
                'detail' => 'Schedule ' . DropSchedule::SCHEDULE_VERSION . ($dsValid ? ' — Ed25519 + ChaCha20 Verified' : ' — Integrity Check Failed'),
            ];

            // 3. AfterburnerServer DRM Handshake
            $ent = DrmEntitlements::current(true);
            $validKey = !empty($ent['valid']) && empty($ent['version_blocked']);
            $tierStr = strtoupper((string) ($ent['tier'] ?? 'basic'));
            $errStr = !empty($ent['error']) ? (' — ' . $ent['error']) : '';
            $checks[] = [
                'name'   => 'AfterburnerServer DRM Handshake',
                'status' => $validKey ? 'pass' : 'warn',
                'detail' => 'Tier: ' . $tierStr . ' | Verified: ' . ($validKey ? 'Yes' : 'No') . $errStr,
            ];

            // 4. Built-in phpVMS Cron Listeners
            $dailyBound = class_exists(\App\Events\CronDaily::class) && Event::hasListeners(\App\Events\CronDaily::class);
            $hourlyBound = class_exists(\App\Events\CronHourly::class) && Event::hasListeners(\App\Events\CronHourly::class);
            $checks[] = [
                'name'   => 'Native phpVMS Cron Hooks',
                'status' => ($dailyBound && $hourlyBound) ? 'pass' : 'warn',
                'detail' => 'CronDaily (Batch Digest): ' . ($dailyBound ? 'Registered' : 'Not detected') . ' | CronHourly (Force-Update): ' . ($hourlyBound ? 'Registered' : 'Not detected'),
            ];

            // 5. Database Tables & Module Folder Writability
            $tablesOk = Schema::hasTable('route_requests') && Schema::hasTable('routerequest_client_settings');
            $modPath = base_path('modules/AfterburnerDispatch');
            $writable = is_dir($modPath) && is_writable($modPath);
            $checks[] = [
                'name'   => 'Database & 1-Click Updater Permissions',
                'status' => ($tablesOk && $writable) ? 'pass' : 'warn',
                'detail' => 'Tables: ' . ($tablesOk ? 'OK' : 'Missing') . ' | /modules/AfterburnerDispatch: ' . ($writable ? 'Writable (GitHub 1-Click Ready)' : 'Read-Only'),
            ];

            $overall = 'pass';
            foreach ($checks as $c) {
                if ($c['status'] === 'fail') {
                    $overall = 'fail';
                    break;
                }
                if ($c['status'] === 'warn') {
                    $overall = 'warn';
                }
            }

            return [
                'success'     => ($overall !== 'fail'),
                'overall'     => $overall,
                'edition'     => 'Cloud DRM Client',
                'version'     => UpdateManager::getCurrentVersion(),
                'checked_at'  => Carbon::now()->format('Y-m-d H:i:s') . ' UTC',
                'checks'      => $checks,
            ];
        }
    }
}
