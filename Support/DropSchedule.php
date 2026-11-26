<?php

declare(strict_types=1);

namespace Modules\AfterburnerDispatch\Support;

if (!class_exists(DropSchedule::class, false)) {
    final class DropSchedule
    {
        public const string SCHEDULE_VERSION = '2026.11.26-v4.0.0';

        private const string PUBLIC_KEY_B64 = 'MwG16GpOXANYFMeNeV/IJr9eH3YDPASAo8QdkXaFsu0=';
        private const string AAD = 'RR-DROPSCHEDULE-2026.11.26-v4.0.0';
        private const string CIPHER_KEY_B64 = 'aw681FgABqDr1BgnTtuazAijgOFmgahk+wUD1UMPhYA=';
        private const string CIPHER_NONCE_B64 = 'B4EffwhWbYblDsA7';
        private const string ENCRYPTED_BLOB_B64 = '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';
        private const string ED25519_SIG_B64 = 'ylWnx62OK2E29vT97ir/ZbIqhWvSzmTZOLd0HYaBJ8N3ekFMV4HZuWIMx0j7u7g1P5OQ4FNEIRU9xnhR8fhDDg==';

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
                'system_self_test' => [
                    'label'              => '1-Click System Self-Test & Cryptographic Health Diagnostics',
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
