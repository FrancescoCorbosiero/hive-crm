<?php

declare(strict_types=1);

namespace App\Domains\Websites\Filament\Widgets;

use App\Domains\Websites\Models\Website;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingRenewalsWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('websites/labels.widgets.upcoming_renewals');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Website::query()
                    ->active()
                    ->renewingWithin(30)
                    ->orderBy('next_renewal_at'),
            )
            ->emptyStateHeading(__('websites/labels.widgets.no_upcoming_renewals'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('websites/labels.name'))
                    ->getStateUsing(fn (Website $w) => $w->getTranslation('name', app()->getLocale()))
                    ->searchable(),
                Tables\Columns\TextColumn::make('url')
                    ->label(__('websites/labels.url'))
                    ->url(fn (Website $w) => $w->url, true)
                    ->limit(40),
                Tables\Columns\TextColumn::make('next_renewal_at')
                    ->label(__('websites/labels.next_renewal_at'))
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('days_until_renewal')
                    ->label(__('websites/labels.days_until_renewal'))
                    ->getStateUsing(fn (Website $w) => $w->daysUntilRenewal())
                    ->badge()
                    ->color(fn ($state) => $state <= 7 ? 'danger' : ($state <= 14 ? 'warning' : 'success')),
            ])
            ->paginated([5, 10]);
    }
}
