<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Services\Public\FinanceService;
use App\Domains\Leads\Models\Lead;
use App\Shared\ValueObjects\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Dashboard KPI strip — at-a-glance financial and pipeline numbers
 * sitting between the quick-actions tile grid and the operational
 * widgets below. Reuses FinanceService for YTD totals so the same
 * aggregation logic powers the Finance analytics page.
 */
class DashboardKpisWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $finance = app(FinanceService::class);
        $locale = app()->getLocale();

        $income = $finance->ytdTotal(FinancialEntryType::Income);
        $loss = $finance->ytdTotal(FinancialEntryType::Loss);
        $net = $income->subtract($loss);

        $pipelineCents = (int) Lead::query()
            ->open()
            ->whereNotNull('estimated_value_cents')
            ->sum('estimated_value_cents');
        $pipeline = new Money($pipelineCents, config('app.currency', 'EUR'));
        $openLeadsCount = Lead::query()->open()->count();

        return [
            Stat::make(__('dashboard.kpis.ytd_income'), $income->format($locale))
                ->description(__('dashboard.kpis.ytd_income_desc'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart($this->incomeSparkline()),

            Stat::make(__('dashboard.kpis.ytd_expense'), $loss->format($locale))
                ->description(__('dashboard.kpis.ytd_expense_desc'))
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make(__('dashboard.kpis.ytd_net'), $net->format($locale))
                ->description($net->isNegative()
                    ? __('dashboard.kpis.ytd_net_negative')
                    : __('dashboard.kpis.ytd_net_positive'))
                ->descriptionIcon($net->isNegative()
                    ? 'heroicon-m-exclamation-triangle'
                    : 'heroicon-m-check-circle')
                ->color($net->isNegative() ? 'danger' : 'success'),

            Stat::make(__('dashboard.kpis.pipeline'), $pipeline->format($locale))
                ->description(__('dashboard.kpis.pipeline_desc', ['count' => $openLeadsCount]))
                ->descriptionIcon('heroicon-m-funnel')
                ->color('primary'),
        ];
    }

    /**
     * Last 6 months of income totals as a sparkline. Empty array when
     * there is no data so Filament falls back to no chart.
     *
     * @return array<int, int>
     */
    private function incomeSparkline(): array
    {
        $series = app(FinanceService::class)
            ->monthlyIncomeSeries(6)
            ->map(fn (Money $m) => (int) round($m->cents / 100))
            ->values()
            ->all();

        return array_sum($series) > 0 ? $series : [];
    }
}
