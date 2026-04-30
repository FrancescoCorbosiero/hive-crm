<?php

declare(strict_types=1);

namespace App\Domains\Websites\Console\Commands;

use App\Domains\Websites\Models\Website;
use App\Domains\Websites\Services\Internal\WebsitePinger;
use Illuminate\Console\Command;

/**
 * Pings every active website and updates its liveness columns.
 *
 * Scheduled every 15 minutes — see routes/console.php. Skips archived
 * and suspended sites because they're not expected to be reachable.
 */
class PingWebsitesCommand extends Command
{
    protected $signature = 'websites:ping';

    protected $description = 'Ping every active website and update is_up / last_status_code / last_pinged_at.';

    public function handle(WebsitePinger $pinger): int
    {
        $count = 0;
        Website::query()
            ->active()
            ->whereNotNull('url')
            ->each(function (Website $website) use ($pinger, &$count) {
                $pinger->ping($website);
                $count++;
            });

        $this->info("Pinged {$count} website(s).");

        return self::SUCCESS;
    }
}
