<?php

declare(strict_types=1);

namespace Modules\AfterburnerDispatch\Support;

use App\Models\Airport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

if (!class_exists(VatsimOverlayService::class, false)) {
    class VatsimOverlayService
    {
        public static function getActiveAtcAirports(): array
        {
            return Cache::remember('rr_vatsim_atc_overlay_v1', 120, function (): array {
                try {
                    $res = Http::withHeaders([
                        'User-Agent' => 'phpVMS7-AfterburnerDispatch-VatsimOverlay/4.0.0',
                        'Accept'     => 'application/json',
                    ])->timeout(8)->get('https://data.vatsim.net/v3/vatsim-data.json');

                    if (!$res->successful()) {
                        return [];
                    }

                    $data = $res->json();
                    $controllers = is_array($data['controllers'] ?? null) ? $data['controllers'] : [];
                    $byIcao = [];

                    foreach ($controllers as $c) {
                        $cs = strtoupper(trim((string) ($c['callsign'] ?? '')));
                        $fac = (int) ($c['facility'] ?? 0);
                        if ($cs === '' || $fac < 2) {
                            continue;
                        }
                        $parts = explode('_', $cs);
                        $prefix = $parts[0] ?? '';
                        if (strlen($prefix) === 3 && preg_match('/^[A-Z]{3}$/', $prefix)) {
                            $prefix = 'K' . $prefix; // US 3-letter VATSIM callsigns (e.g. ORD_TWR -> KORD)
                        }
                        if (strlen($prefix) !== 4 || !preg_match('/^[A-Z]{4}$/', $prefix)) {
                            continue;
                        }
                        if (!isset($byIcao[$prefix])) {
                            $byIcao[$prefix] = [];
                        }
                        $byIcao[$prefix][] = $cs . ' (' . (string) ($c['frequency'] ?? '') . ')';
                    }

                    if (empty($byIcao)) {
                        return [];
                    }

                    $airports = Airport::select('id', 'name', 'lat', 'lon')
                        ->whereIn('id', array_keys($byIcao))
                        ->whereNotNull('lat')
                        ->whereNotNull('lon')
                        ->limit(150)
                        ->get();

                    $out = [];
                    foreach ($airports as $a) {
                        $id = strtoupper((string) $a->id);
                        $out[] = [
                            'icao'     => $id,
                            'name'     => (string) $a->name,
                            'lat'      => (float) $a->lat,
                            'lon'      => (float) $a->lon,
                            'stations' => array_slice(array_values(array_unique($byIcao[$id] ?? [])), 0, 4),
                        ];
                    }

                    return $out;
                } catch (\Throwable $e) {
                    return [];
                }
            });
        }
    }
}
