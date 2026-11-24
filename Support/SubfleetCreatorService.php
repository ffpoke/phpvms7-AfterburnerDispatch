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
                'operators'          => self::getOperatorCatalog(),
                'operator_directory' => self::getOperatorDirectory(),
                'starter_fleets'     => self::getCategoryStarterFleets(),
                'fares'        => $fares,
                'typeratings'  => $typeRatings,
                'ranks'        => $ranks,
                'type_syntax'  => trim((string) ($cs['subfleet_type_syntax'] ?? '')) ?: self::DEFAULT_TYPE_SYNTAX,
                'name_syntax'  => trim((string) ($cs['subfleet_name_syntax'] ?? '')) ?: self::DEFAULT_NAME_SYNTAX,
            ];
        }

        public static function getIcaoToIataMap(): array
        {
            return [
            'AAL' => 'AA', 'AAR' => 'OZ', 'AAY' => 'G4', 'ABL' => 'BX', 'ABX' => 'GB', 'ABY' => 'G9', 'ACA' => 'AC', 'ACI' => 'SB',
            'ACN' => '2F', 'ADO' => 'HD', 'AEA' => 'UX', 'AEE' => 'A3', 'AEZ' => 'XZ', 'AFR' => 'AF', 'AHY' => 'J2', 'AIC' => 'AI',
            'AIE' => '3H', 'AIQ' => 'FD', 'AIZ' => 'IZ', 'AJT' => 'M6', 'AJX' => 'NQ', 'AKJ' => 'QP', 'AKT' => '5T', 'ALK' => 'UL',
            'AMF' => 'A8', 'AMU' => 'NX', 'AMX' => 'AM', 'ANA' => 'NH', 'ANE' => 'YW', 'ANG' => 'PX', 'ANT' => '4N', 'ANZ' => 'NZ',
            'APG' => 'Z2', 'APJ' => 'MM', 'APK' => 'P4', 'APZ' => 'YP', 'ARE' => '4C', 'ARG' => 'AR', 'ASA' => 'AS', 'ASH' => 'YV',
            'ASL' => 'JU', 'ASV' => 'RS', 'ATC' => 'TC', 'ATN' => '8C', 'AUA' => 'OS', 'AUR' => 'GR', 'AVA' => 'AV', 'AWC' => 'ZT',
            'AWG' => 'A2', 'AWI' => 'ZW', 'AWQ' => 'QZ', 'AXB' => 'IX', 'AXM' => 'AK', 'AZG' => '7L', 'AZP' => 'ZP', 'AZU' => 'AD',
            'BAV' => 'QH', 'BAW' => 'BA', 'BBC' => 'BG', 'BCS' => 'QY', 'BEL' => 'SN', 'BHS' => 'UP', 'BKP' => 'PG', 'BLS' => 'JV',
            'BLX' => '6B', 'BMA' => '2T', 'BOV' => 'OB', 'BOX' => '3S', 'BRX' => 'TF', 'BTI' => 'BT', 'BTK' => 'ID', 'BWA' => 'BW',
            'BYD' => 'B4', 'CAI' => 'XC', 'CAL' => 'CI', 'CAO' => 'CA', 'CAY' => 'KX', 'CCA' => 'CA', 'CCM' => 'XK', 'CEB' => '5J',
            'CES' => 'MU', 'CFE' => 'CJ', 'CFG' => 'DE', 'CFS' => 'EM', 'CHH' => 'HU', 'CJT' => 'W8', 'CKK' => 'CK', 'CKS' => 'K4',
            'CLG' => 'CE', 'CLH' => 'CL', 'CLX' => 'CV', 'CMA' => '2C', 'CMP' => 'CM', 'CND' => 'CD', 'CNF' => 'PM', 'CPA' => 'CX',
            'CQH' => '9C', 'CRK' => 'HX', 'CRL' => 'SS', 'CSC' => '3U', 'CSN' => 'CZ', 'CSS' => 'O3', 'CSZ' => 'ZH', 'CTN' => 'OU',
            'CTV' => 'QG', 'CXA' => 'MF', 'CYF' => 'U8', 'CYP' => 'CY', 'DAH' => 'AH', 'DAL' => 'DL', 'DHK' => 'D0', 'DJT' => 'B0',
            'DKH' => 'HO', 'DLA' => 'EN', 'DLH' => 'LH', 'DRK' => 'KB', 'DSB' => '4P', 'DTA' => 'DT', 'DTR' => 'DX', 'DWI' => 'DM',
            'EAI' => 'EA', 'EAL' => '2D', 'EDV' => '9E', 'EDW' => 'WK', 'EFW' => 'BA', 'EFY' => 'VE', 'EIN' => 'EI', 'EJU' => 'EC',
            'ELY' => 'LY', 'ENT' => 'E4', 'ENY' => 'MQ', 'ESR' => 'ZE', 'ETD' => 'EY', 'ETH' => 'ET', 'EVA' => 'BR', 'EVE' => 'E9',
            'EWG' => 'EW', 'EXS' => 'LS', 'EZS' => 'DS', 'EZY' => 'U2', 'FAD' => 'F3', 'FBU' => 'BF', 'FBZ' => 'FO', 'FCM' => 'N7',
            'FDA' => 'JH', 'FDB' => 'FZ', 'FDX' => 'FX', 'FDY' => '9X', 'FFM' => 'FY', 'FFT' => 'F9', 'FHY' => 'FH', 'FIA' => '5F',
            'FIN' => 'AY', 'FJI' => 'FJ', 'FLE' => 'F8', 'FLI' => 'RC', 'FPO' => '5O', 'FWI' => 'TX', 'GEC' => 'LH', 'GFA' => 'GF',
            'GIA' => 'GA', 'GJS' => 'G7', 'GLO' => 'G3', 'GPD' => 'TJ', 'GRL' => 'GL', 'GSW' => 'GM', 'GTI' => '5Y', 'GXA' => 'G6',
            'HAL' => 'HA', 'HES' => 'YB', 'HFM' => '5K', 'HGB' => 'HB', 'HKE' => 'UO', 'HOP' => 'A5', 'HVN' => 'VN', 'HYS' => 'H4',
            'IBB' => 'NT', 'IBE' => 'IB', 'IBS' => 'I2', 'ICE' => 'FI', 'ICL' => '5S', 'IGO' => '6E', 'ISR' => '6H', 'ITY' => 'AZ',
            'IWY' => 'JY', 'JAF' => 'TB', 'JAL' => 'JL', 'JAT' => 'JA', 'JBU' => 'B6', 'JIA' => 'OH', 'JJA' => '7C', 'JJP' => 'GK',
            'JMA' => 'JM', 'JNA' => 'LJ', 'JST' => 'JQ', 'JSX' => 'XE', 'JZA' => 'QK', 'JZR' => 'J9', 'KAC' => 'KU', 'KAL' => 'KE',
            'KAP' => '9K', 'KEM' => '5Z', 'KEN' => 'M5', 'KLC' => 'WA', 'KLM' => 'KL', 'KMM' => 'KM', 'KNE' => 'XY', 'KQA' => 'KQ',
            'KRP' => 'V3', 'KZR' => 'KC', 'LAN' => 'LA', 'LCO' => 'UC', 'LDA' => 'LW', 'LGL' => 'LG', 'LIA' => '5L', 'LNE' => 'XL',
            'LNI' => 'JT', 'LNK' => '4Z', 'LOG' => 'LM', 'LOT' => 'LO', 'LPE' => 'LP', 'LYC' => 'L2', 'LYM' => 'KG', 'LZB' => 'FB',
            'MAS' => 'MH', 'MAU' => 'MK', 'MAY' => 'AL', 'MBU' => 'DI', 'MDA' => 'AE', 'MEA' => 'ME', 'MHO' => 'MW', 'MNB' => 'MB',
            'MNE' => '4O', 'MPH' => 'MP', 'MSC' => 'SM', 'MSR' => 'MS', 'MTN' => 'C2', 'MXA' => 'XN', 'MXD' => 'OD', 'MXY' => 'MX',
            'NAC' => 'NC', 'NBT' => 'N0', 'NCA' => 'KZ', 'NCR' => 'N8', 'NIA' => 'NP', 'NOK' => 'DD', 'NOS' => 'NO', 'NOZ' => 'DY',
            'NSE' => '9R', 'NSZ' => 'D8', 'NVD' => 'X9', 'OAE' => 'OY', 'OAL' => 'OA', 'OAW' => '2L', 'OCN' => '4Y', 'OMA' => 'WY',
            'OMS' => 'OV', 'PAC' => 'PO', 'PAG' => 'YP', 'PAL' => 'PR', 'PCO' => '8P', 'PDT' => 'PT', 'PGT' => 'PC', 'PIA' => 'PK',
            'PLM' => 'EB', 'POE' => 'PD', 'PSC' => 'P6', 'PUE' => 'PU', 'PVG' => 'P6', 'PVL' => 'PB', 'QFA' => 'QF', 'QLK' => 'QF',
            'QTR' => 'QR', 'QXE' => 'QX', 'RAM' => 'AT', 'RBA' => 'BI', 'REU' => 'UU', 'RJA' => 'RJ', 'ROT' => 'RO', 'ROU' => 'RV',
            'RPA' => 'YX', 'RPB' => 'P5', 'RUK' => 'RK', 'RVF' => '7H', 'RWD' => 'WB', 'RXA' => 'ZL', 'RXI' => 'RX', 'RYR' => 'FR',
            'RYS' => 'RR', 'RZO' => 'S4', 'SAA' => 'SA', 'SAS' => 'SK', 'SAT' => 'SP', 'SBS' => 'BB', 'SBU' => 'PV', 'SCX' => 'SY',
            'SDR' => 'SR', 'SEH' => 'GQ', 'SEJ' => 'SG', 'SEY' => 'HM', 'SFJ' => '7G', 'SFR' => 'FA', 'SIA' => 'SQ', 'SIL' => '3M',
            'SJV' => 'IU', 'SJX' => 'JX', 'SKK' => 'KP', 'SKU' => 'H2', 'SKW' => 'OO', 'SKY' => 'BC', 'SLI' => '5D', 'SNJ' => '6J',
            'SPM' => 'PJ', 'SQC' => 'SQ', 'SRR' => 'DJ', 'SVA' => 'SV', 'SVS' => 'SK', 'SWA' => 'WN', 'SWN' => 'PT', 'SWR' => 'LX',
            'SWT' => 'WT', 'SWU' => 'BQ', 'SXS' => 'XQ', 'SZN' => 'HC', 'SZS' => 'SK', 'TAM' => 'JJ', 'TAP' => 'TP', 'TAR' => 'TU',
            'TAY' => '3V', 'TCV' => 'VR', 'TFL' => 'OR', 'TGW' => 'TR', 'TGZ' => 'A9', 'THA' => 'TG', 'THT' => 'TN', 'THY' => 'TK',
            'TJT' => 'T7', 'TKJ' => 'VF', 'TLM' => 'SL', 'TOM' => 'BY', 'TOS' => '9N', 'TPA' => 'QT', 'TRA' => 'HV', 'TSC' => 'TS',
            'TTW' => 'IT', 'TUI' => 'X3', 'TVF' => 'TO', 'TVJ' => 'VZ', 'TVS' => 'QS', 'TWB' => 'TW', 'TWI' => 'TI', 'TZP' => 'ZG',
            'UAE' => 'EK', 'UAL' => 'UA', 'UCA' => 'C5', 'UGD' => 'UR', 'UIA' => 'B7', 'UPS' => '5X', 'UTY' => 'QQ', 'UZB' => 'HY',
            'VIR' => 'VS', 'VIV' => 'VB', 'VJC' => 'VJ', 'VKG' => 'DK', 'VLG' => 'VY', 'VLH' => 'VL', 'VOE' => 'V7', 'VOI' => 'Y4',
            'VOZ' => 'VA', 'VTA' => 'VT', 'VTE' => 'LF', 'VTS' => '5V', 'VXP' => 'XP', 'WEN' => 'WR', 'WFL' => '2P', 'WGN' => 'KD',
            'WIA' => 'WM', 'WIF' => 'WF', 'WJA' => 'WS', 'WMT' => 'W4', 'WSN' => 'AN', 'WUK' => 'W9', 'WZZ' => 'W6', 'XAX' => 'D7',
            'YZR' => 'Y8',
            ];
        }

        public static function getCategoryStarterFleets(): array
        {
            return [
                'cargo' => [
                    ['aircraft' => 'Boeing 767-300F', 'icao' => 'B763', 'iata' => '76F', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 120000]]],
                    ['aircraft' => 'Boeing 777F', 'icao' => 'B77L', 'iata' => '77X', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 225000]]],
                    ['aircraft' => 'Boeing 747-400F', 'icao' => 'B744', 'iata' => '74Y', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 248000]]],
                    ['aircraft' => 'Airbus A300-600F', 'icao' => 'A306', 'iata' => 'ABY', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 106000]]],
                ],
                'private' => [
                    ['aircraft' => 'Pilatus PC-12 NGX', 'icao' => 'PC12', 'iata' => 'PL2', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 8]]],
                    ['aircraft' => 'Pilatus PC-24', 'icao' => 'PC24', 'iata' => 'P24', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 8]]],
                    ['aircraft' => 'Cessna Citation XLS+', 'icao' => 'C56X', 'iata' => 'C5X', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 9]]],
                    ['aircraft' => 'Bombardier Challenger 350', 'icao' => 'CL35', 'iata' => 'CL3', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 9]]],
                    ['aircraft' => 'Gulfstream G650', 'icao' => 'GLF6', 'iata' => 'G65', 'variant' => '', 'cabins' => [['cabin' => 'VIP', 'code' => 'F', 'seats' => 14]]],
                ],
                'commercial' => [
                    ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 138]]],
                    ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 144]]],
                    ['aircraft' => 'Embraer E175', 'icao' => 'E75L', 'iata' => 'E75', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 64]]],
                ],
            ];
        }

        public static function getOperatorDirectory(): array
        {
            $presets = self::getOperatorCatalog();
            $iataMap = self::getIcaoToIataMap();
            $directory = [];
            $seen = [];

            try {
                $rawGroups = RealWorldAirlines::all();
                if (is_array($rawGroups)) {
                    foreach ($rawGroups as $category => $airlines) {
                        if (!is_array($airlines)) {
                            continue;
                        }
                        $catLower = strtolower((string) $category);
                        if (str_contains($catLower, 'license')) {
                            continue;
                        }
                        $starterType = 'commercial';
                        if (str_contains($catLower, 'cargo') || str_contains($catLower, 'freighter')) {
                            $starterType = 'cargo';
                        } elseif (str_contains($catLower, 'private') || str_contains($catLower, 'vip') || str_contains($catLower, 'fractional')) {
                            $starterType = 'private';
                        }

                        foreach ($airlines as $label) {
                            $labelStr = trim((string) $label);
                            if (!preg_match('/^(.+?)\s*\(([A-Z0-9]{3})\)$/', $labelStr, $m)) {
                                continue;
                            }
                            $cleanName = trim($m[1]);
                            $icao = strtoupper(trim($m[2]));
                            $dirKey = $icao . '|' . $starterType;
                            if (isset($seen[$dirKey])) {
                                continue;
                            }
                            $seen[$dirKey] = true;

                            $hasPreset = isset($presets[$icao]) && $starterType !== 'cargo' || (isset($presets[$icao]) && in_array($icao, ['FDX', 'UPS', 'GTI', 'CLX', 'GEC', 'BCS', 'CKS', 'CJT', 'SRR'], true));
                            $iata = $presets[$icao]['iata'] ?? ($iataMap[$icao] ?? '');
                            if ($starterType === 'private' && !isset($iataMap[$icao])) {
                                $iata = '';
                            }

                            $directory[] = [
                                'key'          => $hasPreset ? $icao : ('DIR:' . $icao . ':' . $starterType),
                                'icao'         => $icao,
                                'iata'         => $iata,
                                'name'         => $hasPreset ? $presets[$icao]['name'] : $cleanName,
                                'label'        => $labelStr,
                                'category'     => (string) $category,
                                'starter_type' => $starterType,
                                'has_preset'   => $hasPreset,
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {}

            // Fallback to built-in catalog if RealWorldAirlines wasn't loaded (e.g. unlicensed DRM Client)
            if (empty($directory)) {
                foreach ($presets as $icao => $p) {
                    $directory[] = [
                        'key'          => $icao,
                        'icao'         => $p['icao'],
                        'iata'         => $p['iata'],
                        'name'         => $p['name'],
                        'label'        => $p['name'] . ' (' . $p['icao'] . ')',
                        'category'     => 'Built-In Real-World Presets',
                        'starter_type' => 'commercial',
                        'has_preset'   => true,
                    ];
                }
            }

            return $directory;
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
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '', 'cabins' => [['cabin' => 'Polaris', 'code' => 'J', 'seats' => 60], ['cabin' => 'Premium Plus', 'code' => 'P', 'seats' => 24], ['cabin' => 'Economy Plus', 'code' => 'W', 'seats' => 62], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 204]]],
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
                        ['aircraft' => 'Boeing 777-200ER', 'icao' => 'B772', 'iata' => '772', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 37], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 24], ['cabin' => 'Main Cabin Extra', 'code' => 'W', 'seats' => 66], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 146]]],
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '', 'cabins' => [['cabin' => 'Flagship First', 'code' => 'F', 'seats' => 8], ['cabin' => 'Business', 'code' => 'J', 'seats' => 52], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 28], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 216]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 30], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 21], ['cabin' => 'Main Cabin Extra', 'code' => 'W', 'seats' => 34], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 200]]],
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
                'ASA' => [
                    'icao' => 'ASA', 'iata' => 'AS', 'name' => 'Alaska',
                    'fleet' => [
                        ['aircraft' => 'Boeing 737-700', 'icao' => 'B737', 'iata' => '73G', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 12], ['cabin' => 'Premium', 'code' => 'W', 'seats' => 18], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 94]]],
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 12], ['cabin' => 'Premium', 'code' => 'W', 'seats' => 30], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 117]]],
                        ['aircraft' => 'Boeing 737-900ER', 'icao' => 'B739', 'iata' => '739', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 16], ['cabin' => 'Premium', 'code' => 'W', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 138]]],
                        ['aircraft' => 'Boeing 737 MAX 8', 'icao' => 'B38M', 'iata' => '7M8', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 12], ['cabin' => 'Premium', 'code' => 'W', 'seats' => 30], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 117]]],
                        ['aircraft' => 'Boeing 737 MAX 9', 'icao' => 'B39M', 'iata' => '7M9', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 16], ['cabin' => 'Premium', 'code' => 'W', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 138]]],
                    ],
                ],
                'JBU' => [
                    'icao' => 'JBU', 'iata' => 'B6', 'name' => 'JetBlue',
                    'fleet' => [
                        ['aircraft' => 'Airbus A220-300', 'icao' => 'BCS3', 'iata' => '223', 'variant' => '', 'cabins' => [['cabin' => 'Even More Space', 'code' => 'W', 'seats' => 30], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 110]]],
                        ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'Even More Space', 'code' => 'W', 'seats' => 42], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 120]]],
                        ['aircraft' => 'Airbus A321-200', 'icao' => 'A321', 'iata' => '321', 'variant' => 'CORE', 'cabins' => [['cabin' => 'Even More Space', 'code' => 'W', 'seats' => 42], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 158]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => 'MINT', 'cabins' => [['cabin' => 'Mint', 'code' => 'J', 'seats' => 16], ['cabin' => 'Even More Space', 'code' => 'W', 'seats' => 41], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 103]]],
                        ['aircraft' => 'Airbus A321LR', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => 'LR', 'cabins' => [['cabin' => 'Mint', 'code' => 'J', 'seats' => 24], ['cabin' => 'Even More Space', 'code' => 'W', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 90]]],
                    ],
                ],
                'AAY' => [
                    'icao' => 'AAY', 'iata' => 'G4', 'name' => 'Allegiant',
                    'fleet' => [
                        ['aircraft' => 'Airbus A319-100', 'icao' => 'A319', 'iata' => '319', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 156]]],
                        ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 186]]],
                        ['aircraft' => 'Boeing 737 MAX 8-200', 'icao' => 'B38M', 'iata' => '7M8', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 190]]],
                    ],
                ],
                'MXY' => [
                    'icao' => 'MXY', 'iata' => 'MX', 'name' => 'Breeze',
                    'fleet' => [
                        ['aircraft' => 'Airbus A220-300', 'icao' => 'BCS3', 'iata' => '223', 'variant' => '', 'cabins' => [['cabin' => 'Ascent', 'code' => 'F', 'seats' => 12], ['cabin' => 'Extra Legroom', 'code' => 'W', 'seats' => 45], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 80]]],
                        ['aircraft' => 'Embraer E190', 'icao' => 'E190', 'iata' => 'E90', 'variant' => '', 'cabins' => [['cabin' => 'Extra Legroom', 'code' => 'W', 'seats' => 48], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 60]]],
                    ],
                ],
                'FFT' => [
                    'icao' => 'FFT', 'iata' => 'F9', 'name' => 'Frontier',
                    'fleet' => [
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'UpFront Plus', 'code' => 'W', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 174]]],
                        ['aircraft' => 'Airbus A321-200', 'icao' => 'A321', 'iata' => '321', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 230]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'UpFront Plus', 'code' => 'W', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 228]]],
                    ],
                ],
                'HAL' => [
                    'icao' => 'HAL', 'iata' => 'HA', 'name' => 'Hawaiian',
                    'fleet' => [
                        ['aircraft' => 'Boeing 717-200', 'icao' => 'B712', 'iata' => '717', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 8], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 120]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 16], ['cabin' => 'Extra Comfort', 'code' => 'W', 'seats' => 44], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 129]]],
                        ['aircraft' => 'Airbus A330-200', 'icao' => 'A332', 'iata' => '332', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 18], ['cabin' => 'Extra Comfort', 'code' => 'W', 'seats' => 68], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 192]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Leihoku Suites', 'code' => 'J', 'seats' => 34], ['cabin' => 'Extra Comfort', 'code' => 'W', 'seats' => 79], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 187]]],
                    ],
                ],
                'SCX' => [
                    'icao' => 'SCX', 'iata' => 'SY', 'name' => 'Sun Country',
                    'fleet' => [
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'Best', 'code' => 'W', 'seats' => 30], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 156]]],
                        ['aircraft' => 'Boeing 737-900ER', 'icao' => 'B739', 'iata' => '739', 'variant' => '', 'cabins' => [['cabin' => 'Best', 'code' => 'W', 'seats' => 30], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 165]]],
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
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '', 'cabins' => [['cabin' => 'Signature', 'code' => 'J', 'seats' => 40], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 336]]],
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
                'WJA' => [
                    'icao' => 'WJA', 'iata' => 'WS', 'name' => 'WestJet',
                    'fleet' => [
                        ['aircraft' => 'Boeing 737-700', 'icao' => 'B737', 'iata' => '73G', 'variant' => '', 'cabins' => [['cabin' => 'Premium', 'code' => 'W', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 120]]],
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'Premium', 'code' => 'W', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 162]]],
                        ['aircraft' => 'Boeing 737 MAX 8', 'icao' => 'B38M', 'iata' => '7M8', 'variant' => '', 'cabins' => [['cabin' => 'Premium', 'code' => 'W', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 162]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 16], ['cabin' => 'Premium', 'code' => 'P', 'seats' => 28], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 276]]],
                    ],
                ],
                'POE' => [
                    'icao' => 'POE', 'iata' => 'PD', 'name' => 'Porter',
                    'fleet' => [
                        ['aircraft' => 'de Havilland Dash 8-400', 'icao' => 'DH8D', 'iata' => 'DH4', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 78]]],
                        ['aircraft' => 'Embraer E195-E2', 'icao' => 'E295', 'iata' => '295', 'variant' => '', 'cabins' => [['cabin' => 'PorterReserve', 'code' => 'W', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 116]]],
                    ],
                ],
                'SKW' => [
                    'icao' => 'SKW', 'iata' => 'OO', 'name' => 'SkyWest',
                    'fleet' => [
                        ['aircraft' => 'CRJ-200', 'icao' => 'CRJ2', 'iata' => 'CR2', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 50]]],
                        ['aircraft' => 'CRJ-700', 'icao' => 'CRJ7', 'iata' => 'CR7', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 9], ['cabin' => 'Extra Legroom', 'code' => 'W', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 45]]],
                        ['aircraft' => 'CRJ-900', 'icao' => 'CRJ9', 'iata' => 'CR9', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 12], ['cabin' => 'Extra Legroom', 'code' => 'W', 'seats' => 20], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 44]]],
                        ['aircraft' => 'Embraer E175', 'icao' => 'E75L', 'iata' => 'E75', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 12], ['cabin' => 'Extra Legroom', 'code' => 'W', 'seats' => 20], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 44]]],
                    ],
                ],
                'RPA' => [
                    'icao' => 'RPA', 'iata' => 'YX', 'name' => 'Republic',
                    'fleet' => [
                        ['aircraft' => 'Embraer E170', 'icao' => 'E170', 'iata' => 'E70', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 9], ['cabin' => 'Extra Legroom', 'code' => 'W', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 45]]],
                        ['aircraft' => 'Embraer E175', 'icao' => 'E75L', 'iata' => 'E75', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 12], ['cabin' => 'Extra Legroom', 'code' => 'W', 'seats' => 20], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 44]]],
                    ],
                ],
                'EDV' => [
                    'icao' => 'EDV', 'iata' => '9E', 'name' => 'Endeavor',
                    'fleet' => [
                        ['aircraft' => 'CRJ-700', 'icao' => 'CRJ7', 'iata' => 'CR7', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 9], ['cabin' => 'Comfort+', 'code' => 'W', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 44]]],
                        ['aircraft' => 'CRJ-900', 'icao' => 'CRJ9', 'iata' => 'CR9', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 12], ['cabin' => 'Comfort+', 'code' => 'W', 'seats' => 20], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 44]]],
                    ],
                ],
                'ENY' => [
                    'icao' => 'ENY', 'iata' => 'MQ', 'name' => 'Envoy',
                    'fleet' => [
                        ['aircraft' => 'Embraer E170', 'icao' => 'E170', 'iata' => 'E70', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 12], ['cabin' => 'Main Cabin Extra', 'code' => 'W', 'seats' => 20], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 33]]],
                        ['aircraft' => 'Embraer E175', 'icao' => 'E75L', 'iata' => 'E75', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 12], ['cabin' => 'Main Cabin Extra', 'code' => 'W', 'seats' => 20], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 44]]],
                    ],
                ],
                'BAW' => [
                    'icao' => 'BAW', 'iata' => 'BA', 'name' => 'British Airways',
                    'fleet' => [
                        ['aircraft' => 'Airbus A319-100', 'icao' => 'A319', 'iata' => '319', 'variant' => '', 'cabins' => [['cabin' => 'Club Europe', 'code' => 'J', 'seats' => 24], ['cabin' => 'Euro Traveller', 'code' => 'Y', 'seats' => 120]]],
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'Club Europe', 'code' => 'J', 'seats' => 32], ['cabin' => 'Euro Traveller', 'code' => 'Y', 'seats' => 148]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Club Europe', 'code' => 'J', 'seats' => 36], ['cabin' => 'Euro Traveller', 'code' => 'Y', 'seats' => 184]]],
                        ['aircraft' => 'Airbus A350-1000', 'icao' => 'A35K', 'iata' => '351', 'variant' => '', 'cabins' => [['cabin' => 'Club Suite', 'code' => 'J', 'seats' => 56], ['cabin' => 'World Traveller Plus', 'code' => 'P', 'seats' => 56], ['cabin' => 'World Traveller', 'code' => 'Y', 'seats' => 219]]],
                        ['aircraft' => 'Airbus A380-800', 'icao' => 'A388', 'iata' => '388', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 14], ['cabin' => 'Club World', 'code' => 'J', 'seats' => 97], ['cabin' => 'World Traveller Plus', 'code' => 'P', 'seats' => 55], ['cabin' => 'World Traveller', 'code' => 'Y', 'seats' => 303]]],
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 8], ['cabin' => 'Club Suite', 'code' => 'J', 'seats' => 76], ['cabin' => 'World Traveller Plus', 'code' => 'P', 'seats' => 40], ['cabin' => 'World Traveller', 'code' => 'Y', 'seats' => 132]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 8], ['cabin' => 'Club World', 'code' => 'J', 'seats' => 42], ['cabin' => 'World Traveller Plus', 'code' => 'P', 'seats' => 39], ['cabin' => 'World Traveller', 'code' => 'Y', 'seats' => 127]]],
                    ],
                ],
                'AFR' => [
                    'icao' => 'AFR', 'iata' => 'AF', 'name' => 'Air France',
                    'fleet' => [
                        ['aircraft' => 'Airbus A220-300', 'icao' => 'BCS3', 'iata' => '223', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 132]]],
                        ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 20], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 154]]],
                        ['aircraft' => 'Airbus A321-200', 'icao' => 'A321', 'iata' => '321', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 188]]],
                        ['aircraft' => 'Airbus A350-900', 'icao' => 'A359', 'iata' => '359', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 34], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 266]]],
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '4P', 'cabins' => [['cabin' => 'La Premiere', 'code' => 'F', 'seats' => 4], ['cabin' => 'Business', 'code' => 'J', 'seats' => 58], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 28], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 206]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 30], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 21], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 228]]],
                    ],
                ],
                'DLH' => [
                    'icao' => 'DLH', 'iata' => 'LH', 'name' => 'Lufthansa',
                    'fleet' => [
                        ['aircraft' => 'Airbus A319-100', 'icao' => 'A319', 'iata' => '319', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 114]]],
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 28], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 152]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 32], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 183]]],
                        ['aircraft' => 'Airbus A350-900', 'icao' => 'A359', 'iata' => '359', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 48], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 21], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 224]]],
                        ['aircraft' => 'Boeing 747-8I', 'icao' => 'B748', 'iata' => '74H', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 8], ['cabin' => 'Business', 'code' => 'J', 'seats' => 80], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 32], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 244]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 26], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 21], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 247]]],
                    ],
                ],
                'KLM' => [
                    'icao' => 'KLM', 'iata' => 'KL', 'name' => 'KLM',
                    'fleet' => [
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'Europe Business', 'code' => 'J', 'seats' => 20], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 166]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Europe Business', 'code' => 'J', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 203]]],
                        ['aircraft' => 'Airbus A330-300', 'icao' => 'A333', 'iata' => '333', 'variant' => '', 'cabins' => [['cabin' => 'World Business', 'code' => 'J', 'seats' => 30], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 262]]],
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '', 'cabins' => [['cabin' => 'World Business', 'code' => 'J', 'seats' => 35], ['cabin' => 'Premium Comfort', 'code' => 'P', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 322]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'World Business', 'code' => 'J', 'seats' => 30], ['cabin' => 'Premium Comfort', 'code' => 'P', 'seats' => 21], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 224]]],
                    ],
                ],
                'SWR' => [
                    'icao' => 'SWR', 'iata' => 'LX', 'name' => 'SWISS',
                    'fleet' => [
                        ['aircraft' => 'Airbus A220-300', 'icao' => 'BCS3', 'iata' => '223', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 20], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 125]]],
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 156]]],
                        ['aircraft' => 'Airbus A330-300', 'icao' => 'A333', 'iata' => '333', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 8], ['cabin' => 'Business', 'code' => 'J', 'seats' => 45], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 183]]],
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 8], ['cabin' => 'Business', 'code' => 'J', 'seats' => 62], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 226]]],
                    ],
                ],
                'IBE' => [
                    'icao' => 'IBE', 'iata' => 'IB', 'name' => 'Iberia',
                    'fleet' => [
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 170]]],
                        ['aircraft' => 'Airbus A321XLR', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => 'XLR', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 14], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 168]]],
                        ['aircraft' => 'Airbus A330-300', 'icao' => 'A333', 'iata' => '333', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 29], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 21], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 242]]],
                        ['aircraft' => 'Airbus A350-900', 'icao' => 'A359', 'iata' => '359', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 31], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 293]]],
                    ],
                ],
                'RYR' => [
                    'icao' => 'RYR', 'iata' => 'FR', 'name' => 'Ryanair',
                    'fleet' => [
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 189]]],
                        ['aircraft' => 'Boeing 737 MAX 8-200', 'icao' => 'B38M', 'iata' => '7M8', 'variant' => '8200', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 197]]],
                    ],
                ],
                'EZY' => [
                    'icao' => 'EZY', 'iata' => 'U2', 'name' => 'easyJet',
                    'fleet' => [
                        ['aircraft' => 'Airbus A319-100', 'icao' => 'A319', 'iata' => '319', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 156]]],
                        ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 186]]],
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 186]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 235]]],
                    ],
                ],
                'SAS' => [
                    'icao' => 'SAS', 'iata' => 'SK', 'name' => 'SAS',
                    'fleet' => [
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'SAS Plus', 'code' => 'W', 'seats' => 30], ['cabin' => 'SAS Go', 'code' => 'Y', 'seats' => 150]]],
                        ['aircraft' => 'Airbus A321LR', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => 'LR', 'cabins' => [['cabin' => 'SAS Business', 'code' => 'J', 'seats' => 22], ['cabin' => 'SAS Plus', 'code' => 'P', 'seats' => 12], ['cabin' => 'SAS Go', 'code' => 'Y', 'seats' => 123]]],
                        ['aircraft' => 'Airbus A330-300', 'icao' => 'A333', 'iata' => '333', 'variant' => '', 'cabins' => [['cabin' => 'SAS Business', 'code' => 'J', 'seats' => 32], ['cabin' => 'SAS Plus', 'code' => 'P', 'seats' => 56], ['cabin' => 'SAS Go', 'code' => 'Y', 'seats' => 178]]],
                        ['aircraft' => 'Airbus A350-900', 'icao' => 'A359', 'iata' => '359', 'variant' => '', 'cabins' => [['cabin' => 'SAS Business', 'code' => 'J', 'seats' => 40], ['cabin' => 'SAS Plus', 'code' => 'P', 'seats' => 32], ['cabin' => 'SAS Go', 'code' => 'Y', 'seats' => 228]]],
                    ],
                ],
                'FIN' => [
                    'icao' => 'FIN', 'iata' => 'AY', 'name' => 'Finnair',
                    'fleet' => [
                        ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 158]]],
                        ['aircraft' => 'Airbus A321-200', 'icao' => 'A321', 'iata' => '321', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 20], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 189]]],
                        ['aircraft' => 'Airbus A330-300', 'icao' => 'A333', 'iata' => '333', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 28], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 21], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 230]]],
                        ['aircraft' => 'Airbus A350-900', 'icao' => 'A359', 'iata' => '359', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 43], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 211]]],
                    ],
                ],
                'THY' => [
                    'icao' => 'THY', 'iata' => 'TK', 'name' => 'Turkish Airlines',
                    'fleet' => [
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 20], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 162]]],
                        ['aircraft' => 'Boeing 737 MAX 8', 'icao' => 'B38M', 'iata' => '7M8', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 135]]],
                        ['aircraft' => 'Airbus A350-900', 'icao' => 'A359', 'iata' => '359', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 32], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 297]]],
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 49], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 300]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 30], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 270]]],
                    ],
                ],
                'UAE' => [
                    'icao' => 'UAE', 'iata' => 'EK', 'name' => 'Emirates',
                    'fleet' => [
                        ['aircraft' => 'Airbus A350-900', 'icao' => 'A359', 'iata' => '359', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 32], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 21], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 259]]],
                        ['aircraft' => 'Airbus A380-800', 'icao' => 'A388', 'iata' => '388', 'variant' => '4C', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 14], ['cabin' => 'Business', 'code' => 'J', 'seats' => 76], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 56], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 338]]],
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '4C', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 8], ['cabin' => 'Business', 'code' => 'J', 'seats' => 40], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 256]]],
                    ],
                ],
                'QTR' => [
                    'icao' => 'QTR', 'iata' => 'QR', 'name' => 'Qatar Airways',
                    'fleet' => [
                        ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 132]]],
                        ['aircraft' => 'Airbus A350-900', 'icao' => 'A359', 'iata' => '359', 'variant' => '', 'cabins' => [['cabin' => 'Qsuite', 'code' => 'J', 'seats' => 36], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 247]]],
                        ['aircraft' => 'Airbus A350-1000', 'icao' => 'A35K', 'iata' => '351', 'variant' => '', 'cabins' => [['cabin' => 'Qsuite', 'code' => 'J', 'seats' => 46], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 281]]],
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '', 'cabins' => [['cabin' => 'Qsuite', 'code' => 'J', 'seats' => 42], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 312]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 30], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 281]]],
                    ],
                ],
                'ETD' => [
                    'icao' => 'ETD', 'iata' => 'EY', 'name' => 'Etihad',
                    'fleet' => [
                        ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 8], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 150]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 180]]],
                        ['aircraft' => 'Airbus A350-1000', 'icao' => 'A35K', 'iata' => '351', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 44], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 327]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 28], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 262]]],
                    ],
                ],
                'SIA' => [
                    'icao' => 'SIA', 'iata' => 'SQ', 'name' => 'Singapore Airlines',
                    'fleet' => [
                        ['aircraft' => 'Boeing 737 MAX 8', 'icao' => 'B38M', 'iata' => '7M8', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 10], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 144]]],
                        ['aircraft' => 'Airbus A350-900', 'icao' => 'A359', 'iata' => '359', 'variant' => 'LH', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 42], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 187]]],
                        ['aircraft' => 'Airbus A350-900ULR', 'icao' => 'A359', 'iata' => '359', 'variant' => 'ULR', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 67], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 94]]],
                        ['aircraft' => 'Airbus A380-800', 'icao' => 'A388', 'iata' => '388', 'variant' => '', 'cabins' => [['cabin' => 'Suites', 'code' => 'F', 'seats' => 6], ['cabin' => 'Business', 'code' => 'J', 'seats' => 78], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 44], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 343]]],
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 4], ['cabin' => 'Business', 'code' => 'J', 'seats' => 48], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 28], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 184]]],
                        ['aircraft' => 'Boeing 787-10', 'icao' => 'B78X', 'iata' => '781', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 36], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 301]]],
                    ],
                ],
                'CPA' => [
                    'icao' => 'CPA', 'iata' => 'CX', 'name' => 'Cathay Pacific',
                    'fleet' => [
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 190]]],
                        ['aircraft' => 'Airbus A330-300', 'icao' => 'A333', 'iata' => '333', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 39], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 21], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 191]]],
                        ['aircraft' => 'Airbus A350-900', 'icao' => 'A359', 'iata' => '359', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 38], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 28], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 214]]],
                        ['aircraft' => 'Airbus A350-1000', 'icao' => 'A35K', 'iata' => '351', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 46], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 32], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 256]]],
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 6], ['cabin' => 'Business', 'code' => 'J', 'seats' => 53], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 34], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 201]]],
                    ],
                ],
                'JAL' => [
                    'icao' => 'JAL', 'iata' => 'JL', 'name' => 'Japan Airlines',
                    'fleet' => [
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'Class J', 'code' => 'J', 'seats' => 20], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 145]]],
                        ['aircraft' => 'Airbus A350-900', 'icao' => 'A359', 'iata' => '359', 'variant' => 'DOM', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 12], ['cabin' => 'Class J', 'code' => 'J', 'seats' => 94], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 263]]],
                        ['aircraft' => 'Airbus A350-1000', 'icao' => 'A35K', 'iata' => '351', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 6], ['cabin' => 'Business', 'code' => 'J', 'seats' => 54], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 155]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 52], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 35], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 116]]],
                    ],
                ],
                'ANA' => [
                    'icao' => 'ANA', 'iata' => 'NH', 'name' => 'ANA',
                    'fleet' => [
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Premium', 'code' => 'J', 'seats' => 8], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 186]]],
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'Premium', 'code' => 'J', 'seats' => 8], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 158]]],
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 8], ['cabin' => 'Business', 'code' => 'J', 'seats' => 64], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 116]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 48], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 21], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 146]]],
                    ],
                ],
                'QFA' => [
                    'icao' => 'QFA', 'iata' => 'QF', 'name' => 'Qantas',
                    'fleet' => [
                        ['aircraft' => 'Airbus A220-300', 'icao' => 'BCS3', 'iata' => '223', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 10], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 127]]],
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 162]]],
                        ['aircraft' => 'Airbus A330-300', 'icao' => 'A333', 'iata' => '333', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 28], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 269]]],
                        ['aircraft' => 'Airbus A380-800', 'icao' => 'A388', 'iata' => '388', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 14], ['cabin' => 'Business', 'code' => 'J', 'seats' => 70], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 60], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 341]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 42], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 28], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 166]]],
                    ],
                ],
                'KAL' => [
                    'icao' => 'KAL', 'iata' => 'KE', 'name' => 'Korean Air',
                    'fleet' => [
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Prestige', 'code' => 'J', 'seats' => 8], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 174]]],
                        ['aircraft' => 'Boeing 737 MAX 8', 'icao' => 'B38M', 'iata' => '7M8', 'variant' => '', 'cabins' => [['cabin' => 'Prestige', 'code' => 'J', 'seats' => 8], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 138]]],
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 8], ['cabin' => 'Prestige', 'code' => 'J', 'seats' => 42], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 227]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Prestige', 'code' => 'J', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 254]]],
                    ],
                ],
                'AMX' => [
                    'icao' => 'AMX', 'iata' => 'AM', 'name' => 'Aeromexico',
                    'fleet' => [
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'Clase Premier', 'code' => 'J', 'seats' => 16], ['cabin' => 'AM Plus', 'code' => 'W', 'seats' => 18], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 126]]],
                        ['aircraft' => 'Boeing 737 MAX 8', 'icao' => 'B38M', 'iata' => '7M8', 'variant' => '', 'cabins' => [['cabin' => 'Clase Premier', 'code' => 'J', 'seats' => 16], ['cabin' => 'AM Plus', 'code' => 'W', 'seats' => 18], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 132]]],
                        ['aircraft' => 'Boeing 737 MAX 9', 'icao' => 'B39M', 'iata' => '7M9', 'variant' => '', 'cabins' => [['cabin' => 'Clase Premier', 'code' => 'J', 'seats' => 16], ['cabin' => 'AM Plus', 'code' => 'W', 'seats' => 18], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 147]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Clase Premier', 'code' => 'J', 'seats' => 36], ['cabin' => 'AM Plus', 'code' => 'W', 'seats' => 27], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 211]]],
                    ],
                ],
                'CMP' => [
                    'icao' => 'CMP', 'iata' => 'CM', 'name' => 'Copa Airlines',
                    'fleet' => [
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 144]]],
                        ['aircraft' => 'Boeing 737 MAX 9', 'icao' => 'B39M', 'iata' => '7M9', 'variant' => '', 'cabins' => [['cabin' => 'Dreams Business', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy Extra', 'code' => 'W', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 126]]],
                    ],
                ],
                'FDX' => [
                    'icao' => 'FDX', 'iata' => 'FX', 'name' => 'FedEx Express',
                    'fleet' => [
                        ['aircraft' => 'Cessna 208B Super Cargomaster', 'icao' => 'C208', 'iata' => 'CN1', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 4000]]],
                        ['aircraft' => 'ATR 72-600F', 'icao' => 'AT76', 'iata' => 'ATY', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 19000]]],
                        ['aircraft' => 'Boeing 757-200SF', 'icao' => 'B752', 'iata' => '75F', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 65000]]],
                        ['aircraft' => 'Boeing 767-300F', 'icao' => 'B763', 'iata' => '76F', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 116000]]],
                        ['aircraft' => 'Airbus A300-600F', 'icao' => 'A306', 'iata' => 'ABY', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 106000]]],
                        ['aircraft' => 'McDonnell Douglas MD-11F', 'icao' => 'MD11', 'iata' => 'M1F', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 192000]]],
                        ['aircraft' => 'Boeing 777F', 'icao' => 'B77L', 'iata' => '77X', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 225000]]],
                    ],
                ],
                'UPS' => [
                    'icao' => 'UPS', 'iata' => '5X', 'name' => 'UPS Airlines',
                    'fleet' => [
                        ['aircraft' => 'Airbus A300-600F', 'icao' => 'A306', 'iata' => 'ABY', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 106000]]],
                        ['aircraft' => 'Boeing 757-200PF', 'icao' => 'B752', 'iata' => '75F', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 87000]]],
                        ['aircraft' => 'Boeing 767-300F', 'icao' => 'B763', 'iata' => '76F', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 132000]]],
                        ['aircraft' => 'McDonnell Douglas MD-11F', 'icao' => 'MD11', 'iata' => 'M1F', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 190000]]],
                        ['aircraft' => 'Boeing 747-400F', 'icao' => 'B744', 'iata' => '74Y', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 248000]]],
                        ['aircraft' => 'Boeing 747-8F', 'icao' => 'B748', 'iata' => '74N', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 303000]]],
                    ],
                ],
                'GTI' => [
                    'icao' => 'GTI', 'iata' => '5Y', 'name' => 'Atlas Air',
                    'fleet' => [
                        ['aircraft' => 'Boeing 767-300F', 'icao' => 'B763', 'iata' => '76F', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 120000]]],
                        ['aircraft' => 'Boeing 777F', 'icao' => 'B77L', 'iata' => '77X', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 225000]]],
                        ['aircraft' => 'Boeing 747-400F', 'icao' => 'B744', 'iata' => '74Y', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 248000]]],
                        ['aircraft' => 'Boeing 747-8F', 'icao' => 'B748', 'iata' => '74N', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 303000]]],
                    ],
                ],
                'EJA' => [
                    'icao' => 'EJA', 'iata' => '', 'name' => 'NetJets',
                    'fleet' => [
                        ['aircraft' => 'Embraer Phenom 300', 'icao' => 'E55P', 'iata' => 'EP3', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 7]]],
                        ['aircraft' => 'Cessna Citation Latitude', 'icao' => 'C68A', 'iata' => 'CLD', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 9]]],
                        ['aircraft' => 'Cessna Citation Longitude', 'icao' => 'C700', 'iata' => 'CLG', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 8]]],
                        ['aircraft' => 'Bombardier Challenger 350', 'icao' => 'CL35', 'iata' => 'CL3', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 9]]],
                        ['aircraft' => 'Bombardier Challenger 650', 'icao' => 'CL60', 'iata' => 'CL6', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 11]]],
                        ['aircraft' => 'Bombardier Global 6000', 'icao' => 'GLEX', 'iata' => 'G60', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 13]]],
                    ],
                ],
                'VJT' => [
                    'icao' => 'VJT', 'iata' => '', 'name' => 'VistaJet',
                    'fleet' => [
                        ['aircraft' => 'Bombardier Challenger 350', 'icao' => 'CL35', 'iata' => 'CL3', 'variant' => '', 'cabins' => [['cabin' => 'VIP', 'code' => 'F', 'seats' => 8]]],
                        ['aircraft' => 'Bombardier Challenger 605', 'icao' => 'CL60', 'iata' => 'CL6', 'variant' => '', 'cabins' => [['cabin' => 'VIP', 'code' => 'F', 'seats' => 12]]],
                        ['aircraft' => 'Bombardier Global 6000', 'icao' => 'GLEX', 'iata' => 'G60', 'variant' => '', 'cabins' => [['cabin' => 'VIP', 'code' => 'F', 'seats' => 14]]],
                        ['aircraft' => 'Bombardier Global 7500', 'icao' => 'GL7T', 'iata' => 'G75', 'variant' => '', 'cabins' => [['cabin' => 'VIP', 'code' => 'F', 'seats' => 14]]],
                    ],
                ],
                'LXJ' => [
                    'icao' => 'LXJ', 'iata' => '', 'name' => 'Flexjet',
                    'fleet' => [
                        ['aircraft' => 'Embraer Phenom 300', 'icao' => 'E55P', 'iata' => 'EP3', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 7]]],
                        ['aircraft' => 'Embraer Praetor 500', 'icao' => 'E545', 'iata' => 'E54', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 8]]],
                        ['aircraft' => 'Embraer Praetor 600', 'icao' => 'E550', 'iata' => 'E55', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 9]]],
                        ['aircraft' => 'Bombardier Challenger 350', 'icao' => 'CL35', 'iata' => 'CL3', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 9]]],
                        ['aircraft' => 'Gulfstream G650', 'icao' => 'GLF6', 'iata' => 'G65', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 15]]],
                    ],
                ],
                'FCB' => [
                    'icao' => 'FCB', 'iata' => '', 'name' => 'Cobalt Air',
                    'fleet' => [
                        ['aircraft' => 'Pilatus PC-12 NGX', 'icao' => 'PC12', 'iata' => 'PL2', 'variant' => 'NGX', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 8]]],
                        ['aircraft' => 'Pilatus PC-24', 'icao' => 'PC24', 'iata' => 'P24', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 8]]],
                    ],
                ],
                'JSX' => [
                    'icao' => 'JSX', 'iata' => 'XE', 'name' => 'JSX',
                    'fleet' => [
                        ['aircraft' => 'Embraer ERJ-135', 'icao' => 'E135', 'iata' => 'ER3', 'variant' => '', 'cabins' => [['cabin' => 'Semi-Private', 'code' => 'J', 'seats' => 30]]],
                        ['aircraft' => 'Embraer ERJ-145', 'icao' => 'E145', 'iata' => 'ER4', 'variant' => '', 'cabins' => [['cabin' => 'Semi-Private', 'code' => 'J', 'seats' => 30]]],
                        ['aircraft' => 'ATR 42-600', 'icao' => 'AT46', 'iata' => 'AT4', 'variant' => '', 'cabins' => [['cabin' => 'Semi-Private', 'code' => 'J', 'seats' => 30]]],
                    ],
                ],
                'VIR' => [
                    'icao' => 'VIR', 'iata' => 'VS', 'name' => 'Virgin Atlantic',
                    'fleet' => [
                        ['aircraft' => 'Airbus A330-300', 'icao' => 'A333', 'iata' => '333', 'variant' => '', 'cabins' => [['cabin' => 'Upper Class', 'code' => 'J', 'seats' => 31], ['cabin' => 'Premium', 'code' => 'W', 'seats' => 48], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 185]]],
                        ['aircraft' => 'Airbus A330-900neo', 'icao' => 'A339', 'iata' => '339', 'variant' => '32J', 'cabins' => [['cabin' => 'Upper Class', 'code' => 'J', 'seats' => 32], ['cabin' => 'Premium', 'code' => 'W', 'seats' => 46], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 184]]],
                        ['aircraft' => 'Airbus A330-900neo', 'icao' => 'A339', 'iata' => '339', 'variant' => '48J', 'cabins' => [['cabin' => 'Upper Class', 'code' => 'J', 'seats' => 48], ['cabin' => 'Premium', 'code' => 'W', 'seats' => 56], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 128]]],
                        ['aircraft' => 'Airbus A350-1000', 'icao' => 'A35K', 'iata' => '351', 'variant' => '44J', 'cabins' => [['cabin' => 'Upper Class', 'code' => 'J', 'seats' => 44], ['cabin' => 'Premium', 'code' => 'W', 'seats' => 56], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 235]]],
                        ['aircraft' => 'Airbus A350-1000', 'icao' => 'A35K', 'iata' => '351', 'variant' => '16J', 'cabins' => [['cabin' => 'Upper Class', 'code' => 'J', 'seats' => 16], ['cabin' => 'Premium', 'code' => 'W', 'seats' => 56], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 325]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Upper Class', 'code' => 'J', 'seats' => 31], ['cabin' => 'Premium', 'code' => 'W', 'seats' => 35], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 192]]],
                    ],
                ],
                'EIN' => [
                    'icao' => 'EIN', 'iata' => 'EI', 'name' => 'Aer Lingus',
                    'fleet' => [
                        ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 174]]],
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 186]]],
                        ['aircraft' => 'Airbus A321LR', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => 'LR', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 168]]],
                        ['aircraft' => 'Airbus A321XLR', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => 'XLR', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 168]]],
                        ['aircraft' => 'Airbus A330-200', 'icao' => 'A332', 'iata' => '332', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 23], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 243]]],
                        ['aircraft' => 'Airbus A330-300', 'icao' => 'A333', 'iata' => '333', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 30], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 287]]],
                    ],
                ],
                'TAP' => [
                    'icao' => 'TAP', 'iata' => 'TP', 'name' => 'TAP Air Portugal',
                    'fleet' => [
                        ['aircraft' => 'Airbus A319-100', 'icao' => 'A319', 'iata' => '319', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 132]]],
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 158]]],
                        ['aircraft' => 'Airbus A321LR', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => 'LR', 'cabins' => [['cabin' => 'Executive', 'code' => 'J', 'seats' => 16], ['cabin' => 'EconomyXtra', 'code' => 'W', 'seats' => 42], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 113]]],
                        ['aircraft' => 'Airbus A330-200', 'icao' => 'A332', 'iata' => '332', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'J', 'seats' => 25], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 244]]],
                        ['aircraft' => 'Airbus A330-900neo', 'icao' => 'A339', 'iata' => '339', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'J', 'seats' => 34], ['cabin' => 'EconomyXtra', 'code' => 'W', 'seats' => 96], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 168]]],
                    ],
                ],
                'ITY' => [
                    'icao' => 'ITY', 'iata' => 'AZ', 'name' => 'ITA Airways',
                    'fleet' => [
                        ['aircraft' => 'Airbus A220-100', 'icao' => 'BCS1', 'iata' => '221', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 113]]],
                        ['aircraft' => 'Airbus A220-300', 'icao' => 'BCS3', 'iata' => '223', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 132]]],
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 164]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 141]]],
                        ['aircraft' => 'Airbus A330-900neo', 'icao' => 'A339', 'iata' => '339', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 30], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 237]]],
                        ['aircraft' => 'Airbus A350-900', 'icao' => 'A359', 'iata' => '359', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 33], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 262]]],
                    ],
                ],
                'AUA' => [
                    'icao' => 'AUA', 'iata' => 'OS', 'name' => 'Austrian Airlines',
                    'fleet' => [
                        ['aircraft' => 'Embraer E195', 'icao' => 'E195', 'iata' => 'E95', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 108]]],
                        ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 20], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 154]]],
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 20], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 160]]],
                        ['aircraft' => 'Boeing 777-200ER', 'icao' => 'B772', 'iata' => '772', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 32], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 40], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 258]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 26], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 21], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 247]]],
                    ],
                ],
                'BEL' => [
                    'icao' => 'BEL', 'iata' => 'SN', 'name' => 'Brussels Airlines',
                    'fleet' => [
                        ['aircraft' => 'Airbus A319-100', 'icao' => 'A319', 'iata' => '319', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 125]]],
                        ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 20], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 160]]],
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 20], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 160]]],
                        ['aircraft' => 'Airbus A330-300', 'icao' => 'A333', 'iata' => '333', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 30], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 21], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 244]]],
                    ],
                ],
                'LOT' => [
                    'icao' => 'LOT', 'iata' => 'LO', 'name' => 'LOT Polish Airlines',
                    'fleet' => [
                        ['aircraft' => 'Embraer E175', 'icao' => 'E175', 'iata' => 'E75', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 8], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 74]]],
                        ['aircraft' => 'Embraer E195', 'icao' => 'E195', 'iata' => 'E95', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 106]]],
                        ['aircraft' => 'Embraer E195-E2', 'icao' => 'E295', 'iata' => '295', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 124]]],
                        ['aircraft' => 'Boeing 737 MAX 8', 'icao' => 'B38M', 'iata' => '7M8', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 174]]],
                        ['aircraft' => 'Boeing 787-8', 'icao' => 'B788', 'iata' => '788', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 18], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 21], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 213]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 24], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 21], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 249]]],
                    ],
                ],
                'ICE' => [
                    'icao' => 'ICE', 'iata' => 'FI', 'name' => 'Icelandair',
                    'fleet' => [
                        ['aircraft' => 'de Havilland Dash 8-400', 'icao' => 'DH8D', 'iata' => 'DH4', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 76]]],
                        ['aircraft' => 'Boeing 737 MAX 8', 'icao' => 'B38M', 'iata' => '7M8', 'variant' => '', 'cabins' => [['cabin' => 'Saga Premium', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 144]]],
                        ['aircraft' => 'Boeing 737 MAX 9', 'icao' => 'B39M', 'iata' => '7M9', 'variant' => '', 'cabins' => [['cabin' => 'Saga Premium', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 162]]],
                        ['aircraft' => 'Airbus A321LR', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => 'LR', 'cabins' => [['cabin' => 'Saga Premium', 'code' => 'J', 'seats' => 22], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 165]]],
                        ['aircraft' => 'Boeing 767-300ER', 'icao' => 'B763', 'iata' => '763', 'variant' => '', 'cabins' => [['cabin' => 'Saga Premium', 'code' => 'J', 'seats' => 25], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 237]]],
                    ],
                ],
                'CFG' => [
                    'icao' => 'CFG', 'iata' => 'DE', 'name' => 'Condor',
                    'fleet' => [
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 156]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 209]]],
                        ['aircraft' => 'Airbus A330-900neo', 'icao' => 'A339', 'iata' => '339', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 30], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 64], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 216]]],
                    ],
                ],
                'WZZ' => [
                    'icao' => 'WZZ', 'iata' => 'W6', 'name' => 'Wizz Air',
                    'fleet' => [
                        ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 180]]],
                        ['aircraft' => 'Airbus A321-200', 'icao' => 'A321', 'iata' => '321', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 230]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 239]]],
                        ['aircraft' => 'Airbus A321XLR', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => 'XLR', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 239]]],
                    ],
                ],
                'VLG' => [
                    'icao' => 'VLG', 'iata' => 'VY', 'name' => 'Vueling',
                    'fleet' => [
                        ['aircraft' => 'Airbus A319-100', 'icao' => 'A319', 'iata' => '319', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 144]]],
                        ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 180]]],
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 186]]],
                        ['aircraft' => 'Airbus A321-200', 'icao' => 'A321', 'iata' => '321', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 220]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 236]]],
                    ],
                ],
                'BTI' => [
                    'icao' => 'BTI', 'iata' => 'BT', 'name' => 'airBaltic',
                    'fleet' => [
                        ['aircraft' => 'Airbus A220-300', 'icao' => 'BCS3', 'iata' => '223', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 136]]],
                    ],
                ],
                'QXE' => [
                    'icao' => 'QXE', 'iata' => 'QX', 'name' => 'Horizon Air',
                    'fleet' => [
                        ['aircraft' => 'Embraer E175', 'icao' => 'E75L', 'iata' => 'E75', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 12], ['cabin' => 'Premium', 'code' => 'W', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 48]]],
                    ],
                ],
                'JIA' => [
                    'icao' => 'JIA', 'iata' => 'OH', 'name' => 'PSA Airlines',
                    'fleet' => [
                        ['aircraft' => 'CRJ-700', 'icao' => 'CRJ7', 'iata' => 'CR7', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 9], ['cabin' => 'Main Cabin Extra', 'code' => 'W', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 40]]],
                        ['aircraft' => 'CRJ-900', 'icao' => 'CRJ9', 'iata' => 'CR9', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 12], ['cabin' => 'Main Cabin Extra', 'code' => 'W', 'seats' => 20], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 44]]],
                    ],
                ],
                'PDT' => [
                    'icao' => 'PDT', 'iata' => 'PT', 'name' => 'Piedmont Airlines',
                    'fleet' => [
                        ['aircraft' => 'Embraer ERJ-145', 'icao' => 'E145', 'iata' => 'ER4', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 50]]],
                    ],
                ],
                'VXP' => [
                    'icao' => 'VXP', 'iata' => 'XP', 'name' => 'Avelo Airlines',
                    'fleet' => [
                        ['aircraft' => 'Boeing 737-700', 'icao' => 'B737', 'iata' => '73G', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 147]]],
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 189]]],
                    ],
                ],
                'BMA' => [
                    'icao' => 'BMA', 'iata' => '2T', 'name' => 'BermudAir',
                    'fleet' => [
                        ['aircraft' => 'Embraer E175', 'icao' => 'E175', 'iata' => 'E75', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 14], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 56]]],
                        ['aircraft' => 'Embraer E190', 'icao' => 'E190', 'iata' => 'E90', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 14], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 82]]],
                    ],
                ],
                'TSC' => [
                    'icao' => 'TSC', 'iata' => 'TS', 'name' => 'Air Transat',
                    'fleet' => [
                        ['aircraft' => 'Airbus A321LR', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => 'LR', 'cabins' => [['cabin' => 'Club Class', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 187]]],
                        ['aircraft' => 'Airbus A330-200', 'icao' => 'A332', 'iata' => '332', 'variant' => '', 'cabins' => [['cabin' => 'Club Class', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 320]]],
                        ['aircraft' => 'Airbus A330-300', 'icao' => 'A333', 'iata' => '333', 'variant' => '', 'cabins' => [['cabin' => 'Club Class', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 334]]],
                    ],
                ],
                'FLE' => [
                    'icao' => 'FLE', 'iata' => 'F8', 'name' => 'Flair Airlines',
                    'fleet' => [
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 189]]],
                        ['aircraft' => 'Boeing 737 MAX 8', 'icao' => 'B38M', 'iata' => '7M8', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 189]]],
                    ],
                ],
                'VOI' => [
                    'icao' => 'VOI', 'iata' => 'Y4', 'name' => 'Volaris',
                    'fleet' => [
                        ['aircraft' => 'Airbus A319-100', 'icao' => 'A319', 'iata' => '319', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 144]]],
                        ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 179]]],
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 186]]],
                        ['aircraft' => 'Airbus A321-200', 'icao' => 'A321', 'iata' => '321', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 220]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 239]]],
                    ],
                ],
                'VIV' => [
                    'icao' => 'VIV', 'iata' => 'VB', 'name' => 'VivaAerobus',
                    'fleet' => [
                        ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 186]]],
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 186]]],
                        ['aircraft' => 'Airbus A321-200', 'icao' => 'A321', 'iata' => '321', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 240]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 240]]],
                    ],
                ],
                'RXI' => [
                    'icao' => 'RXI', 'iata' => 'RX', 'name' => 'Riyadh Air',
                    'fleet' => [
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Business Elite', 'code' => 'F', 'seats' => 4], ['cabin' => 'Business', 'code' => 'J', 'seats' => 24], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 39], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 223]]],
                    ],
                ],
                'SVA' => [
                    'icao' => 'SVA', 'iata' => 'SV', 'name' => 'Saudia',
                    'fleet' => [
                        ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Guest', 'code' => 'Y', 'seats' => 132]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 20], ['cabin' => 'Guest', 'code' => 'Y', 'seats' => 168]]],
                        ['aircraft' => 'Airbus A330-300', 'icao' => 'A333', 'iata' => '333', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 36], ['cabin' => 'Guest', 'code' => 'Y', 'seats' => 262]]],
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 12], ['cabin' => 'Business', 'code' => 'J', 'seats' => 36], ['cabin' => 'Guest', 'code' => 'Y', 'seats' => 242]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 24], ['cabin' => 'Guest', 'code' => 'Y', 'seats' => 274]]],
                        ['aircraft' => 'Boeing 787-10', 'icao' => 'B78X', 'iata' => '781', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 24], ['cabin' => 'Guest', 'code' => 'Y', 'seats' => 333]]],
                    ],
                ],
                'FDB' => [
                    'icao' => 'FDB', 'iata' => 'FZ', 'name' => 'flydubai',
                    'fleet' => [
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 162]]],
                        ['aircraft' => 'Boeing 737 MAX 8', 'icao' => 'B38M', 'iata' => '7M8', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 10], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 156]]],
                        ['aircraft' => 'Boeing 737 MAX 9', 'icao' => 'B39M', 'iata' => '7M9', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 156]]],
                    ],
                ],
                'ETH' => [
                    'icao' => 'ETH', 'iata' => 'ET', 'name' => 'Ethiopian Airlines',
                    'fleet' => [
                        ['aircraft' => 'de Havilland Dash 8-400', 'icao' => 'DH8D', 'iata' => 'DH4', 'variant' => '', 'cabins' => [['cabin' => 'Cloud Nine', 'code' => 'J', 'seats' => 7], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 64]]],
                        ['aircraft' => 'Boeing 737 MAX 8', 'icao' => 'B38M', 'iata' => '7M8', 'variant' => '', 'cabins' => [['cabin' => 'Cloud Nine', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 144]]],
                        ['aircraft' => 'Airbus A350-900', 'icao' => 'A359', 'iata' => '359', 'variant' => '', 'cabins' => [['cabin' => 'Cloud Nine', 'code' => 'J', 'seats' => 30], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 318]]],
                        ['aircraft' => 'Airbus A350-1000', 'icao' => 'A35K', 'iata' => '351', 'variant' => '', 'cabins' => [['cabin' => 'Cloud Nine', 'code' => 'J', 'seats' => 46], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 349]]],
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '', 'cabins' => [['cabin' => 'Cloud Nine', 'code' => 'J', 'seats' => 34], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 365]]],
                        ['aircraft' => 'Boeing 787-8', 'icao' => 'B788', 'iata' => '788', 'variant' => '', 'cabins' => [['cabin' => 'Cloud Nine', 'code' => 'J', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 246]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Cloud Nine', 'code' => 'J', 'seats' => 30], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 285]]],
                    ],
                ],
                'RAM' => [
                    'icao' => 'RAM', 'iata' => 'AT', 'name' => 'Royal Air Maroc',
                    'fleet' => [
                        ['aircraft' => 'ATR 72-600', 'icao' => 'AT76', 'iata' => 'AT7', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 58]]],
                        ['aircraft' => 'Embraer E190', 'icao' => 'E190', 'iata' => 'E90', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 84]]],
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 147]]],
                        ['aircraft' => 'Boeing 737 MAX 8', 'icao' => 'B38M', 'iata' => '7M8', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 144]]],
                        ['aircraft' => 'Boeing 787-8', 'icao' => 'B788', 'iata' => '788', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 18], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 256]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 26], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 276]]],
                    ],
                ],
                'ELY' => [
                    'icao' => 'ELY', 'iata' => 'LY', 'name' => 'El Al',
                    'fleet' => [
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 150]]],
                        ['aircraft' => 'Boeing 737-900ER', 'icao' => 'B739', 'iata' => '739', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 159]]],
                        ['aircraft' => 'Boeing 777-200ER', 'icao' => 'B772', 'iata' => '772', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 28], ['cabin' => 'Premium', 'code' => 'P', 'seats' => 32], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 253]]],
                        ['aircraft' => 'Boeing 787-8', 'icao' => 'B788', 'iata' => '788', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 20], ['cabin' => 'Premium', 'code' => 'P', 'seats' => 35], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 183]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 32], ['cabin' => 'Premium', 'code' => 'P', 'seats' => 28], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 222]]],
                    ],
                ],
                'MSR' => [
                    'icao' => 'MSR', 'iata' => 'MS', 'name' => 'EgyptAir',
                    'fleet' => [
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 126]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 16], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 166]]],
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 120]]],
                        ['aircraft' => 'Airbus A330-300', 'icao' => 'A333', 'iata' => '333', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 36], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 265]]],
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 49], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 297]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 30], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 279]]],
                    ],
                ],
                'SJX' => [
                    'icao' => 'SJX', 'iata' => 'JX', 'name' => 'STARLUX',
                    'fleet' => [
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 8], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 180]]],
                        ['aircraft' => 'Airbus A330-900neo', 'icao' => 'A339', 'iata' => '339', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 28], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 269]]],
                        ['aircraft' => 'Airbus A350-900', 'icao' => 'A359', 'iata' => '359', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 4], ['cabin' => 'Business', 'code' => 'J', 'seats' => 26], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 36], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 240]]],
                        ['aircraft' => 'Airbus A350-1000', 'icao' => 'A35K', 'iata' => '351', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 4], ['cabin' => 'Business', 'code' => 'J', 'seats' => 40], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 36], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 270]]],
                    ],
                ],
                'ANZ' => [
                    'icao' => 'ANZ', 'iata' => 'NZ', 'name' => 'Air New Zealand',
                    'fleet' => [
                        ['aircraft' => 'ATR 72-600', 'icao' => 'AT76', 'iata' => 'AT7', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 68]]],
                        ['aircraft' => 'de Havilland Dash 8-300', 'icao' => 'DH8C', 'iata' => 'DH3', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 50]]],
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 165]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 214]]],
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '', 'cabins' => [['cabin' => 'Business Premier', 'code' => 'J', 'seats' => 44], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 54], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 244]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Business Premier', 'code' => 'J', 'seats' => 27], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 33], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 215]]],
                    ],
                ],
                'EVA' => [
                    'icao' => 'EVA', 'iata' => 'BR', 'name' => 'EVA Air',
                    'fleet' => [
                        ['aircraft' => 'Airbus A321-200', 'icao' => 'A321', 'iata' => '321', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 8], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 176]]],
                        ['aircraft' => 'Airbus A330-300', 'icao' => 'A333', 'iata' => '333', 'variant' => '', 'cabins' => [['cabin' => 'Royal Laurel', 'code' => 'J', 'seats' => 30], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 279]]],
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '', 'cabins' => [['cabin' => 'Royal Laurel', 'code' => 'J', 'seats' => 39], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 56], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 258]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Royal Laurel', 'code' => 'J', 'seats' => 26], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 28], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 224]]],
                        ['aircraft' => 'Boeing 787-10', 'icao' => 'B78X', 'iata' => '781', 'variant' => '', 'cabins' => [['cabin' => 'Royal Laurel', 'code' => 'J', 'seats' => 34], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 308]]],
                    ],
                ],
                'CAL' => [
                    'icao' => 'CAL', 'iata' => 'CI', 'name' => 'China Airlines',
                    'fleet' => [
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 168]]],
                        ['aircraft' => 'Airbus A330-300', 'icao' => 'A333', 'iata' => '333', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 30], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 277]]],
                        ['aircraft' => 'Airbus A350-900', 'icao' => 'A359', 'iata' => '359', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 32], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 31], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 243]]],
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 40], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 62], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 256]]],
                    ],
                ],
                'THA' => [
                    'icao' => 'THA', 'iata' => 'TG', 'name' => 'Thai Airways',
                    'fleet' => [
                        ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'Royal Silk', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 144]]],
                        ['aircraft' => 'Airbus A330-300', 'icao' => 'A333', 'iata' => '333', 'variant' => '', 'cabins' => [['cabin' => 'Royal Silk', 'code' => 'J', 'seats' => 31], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 48], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 185]]],
                        ['aircraft' => 'Airbus A350-900', 'icao' => 'A359', 'iata' => '359', 'variant' => '', 'cabins' => [['cabin' => 'Royal Silk', 'code' => 'J', 'seats' => 32], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 289]]],
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '', 'cabins' => [['cabin' => 'Royal First', 'code' => 'F', 'seats' => 8], ['cabin' => 'Royal Silk', 'code' => 'J', 'seats' => 40], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 255]]],
                        ['aircraft' => 'Boeing 787-8', 'icao' => 'B788', 'iata' => '788', 'variant' => '', 'cabins' => [['cabin' => 'Royal Silk', 'code' => 'J', 'seats' => 22], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 234]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Royal Silk', 'code' => 'J', 'seats' => 30], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 268]]],
                    ],
                ],
                'MAS' => [
                    'icao' => 'MAS', 'iata' => 'MH', 'name' => 'Malaysia Airlines',
                    'fleet' => [
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 162]]],
                        ['aircraft' => 'Boeing 737 MAX 8', 'icao' => 'B38M', 'iata' => '7M8', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 162]]],
                        ['aircraft' => 'Airbus A330-300', 'icao' => 'A333', 'iata' => '333', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 27], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 263]]],
                        ['aircraft' => 'Airbus A330-900neo', 'icao' => 'A339', 'iata' => '339', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 28], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 269]]],
                        ['aircraft' => 'Airbus A350-900', 'icao' => 'A359', 'iata' => '359', 'variant' => '', 'cabins' => [['cabin' => 'Business Suite', 'code' => 'F', 'seats' => 4], ['cabin' => 'Business', 'code' => 'J', 'seats' => 35], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 247]]],
                    ],
                ],
                'AIC' => [
                    'icao' => 'AIC', 'iata' => 'AI', 'name' => 'Air India',
                    'fleet' => [
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 8], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 132]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 180]]],
                        ['aircraft' => 'Airbus A350-900', 'icao' => 'A359', 'iata' => '359', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 28], ['cabin' => 'Premium Economy', 'code' => 'P', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 264]]],
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '', 'cabins' => [['cabin' => 'First', 'code' => 'F', 'seats' => 4], ['cabin' => 'Business', 'code' => 'J', 'seats' => 35], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 303]]],
                        ['aircraft' => 'Boeing 787-8', 'icao' => 'B788', 'iata' => '788', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 18], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 238]]],
                    ],
                ],
                'IGO' => [
                    'icao' => 'IGO', 'iata' => '6E', 'name' => 'IndiGo',
                    'fleet' => [
                        ['aircraft' => 'ATR 72-600', 'icao' => 'AT76', 'iata' => 'AT7', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 78]]],
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 186]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Stretch', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 208]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '232Y', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 232]]],
                    ],
                ],
                'VOZ' => [
                    'icao' => 'VOZ', 'iata' => 'VA', 'name' => 'Virgin Australia',
                    'fleet' => [
                        ['aircraft' => 'Boeing 737-700', 'icao' => 'B737', 'iata' => '73G', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 8], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 120]]],
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 8], ['cabin' => 'Economy X', 'code' => 'W', 'seats' => 30], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 138]]],
                        ['aircraft' => 'Boeing 737 MAX 8', 'icao' => 'B38M', 'iata' => '7M8', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 8], ['cabin' => 'Economy X', 'code' => 'W', 'seats' => 30], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 144]]],
                    ],
                ],
                'TGW' => [
                    'icao' => 'TGW', 'iata' => 'TR', 'name' => 'Scoot',
                    'fleet' => [
                        ['aircraft' => 'Embraer E190-E2', 'icao' => 'E290', 'iata' => '290', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 112]]],
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 186]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 236]]],
                        ['aircraft' => 'Boeing 787-8', 'icao' => 'B788', 'iata' => '788', 'variant' => '', 'cabins' => [['cabin' => 'ScootPlus', 'code' => 'P', 'seats' => 18], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 311]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'ScootPlus', 'code' => 'P', 'seats' => 35], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 340]]],
                    ],
                ],
                'LAN' => [
                    'icao' => 'LAN', 'iata' => 'LA', 'name' => 'LATAM Chile',
                    'fleet' => [
                        ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'Premium Economy', 'code' => 'W', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 162]]],
                        ['aircraft' => 'Airbus A321-200', 'icao' => 'A321', 'iata' => '321', 'variant' => '', 'cabins' => [['cabin' => 'Premium Economy', 'code' => 'W', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 208]]],
                        ['aircraft' => 'Boeing 787-8', 'icao' => 'B788', 'iata' => '788', 'variant' => '', 'cabins' => [['cabin' => 'Premium Business', 'code' => 'J', 'seats' => 20], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 251]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Premium Business', 'code' => 'J', 'seats' => 30], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 270]]],
                    ],
                ],
                'TAM' => [
                    'icao' => 'TAM', 'iata' => 'JJ', 'name' => 'LATAM Brasil',
                    'fleet' => [
                        ['aircraft' => 'Airbus A319-100', 'icao' => 'A319', 'iata' => '319', 'variant' => '', 'cabins' => [['cabin' => 'Premium Economy', 'code' => 'W', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 126]]],
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'Premium Economy', 'code' => 'W', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 162]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Premium Economy', 'code' => 'W', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 212]]],
                        ['aircraft' => 'Boeing 777-300ER', 'icao' => 'B77W', 'iata' => '77W', 'variant' => '', 'cabins' => [['cabin' => 'Premium Business', 'code' => 'J', 'seats' => 38], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 372]]],
                        ['aircraft' => 'Boeing 787-9', 'icao' => 'B789', 'iata' => '789', 'variant' => '', 'cabins' => [['cabin' => 'Premium Business', 'code' => 'J', 'seats' => 30], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 270]]],
                    ],
                ],
                'AVA' => [
                    'icao' => 'AVA', 'iata' => 'AV', 'name' => 'Avianca',
                    'fleet' => [
                        ['aircraft' => 'Airbus A319-100', 'icao' => 'A319', 'iata' => '319', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 144]]],
                        ['aircraft' => 'Airbus A320-200', 'icao' => 'A320', 'iata' => '320', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 168]]],
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 12], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 168]]],
                        ['aircraft' => 'Boeing 787-8', 'icao' => 'B788', 'iata' => '788', 'variant' => '', 'cabins' => [['cabin' => 'Insignia Business', 'code' => 'J', 'seats' => 20], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 271]]],
                    ],
                ],
                'AZU' => [
                    'icao' => 'AZU', 'iata' => 'AD', 'name' => 'Azul',
                    'fleet' => [
                        ['aircraft' => 'ATR 72-600', 'icao' => 'AT76', 'iata' => 'AT7', 'variant' => '', 'cabins' => [['cabin' => 'Economy', 'code' => 'Y', 'seats' => 70]]],
                        ['aircraft' => 'Embraer E195-E2', 'icao' => 'E295', 'iata' => '295', 'variant' => '', 'cabins' => [['cabin' => 'Espaco Azul', 'code' => 'W', 'seats' => 28], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 108]]],
                        ['aircraft' => 'Airbus A320neo', 'icao' => 'A20N', 'iata' => '32N', 'variant' => '', 'cabins' => [['cabin' => 'Espaco Azul', 'code' => 'W', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 150]]],
                        ['aircraft' => 'Airbus A321neo', 'icao' => 'A21N', 'iata' => '32Q', 'variant' => '', 'cabins' => [['cabin' => 'Espaco Azul', 'code' => 'W', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 190]]],
                        ['aircraft' => 'Airbus A330-900neo', 'icao' => 'A339', 'iata' => '339', 'variant' => '', 'cabins' => [['cabin' => 'Business', 'code' => 'J', 'seats' => 34], ['cabin' => 'Economy Xtra', 'code' => 'W', 'seats' => 96], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 168]]],
                    ],
                ],
                'GLO' => [
                    'icao' => 'GLO', 'iata' => 'G3', 'name' => 'GOL',
                    'fleet' => [
                        ['aircraft' => 'Boeing 737-700', 'icao' => 'B737', 'iata' => '73G', 'variant' => '', 'cabins' => [['cabin' => 'GOL+ Conforto', 'code' => 'W', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 114]]],
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'GOL+ Conforto', 'code' => 'W', 'seats' => 30], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 156]]],
                        ['aircraft' => 'Boeing 737 MAX 8', 'icao' => 'B38M', 'iata' => '7M8', 'variant' => '', 'cabins' => [['cabin' => 'GOL+ Conforto', 'code' => 'W', 'seats' => 30], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 156]]],
                    ],
                ],
                'ARG' => [
                    'icao' => 'ARG', 'iata' => 'AR', 'name' => 'Aerolineas Argentinas',
                    'fleet' => [
                        ['aircraft' => 'Embraer E190', 'icao' => 'E190', 'iata' => 'E90', 'variant' => '', 'cabins' => [['cabin' => 'Club Economy', 'code' => 'W', 'seats' => 8], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 88]]],
                        ['aircraft' => 'Boeing 737-800', 'icao' => 'B738', 'iata' => '738', 'variant' => '', 'cabins' => [['cabin' => 'Club Economy', 'code' => 'W', 'seats' => 8], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 162]]],
                        ['aircraft' => 'Boeing 737 MAX 8', 'icao' => 'B38M', 'iata' => '7M8', 'variant' => '', 'cabins' => [['cabin' => 'Club Economy', 'code' => 'W', 'seats' => 8], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 162]]],
                        ['aircraft' => 'Airbus A330-200', 'icao' => 'A332', 'iata' => '332', 'variant' => '', 'cabins' => [['cabin' => 'Club Condor', 'code' => 'J', 'seats' => 24], ['cabin' => 'Economy', 'code' => 'Y', 'seats' => 248]]],
                    ],
                ],
                'CLX' => [
                    'icao' => 'CLX', 'iata' => 'CV', 'name' => 'Cargolux',
                    'fleet' => [
                        ['aircraft' => 'Boeing 747-400F', 'icao' => 'B744', 'iata' => '74Y', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 248000]]],
                        ['aircraft' => 'Boeing 747-400ERF', 'icao' => 'B744', 'iata' => '74Y', 'variant' => 'ERF', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 248000]]],
                        ['aircraft' => 'Boeing 747-8F', 'icao' => 'B748', 'iata' => '74N', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 303000]]],
                    ],
                ],
                'GEC' => [
                    'icao' => 'GEC', 'iata' => 'LH', 'name' => 'Lufthansa Cargo',
                    'fleet' => [
                        ['aircraft' => 'Airbus A321P2F', 'icao' => 'A321', 'iata' => '32P', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 61000]]],
                        ['aircraft' => 'Boeing 777F', 'icao' => 'B77L', 'iata' => '77X', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 227000]]],
                    ],
                ],
                'BCS' => [
                    'icao' => 'BCS', 'iata' => 'QY', 'name' => 'DHL Aviation',
                    'fleet' => [
                        ['aircraft' => 'Boeing 757-200PCF', 'icao' => 'B752', 'iata' => '75F', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 66000]]],
                        ['aircraft' => 'Airbus A300-600F', 'icao' => 'A306', 'iata' => 'ABY', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 106000]]],
                        ['aircraft' => 'Airbus A330-200F', 'icao' => 'A332', 'iata' => '33X', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 154000]]],
                        ['aircraft' => 'Airbus A330-300P2F', 'icao' => 'A333', 'iata' => '33F', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 134000]]],
                        ['aircraft' => 'Boeing 767-300F', 'icao' => 'B763', 'iata' => '76F', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 120000]]],
                        ['aircraft' => 'Boeing 777F', 'icao' => 'B77L', 'iata' => '77X', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 225000]]],
                    ],
                ],
                'CKS' => [
                    'icao' => 'CKS', 'iata' => 'K4', 'name' => 'Kalitta Air',
                    'fleet' => [
                        ['aircraft' => 'Boeing 747-400F', 'icao' => 'B744', 'iata' => '74Y', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 248000]]],
                        ['aircraft' => 'Boeing 777F', 'icao' => 'B77L', 'iata' => '77X', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 225000]]],
                    ],
                ],
                'CJT' => [
                    'icao' => 'CJT', 'iata' => 'W8', 'name' => 'Cargojet',
                    'fleet' => [
                        ['aircraft' => 'Boeing 757-200PCF', 'icao' => 'B752', 'iata' => '75F', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 66000]]],
                        ['aircraft' => 'Boeing 767-200F', 'icao' => 'B762', 'iata' => '76X', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 95000]]],
                        ['aircraft' => 'Boeing 767-300F', 'icao' => 'B763', 'iata' => '76F', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 125000]]],
                    ],
                ],
                'SRR' => [
                    'icao' => 'SRR', 'iata' => 'DJ', 'name' => 'Maersk Air Cargo',
                    'fleet' => [
                        ['aircraft' => 'Boeing 767-200F', 'icao' => 'B762', 'iata' => '76X', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 95000]]],
                        ['aircraft' => 'Boeing 767-300F', 'icao' => 'B763', 'iata' => '76F', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 120000]]],
                        ['aircraft' => 'Boeing 777F', 'icao' => 'B77L', 'iata' => '77X', 'variant' => 'F', 'cabins' => [['cabin' => 'Cargo', 'code' => 'C', 'seats' => 225000]]],
                    ],
                ],
                'WUP' => [
                    'icao' => 'WUP', 'iata' => '', 'name' => 'Wheels Up',
                    'fleet' => [
                        ['aircraft' => 'Beechcraft King Air 350i', 'icao' => 'B350', 'iata' => 'BEH', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 8]]],
                        ['aircraft' => 'Cessna Citation CJ3', 'icao' => 'C25B', 'iata' => 'CNJ', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 6]]],
                        ['aircraft' => 'Cessna Citation Excel / XLS', 'icao' => 'C56X', 'iata' => 'C5X', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 8]]],
                        ['aircraft' => 'Cessna Citation X', 'icao' => 'C750', 'iata' => 'C75', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 8]]],
                        ['aircraft' => 'Bombardier Challenger 300', 'icao' => 'CL30', 'iata' => 'CL3', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 9]]],
                        ['aircraft' => 'Embraer Phenom 300', 'icao' => 'E55P', 'iata' => 'EP3', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 7]]],
                    ],
                ],
                'APS' => [
                    'icao' => 'APS', 'iata' => '', 'name' => 'PlaneSense',
                    'fleet' => [
                        ['aircraft' => 'Pilatus PC-12 NGX', 'icao' => 'PC12', 'iata' => 'PL2', 'variant' => 'NGX', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 8]]],
                        ['aircraft' => 'Pilatus PC-24', 'icao' => 'PC24', 'iata' => 'P24', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 8]]],
                    ],
                ],
                'GPD' => [
                    'icao' => 'GPD', 'iata' => 'TJ', 'name' => 'Tradewind Aviation',
                    'fleet' => [
                        ['aircraft' => 'Pilatus PC-12 NGX', 'icao' => 'PC12', 'iata' => 'PL2', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 8]]],
                        ['aircraft' => 'Cessna Citation CJ3+', 'icao' => 'C25B', 'iata' => 'CNJ', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 7]]],
                    ],
                ],
                'JTZ' => [
                    'icao' => 'JTZ', 'iata' => '', 'name' => 'Nicholas Air',
                    'fleet' => [
                        ['aircraft' => 'Pilatus PC-12', 'icao' => 'PC12', 'iata' => 'PL2', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 8]]],
                        ['aircraft' => 'Embraer Phenom 100', 'icao' => 'E50P', 'iata' => 'EP1', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 4]]],
                        ['aircraft' => 'Embraer Phenom 300', 'icao' => 'E55P', 'iata' => 'EP3', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 7]]],
                        ['aircraft' => 'Cessna Citation Latitude', 'icao' => 'C68A', 'iata' => 'CLD', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 9]]],
                        ['aircraft' => 'Bombardier Challenger 350', 'icao' => 'CL35', 'iata' => 'CL3', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 9]]],
                    ],
                ],
                'JRE' => [
                    'icao' => 'JRE', 'iata' => '', 'name' => 'FlyExclusive',
                    'fleet' => [
                        ['aircraft' => 'Cessna Citation CJ3+', 'icao' => 'C25B', 'iata' => 'CNJ', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 7]]],
                        ['aircraft' => 'Cessna Citation Encore+', 'icao' => 'C560', 'iata' => 'C56', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 7]]],
                        ['aircraft' => 'Cessna Citation XLS', 'icao' => 'C56X', 'iata' => 'C5X', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 8]]],
                        ['aircraft' => 'Cessna Citation Sovereign', 'icao' => 'C680', 'iata' => 'C68', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 9]]],
                        ['aircraft' => 'Cessna Citation X', 'icao' => 'C750', 'iata' => 'C75', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 8]]],
                        ['aircraft' => 'Bombardier Challenger 350', 'icao' => 'CL35', 'iata' => 'CL3', 'variant' => '', 'cabins' => [['cabin' => 'Executive', 'code' => 'F', 'seats' => 9]]],
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
                $simbriefType = trim((string) ($item['simbrief_type'] ?? ''));
                if ($simbriefType === '') {
                    $simbriefType = strtoupper(trim((string) ($item['icao'] ?? '')));
                }
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
                    $sf->simbrief_type = substr($simbriefType, 0, 40);
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
                $hasSimbrief = array_key_exists('simbrief_type', $u);
                $newSimbrief = $hasSimbrief ? substr(trim((string) ($u['simbrief_type'] ?? '')), 0, 40) : null;
                if ($id <= 0 || $newType === '' || $newName === '') {
                    continue;
                }

                $sf = Subfleet::find($id);
                if (!$sf) {
                    continue;
                }

                $sbSame = !$hasSimbrief || ((string) ($sf->simbrief_type ?? '') === (string) $newSimbrief);
                if ($sf->type === $newType && $sf->name === $newName && $sbSame) {
                    continue;
                }

                $conflict = Subfleet::where('type', $newType)->where('id', '!=', $id)->exists();
                if ($conflict) {
                    $collisions[] = "#{$id} -> {$newType} (collision)";
                    continue;
                }

                $sf->type = $newType;
                $sf->name = $newName;
                if ($hasSimbrief) {
                    $sf->simbrief_type = $newSimbrief !== '' ? $newSimbrief : null;
                }
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
