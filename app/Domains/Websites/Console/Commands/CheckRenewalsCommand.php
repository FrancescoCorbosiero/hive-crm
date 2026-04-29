<?php

declare(strict_types=1);

namespace App\Domains\Websites\Console\Commands;

use App\Domains\Websites\Events\RenewalApproaching;
use App\Domains\Websites\Models\Website;
use Illuminate\Console\Command;

/**
 * Surfaces websites whose subscription is due to renew in the standard
 * 7 / 14 / 30-day windows. Fires a RenewalApproaching event for each so
 * downstream consumers (widget, future notification listener) can react.
 *
 * Scheduled daily — see routes/console.php.
 */
class CheckRenewalsCommand extends Command
{
    protected $signature = 'websites:check-renewals';

    protected $description = 'Dispatch RenewalApproaching events for sites in the 7/14/30-day windows.';

    /** @var array<int,int> */
    private const WINDOWS = [7, 14, 30];

    public function handle(): int
    {
        $today = now()->startOfDay();
        $count = 0;

        foreach (self::WINDOWS as $days) {
            $target = $today->copy()->addDays($days);

            Website::query()
                ->active()
                ->whereDate('next_renewal_at', $target->toDateString())
                ->each(function (Website $website) use ($days, &$count) {
                    RenewalApproaching::dispatch($website->id, $days);
                    $count++;
                });
        }

        $this->info("Dispatched {$count} renewal alert(s).");

        return self::SUCCESS;
    }
}
