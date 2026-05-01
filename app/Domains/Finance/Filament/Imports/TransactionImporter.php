<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Imports;

use App\Domains\Finance\Enums\TransactionType;
use App\Domains\Finance\Models\Transaction;
use App\Shared\Filament\MoneyInput;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * Bank-statement-friendly transaction importer.
 *
 * Required CSV columns: occurred_at, description, amount_cents, type.
 * `amount_cents` is read as a major-unit decimal ("125.50") and stored
 * as integer cents. `type` MUST be 'income' or 'expense' explicitly —
 * Filament's per-column cast closures can't peek at sibling cells, so
 * sign-inference from the amount isn't possible.
 */
class TransactionImporter extends Importer
{
    protected static ?string $model = Transaction::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('occurred_at')
                ->requiredMapping()
                ->rules(['required', 'date']),
            ImportColumn::make('description')
                ->requiredMapping()
                ->rules(['required', 'string']),
            ImportColumn::make('amount_cents')
                ->requiredMapping()
                ->rules(['required'])
                ->castStateUsing(function ($state) {
                    if ($state === null || $state === '') {
                        return null;
                    }
                    $cleaned = is_string($state) ? str_replace(' ', '', $state) : $state;
                    $cents = MoneyInput::majorToCents($cleaned);

                    // Strip sign — type column carries direction.
                    return $cents === null ? null : abs((int) $cents);
                }),
            ImportColumn::make('type')
                ->requiredMapping()
                ->castStateUsing(fn (?string $state) => $state
                    ? (TransactionType::tryFrom(strtolower(trim($state)))?->value
                       ?? TransactionType::Expense->value)
                    : TransactionType::Expense->value),
            ImportColumn::make('currency')
                ->castStateUsing(fn (?string $state) => $state ?: 'EUR'),
            ImportColumn::make('category'),
            ImportColumn::make('notes'),
        ];
    }

    public function resolveRecord(): ?Transaction
    {
        return new Transaction();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return 'Imported '.number_format($import->successful_rows).' transaction(s).';
    }
}
