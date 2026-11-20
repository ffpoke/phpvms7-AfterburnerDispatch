<?php

declare(strict_types=1);

namespace Modules\RouteRequest\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\RouteRequest\Models\RouteRequest;
use Nwidart\Modules\Facades\Module;

if (!class_exists(DrmEntitlements::class, false)) {
    class DrmEntitlements
    {
        // Ed25519 Public Key matching RouteRequestServer v1.6.0
        private const string ED25519_PUBLIC_KEY_B64 = 'MwG16GpOXANYFMeNeV/IJr9eH3YDPASAo8QdkXaFsu0=';

        /**
         * Verifies the libsodium Ed25519 cryptographic signature on an entitlement payload.
         */
        private static function verifySignedEntitlements(array $data): bool
        {
            if (!function_exists('sodium_crypto_sign_verify_detached')) {
                return true;
            }
            // Allow seamless transition if Master Server hasn't upgraded to v1.6.0 yet
            if (empty($data['signature']) || empty($data['signed_payload'])) {
                return true;
            }
            $pubKey = base64_decode(self::ED25519_PUBLIC_KEY_B64, true);
            $sig = base64_decode((string) $data['signature'], true);
            $payload = base64_decode((string) $data['signed_payload'], true);
            if ($pubKey === false || $sig === false || $payload === false || strlen($sig) !== SODIUM_CRYPTO_SIGN_BYTES) {
                return false;
            }
            if (!sodium_crypto_sign_verify_detached($sig, $payload, $pubKey)) {
                return false;
            }
            if (!json_validate($payload)) {
                return false;
            }
            $decoded = json_decode($payload, true);
            if (!is_array($decoded)) {
                return false;
            }
            // Reject replay tokens older than 15 minutes
            $signedAt = (int) ($decoded['signed_at'] ?? 0);
            if ($signedAt > 0 && abs(time() - $signedAt) > 900) {
                return false;
            }
            // Ensure signed tier matches outer tier
            return ($decoded['tier'] ?? '') === ($data['tier'] ?? '');
        }

        public static function getBaseApi(): string
        {
            $base = rtrim((string) config('routerequest.master_api_url', 'https://crew.flyfantasyair.net/api/routerequest-drm'), '/');
            foreach (['/airlines', '/parse-fa', '/entitlements', '/record-routes', '/claim-free', '/checkout/create', '/storefront'] as $suffix) {
                if (str_ends_with($base, $suffix)) {
                    $base = substr($base, 0, -strlen($suffix));
                }
            }
            return $base;
        }

        public static function getLicenseKey(): string
        {
            if (!class_exists(SchemaHelper::class, false)) {
                require_once __DIR__ . '/SchemaHelper.php';
            }
            SchemaHelper::ensureRouteRequestsColumns();

            try {
                $dbKey = DB::table('routerequest_client_settings')
                    ->where('setting_key', 'license_key')
                    ->value('setting_value');
                if (!empty($dbKey) && trim((string) $dbKey) !== '') {
                    return trim((string) $dbKey);
                }
            } catch (\Throwable $e) {}

            return trim((string) config('routerequest.license_key', env('ROUTEREQUEST_LICENSE_KEY', '')));
        }

        public static function saveLicenseKey(string $key): void
        {
            if (!class_exists(SchemaHelper::class, false)) {
                require_once __DIR__ . '/SchemaHelper.php';
            }
            SchemaHelper::ensureRouteRequestsColumns(true);

            $clean = trim($key);
            try {
                DB::table('routerequest_client_settings')->updateOrInsert(
                    ['setting_key' => 'license_key'],
                    ['setting_value' => $clean, 'updated_at' => now()]
                );
            } catch (\Throwable $e) {}

            try {
                Cache::forget('routerequest_drm_airlines_' . md5($clean));
            } catch (\Throwable $e) {}

            self::current(true);
        }

        public static function getPaxStudioInstalledVersion(): ?string
        {
            $paths = [
                base_path('modules/PaxStudio/module.json'),
                base_path('Modules/PaxStudio/module.json'),
            ];
            foreach ($paths as $p) {
                if (file_exists($p)) {
                    $raw = @file_get_contents($p);
                    if ($raw && json_validate($raw)) {
                        $j = json_decode($raw, true);
                        if (!empty($j['version'])) {
                            return ltrim(trim((string) $j['version']), 'vV');
                        }
                    }
                }
            }
            return null;
        }

        public static function isPaxStudioInstalled(): bool
        {
            try {
                if (class_exists(Module::class)) {
                    $mod = Module::find('PaxStudio');
                    if ($mod && $mod->isEnabled()) {
                        return true;
                    }
                }
                if (class_exists(\Modules\PaxStudio\Providers\PaxStudioServiceProvider::class)) {
                    return true;
                }
            } catch (\Throwable $e) {}
            return false;
        }

        public static function getLocalWeeklyRoutesUsed(): int
        {
            try {
                $since = Carbon::now()->subDays(7);
                $recent = RouteRequest::where('updated_at', '>=', $since)->get();
                $count = 0;
                foreach ($recent as $r) {
                    $count += count($r->getAllGeneratedFlightIds());
                }
                return $count;
            } catch (\Throwable $e) {
                return 0;
            }
        }

        public static function getDefaultStorefront(): array
        {
            return [
                'currency'       => 'USD',
                'square_enabled' => true,
                'paypal_enabled' => true,
                'tier1'          => [
                    'name'        => 'Tier 1: Basic Dispatch Edition',
                    'price_label' => 'FREE',
                    'summary'     => '12 routes/req, 3 round-trip pairs/req, 28 routes/week cap. Includes all bugfixes & trickle-down features from Afterburner Versions.',
                ],
                'tier2'          => [
                    'name'           => 'Tier 2: Executive Dispatch Edition',
                    'monthly_price'  => '2.99',
                    'lifetime_price' => '39.99',
                    'summary'        => '15 routes/req, 4 round-trip pairs/req, Unlimited weekly routes, FlightAware Auto-Matcher, 1-Click Auto-Create, plus Unlimited Afterburner version updates & trickle-down updates from Afterburner Tier 3.',
                ],
                'tier3'          => [
                    'name'           => 'Tier 3: Flagship Edition',
                    'monthly_price'  => '4.99',
                    'lifetime_price' => '59.99',
                    'summary'        => 'All Executive features + PaxStudio v2.25+ Loadsheet Bridge, Unlimited Afterburner version updates, Pre-Release Update Channel, and hand-selected new features from the next version.',
                ],
            ];
        }

        public static function current(bool $forceRefresh = false): array
        {
            $key = self::getLicenseKey();
            $cacheKey = 'routerequest_drm_entitlements_v2_8_' . md5($key ?: 'unlicensed');

            if ($forceRefresh) {
                try {
                    Cache::forget($cacheKey);
                } catch (\Throwable $e) {}
            }

            $localWeeklyUsed = self::getLocalWeeklyRoutesUsed();
            $paxInstalled = self::isPaxStudioInstalled();

            $defaultBasic = [
                'valid'                => ($key !== ''),
                'license_key'          => $key,
                'va_name'              => config('app.name', 'Virtual Airline'),
                'tier'                 => 'basic',
                'tier_label'           => 'Tier 1: Basic Dispatch Edition (Free — Bugfixes & Afterburner Trickle-Down)',
                'billing_cycle'        => 'lifetime',
                'expires_at'           => null,
                'max_routes_per_req'   => 12,
                'max_return_pairs'     => 3,
                'weekly_route_cap'     => 28,
                'weekly_routes_used'   => $localWeeklyUsed,
                'weekly_routes_left'   => max(0, 28 - $localWeeklyUsed),
                'week_reset_at'        => null,
                'paxstudio_installed'  => $paxInstalled,
                'features'             => [
                    'fa_auto_matcher'    => false,
                    'one_click_create'   => false,
                    'pax_studio'         => false,
                    'prerelease_updates' => false,
                ],
                'storefront'           => self::getDefaultStorefront(),
            ];

            if ($key === '') {
                return $defaultBasic;
            }

            $fetchFn = function () use ($key, $defaultBasic, $localWeeklyUsed, $paxInstalled) {
                try {
                    $endpoint = self::getBaseApi() . '/entitlements';
                    $response = Http::timeout(6)->withHeaders([
                        'X-RouteRequest-License' => $key,
                        'X-RouteRequest-Domain'  => url(''),
                    ])->get($endpoint);

                    if ($response->successful() && is_array($response->json()) && !empty($response->json('tier'))) {
                        $data = $response->json();
                        if (!self::verifySignedEntitlements($data)) {
                            return $defaultBasic;
                        }
                        $data['license_key'] = $key;
                        $data['paxstudio_installed'] = $paxInstalled;
                        if (empty($data['storefront'])) {
                            $data['storefront'] = self::getDefaultStorefront();
                        }
                        if (($data['tier'] ?? 'basic') === 'basic') {
                            $serverUsed = (int) ($data['weekly_routes_used'] ?? 0);
                            $used = max($serverUsed, $localWeeklyUsed);
                            $data['weekly_routes_used'] = $used;
                            $data['weekly_routes_left'] = max(0, 28 - $used);
                        }
                        return $data;
                    }
                } catch (\Throwable $e) {}

                return $defaultBasic;
            };

            try {
                return Cache::remember($cacheKey, 600, $fetchFn);
            } catch (\Throwable $e) {
                return $fetchFn();
            }
        }

        public static function claimFreeKey(string $vaName, string $adminEmail): array
        {
            try {
                $endpoint = self::getBaseApi() . '/claim-free';
                $response = Http::timeout(10)->withHeaders([
                    'X-RouteRequest-Domain' => url(''),
                ])->post($endpoint, [
                    'va_name'     => $vaName ?: config('app.name', 'Virtual Airline'),
                    'domain'      => url(''),
                    'admin_email' => $adminEmail,
                ]);

                $json = $response->json();
                if ($response->successful() && !empty($json['license_key'])) {
                    self::saveLicenseKey((string) $json['license_key']);
                    return [
                        'success'     => true,
                        'license_key' => $json['license_key'],
                        'message'     => $json['message'] ?? 'Free Tier 1 (Basic Dispatch Edition) License Key claimed and activated!',
                    ];
                }

                return [
                    'success' => false,
                    'message' => $json['error'] ?? ('Master DRM Server returned HTTP ' . $response->status()),
                ];
            } catch (\Throwable $e) {
                return [
                    'success' => false,
                    'message' => 'Could not connect to Master DRM Server: ' . $e->getMessage(),
                ];
            }
        }

        public static function createCheckoutSession(string $provider, string $tier, string $billingCycle, string $vaName, string $adminEmail, string $returnAdminUrl, string $promoCode = ''): array
        {
            try {
                $endpoint = self::getBaseApi() . '/checkout/create';
                $response = Http::timeout(14)->withHeaders([
                    'X-RouteRequest-Domain'  => url(''),
                    'X-RouteRequest-License' => self::getLicenseKey(),
                ])->post($endpoint, [
                    'provider'            => $provider,
                    'tier'                => $tier,
                    'billing_cycle'       => $billingCycle,
                    'va_name'             => $vaName ?: config('app.name', 'Virtual Airline'),
                    'domain'              => url(''),
                    'admin_email'         => $adminEmail,
                    'return_admin_url'    => $returnAdminUrl,
                    'current_license_key' => self::getLicenseKey(),
                    'promo_code'          => strtoupper(trim($promoCode)),
                ]);

                $json = $response->json();
                if ($response->successful() && !empty($json['checkout_url'])) {
                    return [
                        'success'      => true,
                        'checkout_url' => $json['checkout_url'],
                    ];
                }

                return [
                    'success' => false,
                    'message' => $json['error'] ?? ('Could not initialize ' . ucfirst($provider) . ' checkout (HTTP ' . $response->status() . ').'),
                ];
            } catch (\Throwable $e) {
                return [
                    'success' => false,
                    'message' => 'Checkout connection error: ' . $e->getMessage(),
                ];
            }
        }

        public static function verifyAndRecordRouteCreation(int $routesCount): array
        {
            $ent = self::current(false);
            $tier = $ent['tier'] ?? 'basic';

            if ($tier === 'basic') {
                $used = max((int) ($ent['weekly_routes_used'] ?? 0), self::getLocalWeeklyRoutesUsed());
                $remaining = max(0, 28 - $used);
                if ($routesCount > $remaining) {
                    return [
                        'allowed' => false,
                        'message' => sprintf(
                            'Tier 1 (Basic Dispatch Edition) Weekly Limit Reached: %d/28 routes created this week (%d remaining, attempted to create %d). Upgrade to Tier 2 (Executive Dispatch: $2.99/mo or $39.99 Unlimited) or Tier 3 (Flagship Edition: $4.99/mo or $59.99 Unlimited) in the License & Storefront drawer above!',
                            $used,
                            $remaining,
                            $routesCount
                        ),
                    ];
                }
            }

            $key = self::getLicenseKey();
            if ($key !== '') {
                try {
                    $endpoint = self::getBaseApi() . '/record-routes';
                    $response = Http::timeout(6)->withHeaders([
                        'X-RouteRequest-License' => $key,
                        'X-RouteRequest-Domain'  => url(''),
                    ])->post($endpoint, [
                        'routes_count' => $routesCount,
                    ]);

                    if ($response->status() === 429) {
                        $err = $response->json('error') ?: 'Weekly route limit (28/week) reached on Master DRM Server.';
                        self::current(true);
                        return [
                            'allowed' => false,
                            'message' => $err,
                        ];
                    }
                } catch (\Throwable $e) {}
            }

            self::current(true);

            return [
                'allowed' => true,
                'message' => null,
            ];
        }
    }
}
