<?php

declare(strict_types=1);

namespace Modules\RouteRequest\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\RouteRequest\Models\RouteRequest;

if (!class_exists(DiscordNotifier::class, false)) {
    class DiscordNotifier
    {
        public static function isUnlocked(): bool
        {
            if (!class_exists(DrmEntitlements::class, false)) {
                require_once __DIR__ . '/DrmEntitlements.php';
            }
            $ent = DrmEntitlements::current(false);
            $tier = (string) ($ent['tier'] ?? 'basic');
            return $tier !== 'basic' || !empty($ent['features']['fa_auto_matcher']);
        }

        public static function sendEmbed(string $webhookUrl, array $embed): bool
        {
            $url = trim($webhookUrl);
            if ($url === '' || !str_starts_with($url, 'https://')) {
                return false;
            }

            try {
                $response = Http::timeout(8)->post($url, [
                    'username' => 'RouteRequest Dispatch',
                    'embeds'   => [$embed],
                ]);
                return $response->successful();
            } catch (\Throwable $e) {
                Log::warning('RouteRequest Discord Webhook Error: ' . $e->getMessage());
                return false;
            }
        }

        /**
         * Posts a rich embed to `#staff-dispatch` when a pilot submits a new Route Request.
         */
        public static function notifyStaffNewRequest(RouteRequest $req): void
        {
            if (!self::isUnlocked()) {
                return;
            }
            if (!class_exists(ClientSettings::class, false)) {
                require_once __DIR__ . '/ClientSettings.php';
            }
            $settings = ClientSettings::all();
            $webhook = trim($settings['discord_staff_webhook'] ?? '');
            if ($webhook === '') {
                return;
            }

            $pilotName = $req->user ? ($req->user->ident . ' — ' . $req->user->name_private) : ('Pilot #' . $req->user_id);
            $arr = $req->arr_airport_id ?: ($req->multiple_destinations ? 'MULTIPLE (ANY)' : 'ANY / OPEN');
            $tripType = ($req->request_return ? 'Round-Trip' : 'One-Way') . ($req->multiple_destinations ? ' + Multi-Dest' : '');

            self::sendEmbed($webhook, [
                'title'       => '✈️ New Pilot Route Request #' . $req->id,
                'url'         => url('/admin/routerequest?open_gen=' . $req->id),
                'color'       => 0x0284c7,
                'fields'      => [
                    ['name' => 'Pilot', 'value' => $pilotName, 'inline' => true],
                    ['name' => 'Route', 'value' => $req->dep_airport_id . ' ➔ ' . $arr, 'inline' => true],
                    ['name' => 'Trip Type', 'value' => $tripType, 'inline' => true],
                    ['name' => 'Type Rating', 'value' => (string) $req->aircraft_type, 'inline' => true],
                    ['name' => 'Real-World / Charter', 'value' => (string) $req->realworld_airline, 'inline' => true],
                    ['name' => 'Notes', 'value' => $req->notes ? substr((string) $req->notes, 0, 500) : 'None', 'inline' => false],
                ],
                'footer'      => ['text' => 'RouteRequest v2.6.0 Afterburner Edition • Click title to open Dispatch Generator'],
                'timestamp'   => now()->toIso8601String(),
            ]);
        }

        /**
         * Posts a rich embed to `#new-schedules` when staff completes a Route Request and publishes flights.
         */
        public static function notifyPublicCompleted(RouteRequest $req, string $flightsSummary): void
        {
            if (!self::isUnlocked()) {
                return;
            }
            if (!class_exists(ClientSettings::class, false)) {
                require_once __DIR__ . '/ClientSettings.php';
            }
            $settings = ClientSettings::all();
            $webhook = trim($settings['discord_public_webhook'] ?? '');
            if ($webhook === '') {
                return;
            }

            $pilotName = $req->user ? ($req->user->ident . ' — ' . $req->user->name_private) : ('Pilot #' . $req->user_id);

            self::sendEmbed($webhook, [
                'title'       => '🛫 New Flight Schedule(s) Published!',
                'url'         => url('/routerequest'),
                'color'       => 0x16a34a,
                'description' => '**' . $flightsSummary . '**',
                'fields'      => [
                    ['name' => 'Requested By', 'value' => $pilotName, 'inline' => true],
                    ['name' => 'Type Rating', 'value' => (string) $req->aircraft_type, 'inline' => true],
                    ['name' => 'Counterpart', 'value' => (string) $req->realworld_airline, 'inline' => true],
                ],
                'footer'      => ['text' => 'RouteRequest v2.6.0 Afterburner Edition • Ready to Bid & Fly'],
                'timestamp'   => now()->toIso8601String(),
            ]);
        }

        public static function testWebhooks(): array
        {
            if (!self::isUnlocked()) {
                return ['success' => false, 'message' => 'Discord Webhook Notifications require Tier 2 (Executive Dispatch) or Tier 3 (Flagship Edition).'];
            }
            if (!class_exists(ClientSettings::class, false)) {
                require_once __DIR__ . '/ClientSettings.php';
            }
            $s = ClientSettings::all();
            $staffUrl = trim($s['discord_staff_webhook'] ?? '');
            $pubUrl   = trim($s['discord_public_webhook'] ?? '');

            if ($staffUrl === '' && $pubUrl === '') {
                return ['success' => false, 'message' => 'Please enter and save at least one Discord Webhook URL first.'];
            }

            $results = [];
            if ($staffUrl !== '') {
                $ok = self::sendEmbed($staffUrl, [
                    'title'       => '✅ RouteRequest Staff Webhook Test',
                    'description' => 'Your `#staff-dispatch` webhook is connected and ready for new pilot route requests!',
                    'color'       => 0x0284c7,
                    'timestamp'   => now()->toIso8601String(),
                ]);
                $results[] = 'Staff Webhook: ' . ($ok ? 'OK' : 'Failed');
            }
            if ($pubUrl !== '') {
                $ok = self::sendEmbed($pubUrl, [
                    'title'       => '✅ RouteRequest Public Schedules Webhook Test',
                    'description' => 'Your `#new-schedules` webhook is connected and ready to announce newly built routes!',
                    'color'       => 0x16a34a,
                    'timestamp'   => now()->toIso8601String(),
                ]);
                $results[] = 'Public Webhook: ' . ($ok ? 'OK' : 'Failed');
            }

            return ['success' => true, 'message' => 'Discord Webhook Test Results — ' . implode(' | ', $results)];
        }
    }
}
