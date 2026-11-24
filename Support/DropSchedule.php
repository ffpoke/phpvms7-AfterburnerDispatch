<?php

declare(strict_types=1);

namespace Modules\RouteRequest\Support;

if (!class_exists(DropSchedule::class, false)) {
    final class DropSchedule
    {
        public const string SCHEDULE_VERSION = '2026.11.24-v3.2.0';

        private const string PUBLIC_KEY_B64 = 'MwG16GpOXANYFMeNeV/IJr9eH3YDPASAo8QdkXaFsu0=';
        private const string AAD = 'RR-DROPSCHEDULE-2026.11.24-v3.2.0';
        private const string CIPHER_KEY_B64 = 'WpEsulCQPePUnysWwhUtJ9Jg/Ql/1wU5mUyG8oXtTnU=';
        private const string CIPHER_NONCE_B64 = 'olg+Z80U/k4L0L6i';
        private const string ENCRYPTED_BLOB_B64 = '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';
        private const string ED25519_SIG_B64 = '9vuPOuSOP4+Z2w3IKceUcoHAGL3QXIUB84UaS5bFK4qBoN1dq29sVAzzR2xfpKjyeRJraqvFj4kiciomhxq2CQ==';

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
