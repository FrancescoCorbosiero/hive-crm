<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Widgets;

use App\Domains\Finance\Enums\TransactionType;
use App\Domains\Finance\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentTransactionsWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('finance/transactions.widgets.recent_transactions');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Transaction::query()->orderByDesc('occurred_at')->orderByDesc('id'))
            ->columns([
                Tables\Columns\TextColumn::make('occurred_at')
                    ->label(__('finance/transactions.fields.occurred_at'))
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (TransactionType $state) => $state->color())
                    ->formatStateUsing(fn (TransactionType $state) => $state->label()),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50),
                Tables\Columns\TextColumn::make('amount_cents')
                    ->label(__('finance/transactions.fields.amount'))
                    ->getStateUsing(fn (Transaction $tx) => $tx->money->format(app()->getLocale()))
                    ->alignEnd(),
            ])
            ->paginated([10]);
    }
}
