<?php

declare(strict_types=1);

namespace Modules\RouteRequest\Support;

if (!class_exists(DropSchedule::class, false)) {
    /**
     * ED25519-SIGNED & CHACHA20-POLY1305 ENCRYPTED FEATURE DROP SCHEDULE (v3.2.0)
     *
     * The feature timetable is encrypted with RFC 8439 ChaCha20-Poly1305 and
     * cryptographically signed with Ed25519. Any modification to the encrypted
     * payload or schedule version invalidates the signature and fails closed
     * to Tier 1 Basic defaults.
     */
    final class DropSchedule
    {
        public const string SCHEDULE_VERSION = '2026.11.22-v3.2.0';

        private const string PUBLIC_KEY_B64 = 'MwG16GpOXANYFMeNeV/IJr9eH3YDPASAo8QdkXaFsu0=';
        private const string AAD = 'RR-DROPSCHEDULE-2026.11.22-v3.2.0';
        private const string CIPHER_KEY_B64 = 'eZaYNtyRnZDcLt+VrQlZvIE6Rf1qvHMqkiTex50kZHg=';
        private const string CIPHER_NONCE_B64 = 'qOK5GTlzsFCr5qBz';
        private const string ENCRYPTED_BLOB_B64 = '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';
        private const string ED25519_SIG_B64 = 'LQVENbSny2qeRlJl+MfY8gQc12ekMMniCBb3pHCOz6DRzNtokxL98j3vm100BgTVZeT0qdi7WP8BRU4A8TJ1BA==';

        private static ?array $decodedSchedule = null;
        private static bool $integrityValid = false;

        /**
         * Verifies Ed25519 signature and decrypts the timetable in memory.
         * Fails closed to minimal Tier 1 Basic fallback if tampered.
         */
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
