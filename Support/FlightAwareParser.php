<?php

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
            $baseApi = rtrim((string) config('routerequest.master_api_url', 'https://crew.flyfantasyair.net/api/routerequest-drm'), '/');
            $endpoint = str_ends_with($baseApi, '/parse-fa') ? $baseApi : ($baseApi . '/parse-fa');
            $licenseKey = trim((string) config('routerequest.license_key', env('ROUTEREQUEST_LICENSE_KEY', '')));

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
                } catch (\Throwable $e) {
                    // Fallback
                }
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
