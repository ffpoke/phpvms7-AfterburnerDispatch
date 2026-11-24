<?php

declare(strict_types=1);

namespace Modules\RouteRequest\Console;

use Illuminate\Console\Command;
use Modules\RouteRequest\Support\ClientSettings;
use Modules\RouteRequest\Support\DispatchDigestService;

class SendRouteDigestCommand extends Command
{
    protected $signature = 'routerequest:send-digest {--hours= : Lookback window in hours (defaults to Dispatch Settings)} {--force : Send even if Scheduled Digest toggle is off}';
    protected $description = 'Send the RouteRequest Scheduled Batch Route Digest to Discord and/or admin email.';

    public function handle(): int
    {
        $cs = ClientSettings::all();
        $hoursOpt = $this->option('hours');
        $hours = ($hoursOpt !== null && $hoursOpt !== '')
            ? (int) $hoursOpt
            : max(1, (int) ($cs['digest_window_hours'] ?? 24));
        $force = (bool) $this->option('force');

        $res = DispatchDigestService::sendDigest($hours, $force);
        if (!empty($res['success'])) {
            $this->info($res['message']);
            return 0;
        }

        $this->warn($res['message'] ?? 'Batch digest was not sent.');
        return 0;
    }
}
