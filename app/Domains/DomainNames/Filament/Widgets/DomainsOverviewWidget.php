<?php

declare(strict_types=1);

namespace App\Domains\DomainNames\Filament\Widgets;

use App\Domains\DomainNames\Enums\DomainStatus;
use App\Domains\DomainNames\Models\DomainName;
use App\Shared\ValueObjects\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Registrar-panel-style summary: how many domains, how many need
 * attention soon, and the total annual renewal spend.
 */
class DomainsOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $total = DomainName::query()->count();
        $active = DomainName::query()->active()->count();
        $expiringSoon = DomainName::query()->expiringWithin(30)->count();
        $expired = DomainName::query()->whereDate('expires_at', '<', now())->count();

        // Annual renewal spend, normalised to a 12-month figure so a
        // domain registered for 2 years still contributes its yearly
        // share. Mixed-currency rows are summed in the app default —
        // explicit FX is out of scope.
        $annualCents = 0;
        DomainName::query()
            ->active()
            ->whereNotNull('renewal_cost_cents')
            ->get(['renewal_cost_cents', 'renewal_period_months'])
            ->each(function (DomainName $d) use (&$annualCents) {
                $months = max(1, (int) $d->renewal_period_months);
                $annualCents += (int) round(((int) $d->renewal_cost_cents) * 12 / $months);
            });

        $annual = new Money($annualCents, config('app.currency', 'EUR'));
        $locale = app()->getLocale();

        return [
            Stat::make(__('domain_names/labels.widgets.total'), (string) $total)
                ->description(__('domain_names/labels.widgets.active_count', ['count' => $active]))
                ->color('primary'),

            Stat::make(__('domain_names/labels.widgets.expiring_30'), (string) $expiringSoon)
                ->description($expired > 0
                    ? __('domain_names/labels.widgets.expired_count', ['count' => $expired])
                    : __('domain_names/labels.widgets.none_expired'))
                ->color($expiringSoon > 0 ? 'warning' : 'success'),

            Stat::make(__('domain_names/labels.widgets.annual_cost'), $annual->format($locale))
                ->description(__('domain_names/labels.widgets.annual_cost_hint'))
                ->color('danger'),
        ];
    }
}
