<?php

declare(strict_types=1);

namespace Modules\AfterburnerDispatch\Support;

if (!class_exists(DropSchedule::class, false)) {
    final class DropSchedule
    {
        public const string SCHEDULE_VERSION = '2026.11.26-v4.1.1';

        private const string PUBLIC_KEY_B64 = 'MwG16GpOXANYFMeNeV/IJr9eH3YDPASAo8QdkXaFsu0=';
        private const string AAD = 'AB-DROPSCHEDULE-2026.11.26-v4.1.1';
        private const string CIPHER_KEY_B64 = 'aw681FgABqDr1BgnTtuazAijgOFmgahk+wUD1UMPhYA=';
        private const string CIPHER_NONCE_B64 = '2Lkszp1ygMRBGYdE';
        private const string ENCRYPTED_BLOB_B64 = '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';
        private const string ED25519_SIG_B64 = 'FCGo0rXv1+gtN1nYIAPIdPZfYbCCZ73my2v8yyGimsPUI0otnmX2ay3CqGZ6uVyXcwuV3AdT2ueaa8Nd9a/bAQ==';

        public const array FEATURE_FAMILIES = [
            'range_guardrail' => [
                'limited' => ['range_guardrail_basic'],
                'full'    => ['range_guardrail_basic', 'range_guardrail_live_check'],
            ],
            'subfleet_sync' => [
                'limited' => ['subfleet_sync_basic'],
                'full'    => ['subfleet_sync_basic', 'subfleet_sync_multi', 'subfleet_sync_power'],
            ],
            'subfleet_creator' => [
                'limited' => ['subfleet_creator_single'],
                'full'    => ['subfleet_creator_single', 'subfleet_creator_bulk', 'simbrief_airframe_sync'],
            ],
        ];

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

        
        /**
         * Applies Ed25519-verified server feature overrides and global maintenance locks.
         *
         * Precedence order:
         *   1. Global Force Lock (Everyone — e.g. Under Update / Bug Fix)
         *   2. Per-VA Force Lock (single license key)
         *   3. max(DropSchedule, Per-VA Manual Unlock [limited | full]) — auto-upgrades
         *      Limited manual unlocks to Full when the key's scheduled Full date arrives.
         */
        public static function applySignedOverrides(
            array $bakedFeatures,
            array $signedOverrides = [],
            array $signedGlobalLocks = [],
            array $serverFeatures = []
        ): array {
            $effective = [];
            $meta = [];

            // Start with baseline schedule & server flags
            foreach ($bakedFeatures as $fKey => $bakedAllowed) {
                $srvAllowed = array_key_exists($fKey, $serverFeatures) ? (bool) $serverFeatures[$fKey] : (bool) $bakedAllowed;
                $effective[$fKey] = ((bool) $bakedAllowed && $srvAllowed);
            }

            // Build reverse lookup: feature_key -> family_key
            $featToFamily = [];
            foreach (self::FEATURE_FAMILIES as $famKey => $levels) {
                foreach (array_unique(array_merge($levels['limited'], $levels['full'])) as $fk) {
                    $featToFamily[$fk] = $famKey;
                }
            }

            // Helper to normalize override entry (supports string or structured object)
            $parseEntry = static function (mixed $raw): array {
                if (is_string($raw)) {
                    return ['state' => strtolower(trim($raw)), 'reason' => ''];
                }
                if (is_array($raw)) {
                    return [
                        'state'  => strtolower(trim((string) ($raw['state'] ?? $raw['level'] ?? ''))),
                        'reason' => trim((string) ($raw['reason'] ?? '')),
                    ];
                }
                return ['state' => '', 'reason' => ''];
            };

            // Layer 3: Per-VA additive manual unlocks (limited / full) — max(Schedule, Override)
            foreach ($signedOverrides as $ovKey => $rawOv) {
                $entry = $parseEntry($rawOv);
                $state = $entry['state'];
                if (!in_array($state, ['limited', 'full', 'unlock'], true)) {
                    continue;
                }
                if (isset(self::FEATURE_FAMILIES[$ovKey])) {
                    $unlockKeys = ($state === 'limited')
                        ? self::FEATURE_FAMILIES[$ovKey]['limited']
                        : self::FEATURE_FAMILIES[$ovKey]['full'];
                    foreach ($unlockKeys as $fk) {
                        if (!array_key_exists($fk, $effective)) {
                            continue;
                        }
                        if (!$effective[$fk]) {
                            $effective[$fk] = true;
                            $meta[$fk] = [
                                'status' => 'override_unlock',
                                'level'  => $state,
                                'reason' => $entry['reason'],
                            ];
                        }
                    }
                } elseif (array_key_exists($ovKey, $effective)) {
                    if (!$effective[$ovKey]) {
                        $effective[$ovKey] = true;
                        $meta[$ovKey] = [
                            'status' => 'override_unlock',
                            'level'  => $state,
                            'reason' => $entry['reason'],
                        ];
                    }
                }
            }

            // Layer 2: Per-VA Force Lock (overrides schedule & unlocks for this VA only)
            foreach ($signedOverrides as $ovKey => $rawOv) {
                $entry = $parseEntry($rawOv);
                if ($entry['state'] !== 'force_lock') {
                    continue;
                }
                $reason = $entry['reason'] !== '' ? $entry['reason'] : 'Locked by server administrator';
                $lockKeys = isset(self::FEATURE_FAMILIES[$ovKey])
                    ? self::FEATURE_FAMILIES[$ovKey]['full']
                    : (array_key_exists($ovKey, $effective) ? [$ovKey] : []);
                foreach ($lockKeys as $fk) {
                    $effective[$fk] = false;
                    $meta[$fk] = [
                        'status' => 'va_lock',
                        'scope'  => 'va',
                        'reason' => $reason,
                    ];
                }
            }

            // Layer 1 (Highest): Global Force Lock (Everyone — e.g. Under Update / Bug Fix)
            foreach ($signedGlobalLocks as $lockKey => $rawLock) {
                $reason = '';
                $active = true;
                if (is_bool($rawLock)) {
                    $active = $rawLock;
                } elseif (is_string($rawLock)) {
                    $reason = trim($rawLock);
                } elseif (is_array($rawLock)) {
                    $active = array_key_exists('active', $rawLock) ? (bool) $rawLock['active'] : true;
                    $reason = trim((string) ($rawLock['reason'] ?? ''));
                }
                if (!$active) {
                    continue;
                }
                $reason = $reason !== '' ? $reason : 'Under Update / Maintenance';
                $lockKeys = isset(self::FEATURE_FAMILIES[$lockKey])
                    ? self::FEATURE_FAMILIES[$lockKey]['full']
                    : (array_key_exists($lockKey, $effective) ? [$lockKey] : []);
                foreach ($lockKeys as $fk) {
                    $effective[$fk] = false;
                    $meta[$fk] = [
                        'status' => 'global_lock',
                        'scope'  => 'global',
                        'reason' => $reason,
                    ];
                }
            }

            return [
                'features'      => $effective,
                'override_meta' => $meta,
            ];
        }

        public static function getTimetable(string $currentTier = 'basic', ?int $utcTimestamp = null, array $overrideMeta = []): array
        {
            $schedule = self::loadSchedule();
            $normalizedTier = in_array($currentTier, ['basic', 'executive', 'flagship'], true) ? $currentTier : 'basic';
            $nowTs = ($utcTimestamp !== null && $utcTimestamp > 0) ? $utcTimestamp : time();

            $rows = [];
            foreach ($schedule as $key => $meta) {
                $rule = $meta[$normalizedTier] ?? null;
                $unlocked = self::isRuleUnlocked($rule, $nowTs);
                $ov = is_array($overrideMeta[$key] ?? null) ? $overrideMeta[$key] : null;
                $ovStatus = (string) ($ov['status'] ?? '');
                $ovReason = (string) ($ov['reason'] ?? '');

                if ($ovStatus === 'global_lock') {
                    $unlocked = false;
                    $status = 'Temporarily Locked — ' . ($ovReason !== '' ? $ovReason : 'Under Update');
                } elseif ($ovStatus === 'va_lock') {
                    $unlocked = false;
                    $status = 'Locked (Server Override)';
                } elseif ($ovStatus === 'override_unlock') {
                    $unlocked = true;
                    $status = 'Unlocked (Server Override)';
                } elseif ($unlocked) {
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
                    'override_status'    => $ovStatus,
                    'override_reason'    => $ovReason,
                ];
            }

            return $rows;
        }
    }
}
