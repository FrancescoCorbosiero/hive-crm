<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Services\Public\FinanceService;
use App\Domains\Leads\Models\Lead;
use App\Shared\ValueObjects\Money;
use Filament\Widgets\Widget;

/**
 * Dashboard KPI strip — at-a-glance financial and pipeline numbers
 * sitting between the quick-actions tile grid and the operational
 * widgets below. Renders custom editorial-style cards (top accent
 * stripe + oversized tabular number + secondary line + optional
 * sparkline) rather than Filament's generic StatsOverviewWidget so
 * the dashboard reads as a designed surface, not stock admin chrome.
 *
 * Reuses FinanceService for YTD totals so the same aggregation logic
 * powers the Finance analytics page.
 */
class DashboardKpisWidget extends Widget
{
    protected static string $view = 'filament.widgets.dashboard-kpis';

    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<int, array{
     *     key: string,
     *     accent: string,
     *     label: string,
     *     value: string,
     *     hint: string,
     *     hintIcon: ?string,
     *     hintTone: 'positive'|'negative'|'neutral',
     *     sparkline: array<int, int>,
     * }>
     */
    public function getCardsProperty(): array
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
            [
                'key' => 'income',
                'accent' => 'emerald',
                'label' => __('dashboard.kpis.ytd_income'),
                'value' => $income->format($locale),
                'hint' => __('dashboard.kpis.ytd_income_desc'),
                'hintIcon' => 'heroicon-m-arrow-trending-up',
                'hintTone' => 'positive',
                'sparkline' => $this->incomeSparkline(),
            ],
            [
                'key' => 'expense',
                'accent' => 'rose',
                'label' => __('dashboard.kpis.ytd_expense'),
                'value' => $loss->format($locale),
                'hint' => __('dashboard.kpis.ytd_expense_desc'),
                'hintIcon' => 'heroicon-m-arrow-trending-down',
                'hintTone' => 'negative',
                'sparkline' => [],
            ],
            [
                'key' => 'net',
                'accent' => $net->isNegative() ? 'rose' : 'amber',
                'label' => __('dashboard.kpis.ytd_net'),
                'value' => $net->format($locale),
                'hint' => $net->isNegative()
                    ? __('dashboard.kpis.ytd_net_negative')
                    : __('dashboard.kpis.ytd_net_positive'),
                'hintIcon' => $net->isNegative()
                    ? 'heroicon-m-exclamation-triangle'
                    : 'heroicon-m-check-circle',
                'hintTone' => $net->isNegative() ? 'negative' : 'positive',
                'sparkline' => [],
            ],
            [
                'key' => 'pipeline',
                'accent' => 'sky',
                'label' => __('dashboard.kpis.pipeline'),
                'value' => $pipeline->format($locale),
                'hint' => __('dashboard.kpis.pipeline_desc', ['count' => $openLeadsCount]),
                'hintIcon' => 'heroicon-m-funnel',
                'hintTone' => 'neutral',
                'sparkline' => [],
            ],
        ];
    }

    /**
     * Last 6 months of income totals normalised to 0-100 for the SVG
     * sparkline path. Empty array when there is no data so the view
     * can omit the sparkline cleanly.
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

        if (array_sum($series) <= 0) {
            return [];
        }

        $max = max($series) ?: 1;

        return array_map(fn (int $v) => (int) round(($v / $max) * 100), $series);
    }
}
