<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Filament\Widgets;

use App\Domains\Calendar\Enums\CalendarEventStatus;
use App\Domains\Calendar\Models\CalendarEvent;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * "Today" agenda widget. Reads from the local table only — never makes
 * a live Cal.com API call. The webhook + hourly sync command are
 * responsible for keeping the table fresh.
 */
class TodayCalendarWidget extends TableWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('calendar/labels.widgets.today');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CalendarEvent::query()
                    ->today()
                    ->active()
                    ->orderBy('starts_at'),
            )
            ->emptyStateHeading(__('calendar/labels.widgets.no_events_today'))
            ->columns([
                Tables\Columns\TextColumn::make('starts_at')
                    ->label(__('calendar/labels.fields.starts_at'))
                    ->dateTime('H:i'),
                Tables\Columns\TextColumn::make('title')
                    ->label(__('calendar/labels.fields.title'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('attendee_email')
                    ->label(__('calendar/labels.fields.attendee_email'))
                    ->copyable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('calendar/labels.fields.status'))
                    ->badge()
                    ->color(fn (CalendarEventStatus $state) => $state->color())
                    ->formatStateUsing(fn (CalendarEventStatus $state) => $state->label()),
            ])
            ->paginated(false);
    }
}
