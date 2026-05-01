<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Exports;

use App\Domains\Finance\Models\Transaction;
use App\Shared\Filament\MoneyInput;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class TransactionExporter extends Exporter
{
    protected static ?string $model = Transaction::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('occurred_at'),
            ExportColumn::make('type')->state(fn (Transaction $t) => $t->type->value),
            ExportColumn::make('amount')
                ->state(fn (Transaction $t) => MoneyInput::centsToMajor($t->amount_cents)),
            ExportColumn::make('currency'),
            ExportColumn::make('description'),
            ExportColumn::make('category'),
            ExportColumn::make('source_type'),
            ExportColumn::make('source_id'),
            ExportColumn::make('contact_id'),
            ExportColumn::make('notes'),
            ExportColumn::make('created_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Exported '.number_format($export->successful_rows).' transaction(s).';
    }
}
