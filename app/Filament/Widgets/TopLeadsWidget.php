<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domains\Leads\Enums\LeadStatus;
use App\Domains\Leads\Filament\Resources\LeadResource;
use App\Domains\Leads\Models\Lead;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TopLeadsWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = ['md' => 6];

    public function getHeading(): string
    {
        return __('dashboard.top_leads.heading');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Lead::query()
                    ->open()
                    ->whereNotNull('estimated_value_cents')
                    ->orderByDesc('estimated_value_cents')
                    ->limit(5),
            )
            ->emptyStateHeading(__('dashboard.top_leads.empty'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('dashboard.top_leads.name'))
                    ->limit(30)
                    ->url(fn (Lead $lead) => LeadResource::getUrl('edit', ['record' => $lead])),

                Tables\Columns\TextColumn::make('company_name')
                    ->label(__('dashboard.top_leads.company'))
                    ->placeholder('—')
                    ->limit(25)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('dashboard.top_leads.status'))
                    ->badge()
                    ->color(fn (LeadStatus $state) => $state->color())
                    ->formatStateUsing(fn (LeadStatus $state) => $state->label()),

                Tables\Columns\TextColumn::make('estimated_value_cents')
                    ->label(__('dashboard.top_leads.value'))
                    ->alignEnd()
                    ->getStateUsing(fn (Lead $lead) => $lead->estimated_value?->format(app()->getLocale()) ?? '—')
                    ->color('success'),

                Tables\Columns\TextColumn::make('next_action_at')
                    ->label(__('dashboard.top_leads.next_action'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->paginated(false)
            ->defaultPaginationPageOption(5)
            ->paginatedWhileReordering(false)
            ->recordUrl(fn (Lead $lead) => LeadResource::getUrl('edit', ['record' => $lead]));
    }
}
