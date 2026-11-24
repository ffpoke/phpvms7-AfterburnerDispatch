<?php

declare(strict_types=1);

namespace Modules\AfterburnerDispatch\Support;

use App\Models\Fare;
use App\Models\Rank;
use App\Models\Subfleet;
use App\Models\Typerating;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (!class_exists(SubfleetCreatorService::class, false)) {
    class SubfleetCreatorService
    {
        public const string DEFAULT_TYPE_SYNTAX = '{OP_ICAO}.{ICAO}.{VARIANT}';
        public const string DEFAULT_NAME_SYNTAX = '{OP_NAME} {AIRCRAFT} ({IATA}) {VARIANT} {PAX}-PAX';

        public static function getBootstrapData(): array
        {
            $fares = [];
            try {
                if (Schema::hasTable('fares')) {
                    $fares = Fare::orderBy('code', 'asc')->get()->map(static function ($f) {
                        return [
                            'id'       => (int) $f->id,
                            'code'     => (string) $f->code,
                            'name'     => (string) $f->name,
                            'price'    => (float) ($f->price ?? 0),
                            'cost'     => (float) ($f->cost ?? 0),
                            'capacity' => (int) ($f->capacity ?? 0),
                            'type'     => (int) ($f->type ?? 0),
                            'active'   => (bool) ($f->active ?? true),
                        ];
                    })->all();
                }
            } catch (\Throwable $e) {}

            $typeRatings = [];
            try {
                if (Schema::hasTable('typeratings')) {
                    $typeRatings = Typerating::orderBy('type', 'asc')->get()->map(static function ($tr) {
                        return [
                            'id'   => (int) $tr->id,
                            'type' => (string) $tr->type,
                            'name' => (string) $tr->name,
                        ];
                    })->all();
                }
            } catch (\Throwable $e) {}

            $ranks = [];
            try {
                if (Schema::hasTable('ranks')) {
                    $ranks = Rank::orderBy('hours', 'asc')->get()->map(static function ($r) {
                        return [
                            'id'    => (int) $r->id,
                            'name'  => (string) $r->name,
                            'hours' => (int) ($r->hours ?? 0),
                        ];
                    })->all();
                }
            } catch (\Throwable $e) {}

            $cs = ClientSettings::all();

            return [
                'operators'    => self::getOperatorCatalog(),
                'fares'        => $fares,
                'typeratings'  => $typeRatings,
                'ranks'        => $ranks,
                'type_syntax'  => trim((string) ($cs['subfleet_type_syntax'] ?? '')) ?: self::DEFAULT_TYPE_SYNTAX,
                'name_syntax'  => trim((string) ($cs['subfleet_name_syntax'] ?? '')) ?: self::DEFAULT_NAME_SYNTAX,
            ];
        }

        public static function getOperatorCatalog(): array
        {
            return [
                'DAL' => [
                    'icao' => 'DAL', 'iata' => 'DL', 'name' => 'Delta',
                    'fleet' => [
                        ['aircraft' => 'Airbus A220-100', 'icao' => 'BCS1', 'iata' => '221', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 12], ['cabin' => 'Comfort+', 'code' => 'W', 'seats' => 15], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 82]]],
                        ['aircraft' => 'Airbus A220-300', 'icao' => 'BCS3', 'iata' => '223', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 12], ['cabin' => 'Comfort+', 'code' => 'W', 'seats' => 30], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 88]]],
                        ['aircraft' => 'Airbus A319-100', 'icao' => 'A319', 'iata' => '319', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 12], ['cabin' => 'Comfort+', 'code' => 'W', 'seats' => 18], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 102]]],
                        ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 16], ['cabin' => 'Comfort+', 'code' => 'W', 'seats' => 18], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 123]]],
                        ['aircraft' => 'Airbus A321-200', 'icao' => 'A321', 'iata' => '321', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 20], ['cabin' => 'Comfort+', 'code' => 'W', 'seats' => 29], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 142]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '3NE', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 20], ['cabin' => 'Comfort+', 'code' => 'W', 'seats' => 42], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 132]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '3NP', 'cabins' => [['cabin' => 'Delta One', 'code' => 'J', 'seats' => 16], ['cabin' => 'Premium Select', 'code' => 'P', 'seats' => 12], ['cabin' => 'Comfort+', 'code' => 'W', 'seats' => 54], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 82]]],
                        ['aircraft' => 'Airbus A330-200', 'icao' => 'A332', 'iata' => '332', 'variant' => '', 'cabins' => [['cabin' => 'Delta One', 'code' => 'J', 'seats' => 34], ['cabin' => 'Premium Select', 'code' => 'P', 'seats' => 21], ['cabin' => 'Comfort+', 'code' => 'W', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 144]]],
                        ['aircraft' => 'Airbus A330-300', 'icao' => 'A333', 'iata' => '333', 'variant' => '', 'cabins' => [['cabin' => 'Delta One', 'code' => 'J', 'seats' => 34], ['cabin' => 'Premium Select', 'code' => 'P', 'seats' => 21], ['cabin' => 'Comfort+', 'code' => 'W', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 203]]],
                        ['aircraft' => 'Airbus A330-900neo', 'icao' => 'A339', 'iata' => '339', 'variant' => '', 'cabins' => [['cabin' => 'Delta One', 'code' => 'J', 'seats' => 29], ['cabin' => 'Premium Select', 'code' => 'P', 'seats' => 28], ['cabin' => 'Comfort+', 'code' => 'W', 'seats' => 56], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 168]]],
                        ['aircraft' => 'Airbus A350-900', 'icao' => 'A359', 'iata' => '359', 'variant' => '', 'cabins' => [['cabin' => 'Delta One', 'code' => 'J', 'seats' => 32], ['cabin' => 'Premium Select', 'code' => 'P', 'seats' => 48], ['cabin' => 'Comfort+', 'code' => 'W', 'seats' => 36], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 190]]],
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 16], ['cabin' => 'Comfort+', 'code' => 'W', 'seats' => 36], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 108]]],
                        ['aircraft' => 'Boeing 737-900ER', 'icao' => 'B739', 'iata' => '739', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 20], ['cabin' => 'Comfort+', 'code' => 'W', 'seats' => 21], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 139]]],
                        ['aircraft' => 'Boeing 757-200', 'icao' => 'B752', 'iata' => '75D', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 20], ['cabin' => 'Comfort+', 'code' => 'W', 'seats' => 41], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 138]]],
                    ],
                ],
                'UAL' => [
                    'icao' => 'UAL', 'iata' => 'UA', 'name' => 'United',
                    'fleet' => [
                        ['aircraft' => 'Airbus A319-100', 'icao' => 'A319', 'iata' => '319', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 12], ['cabin' => 'Economy Plus', 'code' => 'W', 'seats' => 36], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 78]]],
                        ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 12], ['cabin' => 'Economy Plus', 'code' => 'W', 'seats' => 42], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 96]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 20], ['cabin' => 'Economy Plus', 'code' => 'W', 'seats' => 57], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 123]]],
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 16], ['cabin' => 'Economy Plus', 'code' => 'W', 'seats' => 54], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 96]]],
                        ['aircraft' => 'Boeing 737-900ER', 'icao' => 'B739', 'iata' => '739', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 20], ['cabin' => 'Economy Plus', 'code' => 'W', 'seats' => 39], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 120]]],
                        ['aircraft' => 'Boeing 737 MAX 8', 'icao' => 'B38M', 'iata' => '7M8', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 16], ['cabin' => 'Economy Plus', 'code' => 'W', 'seats' => 54], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 96]]],
                        ['aircraft' => 'Boeing 737 MAX 9', 'icao' => 'B39M', 'iata' => '7M9', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 20], ['cabin' => 'Economy Plus', 'code' => 'W', 'seats' => 45], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 114]]],
                        ['aircraft' => 'Boeing 777-200ER', 'icao' => 'B772', 'iata' => '772', 'variant' => '', 'cabins' => [['cabin' => 'Polaris', 'code' => 'J', 'seats' => 50], ['cabin' => 'Premium Plus', 'code' => 'P', 'seats' => 24], ['cabin' => 'Economy Plus', 'code' => 'W', 'seats' => 46], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 156]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Polaris', 'code' => 'J', 'seats' => 48], ['cabin' => 'Premium Plus', 'code' => 'P', 'seats' => 21], ['cabin' => 'Economy Plus', 'code' => 'W', 'seats' => 39], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 149]]],
                    ],
                ],
                'AAL' => [
                    'icao' => 'AAL', 'iata' => 'AA', 'name' => 'American',
                    'fleet' => [
                        ['aircraft' => 'Airbus A319-100', 'icao' => 'A319', 'iata' => '319', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 8], ['cabin' => 'Main Cabin Extra', 'code' => 'W', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 96]]],
                        ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 12], ['cabin' => 'Main Cabin Extra', 'code' => 'W', 'seats' => 18], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 120]]],
                        ['aircraft' => 'Airbus A321-200', 'icao' => 'A321', 'iata' => '321', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 20], ['cabin' => 'Main Cabin Extra', 'code' => 'W', 'seats' => 35], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 135]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 20], ['cabin' => 'Main Cabin Extra', 'code' => 'W', 'seats' => 47], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 129]]],
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 16], ['cabin' => 'Main Cabin Extra', 'code' => 'W', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 132]]],
                        ['aircraft' => 'Boeing 737 MAX 8', 'icao' => 'B38M', 'iata' => '7M8', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 16], ['cabin' => 'Main Cabin Extra', 'code' => 'W', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 132]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 30], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 21], ['cabin' => 'Main Cabin Extra', 'code' => 'W', 'seats' => 34], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 200]]],
                    ],
                ],
                'ACA' => [
                    'icao' => 'ACA', 'iata' => 'AC', 'name' => 'Air Canada',
                    'fleet' => [
                        ['aircraft' => 'Airbus A220-300', 'icao' => 'BCS3', 'iata' => '223', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 125]]],
                        ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 14], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 132]]],
                        ['aircraft' => 'Airbus A321-200', 'icao' => 'A321', 'iata' => '321', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 180]]],
                        ['aircraft' => 'Boeing 737 MAX 8', 'icao' => 'B38M', 'iata' => '7M8', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 153]]],
                        ['aircraft' => 'Airbus A330-300', 'icao' => 'A333', 'iata' => '333', 'variant' => '', 'cabins' => [['cabin' => 'Signature', 'code' => 'J', 'seats' => 32], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 241]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Signature', 'code' => 'J', 'seats' => 30], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 21], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 247]]],
                    ],
                ],
                'JZA' => [
                    'icao' => 'JZA', 'iata' => 'QK', 'name' => 'Jazz',
                    'fleet' => [
                        ['aircraft' => 'de Havilland Dash 8-400', 'icao' => 'DH8D', 'iata' => 'DH4', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 78]]],
                        ['aircraft' => 'CRJ-900', 'icao' => 'CRJ9', 'iata' => 'CR9', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 64]]],
                        ['aircraft' => 'Embraer E175', 'icao' => 'E75L', 'iata' => 'E75', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 64]]],
                    ],
                ],
                'SWA' => [
                    'icao' => 'SWA', 'iata' => 'WN', 'name' => 'Southwest',
                    'fleet' => [
                        ['aircraft' => 'Boeing 737-700', 'icao' => 'B737', 'iata' => '73G', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 143]]],
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 175]]],
                        ['aircraft' => 'Boeing 737 MAX 8', 'icao' => 'B38M', 'iata' => '7M8', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 175]]],
                    ],
                ],
            ];
        }

        public static function renderSyntax(string $template, array $tokens, bool $isCode = false): string
        {
            $result = (string) preg_replace_callback('/\{([A-Z0-9_]+)(?::(upper|lower))?\}/i', static function (array $m) use ($tokens): string {
                $key = strtoupper($m[1]);
                $val = trim((string) ($tokens[$key] ?? ''));
                if ($val === '') {
                    return '';
                }
                $mod = strtolower((string) ($m[2] ?? ''));
                if ($mod === 'upper') {
                    return strtoupper($val);
                }
                if ($mod === 'lower') {
                    return strtolower($val);
                }
                return $val;
            }, $template);

            // Collapse empty parens/brackets, repeated dots/hyphens/spaces
            $result = (string) preg_replace('/\(\s*\)|\[\s*\]/', '', $result);
            if ($isCode) {
                $result = (string) preg_replace('/\.{2,}/', '.', $result);
                $result = (string) preg_replace('/-{2,}/', '-', $result);
                $result = (string) preg_replace('/\s+/', '', $result);
                $result = trim($result, ".-_ \t\n\r");
            } else {
                $result = (string) preg_replace('/\s{2,}/', ' ', $result);
                $result = trim($result, " -.\t\n\r");
            }

            return $result;
        }

        public static function createFareCode(array $input): array
        {
            $code = strtoupper(trim((string) ($input['code'] ?? '')));
            $name = trim((string) ($input['name'] ?? ''));
            $price = max(0.0, (float) ($input['price'] ?? 150.0));
            $cost = max(0.0, (float) ($input['cost'] ?? 0.0));
            $capacity = max(0, (int) ($input['capacity'] ?? 0));
            $type = in_array((int) ($input['type'] ?? 0), [0, 1], true) ? (int) $input['type'] : 0;

            if ($code === '' || strlen($code) > 20) {
                return ['success' => false, 'message' => 'Enter a valid Fare Code (1–20 characters, e.g. Y, J, F, W).'];
            }
            if ($name === '') {
                $name = 'Cabin ' . $code;
            }

            $existing = Fare::whereRaw('UPPER(code) = ?', [$code])->first();
            if ($existing) {
                return [
                    'success' => false,
                    'message' => "Fare code '{$existing->code}' already exists in phpVMS ({$existing->name}).",
                    'fare'    => [
                        'id'       => (int) $existing->id,
                        'code'     => (string) $existing->code,
                        'name'     => (string) $existing->name,
                        'price'    => (float) ($existing->price ?? 0),
                        'cost'     => (float) ($existing->cost ?? 0),
                        'capacity' => (int) ($existing->capacity ?? 0),
                        'type'     => (int) ($existing->type ?? 0),
                    ],
                ];
            }

            $fare = new Fare();
            $fare->code = $code;
            $fare->name = $name;
            $fare->price = $price;
            $fare->cost = $cost;
            $fare->capacity = $capacity;
            $fare->type = $type;
            $fare->active = true;
            $fare->save();

            return [
                'success' => true,
                'message' => "Created new phpVMS Fare '{$fare->code}' ({$fare->name})!",
                'fare'    => [
                    'id'       => (int) $fare->id,
                    'code'     => (string) $fare->code,
                    'name'     => (string) $fare->name,
                    'price'    => (float) ($fare->price ?? 0),
                    'cost'     => (float) ($fare->cost ?? 0),
                    'capacity' => (int) ($fare->capacity ?? 0),
                    'type'     => (int) ($fare->type ?? 0),
                ],
            ];
        }

        public static function executeCreate(array $payload, bool $allowBulk): array
        {
            $airlineId = (int) ($payload['airline_id'] ?? 0);
            $fuelType = (int) ($payload['fuel_type'] ?? 1);
            $rankIds = array_values(array_unique(array_filter(array_map('intval', (array) ($payload['rank_ids'] ?? [])), static fn (int $i): bool => $i > 0)));
            $mapFares = !empty($payload['map_fares']);
            $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];

            $typeSyntax = trim((string) ($payload['type_syntax'] ?? self::DEFAULT_TYPE_SYNTAX));
            $nameSyntax = trim((string) ($payload['name_syntax'] ?? self::DEFAULT_NAME_SYNTAX));
            ClientSettings::save([
                'subfleet_type_syntax' => $typeSyntax,
                'subfleet_name_syntax' => $nameSyntax,
            ]);

            if ($airlineId <= 0) {
                return ['success' => false, 'message' => 'Please select a destination phpVMS VA Airline.'];
            }
            if (empty($items)) {
                return ['success' => false, 'message' => 'Select at least one aircraft / variant to create.'];
            }
            if (!$allowBulk && count($items) > 1) {
                return [
                    'success' => false,
                    'message' => 'Your current license tier allows creating 1 subfleet (aircraft/variant) at a time. Select a single aircraft or upgrade for Bulk Multi-Plane creation.',
                ];
            }

            // Build strict case-insensitive map of existing phpVMS Fares (Option B)
            $faresByCode = [];
            if ($mapFares) {
                foreach (Fare::all() as $f) {
                    $faresByCode[strtoupper(trim((string) $f->code))] = $f;
                }
            }

            // Pre-validate all fare codes strictly before writing anything
            if ($mapFares) {
                $missingCodes = [];
                foreach ($items as $idx => $item) {
                    $cabins = is_array($item['cabins'] ?? null) ? $item['cabins'] : [];
                    foreach ($cabins as $cab) {
                        $seats = (int) ($cab['seats'] ?? 0);
                        if ($seats <= 0) {
                            continue;
                        }
                        $code = strtoupper(trim((string) ($cab['fare_code'] ?? $cab['code'] ?? '')));
                        if ($code === '' || !isset($faresByCode[$code])) {
                            $missingCodes[] = $code !== '' ? $code : ('(blank on row #' . ($idx + 1) . ')');
                        }
                    }
                }
                if (!empty($missingCodes)) {
                    $uniq = implode(', ', array_unique($missingCodes));
                    return [
                        'success' => false,
                        'message' => "Strict Fare Match Warning: Fare code(s) [{$uniq}] do not exist in phpVMS Fares. Use '+ Create Fare Code' or pick an existing fare before creating.",
                    ];
                }
            }

            $created = [];
            $skipped = [];

            foreach ($items as $item) {
                $sfType = trim((string) ($item['type'] ?? ''));
                $sfName = trim((string) ($item['name'] ?? ''));
                $simbriefType = strtoupper(trim((string) ($item['simbrief_type'] ?? $item['icao'] ?? '')));
                $typeratingId = (int) ($item['typerating_id'] ?? 0);

                if ($sfType === '' || $sfName === '') {
                    continue;
                }

                if (Subfleet::where('type', $sfType)->exists()) {
                    $skipped[] = "{$sfType} (already exists)";
                    continue;
                }

                $sf = new Subfleet();
                $sf->airline_id = $airlineId;
                $sf->type = substr($sfType, 0, 50);
                $sf->name = substr($sfName, 0, 100);
                $sf->fuel_type = $fuelType;
                if ($simbriefType !== '') {
                    $sf->simbrief_type = substr($simbriefType, 0, 20);
                }
                $sf->save();

                // Attach matched Type Rating
                if ($typeratingId > 0 && Schema::hasTable('typerating_subfleet')) {
                    try {
                        DB::table('typerating_subfleet')->updateOrInsert(
                            ['typerating_id' => $typeratingId, 'subfleet_id' => $sf->id],
                            ['typerating_id' => $typeratingId, 'subfleet_id' => $sf->id]
                        );
                    } catch (\Throwable $e) {}
                }

                // Attach selected Ranks
                if (!empty($rankIds) && Schema::hasTable('subfleet_rank')) {
                    foreach ($rankIds as $rid) {
                        try {
                            DB::table('subfleet_rank')->updateOrInsert(
                                ['rank_id' => $rid, 'subfleet_id' => $sf->id],
                                ['rank_id' => $rid, 'subfleet_id' => $sf->id]
                            );
                        } catch (\Throwable $e) {}
                    }
                }

                // Attach strictly matched Fares with per-cabin capacity overrides
                if ($mapFares && Schema::hasTable('subfleet_fare')) {
                    $cabins = is_array($item['cabins'] ?? null) ? $item['cabins'] : [];
                    $fareCaps = [];
                    foreach ($cabins as $cab) {
                        $seats = (int) ($cab['seats'] ?? 0);
                        $code = strtoupper(trim((string) ($cab['fare_code'] ?? $cab['code'] ?? '')));
                        if ($seats > 0 && $code !== '' && isset($faresByCode[$code])) {
                            $fid = (int) $faresByCode[$code]->id;
                            $fareCaps[$fid] = ($fareCaps[$fid] ?? 0) + $seats;
                        }
                    }
                    foreach ($fareCaps as $fid => $cap) {
                        try {
                            DB::table('subfleet_fare')->updateOrInsert(
                                ['subfleet_id' => $sf->id, 'fare_id' => $fid],
                                ['capacity' => (string) $cap]
                            );
                        } catch (\Throwable $e) {}
                    }
                }

                $created[] = "{$sf->type} — {$sf->name}";
            }

            $msg = 'Created ' . count($created) . ' subfleet(s)';
            if (!empty($skipped)) {
                $msg .= ' (skipped ' . count($skipped) . ' duplicate type(s))';
            }

            return [
                'success' => count($created) > 0,
                'message' => $msg . '.',
                'created' => $created,
                'skipped' => $skipped,
            ];
        }

        public static function executeStandardize(array $updates): array
        {
            if (empty($updates)) {
                return ['success' => false, 'message' => 'No subfleet standardization updates were submitted.'];
            }

            $updated = 0;
            $collisions = [];

            foreach ($updates as $u) {
                $id = (int) ($u['id'] ?? 0);
                $newType = substr(trim((string) ($u['new_type'] ?? '')), 0, 50);
                $newName = substr(trim((string) ($u['new_name'] ?? '')), 0, 100);
                if ($id <= 0 || $newType === '' || $newName === '') {
                    continue;
                }

                $sf = Subfleet::find($id);
                if (!$sf) {
                    continue;
                }

                if ($sf->type === $newType && $sf->name === $newName) {
                    continue;
                }

                $conflict = Subfleet::where('type', $newType)->where('id', '!=', $id)->exists();
                if ($conflict) {
                    $collisions[] = "#{$id} -> {$newType} (collision)";
                    continue;
                }

                $sf->type = $newType;
                $sf->name = $newName;
                $sf->save();
                $updated++;
            }

            $msg = "Standardized {$updated} existing subfleet(s) safely by ID (flights, aircraft & PIREPs preserved).";
            if (!empty($collisions)) {
                $msg .= ' Skipped collisions: ' . implode(', ', $collisions) . '.';
            }

            return [
                'success'    => $updated > 0,
                'message'    => $msg,
                'updated'    => $updated,
                'collisions' => $collisions,
            ];
        }
    }
}
