<?php

declare(strict_types=1);

namespace Modules\RouteRequest\Support;

if (!class_exists(DropSchedule::class, false)) {
    final class DropSchedule
    {
        public const string SCHEDULE_VERSION = '2026.11.23-v3.2.0';

        private const string PUBLIC_KEY_B64 = 'MwG16GpOXANYFMeNeV/IJr9eH3YDPASAo8QdkXaFsu0=';
        private const string AAD = 'RR-DROPSCHEDULE-2026.11.23-v3.2.0';
        private const string CIPHER_KEY_B64 = 'MeWPgV7U7a+4h1SeHGySC2QKONEmeYlsbp21VZZFHNI=';
        private const string CIPHER_NONCE_B64 = 'fICY32QQQzIzoroN';
        private const string ENCRYPTED_BLOB_B64 = '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';
        private const string ED25519_SIG_B64 = 'heEO7FpQ8Sc2gQe6zUKdLCr6X2gm/4ppGlg06bBJ2lTnl2jW4xPBrFYprcHffwMGSDLpQaUX9zumuFivMRItCA==';

        private static ?array $decodedSchedule = null;
        private static bool $integrityValid = false;

        private static function loadSchedule(): array
        {
            if (self::$decodedSchedule !== null) {
                return self::$decodedSchedule;
            }

            $failClosed = [
                'admin_queue_filters' => [
                    'label'              => 'Admin Queue Filters, Live Search & Bulk Cleanup',
                    'basic'              => 'always',
                    'executive'          => 'always',
                    'flagship'           => 'always',
                    'permanent_flagship' => false,
                ],
                'portal_telemetry_view' => [
                    'label'              => 'Customer Portal Telemetry & License Health View',
                    'basic'              => 'always',
                    'executive'          => 'always',
                    'flagship'           => 'always',
                    'permanent_flagship' => false,
                ],
            ];

            if (!function_exists('sodium_crypto_sign_verify_detached') || !function_exists('sodium_crypto_aead_chacha20poly1305_ietf_decrypt')) {
                self::$integrityValid = false;
                self::$decodedSchedule = $failClosed;
                return self::$decodedSchedule;
            }

            try {
                $pubKey = base64_decode(self::PUBLIC_KEY_B64, true);
                $sig = base64_decode(self::ED25519_SIG_B64, true);
                $cipherRaw = base64_decode(self::ENCRYPTED_BLOB_B64, true);
                $keyRaw = base64_decode(self::CIPHER_KEY_B64, true);
                $nonceRaw = base64_decode(self::CIPHER_NONCE_B64, true);

                if (
                    $pubKey === false || $sig === false || $cipherRaw === false ||
                    $keyRaw === false || $nonceRaw === false ||
                    strlen($sig) !== SODIUM_CRYPTO_SIGN_BYTES
                ) {
                    self::$integrityValid = false;
                    self::$decodedSchedule = $failClosed;
                    return self::$decodedSchedule;
                }

                $signedMessage = self::SCHEDULE_VERSION . '|' . self::ENCRYPTED_BLOB_B64;
                if (!sodium_crypto_sign_verify_detached($sig, $signedMessage, $pubKey)) {
                    self::$integrityValid = false;
                    self::$decodedSchedule = $failClosed;
                    return self::$decodedSchedule;
                }

                $plain = sodium_crypto_aead_chacha20poly1305_ietf_decrypt($cipherRaw, self::AAD, $nonceRaw, $keyRaw);
                if (!is_string($plain) || !json_validate($plain)) {
                    self::$integrityValid = false;
                    self::$decodedSchedule = $failClosed;
                    return self::$decodedSchedule;
                }

                $decoded = json_decode($plain, true);
                if (!is_array($decoded) || empty($decoded)) {
                    self::$integrityValid = false;
                    self::$decodedSchedule = $failClosed;
                    return self::$decodedSchedule;
                }

                self::$integrityValid = true;
                self::$decodedSchedule = $decoded;
                return self::$decodedSchedule;
            } catch (\Throwable $e) {
                self::$integrityValid = false;
                self::$decodedSchedule = $failClosed;
                return self::$decodedSchedule;
            }
        }

        public static function isIntegrityValid(): bool
        {
            self::loadSchedule();
            return self::$integrityValid;
        }

        private static function isRuleUnlocked(string|null $rule, int $utcTs): bool
        {
            if ($rule === null) {
                return false;
            }
            if ($rule === 'always') {
                return true;
            }
            $unlockTs = strtotime($rule . ' 00:00:00 UTC');
            if ($unlockTs === false) {
                return false;
            }
            return $utcTs >= $unlockTs;
        }

        public static function evaluate(
            string $tier,
            bool $paxStudioOverride = false,
            bool $prereleaseOverride = false,
            ?int $utcTimestamp = null
        ): array {
            $schedule = self::loadSchedule();
            $normalizedTier = in_array($tier, ['basic', 'executive', 'flagship'], true) ? $tier : 'basic';
            $nowTs = ($utcTimestamp !== null && $utcTimestamp > 0) ? $utcTimestamp : time();

            $flags = [];
            foreach ($schedule as $key => $meta) {
                $rule = $meta[$normalizedTier] ?? null;
                $flags[$key] = self::isRuleUnlocked($rule, $nowTs);
            }

            if (self::$integrityValid) {
                if ($paxStudioOverride) {
                    $flags['pax_studio'] = true;
                }
                if ($prereleaseOverride) {
                    $flags['prerelease_updates'] = true;
                }
            }

            return $flags;
        }

        public static function getTimetable(string $currentTier = 'basic', ?int $utcTimestamp = null): array
        {
            $schedule = self::loadSchedule();
            $normalizedTier = in_array($currentTier, ['basic', 'executive', 'flagship'], true) ? $currentTier : 'basic';
            $nowTs = ($utcTimestamp !== null && $utcTimestamp > 0) ? $utcTimestamp : time();

            $rows = [];
            foreach ($schedule as $key => $meta) {
                $rule = $meta[$normalizedTier] ?? null;
                $unlocked = self::isRuleUnlocked($rule, $nowTs);

                if ($unlocked) {
                    $status = 'Unlocked';
                } elseif (is_string($rule) && $rule !== 'always') {
                    $status = 'Auto-Unlocks ' . date('M j, Y', (int) strtotime($rule . ' 00:00:00 UTC'));
                } elseif (!empty($meta['permanent_flagship'])) {
                    $status = 'Permanent Tier 3 Flagship';
                } else {
                    $status = 'Executive / Flagship Only';
                }

                $fmt = static function (string|null $r): string {
                    if ($r === null) {
                        return '—';
                    }
                    if ($r === 'always') {
                        return 'Included';
                    }
                    return date('M j, Y', (int) strtotime($r . ' 00:00:00 UTC'));
                };

                $rows[] = [
                    'key'                => $key,
                    'label'              => $meta['label'],
                    'unlocked'           => $unlocked,
                    'status'             => $status,
                    'basic_col'          => $fmt($meta['basic']),
                    'executive_col'      => $fmt($meta['executive']),
                    'flagship_col'       => $fmt($meta['flagship']),
                    'permanent_flagship' => (bool) $meta['permanent_flagship'],
                ];
            }

            return $rows;
        }
    }
}
