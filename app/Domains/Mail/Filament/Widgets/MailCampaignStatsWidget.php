<?php

declare(strict_types=1);

namespace App\Domains\Mail\Filament\Widgets;

use App\Domains\Mail\Models\Campaign;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Visible only when at least one campaign is in flight (scheduled or
 * sending). Stays out of the way the rest of the time.
 */
class MailCampaignStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 6;

    public static function canView(): bool
    {
        return Campaign::query()->inFlight()->exists();
    }

    public function getHeading(): ?string
    {
        return __('mail/campaigns.widgets.in_flight');
    }

    protected function getStats(): array
    {
        $campaign = Campaign::query()->inFlight()->latest()->first();

        if (! $campaign) {
            return [];
        }

        return [
            Stat::make(__('mail/campaigns.fields.sent_count'), $campaign->sent_count)->color('info'),
            Stat::make(__('mail/campaigns.fields.delivered_count'), $campaign->delivered_count)->color('success'),
            Stat::make(__('mail/campaigns.fields.bounced_count'), $campaign->bounced_count)->color('danger'),
        ];
    }
}
