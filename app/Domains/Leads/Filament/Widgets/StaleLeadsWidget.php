<?php

declare(strict_types=1);

namespace App\Domains\Leads\Filament\Widgets;

use App\Domains\Leads\Enums\LeadStatus;
use App\Domains\Leads\Filament\Resources\LeadResource;
use App\Domains\Leads\Models\Lead;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class StaleLeadsWidget extends TableWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    private const STALE_DAYS = 14;

    public function getHeading(): string
    {
        return __('leads/labels.widgets.stale_leads', ['days' => self::STALE_DAYS]);
    }

    public static function canView(): bool
    {
        return Lead::query()->stale(self::STALE_DAYS)->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Lead::query()
                    ->stale(self::STALE_DAYS)
                    ->orderByRaw('COALESCE(last_contacted_at, created_at) ASC'),
            )
            ->emptyStateHeading(__('leads/labels.widgets.no_stale_leads'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('leads/labels.fields.name'))
                    ->searchable()
                    ->url(fn (Lead $lead) => LeadResource::getUrl('edit', ['record' => $lead])),
                Tables\Columns\TextColumn::make('company_name')
                    ->label(__('leads/labels.fields.company_name'))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('leads/labels.fields.status'))
                    ->badge()
                    ->color(fn (LeadStatus $state) => $state->color())
                    ->formatStateUsing(fn (LeadStatus $state) => $state->label()),
                Tables\Columns\TextColumn::make('last_contacted_at')
                    ->label(__('leads/labels.fields.last_contacted_at'))
                    ->since()
                    ->placeholder(__('leads/labels.never_contacted'))
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('next_action_at')
                    ->label(__('leads/labels.fields.next_action_at'))
                    ->dateTime('d/m/Y H:i'),
            ])
            ->paginated([5, 10]);
    }
}
