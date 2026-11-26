<?php

declare(strict_types=1);

namespace Modules\AfterburnerDispatch\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

if (!class_exists(InstallTelemetry::class, false)) {
    /**
     * Lightweight, Fail-Open Installation & Activation Telemetry (DRM Client Only).
     *
     * Sends non-PII installation metadata (domain, VA name, version, PHP version,
     * activation status) to AfterburnerServer on install, activation, version change,
     * or once per 24h. Never blocks or crashes the VA if offline.
     */
    final class InstallTelemetry
    {
        private const string CACHE_KEY = 'rr_install_telemetry_last_v1';

        public static function pingIfDue(string $event = 'install_heartbeat', bool $force = false): void
        {
            // Never run during CLI / Artisan commands to avoid slowing migrations or cron jobs
            if (PHP_SAPI === 'cli' && !$force) {
                return;
            }

            $ver = UpdateManager::getCurrentVersion();
            $key = DrmEntitlements::getLicenseKey();
            $status = ($key !== '') ? 'activated' : 'unactivated';
            $fingerprint = $ver . '|' . $status;

            if (!$force) {
                try {
                    if (Cache::get(self::CACHE_KEY) === $fingerprint) {
                        return;
                    }
                    // Set cache marker BEFORE HTTP call so concurrent requests don't stampede
                    Cache::put(self::CACHE_KEY, $fingerprint, now()->addHours(24));
                } catch (\Throwable $e) {
                    return;
                }
            }

            try {
                $endpoint = DrmEntitlements::getBaseApi() . '/telemetry/ping';
                Http::withHeaders([
                    'User-Agent' => 'phpVMS7-AfterburnerDispatch-Telemetry/' . $ver,
                    'Accept'     => 'application/json',
                ])->timeout(3)->connectTimeout(2)->post($endpoint, [
                    'domain'             => rtrim((string) url(''), '/'),
                    'app_name'           => substr((string) config('app.name', 'phpVMS VA'), 0, 120),
                    'module_version'     => $ver,
                    'edition'            => 'drm-client',
                    'php_version'        => PHP_VERSION,
                    'license_status'     => $status,
                    'license_key_prefix' => $key !== '' ? substr($key, 0, 12) : '',
                    'event'              => $event,
                ]);
            } catch (\Throwable $e) {
                // Fail-open: never disrupt the VA if AfterburnerServer or internet is unreachable
            }
        }
    }
}
