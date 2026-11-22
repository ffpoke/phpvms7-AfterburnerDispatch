<?php

declare(strict_types=1);

namespace Modules\RouteRequest\Support;

use Illuminate\Support\Facades\DB;

if (!class_exists(ClientSettings::class, false)) {
    class ClientSettings
    {
        public static function all(): array
        {
            if (!class_exists(SchemaHelper::class, false)) {
                require_once __DIR__ . '/SchemaHelper.php';
            }
            SchemaHelper::ensureRouteRequestsColumns();

            $defaults = [
                'discord_staff_webhook'       => '',
                'discord_public_webhook'      => '',
                'max_open_requests_per_pilot' => '0',
                'pilot_cooldown_minutes'      => '0',
                'range_overrides_json'        => '',
                'standalone_update_url'       => '',
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

            return $defaults;
        }

        public static function save(array $input): void
        {
            if (!class_exists(SchemaHelper::class, false)) {
                require_once __DIR__ . '/SchemaHelper.php';
            }
            SchemaHelper::ensureRouteRequestsColumns(true);

            $allowed = [
                'discord_staff_webhook',
                'discord_public_webhook',
                'max_open_requests_per_pilot',
                'pilot_cooldown_minutes',
                'range_overrides_json',
                'standalone_update_url',
            ];

            foreach ($allowed as $key) {
                if (!array_key_exists($key, $input)) {
                    continue;
                }
                $val = trim((string) ($input[$key] ?? ''));
                if (in_array($key, ['max_open_requests_per_pilot', 'pilot_cooldown_minutes'], true)) {
                    $val = (string) max(0, (int) $val);
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
