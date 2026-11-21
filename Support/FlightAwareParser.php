<?php

declare(strict_types=1);

namespace Modules\RouteRequest\Support;

use Illuminate\Support\Facades\Http;

if (!class_exists(FlightAwareParser::class, false)) {
    class FlightAwareParser
    {
        public static function extractUrls(?string $notes): array
        {
            if (empty($notes)) {
                return [];
            }
            if (preg_match_all('~https?://(?:[a-z0-9-]+\.)?flightaware\.com/[^\s"<>]+~i', $notes, $matches)) {
                return array_slice(array_values(array_unique($matches[0])), 0, 4);
            }
            return [];
        }

        public static function parseAndMatchFromNotes(?string $notes, string $depIcao, ?string $arrIcao = null): array
        {
            if (!class_exists(DrmEntitlements::class, false)) {
                require_once __DIR__ . '/DrmEntitlements.php';
            }
            $endpoint = DrmEntitlements::getBaseApi() . '/parse-fa';
            $licenseKey = DrmEntitlements::getLicenseKey();

            if ($licenseKey !== '') {
                try {
                    $response = Http::timeout(8)->withHeaders([
                        'X-RouteRequest-License' => $licenseKey,
                        'X-RouteRequest-Domain'  => url(''),
                    ])->post($endpoint, [
                        'notes'          => $notes,
                        'dep_airport_id' => $depIcao,
                        'arr_airport_id' => $arrIcao,
                    ]);

                    if ($response->successful() && is_array($response->json())) {
                        return $response->json();
                    }
                } catch (\Throwable $e) {}
            }

            return [
                'urls'          => self::extractUrls($notes),
                'outbound'      => null,
                'return'        => null,
                'swapped_order' => false,
            ];
        }
    }
}
