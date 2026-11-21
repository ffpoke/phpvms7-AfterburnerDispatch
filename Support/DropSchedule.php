<?php

declare(strict_types=1);

namespace Modules\RouteRequest\Support;

if (!class_exists(DropSchedule::class, false)) {
    /**
     * IMMUTABLE BAKED-IN FEATURE DROP SCHEDULE
     *
     * Drop dates are compiled directly into module source constants (no DB settings,
     * no .env overrides, no admin UI editors). Dates cannot be altered without
     * shipping a new RouteRequest / RouteRequestServer module update.
     *
     * Features auto-unlock automatically at 00:00:00 UTC on their scheduled date
     * using the Master DRM Server's Ed25519-signed UTC timestamp (`signed_at`).
     */
    final class DropSchedule
    {
        public const string SCHEDULE_VERSION = '2026.11.20-v3.0.3';

        /**
         * Format per feature:
         * - 'always'     : Unlocked immediately for that tier
         * - 'YYYY-MM-DD' : Auto-unlocks at 00:00:00 UTC on this date
         * - null         : Never unlocks for that tier
         */
        public const array SCHEDULE = [
            'fa_auto_matcher' => [
                'label'              => 'FlightAware Route & Altitude Auto-Matcher',
                'basic'              => null,
                'executive'          => 'always',
                'flagship'           => 'always',
                'permanent_flagship' => false,
            ],
            'one_click_create' => [
                'label'              => '1-Click Auto-Create Flights',
                'basic'              => null,
                'executive'          => 'always',
                'flagship'           => 'always',
                'permanent_flagship' => false,
            ],
            'discord_webhooks' => [
                'label'              => 'Server-Gated Discord Webhook Dispatch',
                'basic'              => null,
                'executive'          => 'always',
                'flagship'           => 'always',
                'permanent_flagship' => false,
            ],
            'gc_map_pilot' => [
                'label'              => 'Pilot Interactive Great-Circle Map & Block-Time Preview',
                'basic'              => '2026-12-15',
                'executive'          => 'always',
                'flagship'           => 'always',
                'permanent_flagship' => false,
            ],
            'admin_auto_est' => [
                'label'              => 'Admin Generator Auto-Est Block Time Button',
                'basic'              => null,
                'executive'          => 'always',
                'flagship'           => 'always',
                'permanent_flagship' => false,
            ],
            'range_guardrail_basic' => [
                'label'              => 'Aircraft Range Guardrail (Built-In ICAO Warnings)',
                'basic'              => '2027-01-15',
                'executive'          => '2026-12-15',
                'flagship'           => 'always',
                'permanent_flagship' => false,
            ],
            'range_guardrail_live_check' => [
                'label'              => 'On-Demand FR24 & FlightAware Real-World Range Check',
                'basic'              => null,
                'executive'          => '2027-01-15',
                'flagship'           => 'always',
                'permanent_flagship' => false,
            ],
            'subfleet_sync_basic' => [
                'label'              => 'Bulk Subfleet-to-Route Sync (Basic: 1 Subfleet, Same Airline)',
                'basic'              => '2027-02-15',
                'executive'          => '2026-12-15',
                'flagship'           => 'always',
                'permanent_flagship' => false,
            ],
            'subfleet_sync_multi' => [
                'label'              => 'Bulk Subfleet Sync (Multi-Subfleet Pills & Custom Airlines)',
                'basic'              => null,
                'executive'          => '2026-12-15',
                'flagship'           => 'always',
                'permanent_flagship' => false,
            ],
            'subfleet_sync_power' => [
                'label'              => 'Subfleet Sync Power Suite (Saved Presets & Undo Last Batch)',
                'basic'              => null,
                'executive'          => null,
                'flagship'           => 'always',
                'permanent_flagship' => true,
            ],
            'route_time_fixer' => [
                'label'              => 'Old Route Time Estimator Fixer (Bulk Repair Tool)',
                'basic'              => null,
                'executive'          => null,
                'flagship'           => 'always',
                'permanent_flagship' => true,
            ],
            'pax_studio' => [
                'label'              => 'PaxStudio Bridge, Compatibility Floor & 1-Click Auto-Updater',
                'basic'              => null,
                'executive'          => null,
                'flagship'           => 'always',
                'permanent_flagship' => true,
            ],
            'prerelease_updates' => [
                'label'              => 'Pre-Release / Beta Update Channel (Early Access)',
                'basic'              => null,
                'executive'          => null,
                'flagship'           => 'always',
                'permanent_flagship' => true,
            ],
        ];

        private static function isRuleUnlocked(string|null $rule, int $utcTs): bool
        {
            if ($rule === null) {
                return false;
            }
            if ($rule === 'always') {
                return true;
            }
            $unlockTs = strtotime($rule . ' 00:00:00 UTC');
            if ($unlockTs === false) {
                return false;
            }
            return $utcTs >= $unlockTs;
        }

        /**
         * Evaluates all baked-in feature flags for the given tier at the given UTC Unix timestamp.
         */
        public static function evaluate(
            string $tier,
            bool $paxStudioOverride = false,
            bool $prereleaseOverride = false,
            ?int $utcTimestamp = null
        ): array {
            $normalizedTier = in_array($tier, ['basic', 'executive', 'flagship'], true) ? $tier : 'basic';
            $nowTs = ($utcTimestamp !== null && $utcTimestamp > 0) ? $utcTimestamp : time();

            $flags = [];
            foreach (self::SCHEDULE as $key => $meta) {
                $rule = $meta[$normalizedTier] ?? null;
                $flags[$key] = self::isRuleUnlocked($rule, $nowTs);
            }

            if ($paxStudioOverride) {
                $flags['pax_studio'] = true;
            }
            if ($prereleaseOverride) {
                $flags['prerelease_updates'] = true;
            }

            return $flags;
        }

        /**
         * Returns a read-only timetable array for display in Admin UIs.
         */
        public static function getTimetable(string $currentTier = 'basic', ?int $utcTimestamp = null): array
        {
            $normalizedTier = in_array($currentTier, ['basic', 'executive', 'flagship'], true) ? $currentTier : 'basic';
            $nowTs = ($utcTimestamp !== null && $utcTimestamp > 0) ? $utcTimestamp : time();

            $rows = [];
            foreach (self::SCHEDULE as $key => $meta) {
                $rule = $meta[$normalizedTier] ?? null;
                $unlocked = self::isRuleUnlocked($rule, $nowTs);

                if ($unlocked) {
                    $status = 'Unlocked';
                } elseif (is_string($rule) && $rule !== 'always') {
                    $status = 'Auto-Unlocks ' . date('M j, Y', (int) strtotime($rule . ' 00:00:00 UTC'));
                } elseif (!empty($meta['permanent_flagship'])) {
                    $status = 'Permanent Tier 3 Flagship';
                } else {
                    $status = 'Executive / Flagship Only';
                }

                $fmt = static function (string|null $r): string {
                    if ($r === null) {
                        return '—';
                    }
                    if ($r === 'always') {
                        return 'Included';
                    }
                    return date('M j, Y', (int) strtotime($r . ' 00:00:00 UTC'));
                };

                $rows[] = [
                    'key'                => $key,
                    'label'              => $meta['label'],
                    'unlocked'           => $unlocked,
                    'status'             => $status,
                    'basic_col'          => $fmt($meta['basic']),
                    'executive_col'      => $fmt($meta['executive']),
                    'flagship_col'       => $fmt($meta['flagship']),
                    'permanent_flagship' => (bool) $meta['permanent_flagship'],
                ];
            }

            return $rows;
        }
    }
}
