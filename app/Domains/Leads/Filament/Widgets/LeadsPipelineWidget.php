<?php

declare(strict_types=1);

namespace App\Domains\Leads\Filament\Widgets;

use App\Domains\Leads\Enums\LeadStatus;
use App\Domains\Leads\Services\Public\LeadsService;
use App\Shared\ValueObjects\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LeadsPipelineWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 5;

    public function getHeading(): ?string
    {
        return __('leads/labels.widgets.pipeline_value');
    }

    protected function getStats(): array
    {
        $stages = app(LeadsService::class)->pipelineValueByStage();
        $locale = app()->getLocale();

        return collect(LeadStatus::pipeline())
            ->map(function (LeadStatus $status) use ($stages, $locale) {
                $row = $stages[$status->value] ?? ['count' => 0, 'cents' => 0, 'currency' => 'EUR'];
                $money = new Money((int) $row['cents'], $row['currency']);

                return Stat::make(
                    $status->label(),
                    $money->format($locale),
                )
                    ->description(trans_choice('leads/labels.widgets.lead_count', $row['count'], ['count' => $row['count']]))
                    ->color($status->color());
            })
            ->all();
    }
}
