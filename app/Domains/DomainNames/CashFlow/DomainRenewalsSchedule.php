<?php

declare(strict_types=1);

namespace App\Domains\DomainNames\CashFlow;

use App\Domains\DomainNames\Models\DomainName;
use App\Shared\CashFlow\CashFlowSchedule;
use App\Shared\CashFlow\Occurrences;
use App\Shared\CashFlow\ScheduledFlow;
use Carbon\CarbonImmutable;

/**
 * Loss: each renewal of an active domain with a known renewal cost, on
 * its expiry date — including a renewal already due that nobody has
 * logged yet ("Log renewal" rolls expires_at forward).
 */
final class DomainRenewalsSchedule implements CashFlowSchedule
{
    public function pending(CarbonImmutable $until): iterable
    {
        $domains = DomainName::query()
            ->active()
            ->where('renewal_cost_cents', '>', 0)
            ->whereDate('expires_at', '<=', $until)
            ->get();

        foreach ($domains as $domain) {
            $cost = $domain->renewalCost();

            foreach (Occurrences::until($domain->expires_at, $domain->renewedExpiry(...), $until) as $date) {
                yield ScheduledFlow::loss($date, $cost, (string) $domain->name, 'domain');
            }
        }
    }
}
