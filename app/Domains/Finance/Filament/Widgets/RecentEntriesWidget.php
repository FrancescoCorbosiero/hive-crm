<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Widgets;

use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Models\FinancialEntry;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentEntriesWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('finance/entries.widgets.recent_entries');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(FinancialEntry::query()->orderByDesc('occurred_at')->orderByDesc('id'))
            ->columns([
                Tables\Columns\TextColumn::make('occurred_at')
                    ->label(__('finance/entries.fields.occurred_at'))
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (FinancialEntryType $state) => $state->color())
                    ->formatStateUsing(fn (FinancialEntryType $state) => $state->label()),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50),
                Tables\Columns\TextColumn::make('amount_cents')
                    ->label(__('finance/entries.fields.amount'))
                    ->getStateUsing(fn (FinancialEntry $entry) => $entry->money->format(app()->getLocale()))
                    ->alignEnd(),
            ])
            ->paginated([10]);
    }
}
