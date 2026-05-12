<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Widgets;

use App\Domains\Finance\Services\Public\FinanceService;
use Filament\Widgets\ChartWidget;

class MonthlyIncomeChartWidget extends ChartWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('finance/entries.widgets.monthly_income');
    }

    protected function getData(): array
    {
        $series = app(FinanceService::class)->monthlyIncomeSeries(12);

        $labels = $series->keys()->map(function (string $ym) {
            [$year, $month] = explode('-', $ym);
            return \Carbon\Carbon::createFromDate((int) $year, (int) $month, 1)
                ->locale(app()->getLocale())
                ->isoFormat('MMM YY');
        })->all();

        $values = $series->values()->map(fn ($money) => $money->toMajor())->all();

        return [
            'datasets' => [
                [
                    'label' => __('finance/entries.type.income').' (€)',
                    'data' => $values,
                    'borderColor' => 'rgb(16,185,129)',
                    'backgroundColor' => 'rgba(16,185,129,0.2)',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
