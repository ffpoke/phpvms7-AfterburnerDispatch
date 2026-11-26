<?php

declare(strict_types=1);

namespace Modules\AfterburnerDispatch\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

if (!class_exists(SceneryService::class, false)) {
    final class SceneryService
    {
        public const array SIMULATORS = [
            'msfs2024_pc'      => 'MSFS 2024 (PC)',
            'msfs2024_console' => 'MSFS 2024 (Xbox / PS5)',
            'msfs2020_pc'      => 'MSFS 2020 (PC / Xbox)',
            'xp12'             => 'X-Plane 12',
            'xp11'             => 'X-Plane 11',
            'p3dv6'            => 'Prepar3D v6',
            'p3dv5'            => 'Prepar3D v5',
        ];

        public const array SUPPORTED_SIMS = self::SIMULATORS;

        public const array SCENERY_PREFS = [
            'both'              => 'Both (Payware & Freeware)',
            'freeware_only'     => 'Freeware Only',
            'payware_preferred' => 'Payware First',
        ];

        public const int CACHE_SCHEMA_VERSION = 2;

        /**
         * Built-in MSFS 2024 & MSFS 2020 Default Handcrafted / Bespoke Airport Editions.
         * Format: ICAO => ['msfs2024' => edition_label, 'msfs2020' => edition_label, 'note' => details]
         */
        private const array MSFS_HANDCRAFTED = [
            // Premium Deluxe / Aviator (+5)
            'KDEN' => ['msfs2024' => 'Premium Deluxe / Aviator', 'msfs2020' => 'Premium Deluxe', 'note' => 'Handcrafted bespoke airport included in Premium Deluxe & Aviator editions.'],
            'KSFO' => ['msfs2024' => 'Premium Deluxe / Aviator', 'msfs2020' => 'Premium Deluxe', 'note' => 'Handcrafted bespoke airport included in Premium Deluxe & Aviator editions.'],
            'EGLL' => ['msfs2024' => 'Premium Deluxe / Aviator', 'msfs2020' => 'Premium Deluxe', 'note' => 'Handcrafted London Heathrow included in Premium Deluxe & Aviator editions.'],
            'EDDF' => ['msfs2024' => 'Premium Deluxe / Aviator', 'msfs2020' => 'Premium Deluxe', 'note' => 'Handcrafted Frankfurt included in Premium Deluxe & Aviator editions.'],
            'OMDB' => ['msfs2024' => 'Premium Deluxe / Aviator', 'msfs2020' => 'Premium Deluxe', 'note' => 'Handcrafted Dubai Intl included in Premium Deluxe & Aviator editions.'],
            // Deluxe (+5)
            'KORD' => ['msfs2024' => 'Deluxe / Premium Deluxe / Aviator', 'msfs2020' => 'Deluxe / Premium Deluxe', 'note' => 'Handcrafted Chicago O\'Hare included in Deluxe edition and above.'],
            'EHAM' => ['msfs2024' => 'Deluxe / Premium Deluxe / Aviator', 'msfs2020' => 'Deluxe / Premium Deluxe', 'note' => 'Handcrafted Amsterdam Schiphol included in Deluxe edition and above.'],
            'LEMD' => ['msfs2024' => 'Deluxe / Premium Deluxe / Aviator', 'msfs2020' => 'Deluxe / Premium Deluxe', 'note' => 'Handcrafted Madrid-Barajas included in Deluxe edition and above.'],
            'HECA' => ['msfs2024' => 'Deluxe / Premium Deluxe / Aviator', 'msfs2020' => 'Deluxe / Premium Deluxe', 'note' => 'Handcrafted Cairo Intl included in Deluxe edition and above.'],
            'FACT' => ['msfs2024' => 'Deluxe / Premium Deluxe / Aviator', 'msfs2020' => 'Deluxe / Premium Deluxe', 'note' => 'Handcrafted Cape Town Intl included in Deluxe edition and above.'],
            // Standard Edition & Free World Updates
            'KLAX' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard (All Editions)', 'note' => 'Handcrafted Los Angeles Intl included in Standard Edition.'],
            'KJFK' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard (All Editions)', 'note' => 'Handcrafted New York JFK included in Standard Edition.'],
            'KATL' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 2 (USA)', 'note' => 'Handcrafted Atlanta Hartsfield-Jackson included in Standard / WU2.'],
            'KDFW' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 2 (USA)', 'note' => 'Handcrafted Dallas/Fort Worth included in Standard / WU2.'],
            'KMCO' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard (All Editions)', 'note' => 'Handcrafted Orlando Intl included in Standard Edition.'],
            'KSEA' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard (All Editions)', 'note' => 'Handcrafted Seattle-Tacoma included in Standard Edition.'],
            'KDCA' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update', 'note' => 'Handcrafted Washington Reagan National included in Standard Edition.'],
            'KASE' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard (All Editions)', 'note' => 'Handcrafted Aspen/Pitkin County included in Standard Edition.'],
            'KAVX' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 10 (USA)', 'note' => 'Handcrafted Catalina Airport included in Standard / WU10.'],
            'KTEB' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard (All Editions)', 'note' => 'Handcrafted Teterboro included in Standard Edition.'],
            'KTVL' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 10 (USA)', 'note' => 'Handcrafted Lake Tahoe included in Standard / WU10.'],
            'KBID' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 10 (USA)', 'note' => 'Handcrafted Block Island included in Standard / WU10.'],
            'KFHR' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 2 (USA)', 'note' => 'Handcrafted Friday Harbor included in Standard / WU2.'],
            'KGCN' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard (All Editions)', 'note' => 'Handcrafted Grand Canyon National Park included in Standard Edition.'],
            'KSEZ' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard (All Editions)', 'note' => 'Handcrafted Sedona included in Standard Edition.'],
            'KSWF' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 2 (USA)', 'note' => 'Handcrafted NY Stewart included in Standard / WU2.'],
            'KTEX' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard (All Editions)', 'note' => 'Handcrafted Telluride Regional included in Standard Edition.'],
            'PAVD' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 10 (USA)', 'note' => 'Handcrafted Valdez Pioneer Field included in Standard / WU10.'],
            'PHNL' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard (All Editions)', 'note' => 'Handcrafted Honolulu Intl included in Standard Edition.'],
            'PHKO' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 13 (Oceania)', 'note' => 'Handcrafted Kona Intl included in Standard / WU13.'],
            'PHOG' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard (All Editions)', 'note' => 'Handcrafted Kahului included in Standard Edition.'],
            'PHLI' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard (All Editions)', 'note' => 'Handcrafted Lihue included in Standard Edition.'],
            'PHTO' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard (All Editions)', 'note' => 'Handcrafted Hilo Intl included in Standard Edition.'],
            'CYTZ' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard (All Editions)', 'note' => 'Handcrafted Billy Bishop Toronto City included in Standard Edition.'],
            'CYBD' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 11 (Canada)', 'note' => 'Handcrafted Bella Coola included in Standard / WU11.'],
            'CYCG' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 11 (Canada)', 'note' => 'Handcrafted West Kootenay Castlegar included in Standard / WU11.'],
            'CYDA' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 11 (Canada)', 'note' => 'Handcrafted Dawson City included in Standard / WU11.'],
            'CYDF' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 11 (Canada)', 'note' => 'Handcrafted Deer Lake included in Standard / WU11.'],
            'CZST' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard (All Editions)', 'note' => 'Handcrafted Stewart Aerodrome included in Standard Edition.'],
            'LFPG' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard (All Editions)', 'note' => 'Handcrafted Paris Charles de Gaulle included in Standard Edition.'],
            'LFPB' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + City Update 2 (France)', 'note' => 'Handcrafted Paris Le Bourget included in Standard / CU2.'],
            'LFLJ' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard (All Editions)', 'note' => 'Handcrafted Courchevel Altiport included in Standard Edition.'],
            'LOWI' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard (All Editions)', 'note' => 'Handcrafted Innsbruck included in Standard Edition.'],
            'LOWK' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 6 (DACH)', 'note' => 'Handcrafted Klagenfurt included in Standard / WU6.'],
            'EKCH' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 5 (Nordics)', 'note' => 'Handcrafted Copenhagen included in Standard / WU5.'],
            'ESSA' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 5 (Nordics)', 'note' => 'Handcrafted Stockholm Arlanda included in Standard / WU5.'],
            'BIKF' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 5 (Nordics)', 'note' => 'Handcrafted Keflavik included in Standard / WU5.'],
            'LPMA' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard (All Editions)', 'note' => 'Handcrafted Madeira included in Standard Edition.'],
            'LPFR' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 8 (Iberia)', 'note' => 'Handcrafted Faro included in Standard / WU8.'],
            'LXGB' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard (All Editions)', 'note' => 'Handcrafted Gibraltar included in Standard Edition.'],
            'EGFF' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 17 (UK)', 'note' => 'Handcrafted Cardiff included in Standard / WU17.'],
            'EGGP' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 3 (UK)', 'note' => 'Handcrafted Liverpool John Lennon included in Standard / WU3.'],
            'EGPR' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 3 (UK)', 'note' => 'Handcrafted Barra Beach Airport included in Standard / WU3.'],
            'EDDK' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + City Update 4', 'note' => 'Handcrafted Cologne Bonn included in Standard / CU4.'],
            'EDDP' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 6 (DACH)', 'note' => 'Handcrafted Leipzig/Halle included in Standard / WU6.'],
            'EHRD' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 4 (Benelux)', 'note' => 'Handcrafted Rotterdam The Hague included in Standard / WU4.'],
            'YSSY' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 7 (Australia)', 'note' => 'Handcrafted Sydney Kingsford Smith included in Standard / WU7.'],
            'RJTT' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 1 (Japan)', 'note' => 'Handcrafted Tokyo Haneda included in Standard / WU1.'],
            'TNCM' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard (40th Anniversary)', 'note' => 'Handcrafted St. Maarten Princess Juliana included in Standard Edition.'],
            'TNCS' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard (All Editions)', 'note' => 'Handcrafted Saba Juancho E. Yrausquin included in Standard Edition.'],
            'LEBB' => ['msfs2024' => 'Standard (All Editions)', 'msfs2020' => 'Standard + World Update 8 (Iberia)', 'note' => 'Handcrafted Bilbao included in Standard / WU8.'],
        ];

        private static function matchesIcaoStrict(string $text, string $icao): bool
        {
            return (bool) preg_match('/(?:^|[^A-Z0-9])' . preg_quote($icao, '/') . '(?:[^A-Z0-9]|$)/i', $text);
        }

        private static function detectSimTargetsFromText(string $text, string $defaultSim = 'msfs2020_pc'): array
        {
            $t = strtolower($text);
            $sims = [];
            if (preg_match('/msfs\s*2024|fs2024|fs24|mfs24|2020\s*[\/&\-]\s*2024|2020\/24/', $t)) {
                $sims['msfs2024_pc'] = 'native';
            }
            if (preg_match('/msfs\s*2020|fs2020|fs20|mfs20|\bmsfs\b/', $t)) {
                $sims['msfs2020_pc'] = 'native';
                if (!isset($sims['msfs2024_pc'])) {
                    $sims['msfs2024_pc'] = 'compatible';
                }
            }
            if (preg_match('/x-?plane\s*12|\bxp12\b/', $t)) {
                $sims['xp12'] = 'native';
            }
            if (preg_match('/x-?plane\s*11|\bxp11\b/', $t)) {
                $sims['xp11'] = 'native';
                if (!isset($sims['xp12'])) {
                    $sims['xp12'] = 'compatible';
                }
            }
            if (preg_match('/p3d\s*v?6|prepar3d\s*v?6/', $t)) {
                $sims['p3dv6'] = 'native';
            }
            if (preg_match('/p3d\s*v?5|prepar3d\s*v?5|p3dv4/', $t)) {
                $sims['p3dv5'] = 'native';
                if (!isset($sims['p3dv6'])) {
                    $sims['p3dv6'] = 'compatible';
                }
            }
            if (empty($sims)) {
                $sims[$defaultSim] = 'native';
                if ($defaultSim === 'msfs2020_pc') {
                    $sims['msfs2024_pc'] = 'compatible';
                }
            }
            return $sims;
        }

        private static function probeShopifyStore(string $baseUrl, string $storeName, bool $isOfficialCapable, string $icao): array
        {
            $found = [];
            try {
                $url = rtrim($baseUrl, '/') . '/search/suggest.json?q=' . rawurlencode($icao) . '&resources[type]=product&resources[limit]=10';
                $resp = Http::timeout(4)->connectTimeout(2)
                    ->withHeaders([
                        'Accept'     => 'application/json',
                        'User-Agent' => 'Mozilla/5.0 (compatible; AfterburnerSceneryVerifier/4.1)',
                    ])
                    ->get($url);
                if (!$resp->successful()) {
                    return [];
                }
                $json = $resp->json();
                $products = $json['resources']['results']['products'] ?? [];
                if (!is_array($products)) {
                    return [];
                }
                foreach ($products as $p) {
                    $title = trim((string) ($p['title'] ?? ''));
                    $handle = trim((string) ($p['handle'] ?? ''));
                    $relUrl = trim((string) ($p['url'] ?? ''));
                    $vendor = trim((string) ($p['vendor'] ?? ''));
                    $price = trim((string) ($p['price'] ?? ''));
                    $tags = is_array($p['tags'] ?? null) ? implode(' ', $p['tags']) : (string) ($p['tags'] ?? '');

                    if ($title === '' || $relUrl === '') {
                        continue;
                    }
                    // Strictly require ICAO match in title or handle, and exclude liveries/guides/GSX profiles
                    if (!self::matchesIcaoStrict($title . ' ' . $handle, $icao)) {
                        continue;
                    }
                    if (preg_match('/\b(livery|liveries|paint|gsx\s*profile|sound\s*pack|checklist|manual)\b/i', $title)) {
                        continue;
                    }

                    $fullUrl = str_starts_with($relUrl, 'http') ? $relUrl : (rtrim($baseUrl, '/') . '/' . ltrim(explode('?', $relUrl)[0], '/'));
                    $isOfficial = $isOfficialCapable && ($vendor !== '' && str_contains(strtolower($storeName), strtolower(explode(' ', $vendor)[0])));
                    $sims = self::detectSimTargetsFromText($title . ' ' . $tags . ' ' . $handle, 'msfs2020_pc');

                    $found[] = [
                        'title'        => $title,
                        'developer'    => $vendor !== '' ? $vendor : $storeName,
                        'tier'         => ($price === '0.00' || $price === '0') ? 'freeware' : 'payware',
                        'price'        => ($price !== '' && $price !== '0.00' && $price !== '0') ? $price : null,
                        'store_name'   => $storeName,
                        'is_official'  => $isOfficial,
                        'url'          => $fullUrl,
                        'rating'       => null,
                        'rating_count' => 0,
                        'sims'         => $sims,
                    ];
                }
            } catch (\Throwable $e) {}
            return $found;
        }

        private static function probeOrbxDirect(string $icao): array
        {
            $found = [];
            try {
                $resp = Http::timeout(4)->connectTimeout(2)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (compatible; AfterburnerSceneryVerifier/4.1)',
                        'Accept'     => 'text/html,application/xhtml+xml',
                    ])
                    ->get('https://orbxdirect.com/search?q=' . rawurlencode($icao));
                if (!$resp->successful()) {
                    return [];
                }
                $html = (string) $resp->body();
                if (preg_match_all('/href="(\/product\/[a-z0-9\-]+)"[^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER)) {
                    $seen = [];
                    foreach ($matches as $m) {
                        $slug = $m[1];
                        if (isset($seen[$slug])) {
                            continue;
                        }
                        $text = trim(preg_replace('/\s+/', ' ', strip_tags($m[2])) ?? '');
                        if (!self::matchesIcaoStrict($slug . ' ' . $text, $icao)) {
                            continue;
                        }
                        if (preg_match('/\b(livery|aircraft|mesh|tools)\b/i', $slug . ' ' . $text)) {
                            continue;
                        }
                        $seen[$slug] = true;
                        $title = $text !== '' ? $text : strtoupper(str_replace('-', ' ', basename($slug)));
                        $found[] = [
                            'title'        => $title,
                            'developer'    => str_starts_with(basename($slug), 'orbx-') ? 'Orbx' : 'Orbx Partner',
                            'tier'         => 'payware',
                            'price'        => null,
                            'store_name'   => 'OrbxDirect',
                            'is_official'  => str_starts_with(basename($slug), 'orbx-'),
                            'url'          => 'https://orbxdirect.com' . $slug,
                            'rating'       => null,
                            'rating_count' => 0,
                            'sims'         => self::detectSimTargetsFromText($slug . ' ' . $title, 'msfs2020_pc'),
                        ];
                        if (count($found) >= 6) {
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {}
            return $found;
        }

        private static function probeSimMarket(string $icao): array
        {
            $found = [];
            try {
                $resp = Http::timeout(4)->connectTimeout(2)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (compatible; AfterburnerSceneryVerifier/4.1)',
                        'Accept'     => 'text/html,application/xhtml+xml',
                    ])
                    ->get('https://secure.simmarket.com/advanced_search_result.php?keywords=' . rawurlencode($icao));
                if (!$resp->successful()) {
                    return [];
                }
                $html = (string) $resp->body();
                if (preg_match_all('/href="(https:\/\/secure\.simmarket\.com\/[^"]+\.phtml|\/[^"]+\.phtml)"[^>]*>([^<]{4,140})<\/a>/i', $html, $matches, PREG_SET_ORDER)) {
                    $seen = [];
                    foreach ($matches as $m) {
                        $href = $m[1];
                        $title = trim(html_entity_decode(strip_tags($m[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                        $fullUrl = str_starts_with($href, 'http') ? $href : ('https://secure.simmarket.com' . $href);
                        if (isset($seen[$fullUrl])) {
                            continue;
                        }
                        if (!self::matchesIcaoStrict($title . ' ' . $href, $icao)) {
                            continue;
                        }
                        if (preg_match('/\b(livery|liveries|gsx|sound|fs2crew|voice|manual|chart)\b/i', $title)) {
                            continue;
                        }
                        $seen[$fullUrl] = true;

                        // Try extracting nearby star rating from HTML snippet around link
                        $rating = null;
                        $ratingCount = 0;
                        $pos = strpos($html, $href);
                        if ($pos !== false) {
                            $ctx = substr($html, $pos, 600);
                            if (preg_match('/([1-5](?:\.[0-9])?)\s*\((\d+)\)/', $ctx, $mr)) {
                                $rating = (float) $mr[1];
                                $ratingCount = (int) $mr[2];
                            }
                        }

                        $found[] = [
                            'title'        => $title,
                            'developer'    => 'simMarket Publisher',
                            'tier'         => 'payware',
                            'price'        => null,
                            'store_name'   => 'simMarket',
                            'is_official'  => false,
                            'url'          => $fullUrl,
                            'rating'       => $rating,
                            'rating_count' => $ratingCount,
                            'sims'         => self::detectSimTargetsFromText($title . ' ' . $href, 'msfs2020_pc'),
                        ];
                        if (count($found) >= 6) {
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {}
            return $found;
        }

        private static function probeFlightsimTo(string $icao): array
        {
            $found = [];
            try {
                $resp = Http::timeout(4)->connectTimeout(2)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (compatible; AfterburnerSceneryVerifier/4.1)',
                        'Accept'     => 'text/html,application/xhtml+xml',
                    ])
                    ->get('https://flightsim.to/search?q=' . rawurlencode($icao));
                if (!$resp->successful()) {
                    return [];
                }
                $html = (string) $resp->body();
                if (preg_match_all('/href="(\/(?:file|addon)\/\d+\/[^"]+)"[^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER)) {
                    $seen = [];
                    foreach ($matches as $m) {
                        $path = explode('?', $m[1])[0];
                        if (isset($seen[$path])) {
                            continue;
                        }
                        $title = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($m[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
                        if (!self::matchesIcaoStrict($title . ' ' . $path, $icao)) {
                            continue;
                        }
                        if (preg_match('/\b(livery|liveries|gsx|profile|repaints?|checklist)\b/i', $title . ' ' . $path)) {
                            continue;
                        }
                        $seen[$path] = true;
                        $rating = null;
                        $ratingCount = 0;
                        $pos = strpos($html, $path);
                        if ($pos !== false) {
                            $ctx = substr($html, $pos, 800);
                            if (preg_match('/([0-5]\.[0-9])\s*\((\d+)\)/', $ctx, $mr)) {
                                $rating = (float) $mr[1];
                                $ratingCount = (int) $mr[2];
                            }
                        }
                        $found[] = [
                            'title'        => $title !== '' ? $title : strtoupper(str_replace('-', ' ', basename($path))),
                            'developer'    => 'Flightsim.to Creator',
                            'tier'         => 'freeware',
                            'price'        => null,
                            'store_name'   => 'Flightsim.to',
                            'is_official'  => true,
                            'url'          => 'https://flightsim.to' . $path,
                            'rating'       => $rating,
                            'rating_count' => $ratingCount,
                            'sims'         => self::detectSimTargetsFromText($title . ' ' . $path, 'msfs2020_pc'),
                        ];
                        if (count($found) >= 5) {
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {}
            return $found;
        }

        private static function probeXPlaneOrgStore(string $icao): array
        {
            $found = [];
            try {
                $resp = Http::timeout(4)->connectTimeout(2)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (compatible; AfterburnerSceneryVerifier/4.1)',
                        'Accept'     => 'text/html,application/xhtml+xml',
                    ])
                    ->get('https://store.x-plane.org/search.asp?keyword=' . rawurlencode($icao));
                if (!$resp->successful()) {
                    return [];
                }
                $html = (string) $resp->body();
                if (preg_match_all('/href="([^"]+_p_\d+\.html)"[^>]*>([^<]{4,140})<\/a>/i', $html, $matches, PREG_SET_ORDER)) {
                    $seen = [];
                    foreach ($matches as $m) {
                        $href = $m[1];
                        $fullUrl = str_starts_with($href, 'http') ? $href : ('https://store.x-plane.org/' . ltrim($href, '/'));
                        if (isset($seen[$fullUrl])) {
                            continue;
                        }
                        $title = trim(html_entity_decode(strip_tags($m[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                        if (!self::matchesIcaoStrict($title . ' ' . $href, $icao)) {
                            continue;
                        }
                        $seen[$fullUrl] = true;
                        $found[] = [
                            'title'        => $title,
                            'developer'    => 'X-Plane.org Store',
                            'tier'         => 'payware',
                            'price'        => null,
                            'store_name'   => 'X-Plane.org Store',
                            'is_official'  => true,
                            'url'          => $fullUrl,
                            'rating'       => null,
                            'rating_count' => 0,
                            'sims'         => self::detectSimTargetsFromText($title . ' ' . $href, 'xp12'),
                        ];
                        if (count($found) >= 5) {
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {}
            return $found;
        }

        private static function probeXPlaneGateway(string $icao): array
        {
            try {
                $resp = Http::timeout(3)->connectTimeout(2)
                    ->withHeaders(['Accept' => 'application/json', 'User-Agent' => 'AfterburnerSceneryVerifier/4.1'])
                    ->get('https://gateway.x-plane.com/apiv1/airport/' . rawurlencode($icao));
                if ($resp->successful()) {
                    $json = $resp->json();
                    $ap = is_array($json['airport'] ?? null) ? $json['airport'] : [];
                    if (!empty($ap)) {
                        $recId = $ap['RecommendedSceneryId'] ?? null;
                        return [
                            'name'                   => (string) ($ap['AirportName'] ?? $icao),
                            'has_3d'                 => !empty($recId),
                            'recommended_scenery_id' => $recId,
                        ];
                    }
                }
            } catch (\Throwable $e) {}
            return [];
        }

        /**
         * Scrapes and evaluates all configured payware/freeware storefronts + default sim editions for an ICAO.
         * Groups 3rd-party storefront links alongside official developer links, and marks unavailable sims cleanly.
         */
        private static function scrapeAndEvaluateAirport(string $icao): array
        {
            $nowIso = Carbon::now()->toIso8601String();
            $xpGw = self::probeXPlaneGateway($icao);
            $airportName = $xpGw['name'] ?? $icao;

            // Scrape multiple real storefronts and verify ICAO match
            $rawProducts = array_merge(
                self::probeShopifyStore('https://inibuilds.com', 'iniBuilds Store', true, $icao),
                self::probeShopifyStore('https://contrail.shop', 'Contrail', false, $icao),
                self::probeOrbxDirect($icao),
                self::probeSimMarket($icao),
                self::probeFlightsimTo($icao),
                self::probeXPlaneOrgStore($icao)
            );

            // Group duplicate products across official & 3rd-party storefronts by normalized title/developer stem
            $grouped = [];
            foreach ($rawProducts as $item) {
                $normKey = strtolower(preg_replace('/[^a-z0-9]/', '', $item['developer'] . '_' . $icao . '_' . $item['tier']) ?? '');
                // Keep distinct products if titles differ significantly (e.g. MSFS 2024 Enhanced vs MSFS 2020)
                $simSig = implode(',', array_keys($item['sims']));
                $gKey = $normKey . '|' . $simSig;

                if (!isset($grouped[$gKey])) {
                    $grouped[$gKey] = [
                        'title'             => $item['title'],
                        'developer'         => $item['developer'],
                        'tier'              => $item['tier'],
                        'price'             => $item['price'],
                        'official_store'    => $item['is_official'] ? $item['store_name'] : null,
                        'official_url'      => $item['is_official'] ? $item['url'] : null,
                        'primary_store'     => $item['store_name'],
                        'primary_url'       => $item['url'],
                        'third_party_links' => $item['is_official'] ? [] : [['store' => $item['store_name'], 'url' => $item['url']]],
                        'rating'            => $item['rating'],
                        'rating_count'      => (int) $item['rating_count'],
                        'sims'              => $item['sims'],
                    ];
                } else {
                    if ($item['is_official'] && empty($grouped[$gKey]['official_url'])) {
                        $grouped[$gKey]['official_store'] = $item['store_name'];
                        $grouped[$gKey]['official_url']   = $item['url'];
                        $grouped[$gKey]['primary_store']  = $item['store_name'];
                        $grouped[$gKey]['primary_url']    = $item['url'];
                        $grouped[$gKey]['title']          = $item['title'];
                    } else {
                        $existsStore = false;
                        foreach ($grouped[$gKey]['third_party_links'] as $tpl) {
                            if ($tpl['store'] === $item['store_name']) {
                                $existsStore = true;
                                break;
                            }
                        }
                        if (!$existsStore && $grouped[$gKey]['official_store'] !== $item['store_name']) {
                            $grouped[$gKey]['third_party_links'][] = ['store' => $item['store_name'], 'url' => $item['url']];
                        }
                    }
                    if ($item['rating'] !== null && ($grouped[$gKey]['rating'] === null || $item['rating_count'] > $grouped[$gKey]['rating_count'])) {
                        $grouped[$gKey]['rating'] = $item['rating'];
                        $grouped[$gKey]['rating_count'] = (int) $item['rating_count'];
                    }
                    if (empty($grouped[$gKey]['price']) && !empty($item['price'])) {
                        $grouped[$gKey]['price'] = $item['price'];
                    }
                }
            }

            $hc = self::MSFS_HANDCRAFTED[$icao] ?? null;
            $xp3d = !empty($xpGw['has_3d']);
            $xpGwUrl = 'https://gateway.x-plane.com/airports/' . rawurlencode($icao) . '/show';

            $simCatalog = [];
            $totalVerified = 0;

            foreach (self::SUPPORTED_SIMS as $simKey => $simLabel) {
                // 1. Default Sim / Handcrafted Edition Status
                $defaultSimInfo = match ($simKey) {
                    'msfs2024_pc', 'msfs2024_console' => $hc
                        ? ['included' => true, 'handcrafted' => true, 'edition' => 'MSFS 2024 ' . $hc['msfs2024'], 'note' => $hc['note']]
                        : ['included' => true, 'handcrafted' => false, 'edition' => 'MSFS 2024 Standard (Procedural Auto-Gen)', 'note' => 'Included as a standard procedural airport in all MSFS 2024 editions.'],
                    'msfs2020_pc' => $hc
                        ? ['included' => true, 'handcrafted' => true, 'edition' => 'MSFS 2020 ' . $hc['msfs2020'], 'note' => $hc['note']]
                        : ['included' => true, 'handcrafted' => false, 'edition' => 'MSFS 2020 Standard (Procedural Auto-Gen)', 'note' => 'Included as a standard procedural airport in all MSFS 2020 editions.'],
                    'xp12' => $xp3d
                        ? ['included' => true, 'handcrafted' => true, 'edition' => 'X-Plane 12 Global Airports (3D Scenery Gateway Approved #' . ($xpGw['recommended_scenery_id'] ?? '') . ')', 'note' => 'Verified 3D Gateway scenery included in X-Plane 12.', 'url' => $xpGwUrl]
                        : ['included' => true, 'handcrafted' => false, 'edition' => 'X-Plane 12 Default Global Airports', 'note' => 'Standard default airport in X-Plane 12.'],
                    'xp11' => $xp3d
                        ? ['included' => true, 'handcrafted' => true, 'edition' => 'X-Plane 11 Global Airports (3D Gateway)', 'note' => '3D Scenery Gateway airport available.', 'url' => $xpGwUrl]
                        : ['included' => true, 'handcrafted' => false, 'edition' => 'X-Plane 11 Default Global Airports', 'note' => 'Standard default airport in X-Plane 11.'],
                    'p3dv6' => ['included' => true, 'handcrafted' => false, 'edition' => 'Prepar3D v6 Default Airport', 'note' => 'Standard default airport in Prepar3D v6.'],
                    'p3dv5' => ['included' => true, 'handcrafted' => false, 'edition' => 'Prepar3D v5 Default Airport', 'note' => 'Standard default airport in Prepar3D v5.'],
                    default => ['included' => true, 'handcrafted' => false, 'edition' => $simLabel . ' Default', 'note' => 'Default simulator airport.'],
                };

                // 2. Filter verified scraped products for this simulator
                $options = [];
                foreach ($grouped as $g) {
                    // Console cannot use PC-only freeware/community zips
                    if ($simKey === 'msfs2024_console') {
                        continue;
                    }
                    if (!isset($g['sims'][$simKey])) {
                        continue;
                    }
                    $compatType = $g['sims'][$simKey]; // 'native' or 'compatible'
                    $compatLabel = $compatType === 'native'
                        ? ($simLabel . ' Native')
                        : match ($simKey) {
                            'msfs2024_pc' => 'MSFS 2020 → MSFS 2024 Compatible',
                            'xp12'        => 'X-Plane 11 → X-Plane 12 Compatible',
                            'p3dv6'       => 'Prepar3D v5 → v6 Compatible',
                            default       => 'Compatible',
                        };

                    $installHint = match ($simKey) {
                        'msfs2024_pc' => $compatType === 'native'
                            ? 'Install via vendor manager (iniManager / Contrail / Orbx Central) or extract to MSFS 2024 Community folder.'
                            : 'MSFS 2020 pack: install into Community2024 and disable any duplicate handcrafted airport in My Library.',
                        'msfs2020_pc' => 'Install via vendor manager (iniManager / Contrail / Orbx Central) or extract to MSFS 2020 Community folder.',
                        'xp12', 'xp11' => 'Extract into Custom Scenery and order above Global Airports in scenery_packs.ini.',
                        'p3dv6', 'p3dv5' => 'Install via add-on.xml vendor installer and rebuild scenery library.',
                        default => 'Follow vendor installation instructions.',
                    };

                    $options[] = [
                        'tier'              => $g['tier'],
                        'title'             => $g['title'],
                        'developer'         => $g['developer'],
                        'price'             => $g['price'],
                        'compat_type'       => $compatType,
                        'compat_label'      => $compatLabel,
                        'store_name'        => $g['official_store'] ?: $g['primary_store'],
                        'url'               => $g['official_url'] ?: $g['primary_url'],
                        'is_official_link'  => !empty($g['official_url']),
                        'third_party_links' => $g['third_party_links'],
                        'instructions'      => $installHint,
                        'review'            => [
                            'rating' => $g['rating'],
                            'count'  => $g['rating_count'],
                            'source' => $g['official_store'] ?: $g['primary_store'],
                            'url'    => $g['official_url'] ?: $g['primary_url'],
                        ],
                    ];
                }

                // Sort: Native first, then highest rating, then Payware/Freeware
                usort($options, function ($a, $b) {
                    $na = ($a['compat_type'] === 'native') ? 0 : 1;
                    $nb = ($b['compat_type'] === 'native') ? 0 : 1;
                    if ($na !== $nb) {
                        return $na <=> $nb;
                    }
                    $ra = (float) ($a['review']['rating'] ?? -1);
                    $rb = (float) ($b['review']['rating'] ?? -1);
                    return $rb <=> $ra;
                });

                $totalVerified += count($options);
                $simCatalog[$simKey] = [
                    'label'         => $simLabel,
                    'default_sim'   => $defaultSimInfo,
                    'not_available' => empty($options),
                    'not_available_note' => ($simKey === 'msfs2024_console')
                        ? "Console add-ons are only sold inside the MSFS 2024 In-Sim Marketplace (search '{$icao}' in-sim)."
                        : "Not Available — No verified third-party payware or freeware add-on found for {$icao} ({$simLabel}) across checked storefronts.",
                    'options'       => $options,
                ];
            }

            return [
                'schema_v'             => self::CACHE_SCHEMA_VERSION,
                'icao'                 => $icao,
                'airport_name'         => $airportName,
                'reviews_refreshed_at' => $nowIso,
                'verified_count'       => $totalVerified,
                'stores_checked'       => ['iniBuilds', 'Contrail', 'OrbxDirect', 'simMarket', 'Flightsim.to', 'X-Plane.org', 'X-Plane Gateway'],
                'simulators'           => $simCatalog,
            ];
        }

        public static function isStandalone(): bool
        {
            return class_exists(VaultLoader::class, false);
        }

        public static function getPilotPrefs(int $userId): array
        {
            SchemaHelper::ensureRouteRequestsColumns();
            $default = [
                'simulators'       => [],
                'scenery_pref'     => 'both',
                'needs_onboarding' => true,
                'prompted_version' => null,
            ];
            if ($userId <= 0 || !Schema::hasTable('afterburner_pilot_prefs')) {
                return $default;
            }
            try {
                $row = DB::table('afterburner_pilot_prefs')->where('user_id', $userId)->first();
                if (!$row) {
                    return $default;
                }
                $sims = json_decode((string) ($row->simulators_json ?? '[]'), true);
                if (!is_array($sims)) {
                    $sims = [];
                }
                $sims = array_values(array_intersect($sims, array_keys(self::SIMULATORS)));
                $pref = isset(self::SCENERY_PREFS[(string) ($row->scenery_pref ?? '')])
                    ? (string) $row->scenery_pref
                    : 'both';
                return [
                    'simulators'       => $sims,
                    'scenery_pref'     => $pref,
                    'needs_onboarding' => empty($sims),
                    'prompted_version' => $row->prompted_version,
                ];
            } catch (\Throwable $e) {
                return $default;
            }
        }

        public static function savePilotPrefs(int $userId, array $simulators, string $sceneryPref = 'both'): array
        {
            SchemaHelper::ensureRouteRequestsColumns();
            $cleanSims = array_values(array_intersect(
                array_map(fn ($s) => strtolower(trim((string) $s)), $simulators),
                array_keys(self::SIMULATORS)
            ));
            $pref = isset(self::SCENERY_PREFS[$sceneryPref]) ? $sceneryPref : 'both';
            if ($userId > 0 && Schema::hasTable('afterburner_pilot_prefs')) {
                $now = Carbon::now();
                DB::table('afterburner_pilot_prefs')->updateOrInsert(
                    ['user_id' => $userId],
                    [
                        'simulators_json'  => json_encode($cleanSims),
                        'scenery_pref'     => $pref,
                        'prompted_version' => UpdateManager::getCurrentVersion(),
                        'updated_at'       => $now,
                        'created_at'       => $now,
                    ]
                );
            }
            return self::getPilotPrefs($userId);
        }

        public static function lookupForAirports(array $icaos, array $simulators, string $sceneryPref = 'both', bool $recordDemand = true, bool $forceRefresh = false): array
        {
            SchemaHelper::ensureRouteRequestsColumns();
            $cleanIcaos = [];
            foreach ($icaos as $raw) {
                $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', trim((string) $raw)) ?? '');
                if ($code !== '' && strlen($code) >= 3 && strlen($code) <= 6) {
                    $cleanIcaos[$code] = true;
                }
            }
            $cleanIcaos = array_slice(array_keys($cleanIcaos), 0, 12);
            if (empty($cleanIcaos)) {
                return [];
            }

            $cleanSims = array_values(array_intersect(
                array_map(fn ($s) => strtolower(trim((string) $s)), $simulators),
                array_keys(self::SIMULATORS)
            ));
            if (empty($cleanSims)) {
                $cleanSims = ['msfs2024_pc'];
            }

            $cached = [];
            $staleOrMissing = [];
            $now = Carbon::now();

            if (!$forceRefresh && Schema::hasTable('afterburner_scenery_cache')) {
                $rows = DB::table('afterburner_scenery_cache')->whereIn('icao', $cleanIcaos)->get()->keyBy('icao');
                foreach ($cleanIcaos as $icao) {
                    $r = $rows[$icao] ?? null;
                    if ($r && !empty($r->payload_json) && !empty($r->cached_at) && Carbon::parse($r->cached_at)->addDays(14)->gt($now)) {
                        $dec = json_decode((string) $r->payload_json, true);
                        // Automatically invalidate legacy v1 cache records (such as old flightsim.to/discover/ links)
                        if (is_array($dec) && ((int) ($dec['schema_v'] ?? 0) >= self::CACHE_SCHEMA_VERSION)) {
                            $cached[$icao] = $dec;
                            continue;
                        }
                    }
                    $staleOrMissing[] = $icao;
                }
            } else {
                $staleOrMissing = $cleanIcaos;
            }

            if (!empty($staleOrMissing)) {
                $fetched = !self::isStandalone()
                    ? self::fetchFromAfterburnerServer($staleOrMissing, array_keys(self::SIMULATORS), $recordDemand, $forceRefresh)
                    : [];

                foreach ($staleOrMissing as $icao) {
                    $entry = (isset($fetched[$icao]) && ((int) ($fetched[$icao]['schema_v'] ?? 0) >= self::CACHE_SCHEMA_VERSION))
                        ? $fetched[$icao]
                        : self::scrapeAndEvaluateAirport($icao);
                    $cached[$icao] = $entry;
                    if (Schema::hasTable('afterburner_scenery_cache')) {
                        try {
                            DB::table('afterburner_scenery_cache')->updateOrInsert(
                                ['icao' => $icao],
                                [
                                    'airport_name' => substr((string) ($entry['airport_name'] ?? $icao), 0, 160),
                                    'payload_json' => json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                    'cached_at'    => $now,
                                    'updated_at'   => $now,
                                    'created_at'   => $now,
                                ]
                            );
                        } catch (\Throwable $e) {}
                    }
                }
            }

            $overrides = self::getOverridesForAirports($cleanIcaos);
            $result = [];
            foreach ($cleanIcaos as $icao) {
                $entry = $cached[$icao] ?? self::scrapeAndEvaluateAirport($icao);
                $simOut = [];
                foreach ($cleanSims as $simKey) {
                    $baseSim = $entry['simulators'][$simKey] ?? ['label' => self::SIMULATORS[$simKey], 'options' => [], 'not_available' => true];
                    $opts = is_array($baseSim['options'] ?? null) ? $baseSim['options'] : [];

                    if ($sceneryPref === 'freeware_only') {
                        $opts = array_values(array_filter($opts, fn ($o) => ($o['tier'] ?? '') === 'freeware'));
                    } elseif ($sceneryPref === 'payware_preferred') {
                        usort($opts, fn ($a, $b) => (($a['tier'] ?? '') === 'payware' ? 0 : 1) <=> (($b['tier'] ?? '') === 'payware' ? 0 : 1));
                    }

                    $vaPins = [];
                    foreach (($overrides[$icao] ?? []) as $ov) {
                        if ($ov['simulator'] === $simKey || $ov['simulator'] === 'all') {
                            $vaPins[] = [
                                'tier'              => $ov['tier'] ?: 'va_recommended',
                                'va_pin'            => true,
                                'title'             => '★ VA Recommended: ' . $ov['title'],
                                'developer'         => 'VA Staff Pin',
                                'compat_type'       => $ov['compat_type'] ?: 'native',
                                'compat_label'      => $ov['compat_label'] ?: ('VA Verified for ' . self::SIMULATORS[$simKey]),
                                'store_name'        => 'VA Recommended',
                                'url'               => $ov['url'],
                                'is_official_link'  => true,
                                'third_party_links' => [],
                                'instructions'      => $ov['instructions'] ?: 'Recommended by your Virtual Airline staff.',
                                'review'            => ['rating' => null, 'count' => 0, 'source' => 'VA Staff', 'url' => $ov['url']],
                            ];
                        }
                    }

                    $mergedOpts = array_merge($vaPins, $opts);
                    $simOut[$simKey] = [
                        'label'              => self::SIMULATORS[$simKey],
                        'default_sim'        => $baseSim['default_sim'] ?? null,
                        'not_available'      => empty($mergedOpts),
                        'not_available_note' => $baseSim['not_available_note'] ?? "Not Available — No verified add-on scenery found for {$icao} (" . self::SIMULATORS[$simKey] . ').',
                        'options'            => $mergedOpts,
                    ];
                }

                $result[$icao] = [
                    'schema_v'             => $entry['schema_v'] ?? self::CACHE_SCHEMA_VERSION,
                    'icao'                 => $icao,
                    'airport_name'         => $entry['airport_name'] ?? $icao,
                    'reviews_refreshed_at' => $entry['reviews_refreshed_at'] ?? null,
                    'stores_checked'       => $entry['stores_checked'] ?? [],
                    'simulators'           => $simOut,
                ];
            }

            return $result;
        }

        private static function fetchFromAfterburnerServer(array $icaos, array $simulators, bool $recordDemand, bool $forceRefresh = false): array
        {
            $key = DrmEntitlements::getLicenseKey();
            if ($key === '') {
                return [];
            }
            try {
                $endpoint = DrmEntitlements::getBaseApi() . '/scenery/lookup';
                $resp = Http::withHeaders([
                    'X-Afterburner-License' => $key,
                    'X-Afterburner-Domain'  => url(''),
                    'Accept'                => 'application/json',
                ])->timeout(10)->connectTimeout(3)->post($endpoint, [
                    'icaos'         => $icaos,
                    'simulators'    => $simulators,
                    'record_demand' => $recordDemand,
                    'force_refresh' => $forceRefresh,
                ]);
                if ($resp->successful()) {
                    $json = $resp->json();
                    if (!empty($json['success']) && is_array($json['airports'] ?? null)) {
                        return $json['airports'];
                    }
                }
            } catch (\Throwable $e) {}
            return [];
        }

        public static function getOverridesForAirports(array $icaos): array
        {
            if (empty($icaos) || !Schema::hasTable('afterburner_scenery_overrides')) {
                return [];
            }
            try {
                $rows = DB::table('afterburner_scenery_overrides')->whereIn('icao', $icaos)->orderByDesc('id')->get();
                $map = [];
                foreach ($rows as $r) {
                    $map[$r->icao][] = [
                        'id'           => $r->id,
                        'icao'         => $r->icao,
                        'simulator'    => $r->simulator,
                        'tier'         => $r->tier,
                        'title'        => $r->title,
                        'compat_type'  => $r->compat_type,
                        'compat_label' => $r->compat_label,
                        'url'          => $r->url,
                        'instructions' => $r->instructions,
                    ];
                }
                return $map;
            } catch (\Throwable $e) {
                return [];
            }
        }

        public static function purgeLocalCache(): int
        {
            if (!Schema::hasTable('afterburner_scenery_cache')) {
                return 0;
            }
            return (int) DB::table('afterburner_scenery_cache')->delete();
        }

        public static function refreshRecentAirportsCache(int $limit = 10): int
        {
            if (!Schema::hasTable('afterburner_scenery_cache')) {
                return 0;
            }
            try {
                $staleIcaos = DB::table('afterburner_scenery_cache')
                    ->where('cached_at', '<=', Carbon::now()->subDays(14))
                    ->orderBy('cached_at')
                    ->limit($limit)
                    ->pluck('icao')
                    ->all();
                if (empty($staleIcaos)) {
                    return 0;
                }
                $fetched = !self::isStandalone()
                    ? self::fetchFromAfterburnerServer($staleIcaos, array_keys(self::SIMULATORS), false, false)
                    : [];
                $now = Carbon::now();
                $updated = 0;
                foreach ($staleIcaos as $icao) {
                    $entry = $fetched[$icao] ?? self::scrapeAndEvaluateAirport($icao);
                    DB::table('afterburner_scenery_cache')->where('icao', $icao)->update([
                        'airport_name' => substr((string) ($entry['airport_name'] ?? $icao), 0, 160),
                        'payload_json' => json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        'cached_at'    => $now,
                        'updated_at'   => $now,
                    ]);
                    $updated++;
                }
                return $updated;
            } catch (\Throwable $e) {
                return 0;
            }
        }
    }
}
