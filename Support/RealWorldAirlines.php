<?php

namespace Modules\RouteRequest\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

if (!class_exists(RealWorldAirlines::class, false)) {
    class RealWorldAirlines
    {
        public static function all(): array
        {
            $baseApi = rtrim((string) config('routerequest.master_api_url', 'https://crew.flyfantasyair.net/api/routerequest-drm'), '/');
            $endpoint = str_ends_with($baseApi, '/airlines') ? $baseApi : ($baseApi . '/airlines');
            $licenseKey = trim((string) config('routerequest.license_key', env('ROUTEREQUEST_LICENSE_KEY', '')));

            if ($licenseKey === '') {
                return [
                    'License Required' => [
                        'UNLICENSED — Add ROUTEREQUEST_LICENSE_KEY to .env' => 'UNLICENSED — Add ROUTEREQUEST_LICENSE_KEY to .env',
                    ],
                ];
            }

            return Cache::remember('routerequest_drm_airlines_' . md5($licenseKey), 3600, function () use ($endpoint, $licenseKey) {
                try {
                    $response = Http::timeout(6)->withHeaders([
                        'X-RouteRequest-License' => $licenseKey,
                        'X-RouteRequest-Domain'  => url(''),
                    ])->get($endpoint);

                    if ($response->successful() && is_array($response->json('airlines'))) {
                        return $response->json('airlines');
                    }
                } catch (\Throwable $e) {
                    // Ignore
                }

                return [
                    'License Verification Failed' => [
                        'Unable to verify ROUTEREQUEST_LICENSE_KEY with Master Server' => 'Unable to verify ROUTEREQUEST_LICENSE_KEY with Master Server',
                    ],
                ];
            });
        }
    }
}
