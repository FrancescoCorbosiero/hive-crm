<?php

declare(strict_types=1);

namespace App\Domains\Websites\Filament\Widgets;

use App\Domains\Websites\Models\Website;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Lists websites that came back is_up=false on the most recent ping.
 * Hidden when nothing is down — keeps the dashboard clean.
 */
class DownWebsitesWidget extends TableWidget
{
    protected static ?int $sort = -1; // top of dashboard when visible

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Website::query()
            ->active()
            ->where('is_up', false)
            ->exists();
    }

    public function getHeading(): string
    {
        return __('websites/labels.widgets.down_websites');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Website::query()
                    ->active()
                    ->where('is_up', false)
                    ->orderBy('last_pinged_at', 'desc'),
            )
            ->emptyStateHeading(__('websites/labels.widgets.no_down_websites'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('websites/labels.name'))
                    ->getStateUsing(fn (Website $w) => $w->getTranslation('name', app()->getLocale())),
                Tables\Columns\TextColumn::make('url')
                    ->label(__('websites/labels.url'))
                    ->url(fn (Website $w) => $w->url, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('last_status_code')
                    ->label(__('websites/labels.ping.last_status_code'))
                    ->badge()
                    ->color('danger')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('last_pinged_at')
                    ->label(__('websites/labels.ping.last_pinged_at'))
                    ->dateTime('d/m/Y H:i'),
            ])
            ->paginated(false);
    }
}
