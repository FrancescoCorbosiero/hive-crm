<?php

declare(strict_types=1);

namespace App\Domains\Websites\CashFlow;

use App\Domains\Websites\Models\Website;
use App\Shared\CashFlow\CashFlowSchedule;
use App\Shared\CashFlow\Occurrences;
use App\Shared\CashFlow\ScheduledFlow;
use App\Shared\ValueObjects\Money;
use Carbon\CarbonImmutable;

/**
 * Loss: each hosting renewal of an active website with a known renewal
 * cost. Websites carry no currency column — single-currency by
 * convention, so the app currency applies.
 */
final class HostingRenewalsSchedule implements CashFlowSchedule
{
    public function pending(CarbonImmutable $until): iterable
    {
        $websites = Website::query()
            ->active()
            ->where('renewal_cost_cents', '>', 0)
            ->whereDate('next_renewal_at', '<=', $until)
            ->get();

        $currency = (string) config('app.currency', 'EUR');

        foreach ($websites as $website) {
            $cost = new Money((int) $website->renewal_cost_cents, $currency);
            $name = $website->getTranslation('name', app()->getLocale()) ?: $website->url;

            foreach (Occurrences::until($website->next_renewal_at, $website->renewalDateAfter(...), $until) as $date) {
                yield ScheduledFlow::loss($date, $cost, (string) $name, 'website');
            }
        }
    }
}
