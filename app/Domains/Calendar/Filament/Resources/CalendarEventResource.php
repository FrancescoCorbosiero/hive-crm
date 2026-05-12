<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Filament\Resources;

use App\Domains\Calendar\Enums\CalendarEventStatus;
use App\Domains\Calendar\Filament\Resources\CalendarEventResource\Pages;
use App\Domains\Calendar\Models\CalendarEvent;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CalendarEventResource extends Resource
{
    protected static ?string $model = CalendarEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = CalendarEvent::query()->today()->active()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.calendar');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.calendar');
    }

    public static function getModelLabel(): string
    {
        return __('calendar/labels.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('calendar/labels.plural');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('starts_at')
                    ->label(__('calendar/labels.fields.starts_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label(__('calendar/labels.fields.ends_at'))
                    ->dateTime('H:i')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('title')
                    ->label(__('calendar/labels.fields.title'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('attendee_email')
                    ->label(__('calendar/labels.fields.attendee_email'))
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('calendar/labels.fields.status'))
                    ->badge()
                    ->color(fn (CalendarEventStatus $state) => $state->color())
                    ->formatStateUsing(fn (CalendarEventStatus $state) => $state->label()),
                Tables\Columns\TextColumn::make('cal_event_id')
                    ->label(__('calendar/labels.fields.cal_event_id'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('calendar/labels.fields.status'))
                    ->options(CalendarEventStatus::options()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('starts_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalendarEvents::route('/'),
        ];
    }
}
