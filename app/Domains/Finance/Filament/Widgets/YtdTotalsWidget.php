<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Widgets;

use App\Domains\Finance\Enums\TransactionType;
use App\Domains\Finance\Services\Public\FinanceService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class YtdTotalsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $finance = app(FinanceService::class);
        $income = $finance->ytdTotal(TransactionType::Income);
        $expense = $finance->ytdTotal(TransactionType::Expense);
        $net = $income->subtract($expense);

        return [
            Stat::make(__('finance/transactions.widgets.ytd_income'), $income->format(app()->getLocale()))
                ->color('success'),
            Stat::make(__('finance/transactions.widgets.ytd_expense'), $expense->format(app()->getLocale()))
                ->color('danger'),
            Stat::make(__('finance/transactions.widgets.ytd_net'), $net->format(app()->getLocale()))
                ->color($net->isNegative() ? 'danger' : 'success'),
        ];
    }
}
