<?php

declare(strict_types=1);

namespace App\Domains\Leads\Filament\Widgets;

use App\Domains\Leads\Enums\LeadStatus;
use App\Domains\Leads\Services\Public\LeadsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LeadsPipelineWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 5;

    public function getHeading(): ?string
    {
        return __('leads/labels.widgets.pipeline');
    }

    protected function getStats(): array
    {
        $counts = app(LeadsService::class)->pipelineCounts();

        return collect(LeadStatus::pipeline())
            ->map(fn (LeadStatus $status) => Stat::make(
                $status->label(),
                (string) ($counts[$status->value] ?? 0),
            )->color($status->color()))
            ->all();
    }
}
