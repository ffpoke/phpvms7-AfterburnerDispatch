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
        public const string DEFAULT_NAME_SYNTAX = '{OP_ICAO} {AIRCRAFT} {VARIANT} {CONFIG} ({PAX})';

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

            $existingSubfleetFares = [];
            try {
                if (Schema::hasTable('subfleet_fare') && Schema::hasTable('fares')) {
                    $sfRows = DB::table('subfleet_fare')
                        ->join('fares', 'fares.id', '=', 'subfleet_fare.fare_id')
                        ->select('subfleet_fare.subfleet_id', 'fares.code', 'fares.name', 'subfleet_fare.capacity', 'fares.capacity as default_capacity')
                        ->orderBy('fares.code', 'asc')
                        ->get();
                    foreach ($sfRows as $sfr) {
                        $sid = (int) $sfr->subfleet_id;
                        $cap = ($sfr->capacity !== null && $sfr->capacity !== '') ? (int) $sfr->capacity : (int) ($sfr->default_capacity ?? 0);
                        $existingSubfleetFares[$sid][] = [
                            'code'     => strtoupper(trim((string) $sfr->code)),
                            'name'     => (string) $sfr->name,
                            'capacity' => $cap,
                        ];
                    }
                }
            } catch (\Throwable $e) {}

            $operators = self::getOperatorCatalog();

            return [
                'vault_version'          => '4.1.1-beta.1',
                'existing_subfleet_fares'=> $existingSubfleetFares,
                'operators'          => $operators,
                'vault_locked'       => empty($operators),
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

                            $pMeta = $hasPreset ? ($presets[$icao] ?? []) : [];
                            $directory[] = [
                                'key'          => $hasPreset ? $icao : ('DIR:' . $icao . ':' . $starterType),
                                'icao'         => $icao,
                                'iata'         => $iata,
                                'name'         => $hasPreset ? $presets[$icao]['name'] : $cleanName,
                                'label'        => $labelStr,
                                'category'     => (string) $category,
                                'starter_type' => $starterType,
                                'has_preset'   => $hasPreset,
                                'seatmap_rev'  => (int) ($pMeta['seatmap_rev'] ?? 0),
                                'updated_in'   => (string) ($pMeta['updated_in'] ?? ''),
                                'change_type'  => (string) ($pMeta['change_type'] ?? ''),
                                'update_note'  => (string) ($pMeta['update_note'] ?? ''),
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
                        'seatmap_rev'  => (int) ($p['seatmap_rev'] ?? 0),
                        'updated_in'   => (string) ($p['updated_in'] ?? ''),
                        'change_type'  => (string) ($p['change_type'] ?? ''),
                        'update_note'  => (string) ($p['update_note'] ?? ''),
                    ];
                }
            }

            return $directory;
        }

        public static function getOperatorCatalog(): array
        {
            static $memo = null;
            if (is_array($memo)) {
                return $memo;
            }

            $vaultPath = dirname(__DIR__) . '/Resources/data/fleet_presets.dat';
            if (!is_file($vaultPath) || !function_exists('openssl_encrypt')) {
                return [];
            }

            // Contacts AfterburnerServer Cloud DRM (/entitlements, Ed25519-signed) for the 256-bit fleet_key
            $hexKey = DrmEntitlements::getFleetKey();
            if ($hexKey === '') {
                return [];
            }

            $binKey = @hex2bin($hexKey);
            if (!is_string($binKey) || strlen($binKey) !== 32) {
                return [];
            }

            $raw = @file_get_contents($vaultPath);
            if (!is_string($raw) || strlen($raw) < 35) {
                return [];
            }

            $magic = substr($raw, 0, 6);
            $nonce = substr($raw, 6, 12);
            $tag   = substr($raw, 18, 16);
            $ct    = substr($raw, 34);
            $plain = false;

            if ($magic === 'ABFV02') {
                $plain = self::decryptAes256GcmSiv($binKey, $nonce, $ct, $tag, 'ABFV02|AES-256-GCM-SIV');
            } elseif ($magic === 'ABFV01' && function_exists('openssl_decrypt')) {
                $plain = @openssl_decrypt($ct, 'aes-256-gcm', $binKey, OPENSSL_RAW_DATA, $nonce, $tag);
            }

            if (!is_string($plain) || $plain === '' || !json_validate($plain)) {
                return [];
            }

            $decoded = json_decode($plain, true);
            if (!is_array($decoded)) {
                return [];
            }

            $memo = $decoded;
            return $memo;
        }

        /**
         * RFC 8452 AES-256-GCM-SIV decryption: uses native OpenSSL 3.2+ aes-256-gcm-siv
         * when available, with a constant-time verified pure-PHP 8.3 (aes-256-ecb + POLYVAL) fallback.
         */
        private static function decryptAes256GcmSiv(string $key, string $nonce12, string $ct, string $tag, string $aad = ''): string|false
        {
            if (strlen($key) !== 32 || strlen($nonce12) !== 12 || strlen($tag) !== 16) {
                return false;
            }

            // Path A: Native OpenSSL 3.2+ AES-256-GCM-SIV
            if (function_exists('openssl_decrypt') && function_exists('openssl_get_cipher_methods')) {
                $methods = openssl_get_cipher_methods(true);
                if (in_array('aes-256-gcm-siv', $methods, true)) {
                    $native = @openssl_decrypt($ct, 'aes-256-gcm-siv', $key, OPENSSL_RAW_DATA, $nonce12, $tag, $aad);
                    if (is_string($native)) {
                        return $native;
                    }
                }
            }

            // Path B: RFC 8452 pure-PHP 8.3 fallback (64-bit) using AES-256-ECB + POLYVAL
            $kdfIn = '';
            for ($i = 0; $i < 6; $i++) {
                $kdfIn .= pack('V', $i) . $nonce12;
            }
            $kdfOut = @openssl_encrypt($kdfIn, 'aes-256-ecb', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
            if (!is_string($kdfOut) || strlen($kdfOut) !== 96) {
                return false;
            }

            $authKey = substr($kdfOut, 0, 8) . substr($kdfOut, 16, 8);
            $encKey  = substr($kdfOut, 32, 8) . substr($kdfOut, 48, 8) . substr($kdfOut, 64, 8) . substr($kdfOut, 80, 8);

            // Bulk AES-256-CTR keystream generation (32-bit LE counter, bit 127 forced to 1)
            $ctLen = strlen($ct);
            $pt = '';
            if ($ctLen > 0) {
                $nBlocks = intdiv($ctLen + 15, 16);
                $ctr0    = unpack('V', substr($tag, 0, 4))[1];
                $ctrTail = substr($tag, 4, 11) . chr(ord($tag[15]) | 0x80);
                $ctrIn   = '';
                for ($i = 0; $i < $nBlocks; $i++) {
                    $ctrIn .= pack('V', ($ctr0 + $i) & 0xffffffff) . $ctrTail;
                }
                $ks = @openssl_encrypt($ctrIn, 'aes-256-ecb', $encKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
                if (!is_string($ks) || strlen($ks) < $ctLen) {
                    return false;
                }
                $pt = $ct ^ substr($ks, 0, $ctLen);
            }

            // POLYVAL authentication tag verification
            $aadLen = strlen($aad);
            $ptLen  = strlen($pt);
            $padAad = ($aadLen % 16 === 0) ? $aad : ($aad . str_repeat("\0", 16 - ($aadLen % 16)));
            $padPt  = ($ptLen  % 16 === 0) ? $pt  : ($pt  . str_repeat("\0", 16 - ($ptLen  % 16)));
            $lenBlk = pack('P2', $aadLen * 8, $ptLen * 8);

            $s = self::polyval($authKey, $padAad . $padPt . $lenBlk);
            for ($i = 0; $i < 12; $i++) {
                $s[$i] = chr(ord($s[$i]) ^ ord($nonce12[$i]));
            }
            $s[15] = chr(ord($s[15]) & 0x7f);

            $expectedTag = @openssl_encrypt($s, 'aes-256-ecb', $encKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
            if (!is_string($expectedTag) || strlen($expectedTag) !== 16 || !hash_equals($expectedTag, $tag)) {
                return false;
            }

            return $pt;
        }

        private static function polyval(string $h, string $data): string
        {
            $hv   = unpack('P2', $h);
            $hLo  = $hv[1];
            $hHi  = $hv[2];
            $sLo  = 0;
            $sHi  = 0;
            $maskHi = PHP_INT_MIN | (0x61 << 56); // Exact 64-bit signed int for 0xe100000000000000

            $len = strlen($data);
            for ($off = 0; $off < $len; $off += 16) {
                $blk = unpack('P2', substr($data, $off, 16));
                $xLo = $sLo ^ $blk[1];
                $xHi = $sHi ^ $blk[2];

                $zLo = 0;
                $zHi = 0;
                for ($half = 0; $half < 2; $half++) {
                    $w = ($half === 0) ? $xLo : $xHi;
                    for ($b = 0; $b < 64; $b++) {
                        if (($w & 1) !== 0) {
                            $zLo ^= $hLo;
                            $zHi ^= $hHi;
                        }
                        $w = ($w >> 1) & 0x7fffffffffffffff;
                        $lsb = $zLo & 1;
                        $zLo = (($zLo >> 1) & 0x7fffffffffffffff) | (($zHi & 1) << 63);
                        $zHi = ($zHi >> 1) & 0x7fffffffffffffff;
                        if ($lsb !== 0) {
                            $zHi ^= $maskHi;
                        }
                    }
                }
                $sLo = $zLo;
                $sHi = $zHi;
            }

            return pack('P2', $sLo, $sHi);
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
                $sf->name = substr($sfName, 0, 50);
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

        public static function executeStandardize(array $updates, array $options = []): array
        {
            if (empty($updates)) {
                return ['success' => false, 'message' => 'No subfleet standardization updates were submitted.'];
            }

            $typeSyntax = trim((string) ($options['type_syntax'] ?? ''));
            $nameSyntax = trim((string) ($options['name_syntax'] ?? ''));
            if ($typeSyntax !== '' || $nameSyntax !== '') {
                $saveMap = [];
                if ($typeSyntax !== '') {
                    $saveMap['subfleet_type_syntax'] = $typeSyntax;
                }
                if ($nameSyntax !== '') {
                    $saveMap['subfleet_name_syntax'] = $nameSyntax;
                }
                ClientSettings::save($saveMap);
            }

            $globalFixCabins = !empty($options['fix_cabins']);

            // Build lookup of existing phpVMS fares for cabin layout sync
            $faresByCode = [];
            try {
                foreach (Fare::all() as $f) {
                    $faresByCode[strtoupper(trim((string) $f->code))] = $f;
                }
            } catch (\Throwable $e) {}

            // Pre-validate missing fare codes on any row requesting cabin layout fixes
            $missingCodes = [];
            foreach ($updates as $u) {
                $rowFix = array_key_exists('fix_cabins', $u) ? !empty($u['fix_cabins']) : $globalFixCabins;
                $cabins = is_array($u['cabins'] ?? null) ? $u['cabins'] : [];
                if (!$rowFix || empty($cabins)) {
                    continue;
                }
                foreach ($cabins as $cab) {
                    $seats = (int) ($cab['seats'] ?? 0);
                    $code = strtoupper(trim((string) ($cab['fare_code'] ?? $cab['code'] ?? '')));
                    if ($seats <= 0) {
                        continue;
                    }
                    if ($code === '' || !isset($faresByCode[$code])) {
                        $missingCodes[] = $code !== '' ? $code : ('(blank on #' . (int) ($u['id'] ?? 0) . ')');
                    }
                }
            }
            if (!empty($missingCodes)) {
                $uniq = array_values(array_unique($missingCodes));
                return [
                    'success' => false,
                    'message' => 'Missing phpVMS Fare Code(s) for cabin fix: ' . implode(', ', $uniq) . '. Click "+ Create Fare Code" to create them first, or map each cabin to an existing Fare Code.',
                ];
            }

            $updated = 0;
            $cabinsSynced = 0;
            $collisions = [];

            foreach ($updates as $u) {
                $id = (int) ($u['id'] ?? 0);
                $newType = substr(trim((string) ($u['new_type'] ?? '')), 0, 50);
                $newName = substr(trim((string) ($u['new_name'] ?? '')), 0, 50);
                $hasSimbrief = array_key_exists('simbrief_type', $u);
                $newSimbrief = $hasSimbrief ? substr(trim((string) ($u['simbrief_type'] ?? '')), 0, 40) : null;
                $rowFix = array_key_exists('fix_cabins', $u) ? !empty($u['fix_cabins']) : $globalFixCabins;
                $cabins = is_array($u['cabins'] ?? null) ? $u['cabins'] : [];
                $wantsCabinSync = $rowFix && !empty($cabins) && Schema::hasTable('subfleet_fare');

                if ($id <= 0 || $newType === '' || $newName === '') {
                    continue;
                }

                $sf = Subfleet::find($id);
                if (!$sf) {
                    continue;
                }

                $sbSame = !$hasSimbrief || ((string) ($sf->simbrief_type ?? '') === (string) $newSimbrief);
                $metaChanged = !($sf->type === $newType && $sf->name === $newName && $sbSame);

                if (!$metaChanged && !$wantsCabinSync) {
                    continue;
                }

                if ($metaChanged) {
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
                }

                if ($wantsCabinSync) {
                    $fareCaps = [];
                    foreach ($cabins as $cab) {
                        $seats = (int) ($cab['seats'] ?? 0);
                        $code = strtoupper(trim((string) ($cab['fare_code'] ?? $cab['code'] ?? '')));
                        if ($seats > 0 && $code !== '' && isset($faresByCode[$code])) {
                            $fid = (int) $faresByCode[$code]->id;
                            $fareCaps[$fid] = ($fareCaps[$fid] ?? 0) + $seats;
                        }
                    }
                    if (!empty($fareCaps)) {
                        try {
                            DB::table('subfleet_fare')->where('subfleet_id', $sf->id)->delete();
                            foreach ($fareCaps as $fid => $cap) {
                                DB::table('subfleet_fare')->updateOrInsert(
                                    ['subfleet_id' => $sf->id, 'fare_id' => $fid],
                                    ['capacity' => (string) $cap]
                                );
                            }
                            $cabinsSynced++;
                        } catch (\Throwable $e) {}
                    }
                }

                $updated++;
            }

            $msg = "Standardized {$updated} existing subfleet(s) safely by ID";
            if ($cabinsSynced > 0) {
                $msg .= " ({$cabinsSynced} cabin layout(s) replaced/synced)";
            }
            $msg .= ' — flights, aircraft & PIREPs preserved.';
            if (!empty($collisions)) {
                $msg .= ' Skipped collisions: ' . implode(', ', $collisions) . '.';
            }

            return [
                'success'       => $updated > 0,
                'message'       => $msg,
                'updated'       => $updated,
                'cabins_synced' => $cabinsSynced,
                'collisions'    => $collisions,
            ];
        }
    }
}
