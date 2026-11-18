<?php

namespace Modules\RouteRequest\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\RouteRequest\Models\RouteRequest;
use Nwidart\Modules\Facades\Module;

if (!class_exists(DrmEntitlements::class, false)) {
    class DrmEntitlements
    {
        public static function getBaseApi(): string
        {
            $base = rtrim((string) config('routerequest.master_api_url', 'https://crew.flyfantasyair.net/api/routerequest-drm'), '/');
            foreach (['/airlines', '/parse-fa', '/entitlements', '/record-routes'] as $suffix) {
                if (str_ends_with($base, $suffix)) {
                    $base = substr($base, 0, -strlen($suffix));
                }
            }
            return $base;
        }

        public static function getLicenseKey(): string
        {
            return trim((string) config('routerequest.license_key', env('ROUTEREQUEST_LICENSE_KEY', '')));
        }

        public static function isPaxStudioInstalled(): bool
        {
            try {
                if (class_exists(Module::class)) {
                    $mod = Module::find('PaxStudio');
                    if ($mod && $mod->isEnabled()) {
                        return true;
                    }
                }
                if (class_exists(\Modules\PaxStudio\Providers\PaxStudioServiceProvider::class)) {
                    return true;
                }
            } catch (\Throwable $e) {}
            return false;
        }

        public static function getLocalWeeklyRoutesUsed(): int
        {
            try {
                $since = Carbon::now()->subDays(7);
                $recent = RouteRequest::where('updated_at', '>=', $since)->get();
                $count = 0;
                foreach ($recent as $r) {
                    $count += count($r->getAllGeneratedFlightIds());
                }
                return $count;
            } catch (\Throwable $e) {
                return 0;
            }
        }

        public static function current(bool $forceRefresh = false): array
        {
            $key = self::getLicenseKey();
            $cacheKey = 'routerequest_drm_entitlements_v2_2_' . md5($key ?: 'unlicensed');

            if ($forceRefresh) {
                try {
                    Cache::forget($cacheKey);
                } catch (\Throwable $e) {}
            }

            $localWeeklyUsed = self::getLocalWeeklyRoutesUsed();
            $paxInstalled = self::isPaxStudioInstalled();

            $defaultBasic = [
                'valid'                => ($key !== ''),
                'va_name'              => config('app.name', 'Virtual Airline'),
                'tier'                 => 'basic',
                'tier_label'           => 'Tier 1: Basic Dispatch Edition',
                'max_routes_per_req'   => 12,
                'max_return_pairs'     => 3,
                'weekly_route_cap'     => 28,
                'weekly_routes_used'   => $localWeeklyUsed,
                'weekly_routes_left'   => max(0, 28 - $localWeeklyUsed),
                'week_reset_at'        => null,
                'paxstudio_installed'  => $paxInstalled,
                'features'             => [
                    'fa_auto_matcher'    => false,
                    'one_click_create'   => false,
                    'pax_studio'         => false,
                    'prerelease_updates' => false,
                ],
            ];

            if ($key === '') {
                return $defaultBasic;
            }

            $fetchFn = function () use ($key, $defaultBasic, $localWeeklyUsed, $paxInstalled) {
                try {
                    $endpoint = self::getBaseApi() . '/entitlements';
                    $response = Http::timeout(6)->withHeaders([
                        'X-RouteRequest-License' => $key,
                        'X-RouteRequest-Domain'  => url(''),
                    ])->get($endpoint);

                    if ($response->successful() && is_array($response->json()) && !empty($response->json('tier'))) {
                        $data = $response->json();
                        $data['paxstudio_installed'] = $paxInstalled;
                        if (($data['tier'] ?? 'basic') === 'basic') {
                            $serverUsed = (int) ($data['weekly_routes_used'] ?? 0);
                            $used = max($serverUsed, $localWeeklyUsed);
                            $data['weekly_routes_used'] = $used;
                            $data['weekly_routes_left'] = max(0, 28 - $used);
                        }
                        return $data;
                    }
                } catch (\Throwable $e) {}

                return $defaultBasic;
            };

            try {
                return Cache::remember($cacheKey, 600, $fetchFn);
            } catch (\Throwable $e) {
                return $fetchFn();
            }
        }

        public static function verifyAndRecordRouteCreation(int $routesCount): array
        {
            $ent = self::current(false);
            $tier = $ent['tier'] ?? 'basic';

            if ($tier === 'basic') {
                $used = max((int) ($ent['weekly_routes_used'] ?? 0), self::getLocalWeeklyRoutesUsed());
                $remaining = max(0, 28 - $used);
                if ($routesCount > $remaining) {
                    return [
                        'allowed' => false,
                        'message' => sprintf(
                            'Tier 1 (Basic Dispatch Edition) Weekly Limit Reached: %d/28 routes created this week (%d remaining, attempted to create %d). Upgrade to Tier 2 (Executive Dispatch Edition) or Tier 3 (Flagship Edition) for unlimited weekly routes!',
                            $used,
                            $remaining,
                            $routesCount
                        ),
                    ];
                }
            }

            $key = self::getLicenseKey();
            if ($key !== '') {
                try {
                    $endpoint = self::getBaseApi() . '/record-routes';
                    $response = Http::timeout(6)->withHeaders([
                        'X-RouteRequest-License' => $key,
                        'X-RouteRequest-Domain'  => url(''),
                    ])->post($endpoint, [
                        'routes_count' => $routesCount,
                    ]);

                    if ($response->status() === 429) {
                        $err = $response->json('error') ?: 'Weekly route limit (28/week) reached on Master DRM Server.';
                        self::current(true);
                        return [
                            'allowed' => false,
                            'message' => $err,
                        ];
                    }
                } catch (\Throwable $e) {
                    // Continue with local check if master server is momentarily unreachable
                }
            }

            self::current(true);

            return [
                'allowed' => true,
                'message' => null,
            ];
        }
    }
}
