<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Imports;

use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Models\FinancialEntry;
use App\Shared\Filament\MoneyInput;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * Bank-statement-friendly FinancialEntry importer.
 *
 * Required CSV columns: occurred_at, description, amount_cents, type.
 * `amount_cents` is read as a major-unit decimal ("125.50") and stored
 * as integer cents. `type` MUST be 'income' or 'loss' explicitly —
 * Filament's per-column cast closures can't peek at sibling cells, so
 * sign-inference from the amount isn't possible.
 */
class FinancialEntryImporter extends Importer
{
    protected static ?string $model = FinancialEntry::class;

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
                    ? (FinancialEntryType::tryFrom(strtolower(trim($state)))?->value
                       ?? FinancialEntryType::Loss->value)
                    : FinancialEntryType::Loss->value),
            ImportColumn::make('currency')
                ->castStateUsing(fn (?string $state) => $state ?: 'EUR'),
            ImportColumn::make('category'),
            ImportColumn::make('notes'),
        ];
    }

    public function resolveRecord(): ?FinancialEntry
    {
        return new FinancialEntry();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return 'Imported '.number_format($import->successful_rows).' financial entry(ies).';
    }
}
