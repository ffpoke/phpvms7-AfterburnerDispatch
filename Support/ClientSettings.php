<?php

declare(strict_types=1);

namespace Modules\RouteRequest\Support;

use Illuminate\Support\Facades\DB;

if (!class_exists(ClientSettings::class, false)) {
    class ClientSettings
    {
        private static ?array $memo = null;

        public static function all(bool $fresh = false): array
        {
            if (!$fresh && self::$memo !== null) {
                return self::$memo;
            }
            if (class_exists(SchemaHelper::class, false)) {
                SchemaHelper::ensureRouteRequestsColumns();
            }

            $defaults = [
                'discord_staff_webhook'       => '',
                'discord_public_webhook'      => '',
                'max_open_requests_per_pilot' => '0',
                'pilot_cooldown_minutes'      => '0',
                'range_overrides_json'        => '',
                'digest_enabled'              => '0',
                'digest_window_hours'         => '24',
                'digest_recipient_email'      => '',
                'last_digest_sent_at'         => '',
                'subfleet_type_syntax'        => '{OP_ICAO}.{ICAO}.{VARIANT}',
                'subfleet_name_syntax'        => '{OP_NAME} {AIRCRAFT} ({IATA}) {VARIANT} {PAX}-PAX',
            ];

            try {
                $rows = DB::table('routerequest_client_settings')
                    ->whereIn('setting_key', array_keys($defaults))
                    ->pluck('setting_value', 'setting_key')
                    ->toArray();

                foreach ($defaults as $k => $v) {
                    if (isset($rows[$k]) && $rows[$k] !== null) {
                        $defaults[$k] = (string) $rows[$k];
                    }
                }
            } catch (\Throwable $e) {}

            self::$memo = $defaults;
            return $defaults;
        }

        public static function save(array $input): void
        {
            self::$memo = null;
            if (class_exists(SchemaHelper::class, false)) {
                SchemaHelper::ensureRouteRequestsColumns(true);
            }

            $allowed = [
                'discord_staff_webhook',
                'discord_public_webhook',
                'max_open_requests_per_pilot',
                'pilot_cooldown_minutes',
                'range_overrides_json',
                'digest_enabled',
                'digest_window_hours',
                'digest_recipient_email',
                'last_digest_sent_at',
                'subfleet_type_syntax',
                'subfleet_name_syntax',
            ];

            foreach ($allowed as $key) {
                if (!array_key_exists($key, $input)) {
                    continue;
                }
                $val = trim((string) ($input[$key] ?? ''));
                if (in_array($key, ['max_open_requests_per_pilot', 'pilot_cooldown_minutes'], true)) {
                    $val = (string) max(0, (int) $val);
                } elseif ($key === 'digest_window_hours') {
                    $val = (string) max(1, min(720, (int) $val));
                } elseif ($key === 'digest_enabled') {
                    $val = ($val === '1' || strtolower($val) === 'true') ? '1' : '0';
                }
                try {
                    DB::table('routerequest_client_settings')->updateOrInsert(
                        ['setting_key' => $key],
                        ['setting_value' => $val, 'updated_at' => now()]
                    );
                } catch (\Throwable $e) {}
            }
        }
    }
}
