<?php

declare(strict_types=1);

namespace Modules\AfterburnerDispatch\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (!class_exists(SkyAdventuresBridge::class, false)) {
    /**
     * Read-Only Companion Bridge for SkyAdventures (MANFahrer-GF/SkyAdventures).
     *
     * - Detects SkyAdventures installation, version, and experimental Fleet/Ferry flag (`sa_settings.fleet_enabled`).
     * - Warns admins if SkyAdventures Fleet/Maintenance/Ferry mode is enabled without PaxStudio active.
     * - Surfaces active pilot Tour/Event legs in AfterburnerDispatch with 1-click route-request prefill.
     * - Protects aircraft locked by SkyAdventures maintenance/mandatory ferry missions (`sa_ferry_missions.locked = 1`).
     */
    final class SkyAdventuresBridge
    {
        public static function isActive(): bool
        {
            return CompanionModuleManager::isModuleEnabled('SkyAdventures') && Schema::hasTable('sa_tours');
        }

        public static function getHealthStatus(): array
        {
            $installed = CompanionModuleManager::isModuleInstalled('SkyAdventures');
            $enabled = CompanionModuleManager::isModuleEnabled('SkyAdventures');
            $version = CompanionModuleManager::getInstalledVersion('SkyAdventures');
            $fleetEnabled = false;
            $lockedFerryCount = 0;

            if ($enabled && Schema::hasTable('sa_settings')) {
                try {
                    $val = DB::table('sa_settings')->where('id', 'fleet_enabled')->value('value');
                    $fleetEnabled = $val !== null && in_array(strtolower((string) $val), ['1', 'true', 'on', 'yes'], true);
                } catch (\Throwable $e) {}
            }
            if ($enabled && Schema::hasTable('sa_ferry_missions')) {
                try {
                    $lockedFerryCount = (int) DB::table('sa_ferry_missions')
                        ->where('status', 'open')
                        ->where('locked', true)
                        ->count();
                } catch (\Throwable $e) {}
            }

            $paxActive = DrmEntitlements::isPaxStudioInstalled();
            $warning = ($fleetEnabled && !$paxActive)
                ? 'SkyAdventures experimental Fleet/Ferry mode is ON, but PaxStudio is not installed/active. Install PaxStudio to prevent unguarded aircraft dispatch.'
                : null;

            return [
                'installed'          => $installed,
                'enabled'            => $enabled,
                'version'            => $version,
                'fleet_enabled'      => $fleetEnabled,
                'locked_ferry_count' => $lockedFerryCount,
                'paxstudio_active'   => $paxActive,
                'warning'            => $warning,
            ];
        }

        /**
         * Returns the pilot's next open SkyAdventures tour legs (for 1-click dispatch & scenery preview).
         */
        public static function getPilotNextLegs(int $userId, int $limit = 4): array
        {
            if ($userId <= 0 || !self::isActive() || !Schema::hasTable('sa_signups') || !Schema::hasTable('sa_legs')) {
                return [];
            }

            try {
                $signups = DB::table('sa_signups')
                    ->where('user_id', $userId)
                    ->where('parent_type', 'tour')
                    ->orderByDesc('signed_up_at')
                    ->limit(8)
                    ->get();

                if ($signups->isEmpty()) {
                    return [];
                }

                $completedLegIds = Schema::hasTable('sa_progress')
                    ? DB::table('sa_progress')->where('user_id', $userId)->pluck('leg_id')->all()
                    : [];

                $legsOut = [];
                foreach ($signups as $s) {
                    $tour = DB::table('sa_tours')
                        ->where('id', $s->parent_id)
                        ->where('status', 'published')
                        ->whereNull('deleted_at')
                        ->first();
                    if (!$tour) {
                        continue;
                    }
                    $nextLeg = DB::table('sa_legs')
                        ->where('parent_type', 'tour')
                        ->where('parent_id', $tour->id)
                        ->whereNotIn('id', $completedLegIds ?: [0])
                        ->orderBy('order_index')
                        ->first();
                    if ($nextLeg && !empty($nextLeg->dep_icao) && !empty($nextLeg->arr_icao)) {
                        $legsOut[] = [
                            'tour_id'   => (int) $tour->id,
                            'tour_name' => (string) $tour->name,
                            'leg_id'    => (int) $nextLeg->id,
                            'leg_order' => (int) $nextLeg->order_index,
                            'leg_name'  => (string) ($nextLeg->name ?: ($nextLeg->dep_icao . ' → ' . $nextLeg->arr_icao)),
                            'dep_icao'  => strtoupper((string) $nextLeg->dep_icao),
                            'arr_icao'  => strtoupper((string) $nextLeg->arr_icao),
                        ];
                        if (count($legsOut) >= $limit) {
                            break;
                        }
                    }
                }
                return $legsOut;
            } catch (\Throwable $e) {
                return [];
            }
        }

        /**
         * Returns aircraft IDs currently locked by open SkyAdventures mandatory ferry/maintenance missions.
         */
        public static function getLockedAircraftIds(): array
        {
            if (!self::isActive() || !Schema::hasTable('sa_ferry_missions')) {
                return [];
            }
            try {
                return DB::table('sa_ferry_missions')
                    ->where('status', 'open')
                    ->where('locked', true)
                    ->whereNotNull('aircraft_id')
                    ->pluck('aircraft_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            } catch (\Throwable $e) {
                return [];
            }
        }
    }
}
