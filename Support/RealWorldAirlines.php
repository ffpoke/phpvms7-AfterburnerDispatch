<?php

declare(strict_types=1);

namespace Modules\RouteRequest\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

if (!class_exists(RealWorldAirlines::class, false)) {
    class RealWorldAirlines
    {
        public static function all(): array
        {
            if (!class_exists(DrmEntitlements::class, false)) {
                require_once __DIR__ . '/DrmEntitlements.php';
            }
            $endpoint = DrmEntitlements::getBaseApi() . '/airlines';
            $licenseKey = DrmEntitlements::getLicenseKey();

            if ($licenseKey === '') {
                return [
                    'License Activation Required' => [
                        'UNLICENSED — Claim a Free Key or Activate in Admin -> Route Requests' => 'UNLICENSED — Claim a Free Key or Activate in Admin -> Route Requests',
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
                } catch (\Throwable $e) {}

                return [
                    'License Verification Failed' => [
                        'Unable to verify License Key with Master Server' => 'Unable to verify License Key with Master Server',
                    ],
                ];
            });
        }
    }
}
