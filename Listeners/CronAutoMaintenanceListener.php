<?php

declare(strict_types=1);

namespace Modules\AfterburnerDispatch\Listeners;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\AfterburnerDispatch\Support\ClientSettings;
use Modules\AfterburnerDispatch\Support\DispatchDigestService;
use Modules\AfterburnerDispatch\Support\DrmEntitlements;
use Modules\AfterburnerDispatch\Support\UpdateManager;

class CronAutoMaintenanceListener
{
    public function handleHourly(object $event = null): void
    {
        if (Cache::get('routerequest_force_update_backoff')) {
            return;
        }

        $lock = Cache::lock('routerequest_force_update', 180);
        if (!$lock->get()) {
            return;
        }

        try {
            $ent = DrmEntitlements::current(true);
            if (empty($ent['version_blocked']) || empty($ent['force_auto_update'])) {
                return;
            }

            $minVer = ltrim(trim((string) ($ent['min_rr_version'] ?? '')), 'vV');
            $info = UpdateManager::check(true);
            if (empty($info['available']) || empty($info['latest_version'])) {
                return;
            }

            $latestClean = ltrim(trim((string) $info['latest_version']), 'vV');
            if ($minVer !== '' && version_compare($latestClean, $minVer, '<')) {
                return;
            }

            $res = UpdateManager::installLatestUpdate();
            if (!empty($res['success'])) {
                try {
                    Artisan::call('view:clear');
                    Artisan::call('route:clear');
                } catch (\Throwable $e) {}
                Log::info('RouteRequest CronHourly Force-Update succeeded to v' . $latestClean . '.');
            } else {
                Cache::put('routerequest_force_update_backoff', true, now()->addHours(6));
                Log::warning('RouteRequest CronHourly Force-Update deferred (6h backoff): ' . ($res['message'] ?? 'Unknown error'));
            }
        } catch (\Throwable $e) {
            Cache::put('routerequest_force_update_backoff', true, now()->addHours(6));
            Log::warning('RouteRequest CronHourly Force-Update exception (6h backoff): ' . $e->getMessage());
        } finally {
            try {
                $lock->release();
            } catch (\Throwable $e) {}
        }
    }

    public function handleDaily(object $event = null): void
    {
        try {
            \Modules\AfterburnerDispatch\Support\SceneryService::refreshRecentAirportsCache(10);
        } catch (\Throwable $e) {}

        try {
            $cs = ClientSettings::all();
            if (empty($cs['digest_enabled'])) {
                return;
            }
            $hours = max(1, (int) ($cs['digest_window_hours'] ?? 24));
            DispatchDigestService::sendDigest($hours, false);
        } catch (\Throwable $e) {
            Log::warning('RouteRequest CronDaily Digest error: ' . $e->getMessage());
        }
    }
}
