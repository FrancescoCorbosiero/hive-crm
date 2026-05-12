<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Filament\Resources\ContactResource\RelationManagers;

use App\Domains\Calendar\Enums\CalendarEventStatus;
use App\Domains\Calendar\Models\CalendarEvent;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Upcoming + recent calendar events for this contact, joined by
 * attendee_email. Pending / accepted only — cancelled and rejected
 * are hidden so the Customer 360 stays signal-heavy.
 */
class CalendarEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'calendarEvents';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('contacts/labels.summary.calendar');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->whereIn('status', [
                    CalendarEventStatus::Accepted->value,
                    CalendarEventStatus::Pending->value,
                ])
                ->where('starts_at', '>=', now()->subDays(1))
                ->orderBy('starts_at'))
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('calendar/labels.fields.title'))
                    ->limit(40),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label(__('calendar/labels.fields.starts_at'))
                    ->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label(__('calendar/labels.fields.ends_at'))
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('calendar/labels.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (CalendarEventStatus $state) => $state->label()),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('contacts/labels.summary.calendar_empty'));
    }
}
