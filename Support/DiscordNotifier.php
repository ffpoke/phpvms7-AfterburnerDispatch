<?php

declare(strict_types=1);

namespace Modules\AfterburnerDispatch\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AfterburnerDispatch\Models\RouteRequest;

if (!class_exists(DiscordNotifier::class, false)) {
    class DiscordNotifier
    {
        public static function isUnlocked(): bool
        {
            $ent = DrmEntitlements::current(false);
            $tier = (string) ($ent['tier'] ?? 'basic');
            return $tier !== 'basic' || !empty($ent['features']['fa_auto_matcher']);
        }

        private static function dispatchViaMasterServer(string $webhookUrl, string $eventType, array $payload): bool
        {
            $url = trim($webhookUrl);
            $key = DrmEntitlements::getLicenseKey();
            if ($url === '' || $key === '') {
                return false;
            }

            try {
                $endpoint = DrmEntitlements::getBaseApi() . '/discord/dispatch';
                $res = Http::timeout(10)->withHeaders([
                    'X-RouteRequest-License' => $key,
                    'X-RouteRequest-Domain'  => url(''),
                ])->post($endpoint, array_merge($payload, [
                    'webhook_url' => $url,
                    'event_type'  => $eventType,
                ]));

                return $res->successful() && !empty($res->json('success'));
            } catch (\Throwable $e) {
                Log::warning('RouteRequest Server-Gated Discord Dispatch Error: ' . $e->getMessage());
                return false;
            }
        }

        public static function notifyStaffNewRequest(RouteRequest $req): void
        {
            if (!self::isUnlocked()) {
                return;
            }
            $settings = ClientSettings::all();
            $webhook = trim($settings['discord_staff_webhook'] ?? '');
            if ($webhook === '') {
                return;
            }

            $pilotName = $req->user ? ($req->user->ident . ' — ' . $req->user->name_private) : ('Pilot #' . $req->user_id);
            $arr = $req->arr_airport_id ?: ($req->multiple_destinations ? 'MULTIPLE (ANY)' : 'ANY / OPEN');
            $tripType = ($req->request_return ? 'Round-Trip' : 'One-Way') . ($req->multiple_destinations ? ' + Multi-Dest' : '');

            self::dispatchViaMasterServer($webhook, 'new_request', [
                'request_id'        => (string) $req->id,
                'admin_url'         => url('/admin/afterburner?open_gen=' . $req->id),
                'pilot_name'        => $pilotName,
                'route_pair'        => $req->dep_airport_id . ' ➔ ' . $arr,
                'trip_type'         => $tripType,
                'aircraft_type'     => (string) $req->aircraft_type,
                'realworld_airline' => (string) $req->realworld_airline,
                'notes'             => (string) ($req->notes ?: 'None'),
            ]);
        }

        public static function notifyPublicCompleted(RouteRequest $req, string $flightsSummary): void
        {
            if (!self::isUnlocked()) {
                return;
            }
            $settings = ClientSettings::all();
            $webhook = trim($settings['discord_public_webhook'] ?? '');
            if ($webhook === '') {
                return;
            }

            $pilotName = $req->user ? ($req->user->ident . ' — ' . $req->user->name_private) : ('Pilot #' . $req->user_id);

            self::dispatchViaMasterServer($webhook, 'completed', [
                'frontend_url'      => url('/afterburner'),
                'flights_summary'   => $flightsSummary,
                'pilot_name'        => $pilotName,
                'aircraft_type'     => (string) $req->aircraft_type,
                'realworld_airline' => (string) $req->realworld_airline,
            ]);
        }


        /**
         * Sends a Move Request alert to `#staff-dispatch` via AfterburnerServer.
         */
        public static function notifyStaffMoveRequest(array $info): void
        {
            if (!self::isUnlocked()) {
                return;
            }
            $settings = ClientSettings::all();
            $webhook = trim($settings['discord_staff_webhook'] ?? '');
            if ($webhook === '') {
                return;
            }

            $costStr = sprintf('$%.2f needed vs $%.2f balance', (float) ($info['total_cost'] ?? 0), (float) ($info['pilot_balance'] ?? 0));
            self::dispatchViaMasterServer($webhook, 'new_request', [
                'request_id'        => 'MOVE',
                'admin_url'         => url('/admin/afterburner'),
                'pilot_name'        => (string) ($info['pilot_name'] ?? 'Pilot'),
                'route_pair'        => ($info['pilot_from'] ?? '?') . ' ➔ ' . ($info['to_airport'] ?? '?'),
                'trip_type'         => 'Admin Move Request (Allow / Deny)',
                'aircraft_type'     => (string) ($info['aircraft_reg'] ?? 'Aircraft') . ' from ' . ($info['aircraft_from'] ?? '?'),
                'realworld_airline' => $costStr,
                'notes'             => 'Pilot has insufficient Pilot Pay. Open Companion Modules & Moves in /admin/afterburner to Allow or Deny.',
            ]);
        }


        public static function notifyDispatchDigest(array $summary): bool
        {
            if (!self::isUnlocked()) {
                return false;
            }
            $settings = ClientSettings::all();
            $webhook = trim($settings['discord_staff_webhook'] ?? '');
            if ($webhook === '') {
                return false;
            }

            return self::dispatchViaMasterServer($webhook, 'dispatch_digest', [
                'title'       => (string) ($summary['title'] ?? '📬 Batch Route Digest'),
                'description' => 'Scheduled dispatcher summary for the past ' . (int) ($summary['hours'] ?? 24) . ' hours.',
                'admin_url'   => url('/admin/afterburner'),
                'fields'      => [
                    ['name' => 'Open Queue', 'value' => (string) ($summary['pending_count'] ?? 0), 'inline' => true],
                    ['name' => 'New Requests', 'value' => (string) ($summary['new_in_window'] ?? 0), 'inline' => true],
                    ['name' => 'Completed', 'value' => (string) ($summary['completed_in_window'] ?? 0), 'inline' => true],
                    ['name' => 'Charters Expiring ≤7d', 'value' => (string) ($summary['expiring_charters'] ?? 0), 'inline' => true],
                    ['name' => 'Top Pending / Upvoted Routes', 'value' => substr((string) ($summary['top_text'] ?? 'None'), 0, 1000), 'inline' => false],
                ],
            ]);
        }

        public static function testWebhooks(): array
        {
            if (!self::isUnlocked()) {
                return ['success' => false, 'message' => 'Discord Webhook Notifications require Tier 2 (Executive Dispatch) or Tier 3 (Flagship Edition).'];
            }
            $s = ClientSettings::all();
            $staffUrl = trim($s['discord_staff_webhook'] ?? '');
            $pubUrl   = trim($s['discord_public_webhook'] ?? '');

            if ($staffUrl === '' && $pubUrl === '') {
                return ['success' => false, 'message' => 'Please enter and save at least one Discord Webhook URL first.'];
            }

            $results = [];
            if ($staffUrl !== '') {
                $ok = self::dispatchViaMasterServer($staffUrl, 'test', [
                    'test_message' => 'Your `#staff-dispatch` webhook is connected and verified via AfterburnerServer!',
                ]);
                $results[] = 'Staff Webhook: ' . ($ok ? 'OK' : 'Failed (verify license & webhook URL)');
            }
            if ($pubUrl !== '') {
                $ok = self::dispatchViaMasterServer($pubUrl, 'test', [
                    'test_message' => 'Your `#new-schedules` webhook is connected and verified via AfterburnerServer!',
                ]);
                $results[] = 'Public Webhook: ' . ($ok ? 'OK' : 'Failed (verify license & webhook URL)');
            }

            return ['success' => true, 'message' => 'Discord Webhook Test Results — ' . implode(' | ', $results)];
        }
    }
}
