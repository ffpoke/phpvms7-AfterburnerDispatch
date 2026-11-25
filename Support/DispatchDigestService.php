<?php

declare(strict_types=1);

namespace Modules\AfterburnerDispatch\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Modules\AfterburnerDispatch\Models\RouteRequest;

if (!class_exists(DispatchDigestService::class, false)) {
    class DispatchDigestService
    {
        public static function buildSummary(int $hours = 24): array
        {
            $hours = max(1, min(720, $hours));
            $since = Carbon::now()->subHours($hours);

            $openStatuses = ['pending', 'in_progress', 'approved'];
            $pendingCount = (int) RouteRequest::whereIn('status', $openStatuses)->count();
            $newInWindow = (int) RouteRequest::where('created_at', '>=', $since)->count();
            $completedInWindow = (int) RouteRequest::where('status', 'completed')
                ->where('updated_at', '>=', $since)
                ->count();

            $upvoteMap = [];
            try {
                if (Schema::hasTable('routerequest_upvotes')) {
                    $rows = DB::table('routerequest_upvotes')
                        ->select('route_request_id', DB::raw('COUNT(*) as cnt'))
                        ->groupBy('route_request_id')
                        ->get();
                    foreach ($rows as $r) {
                        $upvoteMap[(int) $r->route_request_id] = (int) $r->cnt;
                    }
                }
            } catch (\Throwable $e) {}

            $openRequests = RouteRequest::with(['user', 'typerating'])
                ->whereIn('status', $openStatuses)
                ->orderBy('id', 'desc')
                ->limit(100)
                ->get()
                ->sortByDesc(static function ($req) use ($upvoteMap) {
                    return ($upvoteMap[(int) $req->id] ?? 0) * 1000000 + (int) $req->id;
                })
                ->take(5)
                ->values();

            $topLines = [];
            foreach ($openRequests as $req) {
                $upv = $upvoteMap[(int) $req->id] ?? 0;
                $arr = $req->arr_airport_id ?: 'ANY';
                $tr = $req->typerating ? $req->typerating->type : ($req->aircraft_type ?: 'Any');
                $pilot = $req->user ? $req->user->ident : ('#' . $req->user_id);
                $badge = $upv > 0 ? " [▲ {$upv}]" : '';
                $topLines[] = "#{$req->id} {$req->dep_airport_id} → {$arr} ({$tr}) by {$pilot}{$badge}";
            }

            $expiringCharters = 0;
            try {
                if (Schema::hasTable('flights') && Schema::hasColumn('flights', 'end_date')) {
                    $today = Carbon::now()->toDateString();
                    $plus7 = Carbon::now()->addDays(7)->toDateString();
                    $expiringCharters = (int) DB::table('flights')
                        ->where('active', 1)
                        ->whereNotNull('end_date')
                        ->whereBetween('end_date', [$today, $plus7])
                        ->count();
                }
            } catch (\Throwable $e) {}

            $vaName = (string) config('app.name', 'Virtual Airline');
            $title = "📬 {$vaName} — Batch Route Digest (Last {$hours}h)";
            $topText = empty($topLines) ? 'All caught up — no pending route requests!' : implode("\n", $topLines);

            $plainText = implode("\n", [
                $title,
                str_repeat('-', 54),
                "Open / Pending Queue:   {$pendingCount}",
                "New Requests ({$hours}h):     {$newInWindow}",
                "Completed ({$hours}h):        {$completedInWindow}",
                "Charters Expiring ≤7d:  {$expiringCharters}",
                "",
                "Top Priority / Upvoted Pending Routes:",
                $topText,
                "",
                "Dispatch Queue: " . url('/admin/afterburner'),
            ]);

            return [
                'hours'              => $hours,
                'title'              => $title,
                'pending_count'      => $pendingCount,
                'new_in_window'      => $newInWindow,
                'completed_in_window'=> $completedInWindow,
                'expiring_charters'  => $expiringCharters,
                'top_lines'          => $topLines,
                'top_text'           => $topText,
                'plain_text'         => $plainText,
                'generated_at'       => Carbon::now()->format('d M Y H:i') . ' UTC',
            ];
        }

        public static function sendDigest(int $hours = 24, bool $force = false): array
        {
            $ent = DrmEntitlements::current(false);
            if (empty($ent['features']['daily_dispatch_digest'])) {
                return [
                    'success' => false,
                    'message' => 'Scheduled Batch Route Digest is not yet unlocked for your current tier.',
                ];
            }

            $cs = ClientSettings::all();
            if (!$force && empty($cs['digest_enabled'])) {
                return [
                    'success' => false,
                    'message' => 'Scheduled Batch Route Digest is currently disabled in Dispatch Settings (pass --force or click Send Digest Now to override).',
                ];
            }

            $summary = self::buildSummary($hours);
            $sentChannels = [];

            // 1. Discord Staff Dispatch Webhook (if configured & unlocked)
            $webhook = trim((string) ($cs['discord_staff_webhook'] ?? ''));
            if ($webhook !== '' && method_exists(DiscordNotifier::class, 'notifyDispatchDigest')) {
                if (DiscordNotifier::notifyDispatchDigest($summary)) {
                    $sentChannels[] = 'Discord (#staff-dispatch)';
                }
            }

            // 2. Optional Admin Digest Email
            $email = trim((string) ($cs['digest_recipient_email'] ?? ''));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                try {
                    Mail::raw($summary['plain_text'], static function ($msg) use ($email, $summary) {
                        $msg->to($email)->subject($summary['title']);
                    });
                    $sentChannels[] = "Email ({$email})";
                } catch (\Throwable $e) {}
            }

            if (empty($sentChannels)) {
                return [
                    'success' => false,
                    'message' => 'Digest compiled, but neither a Discord Staff Webhook nor a Digest Recipient Email is configured in Dispatch Settings.',
                    'summary' => $summary,
                ];
            }

            ClientSettings::save(['last_digest_sent_at' => Carbon::now()->toDateTimeString() . ' UTC']);

            return [
                'success' => true,
                'message' => 'Batch Route Digest sent via ' . implode(' & ', $sentChannels) . " ({$summary['pending_count']} open, {$summary['completed_in_window']} completed in last {$hours}h).",
                'summary' => $summary,
            ];
        }
    }
}
