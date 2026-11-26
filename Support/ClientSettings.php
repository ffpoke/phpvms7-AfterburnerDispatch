<?php

declare(strict_types=1);

namespace Modules\AfterburnerDispatch\Support;

use Illuminate\Support\Facades\Crypt;
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
                'show_simbrief_with_paxstudio' => '0',
                'github_pat_classic'           => '',
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
                'show_simbrief_with_paxstudio',
                'github_pat_classic',
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
                } elseif (in_array($key, ['digest_enabled', 'show_simbrief_with_paxstudio'], true)) {
                    $val = ($val === '1' || strtolower($val) === 'true') ? '1' : '0';
                } elseif ($key === 'github_pat_classic' && $val !== '' && !str_starts_with($val, 'enc:')) {
                    try {
                        $val = 'enc:' . Crypt::encryptString($val);
                    } catch (\Throwable $e) {}
                }
                try {
                    DB::table('routerequest_client_settings')->updateOrInsert(
                        ['setting_key' => $key],
                        ['setting_value' => $val, 'updated_at' => now()]
                    );
                } catch (\Throwable $e) {}
            }
        }

        public static function getGitHubPat(): string
        {
            $all = self::all();
            $raw = trim((string) ($all['github_pat_classic'] ?? ''));
            if ($raw !== '') {
                if (str_starts_with($raw, 'enc:')) {
                    try {
                        return trim(Crypt::decryptString(substr($raw, 4)));
                    } catch (\Throwable $e) {
                        return '';
                    }
                }
                return $raw;
            }
            $envPat = trim((string) (env('AB_GITHUB_PAT', env('GITHUB_PAT', '')) ?: ''));
            return $envPat;
        }

        public static function hasGitHubPat(): bool
        {
            return self::getGitHubPat() !== '';
        }

        public static function getGitHubPatMasked(): string
        {
            $pat = self::getGitHubPat();
            if ($pat === '') {
                return '';
            }
            $len = strlen($pat);
            if ($len <= 8) {
                return str_repeat('•', $len);
            }
            return substr($pat, 0, 4) . '••••••••' . substr($pat, -4);
        }
    }
}