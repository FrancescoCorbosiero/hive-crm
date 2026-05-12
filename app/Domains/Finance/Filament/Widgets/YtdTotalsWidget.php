<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Widgets;

use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Services\Public\FinanceService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class YtdTotalsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $finance = app(FinanceService::class);
        $income = $finance->ytdTotal(FinancialEntryType::Income);
        $loss = $finance->ytdTotal(FinancialEntryType::Loss);
        $net = $income->subtract($loss);

        return [
            Stat::make(__('finance/entries.widgets.ytd_income'), $income->format(app()->getLocale()))
                ->color('success'),
            Stat::make(__('finance/entries.widgets.ytd_loss'), $loss->format(app()->getLocale()))
                ->color('danger'),
            Stat::make(__('finance/entries.widgets.ytd_net'), $net->format(app()->getLocale()))
                ->color($net->isNegative() ? 'danger' : 'success'),
        ];
    }
}
