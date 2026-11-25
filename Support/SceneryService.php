<?php

declare(strict_types=1);

namespace Modules\AfterburnerDispatch\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

if (!class_exists(SceneryService::class, false)) {
    /**
     * Multi-Simulator Scenery Availability, Local Review Caching, VA Overrides & Pilot Feedback
     * (AfterburnerDispatch v4.1.0-beta.1).
     *
     * - Supports MSFS 2024 (PC), MSFS 2024 (Xbox/PS5), MSFS 2020 (PC/Xbox), X-Plane 12, X-Plane 11, P3D v6, P3D v5.
     * - Prioritizes Native options and explicitly flags Compatible fallbacks with source -> target sim metadata.
     * - Caches AfterburnerServer scenery & online reviews locally in `afterburner_scenery_cache`.
     * - Supports VA Admin "Recommended by VA" overrides (`afterburner_scenery_overrides`).
     * - Logs pilot ratings & broken-scenery flags locally (`afterburner_scenery_feedback`) and relays them
     *   to AfterburnerServer on DRM Clients (never phones home on Standalone).
     */
    final class SceneryService
    {
        public const array SIMULATORS = [
            'msfs2024_pc'      => 'MSFS 2024 (PC)',
            'msfs2024_console' => 'MSFS 2024 (Xbox / PS5)',
            'msfs2020_pc'      => 'MSFS 2020 (PC / Xbox)',
            'xp12'             => 'X-Plane 12',
            'xp11'             => 'X-Plane 11',
            'p3dv6'            => 'Prepar3D v6',
            'p3dv5'            => 'Prepar3D v5',
        ];

        public const array SCENERY_PREFS = [
            'both'              => 'Both (Payware & Freeware)',
            'freeware_only'     => 'Freeware Only',
            'payware_preferred' => 'Payware First',
        ];

        public static function isStandalone(): bool
        {
            return class_exists(VaultLoader::class, false);
        }

        public static function getPilotPrefs(int $userId): array
        {
            SchemaHelper::ensureRouteRequestsColumns();
            $default = [
                'simulators'       => [],
                'scenery_pref'     => 'both',
                'needs_onboarding' => true,
                'prompted_version' => null,
            ];
            if ($userId <= 0 || !Schema::hasTable('afterburner_pilot_prefs')) {
                return $default;
            }
            try {
                $row = DB::table('afterburner_pilot_prefs')->where('user_id', $userId)->first();
                if (!$row) {
                    return $default;
                }
                $sims = json_decode((string) ($row->simulators_json ?? '[]'), true);
                if (!is_array($sims)) {
                    $sims = [];
                }
                $sims = array_values(array_intersect($sims, array_keys(self::SIMULATORS)));
                $pref = isset(self::SCENERY_PREFS[(string) ($row->scenery_pref ?? '')])
                    ? (string) $row->scenery_pref
                    : 'both';
                return [
                    'simulators'       => $sims,
                    'scenery_pref'     => $pref,
                    'needs_onboarding' => empty($sims),
                    'prompted_version' => $row->prompted_version,
                ];
            } catch (\Throwable $e) {
                return $default;
            }
        }

        public static function savePilotPrefs(int $userId, array $simulators, string $sceneryPref = 'both'): array
        {
            SchemaHelper::ensureRouteRequestsColumns();
            $cleanSims = array_values(array_intersect(
                array_map(fn ($s) => strtolower(trim((string) $s)), $simulators),
                array_keys(self::SIMULATORS)
            ));
            $pref = isset(self::SCENERY_PREFS[$sceneryPref]) ? $sceneryPref : 'both';
            if ($userId > 0 && Schema::hasTable('afterburner_pilot_prefs')) {
                $now = Carbon::now();
                DB::table('afterburner_pilot_prefs')->updateOrInsert(
                    ['user_id' => $userId],
                    [
                        'simulators_json'  => json_encode($cleanSims),
                        'scenery_pref'     => $pref,
                        'prompted_version' => UpdateManager::getCurrentVersion(),
                        'updated_at'       => $now,
                        'created_at'       => $now,
                    ]
                );
            }
            return self::getPilotPrefs($userId);
        }

        /**
         * Deduplicated multi-airport scenery lookup (origin, destination, return/multi-leg).
         */
        public static function lookupForAirports(array $icaos, array $simulators, string $sceneryPref = 'both', bool $recordDemand = true): array
        {
            SchemaHelper::ensureRouteRequestsColumns();
            $cleanIcaos = [];
            foreach ($icaos as $raw) {
                $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', trim((string) $raw)) ?? '');
                if ($code !== '' && strlen($code) >= 3 && strlen($code) <= 6) {
                    $cleanIcaos[$code] = true;
                }
            }
            $cleanIcaos = array_slice(array_keys($cleanIcaos), 0, 12);
            if (empty($cleanIcaos)) {
                return [];
            }

            $cleanSims = array_values(array_intersect(
                array_map(fn ($s) => strtolower(trim((string) $s)), $simulators),
                array_keys(self::SIMULATORS)
            ));
            if (empty($cleanSims)) {
                $cleanSims = ['msfs2024_pc'];
            }

            $cached = [];
            $staleOrMissing = [];
            $now = Carbon::now();

            if (Schema::hasTable('afterburner_scenery_cache')) {
                $rows = DB::table('afterburner_scenery_cache')->whereIn('icao', $cleanIcaos)->get()->keyBy('icao');
                foreach ($cleanIcaos as $icao) {
                    $r = $rows[$icao] ?? null;
                    if ($r && !empty($r->payload_json) && !empty($r->cached_at) && Carbon::parse($r->cached_at)->addDays(14)->gt($now)) {
                        $dec = json_decode((string) $r->payload_json, true);
                        if (is_array($dec)) {
                            $cached[$icao] = $dec;
                            continue;
                        }
                    }
                    $staleOrMissing[] = $icao;
                }
            } else {
                $staleOrMissing = $cleanIcaos;
            }

            // Fetch stale/missing from AfterburnerServer (DRM Client only; Standalone uses local builder)
            if (!empty($staleOrMissing)) {
                $fetched = !self::isStandalone()
                    ? self::fetchFromAfterburnerServer($staleOrMissing, array_keys(self::SIMULATORS), $recordDemand)
                    : [];

                foreach ($staleOrMissing as $icao) {
                    $entry = $fetched[$icao] ?? self::buildLocalFallback($icao);
                    $cached[$icao] = $entry;
                    if (Schema::hasTable('afterburner_scenery_cache')) {
                        try {
                            DB::table('afterburner_scenery_cache')->updateOrInsert(
                                ['icao' => $icao],
                                [
                                    'airport_name' => substr((string) ($entry['airport_name'] ?? $icao), 0, 160),
                                    'payload_json' => json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                    'cached_at'    => $now,
                                    'updated_at'   => $now,
                                    'created_at'   => $now,
                                ]
                            );
                        } catch (\Throwable $e) {}
                    }
                }
            }

            // Merge VA Admin overrides + local in-VA pilot ratings + filter by pilot simulators & freeware/payware pref
            $overrides = self::getOverridesForAirports($cleanIcaos);
            $localRatings = self::getLocalFeedbackSummary($cleanIcaos);

            $result = [];
            foreach ($cleanIcaos as $icao) {
                $entry = $cached[$icao] ?? self::buildLocalFallback($icao);
                $simOut = [];
                foreach ($cleanSims as $simKey) {
                    $baseSim = $entry['simulators'][$simKey] ?? ['label' => self::SIMULATORS[$simKey], 'options' => []];
                    $opts = is_array($baseSim['options'] ?? null) ? $baseSim['options'] : [];

                    // Filter / sort by freeware vs payware preference
                    if ($sceneryPref === 'freeware_only') {
                        $opts = array_values(array_filter($opts, fn ($o) => ($o['tier'] ?? '') === 'freeware'));
                    } elseif ($sceneryPref === 'payware_preferred') {
                        usort($opts, fn ($a, $b) => (($a['tier'] ?? '') === 'payware' ? 0 : 1) <=> (($b['tier'] ?? '') === 'payware' ? 0 : 1));
                    }

                    // Prepend VA Admin "Recommended by VA" overrides for this ICAO + sim
                    $vaPins = [];
                    foreach (($overrides[$icao] ?? []) as $ov) {
                        if ($ov['simulator'] === $simKey || $ov['simulator'] === 'all') {
                            $vaPins[] = [
                                'tier'         => $ov['tier'] ?: 'va_recommended',
                                'va_pin'       => true,
                                'title'        => '★ VA Recommended: ' . $ov['title'],
                                'compat_type'  => $ov['compat_type'] ?: 'native',
                                'compat_label' => $ov['compat_label'] ?: ('VA Verified for ' . self::SIMULATORS[$simKey]),
                                'source_sim'   => self::SIMULATORS[$simKey],
                                'target_sim'   => self::SIMULATORS[$simKey],
                                'instructions' => $ov['instructions'] ?: 'Recommended by your Virtual Airline staff.',
                                'url'          => $ov['url'],
                                'review'       => [
                                    'rating'  => null,
                                    'count'   => 0,
                                    'summary' => 'Pinned by VA Admin.',
                                    'source'  => 'VA Staff Recommendation',
                                ],
                            ];
                        }
                    }

                    $simOut[$simKey] = [
                        'label'   => self::SIMULATORS[$simKey],
                        'options' => array_merge($vaPins, $opts),
                    ];
                }

                $result[$icao] = [
                    'icao'                 => $icao,
                    'airport_name'         => $entry['airport_name'] ?? $icao,
                    'reviews_refreshed_at' => $entry['reviews_refreshed_at'] ?? null,
                    'community_feedback'   => $entry['community_feedback'] ?? ['avg_rating' => null, 'rating_count' => 0, 'broken_flags' => 0],
                    'va_feedback'          => $localRatings[$icao] ?? ['avg_rating' => null, 'rating_count' => 0, 'broken_flags' => 0],
                    'simulators'           => $simOut,
                ];
            }

            return $result;
        }

        private static function fetchFromAfterburnerServer(array $icaos, array $simulators, bool $recordDemand): array
        {
            $key = DrmEntitlements::getLicenseKey();
            if ($key === '') {
                return [];
            }
            try {
                $endpoint = DrmEntitlements::getBaseApi() . '/scenery/lookup';
                $resp = Http::withHeaders([
                    'X-Afterburner-License' => $key,
                    'X-Afterburner-Domain'  => url(''),
                    'Accept'                => 'application/json',
                ])->timeout(5)->connectTimeout(2)->post($endpoint, [
                    'icaos'         => $icaos,
                    'simulators'    => $simulators,
                    'record_demand' => $recordDemand,
                ]);
                if ($resp->successful()) {
                    $json = $resp->json();
                    if (!empty($json['success']) && is_array($json['airports'] ?? null)) {
                        return $json['airports'];
                    }
                }
            } catch (\Throwable $e) {}
            return [];
        }

        private static function buildLocalFallback(string $icao): array
        {
            $fsto = 'https://flightsim.to/discover/' . rawurlencode($icao);
            $orbx = 'https://orbxdirect.com/search?q=' . rawurlencode($icao);
            $ini  = 'https://inibuilds.com/search?q=' . rawurlencode($icao);
            $simm = 'https://secure.simmarket.com/advanced_search_result.php?keywords=' . rawurlencode($icao);
            $xpo  = 'https://forums.x-plane.org/index.php?/search/&q=' . rawurlencode($icao) . '&type=downloads_file';
            $xpgw = 'https://gateway.x-plane.com/airports/' . rawurlencode($icao) . '/show';

            return [
                'icao'                 => $icao,
                'airport_name'         => $icao,
                'reviews_refreshed_at' => Carbon::now()->toIso8601String(),
                'community_feedback'   => ['avg_rating' => null, 'rating_count' => 0, 'broken_flags' => 0],
                'simulators'           => [
                    'msfs2024_pc' => [
                        'label'   => self::SIMULATORS['msfs2024_pc'],
                        'options' => [
                            ['tier' => 'payware', 'title' => "{$icao} Payware (iniBuilds / Orbx)", 'compat_type' => 'native_or_compatible', 'compat_label' => 'MSFS 2024 Native / 2020 Compatible', 'source_sim' => 'MSFS 2024 / 2020', 'target_sim' => 'MSFS 2024 (PC)', 'instructions' => 'Install MSFS 2024 Native package via iniManager/Orbx Central or drop MSFS 2020-compatible pack into Community2024.', 'url' => $ini, 'alt_url' => $orbx],
                            ['tier' => 'freeware', 'title' => "{$icao} Freeware (Flightsim.to)", 'compat_type' => 'compatible', 'compat_label' => 'MSFS 2024 Native or 2020 → 2024 Compatible', 'source_sim' => 'MSFS 2020 / 2024', 'target_sim' => 'MSFS 2024 (PC)', 'instructions' => 'Filter for MSFS 2024 compatibility and extract into Community folder.', 'url' => $fsto],
                        ],
                    ],
                    'msfs2024_console' => [
                        'label'   => self::SIMULATORS['msfs2024_console'],
                        'options' => [
                            ['tier' => 'payware', 'title' => "{$icao} In-Sim Marketplace (Xbox / PS5)", 'compat_type' => 'native', 'compat_label' => 'MSFS 2024 Marketplace Native', 'source_sim' => 'MSFS 2024 Marketplace', 'target_sim' => 'MSFS 2024 (Xbox / PS5)', 'instructions' => "Search '{$icao}' in MSFS 2024 In-Sim Marketplace.", 'url' => 'https://www.flightsimulator.com/marketplace/'],
                        ],
                    ],
                    'msfs2020_pc' => [
                        'label'   => self::SIMULATORS['msfs2020_pc'],
                        'options' => [
                            ['tier' => 'payware', 'title' => "{$icao} Payware (Orbx / SimMarket)", 'compat_type' => 'native', 'compat_label' => 'MSFS 2020 Native', 'source_sim' => 'MSFS 2020', 'target_sim' => 'MSFS 2020 (PC / Xbox)', 'instructions' => 'Install via Orbx Central, iniManager, or MSFS 2020 Marketplace.', 'url' => $orbx, 'alt_url' => $simm],
                            ['tier' => 'freeware', 'title' => "{$icao} Freeware (Flightsim.to)", 'compat_type' => 'native', 'compat_label' => 'MSFS 2020 Native (PC)', 'source_sim' => 'MSFS 2020', 'target_sim' => 'MSFS 2020 (PC)', 'instructions' => 'Extract into MSFS 2020 Community folder.', 'url' => $fsto],
                        ],
                    ],
                    'xp12' => [
                        'label'   => self::SIMULATORS['xp12'],
                        'options' => [
                            ['tier' => 'freeware', 'title' => "{$icao} X-Plane Scenery Gateway", 'compat_type' => 'native', 'compat_label' => 'X-Plane 12 Native Gateway', 'source_sim' => 'X-Plane 12', 'target_sim' => 'X-Plane 12', 'instructions' => 'Included in Global Airports or download into Custom Scenery.', 'url' => $xpgw],
                            ['tier' => 'payware', 'title' => "{$icao} X-Plane.org / Orbx", 'compat_type' => 'compatible', 'compat_label' => 'XP12 Native or XP11 → XP12 Compatible', 'source_sim' => 'X-Plane 11 / 12', 'target_sim' => 'X-Plane 12', 'instructions' => 'Copy into Custom Scenery above Global Airports in scenery_packs.ini.', 'url' => $xpo, 'alt_url' => $orbx],
                        ],
                    ],
                    'xp11' => [
                        'label'   => self::SIMULATORS['xp11'],
                        'options' => [
                            ['tier' => 'freeware', 'title' => "{$icao} X-Plane.org / Gateway", 'compat_type' => 'native', 'compat_label' => 'X-Plane 11 Native', 'source_sim' => 'X-Plane 11', 'target_sim' => 'X-Plane 11', 'instructions' => 'Copy into X-Plane 11/Custom Scenery above Global Airports.', 'url' => $xpo, 'alt_url' => $xpgw],
                        ],
                    ],
                    'p3dv6' => [
                        'label'   => self::SIMULATORS['p3dv6'],
                        'options' => [
                            ['tier' => 'payware', 'title' => "{$icao} Prepar3D v6 (SimMarket / Orbx)", 'compat_type' => 'compatible', 'compat_label' => 'P3D v6 Native or P3D v5 → v6 Compatible', 'source_sim' => 'Prepar3D v5 / v6', 'target_sim' => 'Prepar3D v6', 'instructions' => 'Install via P3D v6 add-on.xml installer.', 'url' => $simm, 'alt_url' => $orbx],
                        ],
                    ],
                    'p3dv5' => [
                        'label'   => self::SIMULATORS['p3dv5'],
                        'options' => [
                            ['tier' => 'payware', 'title' => "{$icao} Prepar3D v5 (SimMarket / Orbx)", 'compat_type' => 'native', 'compat_label' => 'Prepar3D v5 Native', 'source_sim' => 'Prepar3D v5', 'target_sim' => 'Prepar3D v5', 'instructions' => 'Install via P3D v5 add-on.xml installer.', 'url' => $simm, 'alt_url' => $orbx],
                        ],
                    ],
                ],
            ];
        }

        public static function submitPilotFeedback(int $userId, array $input): array
        {
            SchemaHelper::ensureRouteRequestsColumns();
            $icao = strtoupper(preg_replace('/[^A-Z0-9]/', '', trim((string) ($input['icao'] ?? ''))) ?? '');
            if ($icao === '' || strlen($icao) < 3) {
                return ['success' => false, 'error' => 'Valid airport ICAO required.'];
            }
            $sim = strtolower(trim((string) ($input['simulator'] ?? 'msfs2024_pc')));
            if (!isset(self::SIMULATORS[$sim])) {
                $sim = 'msfs2024_pc';
            }
            $type = in_array((string) ($input['feedback_type'] ?? 'rating'), ['rating', 'broken_flag'], true)
                ? (string) $input['feedback_type']
                : 'rating';
            $rating = isset($input['rating']) && $input['rating'] !== '' ? max(1, min(5, (int) $input['rating'])) : null;
            $compatOk = isset($input['compat_ok']) ? (bool) $input['compat_ok'] : null;
            $title = substr(trim((string) ($input['scenery_title'] ?? '')), 0, 180);
            $comment = substr(trim((string) ($input['comment'] ?? '')), 0, 1000);

            if (Schema::hasTable('afterburner_scenery_feedback')) {
                DB::table('afterburner_scenery_feedback')->insert([
                    'user_id'       => $userId,
                    'icao'          => $icao,
                    'simulator'     => $sim,
                    'scenery_title' => $title,
                    'feedback_type' => $type,
                    'rating'        => $rating,
                    'compat_ok'     => $compatOk,
                    'comment'       => $comment,
                    'created_at'    => Carbon::now(),
                    'updated_at'    => Carbon::now(),
                ]);
            }

            // Relay to AfterburnerServer developer feedback queue (DRM Client only; fail-open)
            if (!self::isStandalone()) {
                try {
                    $key = DrmEntitlements::getLicenseKey();
                    if ($key !== '') {
                        Http::withHeaders([
                            'X-Afterburner-License' => $key,
                            'X-Afterburner-Domain'  => url(''),
                            'Accept'                => 'application/json',
                        ])->timeout(4)->connectTimeout(2)->post(DrmEntitlements::getBaseApi() . '/scenery/feedback', [
                            'icao'          => $icao,
                            'simulator'     => $sim,
                            'scenery_title' => $title,
                            'feedback_type' => $type,
                            'rating'        => $rating,
                            'compat_ok'     => $compatOk,
                            'comment'       => $comment,
                        ]);
                    }
                } catch (\Throwable $e) {}
            }

            return ['success' => true, 'message' => $type === 'broken_flag' ? 'Scenery issue flagged for VA staff & developer relay.' : 'Thank you! Your scenery rating has been saved.'];
        }

        public static function getOverridesForAirports(array $icaos): array
        {
            if (empty($icaos) || !Schema::hasTable('afterburner_scenery_overrides')) {
                return [];
            }
            try {
                $rows = DB::table('afterburner_scenery_overrides')->whereIn('icao', $icaos)->orderByDesc('id')->get();
                $map = [];
                foreach ($rows as $r) {
                    $map[$r->icao][] = [
                        'id'           => $r->id,
                        'icao'         => $r->icao,
                        'simulator'    => $r->simulator,
                        'tier'         => $r->tier,
                        'title'        => $r->title,
                        'compat_type'  => $r->compat_type,
                        'compat_label' => $r->compat_label,
                        'url'          => $r->url,
                        'instructions' => $r->instructions,
                    ];
                }
                return $map;
            } catch (\Throwable $e) {
                return [];
            }
        }

        private static function getLocalFeedbackSummary(array $icaos): array
        {
            if (empty($icaos) || !Schema::hasTable('afterburner_scenery_feedback')) {
                return [];
            }
            try {
                $rows = DB::table('afterburner_scenery_feedback')->whereIn('icao', $icaos)->get();
                $byIcao = [];
                foreach ($rows as $r) {
                    $byIcao[$r->icao] ??= ['ratings' => [], 'broken_flags' => 0];
                    if ($r->feedback_type === 'broken_flag') {
                        $byIcao[$r->icao]['broken_flags']++;
                    }
                    if ($r->rating !== null && (int) $r->rating >= 1 && (int) $r->rating <= 5) {
                        $byIcao[$r->icao]['ratings'][] = (int) $r->rating;
                    }
                }
                $out = [];
                foreach ($byIcao as $icao => $d) {
                    $out[$icao] = [
                        'avg_rating'   => !empty($d['ratings']) ? round(array_sum($d['ratings']) / count($d['ratings']), 1) : null,
                        'rating_count' => count($d['ratings']),
                        'broken_flags' => $d['broken_flags'],
                    ];
                }
                return $out;
            } catch (\Throwable $e) {
                return [];
            }
        }

        /**
         * Scheduled CronDaily refresh of cached scenery/reviews for airports requested in the last 180 days.
         */
        public static function refreshRecentAirportsCache(int $limit = 10): int
        {
            if (self::isStandalone() || !Schema::hasTable('afterburner_scenery_cache')) {
                return 0;
            }
            try {
                $staleIcaos = DB::table('afterburner_scenery_cache')
                    ->where('cached_at', '<=', Carbon::now()->subDays(14))
                    ->orderBy('cached_at')
                    ->limit($limit)
                    ->pluck('icao')
                    ->all();
                if (empty($staleIcaos)) {
                    return 0;
                }
                $fetched = self::fetchFromAfterburnerServer($staleIcaos, array_keys(self::SIMULATORS), false);
                $now = Carbon::now();
                $updated = 0;
                foreach ($fetched as $icao => $entry) {
                    DB::table('afterburner_scenery_cache')->where('icao', $icao)->update([
                        'airport_name' => substr((string) ($entry['airport_name'] ?? $icao), 0, 160),
                        'payload_json' => json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        'cached_at'    => $now,
                        'updated_at'   => $now,
                    ]);
                    $updated++;
                }
                return $updated;
            } catch (\Throwable $e) {
                return 0;
            }
        }
    }
}
