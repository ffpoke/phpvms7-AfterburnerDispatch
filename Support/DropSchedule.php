<?php

declare(strict_types=1);

namespace Modules\AfterburnerDispatch\Support;

if (!class_exists(DropSchedule::class, false)) {
    final class DropSchedule
    {
        public const string SCHEDULE_VERSION = '2026.11.25-v3.2.0';

        private const string PUBLIC_KEY_B64 = 'MwG16GpOXANYFMeNeV/IJr9eH3YDPASAo8QdkXaFsu0=';
        private const string AAD = 'RR-DROPSCHEDULE-2026.11.25-v3.2.0';
        private const string CIPHER_KEY_B64 = 'DiQ22gOTInd+i6Fl+1312HmdD3cs96M6W9McAuO/9oI=';
        private const string CIPHER_NONCE_B64 = '0Pg+igQ5y/QyXGgH';
        private const string ENCRYPTED_BLOB_B64 = 'LyvAiMLODf+NAZnzV1+14yMcA8USB1jlhzYeBKFqwExrhRNIY8eL5lB2Or9aecI8eLSdz6tvnbh3Qzq2SDhEnB8X7PsUMwXlG8wNcVltIjk1Mt0SD3D5WWYzUVnx+M4XC6Lg91l8kLxfeTF2o0fvZ99t3IIzy78Skrs/Toudr/fu5/bmpAx2yxr853COGoVESKWwbA+cq4wntPI234xhPWIJSD5LXrn9IQcaSgnvOTTX4ny1kwv4R6SQh9KkdSxQ0W8ig/piQeqQOgiIBS54+ctTfhQlkoI5nYe0ZTFCTcXk4mkjXT41p8LN5Vonr7XU3oJI4b5fPNTRLNYIem1GGOI2bh3AexqoHLzle3ZVZ2JVrGZM2dsehUB7Yxfql16ci/wuloZscnqaHFKVMG75OVqfiq0MqS0Vx56Y/xvcE7gH64F6TnKA9Ldtyu5XpGkSGuRnWYofadaNORndulyt1MJehDeXb4t5a71l9FjBRlgF/3P5i25594jYcVbTnMOuxV1el5kDZAO5RJk7tkBXXtTYj7K+ei5UHOS7S/o3J2Sj5CxMYXvuRFnI6MqVsJGx2wPWpKimekjdfiW2eMrEuLpOocVLH9VaC9zGoJ2KfpFY9aoRZdTBgAcSqczWZ24vXtk+qRS037SDBNEGHaX7i/slWTsFZnp9yK87mOUFklSNRA5MJUXN3CBT/3imakSejaPm1YiezjMt0cJT2HJYsOeSj+Zl3d8kT36dTgt3B6fKk0aQII5IiJSmxEyjW1/LaxW0MXAoXPyVKd5AwjFyRmOVskt0ROb/fb6bmW087OMIYGGkgVzrM0ucwI2BSNqyUQrEz7RYTgu8HPpk1C5bfGx8N1vldIc83LFgD6Fr7hXjuJnDV+pMPyi0NzlOrjDDQpjMpuqyOOJDCmBzEYrET8b1UokZAXCIgVI8UKbzE5/O3tA5cQpBgVYsNQXLii/2fKR30AgpK3cQsu/q3WmP4AeBn96yhIiHsKqn9wXXhISyswSdNjku6/mUCHHst0zxB6HbVxeh8Ojx97QqBfKrZwfvWUQWXk2bmxDVJqPlINw4I4xl/Wm+mnCENSnI6zky1a+jnaz3SKP9oRB6E6tMmN+dW6Cl32To/ljDgjtCUVWwSZSJeR+mcVz5DCqm7y6Xx9D8DeaNacgGiaP2U7Rzi985UGevfZ0+snnZuLgmZaQJvNc36aNNffBOEZvXBZYJijT39bqnb/X4bSXp5scg1Jn+qHPFVVPQXnkexKHF76Ja7qIp2oK6qHcBmq5Kq1TGLmvpMsyuhKsPrsKB47OX85RTqSj/bk66HAbeAb/YTOOupVY1L8ie0z8xV2T7BljaSExVO7KfKB2DNUh9pYQ5BxE2+qUE+Y6CYRS/ZvYlZBvEBC+zUuN6ii39ON0MvSQB9W17BS9tj/Tdjz1ybgp81Pm0ZawQkSDaPb1YjbkkXX8IT/j9MvKIFXv7nb7dgN3Jao3tO1ACxTStLGgrT1KXI0/t/AZeWCkyFRp6uVe6UJyWup0Urf9Yt4UNfTDryOGo50N9UHd6kiJTBaAw+3AYz2IRALzw89YXcDBxDktieYtLi16f8dhOzpNJCTpeMU8BGyFYmuT0JgnHKg8ErxCU0FswV7F/I0lvYfSQ3vnpMx859/NH4cb7b0w3rxrAGCrVoa6ZGOYGxfCqLZ6dDgFfOX/GW0LBJ2IwLWJalZ/l7L5h+PEpZLNLEGkbRfINvUe0C+nWW9QYCToM37gVf04q2B9wplPPWNHT6cbsIVSCIaqeNpxPkjYd2q0eTjpe1e/mYPZ3SSyUhNUrRaBIg1JHjyNsIDU1xOY1esQKWl1z21F1biW+JhCFwedzbHImbHn40M8S1YWzIGW2XUY3jPEb/Q6EVnyk/7kICG+IGIjDw9C7LT3+jxocCJs8MXR4CW/UNIdlQ2N9M/xgCVhvSO53ASp/3lfspyJVFMpGjC6a4o55oewk1NTv+M/uk/YewE1eQ7j4CcIzVmKzgjP6oBKenFpHtqMRBonCGyRuaXeQLlZwWEmGyCsO/yHuWx/2sPsYxlKmEZQN7L6et2hrtBReEYIdGAPyne+QkOf7Qnw1/3hUmVj96QsnQvkZHq+re9dzyzgfVjw68R5lbzq/mR7ZdetVqGw5g6lVB3X1HPCAKPWdKGBdt8uyhWTmXQ7Kpq7dV1tYXiOHydpvGVzdd1j4DCDA5Y3U9khAP/o4HCAxD+XT4qhIV6S62VMZJprco0rAX7lysJ7NjbP1vltmuD6vytAhnwBR1X0hxgos5t9RJQEvLqgx7oMVic2AUEhwdMrRQu7+7IHQ6lg4XxAqc9b0ulDjlBkROyNkaloGLpDFm5A1JP+iBIvH5QUFGNFkHnH3s/ylwDSxFe3klT4QpTcHp3jfvFXoTs/x5LupDQIuQulZFA35ZMe+GNq9CJmqnBHa1MKNNrnGYApRAYxHKEn4guByNFMKg6vNm+J9A1l+C35W4tCZsLG/NGb3bX2GLw7c2iHi5pcOTH9p1L6gieFFIe3GQm6VM5vGk2KdOllae/HMHYfGYfM6E2HhUeC1Dlu8PKRUQ7AK6WV5ATHHKDeye/BveJnHFnKnqm0Upuq4OPuupANhvR/tHI7WpAPXpT1bAXbdfmocTCY2/vp4gBF5S/+AXCgUj9ZGQEIwKa00gYVDrFzo4PxyG0dGGknU4u+MCjSvJqvq8/h8e/+7CSPVOhSMmDAgN0pCLD0gtDWox5OnWrTZ/uZRsCTX4Yx1iJ4Cv5CVq7ApdfdQlRfkHw4t9gUCoHkrK0aifdkVnj2+1/VFdGN3YqmkbyrQg0gd8U6CP9Nq4YS0UIylqJ4GLOpOVbUavuL0AxT/AzjdX1+c39xR0B1BzvYyl9pwgaOADnSOVQ3vu/F/Wt/ETk2GnpFiWptJKvZSPMBgCzW0WqNZmMrbviG8TM+xzj/JkgpkLl5+W/041I3OvzpoMJ0tVIZW3hQKbvXimaWPGpc/AOjllNheoAYUV1d8Har3maueA/tE0ez+aC6M4gvfGQqkkcmeVbo7dWFx3L+DIIiav8olmyQIuRkQVMz189pQADv2uhd3/tB8HGx2lF/AfkXrwvmU502Ktb6hiy2kAhWu1oAHN47QhchE6pHJMaocgZV/Q7UMefx+BzBsfMtxfDzFjLYxNgxnlzEldTlJ+kTQoqDpJAD8dbzvtl29RNVxQ69zXHRDX3AiTzZsR4Ud0MppMUEnwjckQAmr2TgzStUmPoI+B7JosFc6+HcQ2klJkf7H3n0ItOOrdviSIL+i3jLGguqg1nIRkp2wyRl6UHCdvaj0DUDl0uVb0F30p5mAC+hX+NOufcNL/GbXN3/IpKZaXYuCAa7dCUXn7eeI4HTYMqutJDovIyV/sJHJttMbxFBejM0meQvdkRTp0gbL11vwTN2gKH7hbfKYPthxnVbzau5jCpy5QUF/x5GMRUJCWfNkJ/xK6D2j1fxrSUdiZ8xLTJio9YPU0agmEnByz/7HkbPnFXHRVRr1uFmuIDFkB7Kh78RP6yiOjcTiubKG63RAZoLa4hC0KYy5Vzu2BasZQuTYdtJ2UJUSgw9fyr6Che4zDa8jzgM6uV9W0Yf7rSSMoQGOPoj3K5MEXOQPJRp7cpvEjj3KAwtinlMdSBOaG3SyHjntXFCxr78EPcoA5Z70NIZpdbOiOkAoJomSFgYU2RUw1h1P6GBIvFbT30XVVJmAxIO5ojnTac34TQIM1eKCnWg/ICEo2DwXxLYS316sYZFaQ+TqG4LGmaiWx3LQl1VP89FqR4hTq1aNlScnv6aAd76kye+Kj6xCqTdSXKevs4yIHFhYM9PKFjOCBMA8hvwbh1nV2s+ihvnrRpVnxKeVUvI2LMpjjW504wiMA6mtXYtUBe5P6IYVxE1N2MiB5V6lWN9udUlvLu39dwT4JnLaWyKxwPXXfWPIunUXZnuAmKR3vcBvEz9SwHynK2qvSE6bVbRK+Bx253o7EHYZsupJkHYDF7JCbTKoknZdYKnIaKS3+J3Tn36WD9aH6KhWkHg8/1MeljuBaLsOLahy7tWQ9Jue4dfVSPOZC5dTMvw08bCfOKOHuvuN4O8y5ubyR9y1gul0fsS5IPK5bXewTk23BXdF/KrVxqeYFRxxllhbIff43qnhHCgH0XXKwHfdB6CWXn4aAtSMvpm64/AuvJzsarnyA7rFoy0NMxAS9dP/B3LsebmVh5uPWO4SZjs4Lfsu1c6PL4rfL+4py+4z7oYMrQH83/VkcArDTqrQkydxWmOF9JOMcdJptOAjT/jFl8H8uB/RY6MQ4tCEKbaCTt+u31HOlbG+xGecjgmurVT+CTdm8nSKPd6CBnA3fxyWabrwUP+knrvMaJKt45DVolU0lB10jWuS2b1lYNFRrhL1/k1oxmBeOGzNlgrhwidRvCRiYPUl43qjo4ZdHJR9RD8dtzfixIvFTo6dOWSMbg6wEted1CJD67XMar3MBIjQLmbVRuN/bjPB8DZ9xXZdDUfxPClPEhCF8s5icH6xEufHoVnb7+AztTbUgGDZNF+2CWs7Rv1fAk3BkHxqNXJXJSrfj5Iruz11yznNx/Br44EZkn9p8Ht+5P9dgArTe7LSN5kTtyBpfboxK9rdYyUntaHISYyglLUpDA5aDZhHS4d0ysgsOx4aZMGYeiLunTgWum0RIn4ZjNy9FRV0cEnWkcfIX2UWpCpzbk9hRUs77XNpOQuxAI6u5ewC0oLKtVr4f1moI9whM+stjc4pkPJGqVnwBAd4hATnSao7A/D0/6G3+AjzoiEokU8LoU/pgIBknUDRUTcHlE+9ORIpWIciTG2AjT0bYRu+Jqvd9dHvzpBTN6QM7v74c4Ny+i+2VY3rgcF1/bMhvnyNG6P62lz3jL7ezHrKf8PuePMOZtskYyOE0JCBTZszl/bM3PYHB2CGz+VRH5X9pbZDMSr93paZJoFwJrX1b+ccm6BfFWYEDAbswYPmpJMJGWUQxFGnzyBRFc9zqJUwet9vHUHjsvxV2EhIVKp9ECHxrIBhd0AFUCbm8kbhXjlmF7v3AzuG6YPotGZegZrF197tGFSPo+9lAn7puJlnpiW4+e2iDKziQ16OuBX/F+RKdjEiwH4LwSyLiesCVAgEQN6anevSblneY7TiC6CPSIk/2T3um1jKAH8TyC5jBDJlEoFyyoIVFN9PW1PBusd8/qDBPFv2JrbB4O5Alu+4TfG2+oBygVBz9XMi5MrEXimk5gRnRzJJMHTi0/lsaBAFsCIXVm9xL5lo8A+TpLpxK3HDDNcUAbk2Kyjf5QENvGf/ZYO1xaeY87ZjkEpYr8RqTqU5T+P7rleOTV8poYtzQ5P4Si8CPH+fa4vcvbAXgTkTGeYRcHAgQJIC8gQeemSBgAqOzjTo34RnAzdOgxO9W2D8rlUaGYzmuDouowvlyArdYgGipY8ZnQH0beiDVlJaRGW8+MqWa/EeBRlWG94Jd2gqMw45lvIS/stmUydKNyg5kDDuqJ4vlRJuI4At2wKTab7Mgu8ptl+vWCtdDCb7awWz5pCLf5d3lkY7KNzsmoBJ+eNjnJjTc96LiVFfUcOkxKXcp1ybCMi6iMJCKcmkdahpRzWmx4XnQ+mQwcXB/g3ghPNO0cBm3MgxNkz9l3cnbqmBoUt86hhF58OKF65dWBYmLr7YVHftkT/hJUOXhCLO/oAZzXJ1ddZHB8cwVr91zRInKVbY6Ftbg+Poz3wiLrW0nzup4cYaQIvzBi1aSdaJSZ9c1VJbSCLqZwBTzdhTLk29plxUQVdGen5YWE/UHS9MPZO9CofPxxSVW7zt9wmSmSPq3UhZWADWu6AD99fR/iVT+oxFzaYMAPfeuob8uaImodsk4Qwkvpl8Y86Yq6f6fmg0H2o6Db+viASxiqSdZYR1mLEZdQ79burF07TL5+EDiyuRf4V5/Nyr44FJSUBUg5nVrw4dtAJEyZ6qDOGVOd/10Lj3hGVfyWZTjidzlIn0Fz20E4qdSlhndRbWnubvWmlVEwrW0AjVLVMaAR/QzFRXfcsV6JoBcJzLPT1F9u5nww3e5En2xMTdDjfUGMCU7TRkybTR3qj0AQUDrKg7ssp33O82C7icijo8K8XCALB1OyIc3ylBhal6d9AwLxA3mUAqJbGh8qBVMZM6/wfVUxRUNBJZ1VAtIZ/DqHyuDpdgZzxSG9Pnkgrgp04B8wBovdspRUxv0KnPV1bgiDP9MEkf/R0VD3dwhZTG+LBBlZA=';
        private const string ED25519_SIG_B64 = 'UySa4BaHMzrSd4Iec2P2N5ZP965avfo7abDpaZ/J1+0HQZ1vMhHYXRxFq0WPPefyiE1+mbzpMXrHxofAQsV0Aw==';

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
