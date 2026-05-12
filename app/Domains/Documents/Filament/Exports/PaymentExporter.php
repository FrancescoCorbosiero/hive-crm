<?php

declare(strict_types=1);

namespace App\Domains\Documents\Filament\Exports;

use App\Domains\Documents\Models\Payment;
use App\Shared\Filament\MoneyInput;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * Export-only — recording a payment via CSV would bypass the
 * Documents → Finance auto-mirroring (Phase 9). Use the Filament
 * relation manager or the public service to record payments instead.
 */
class PaymentExporter extends Exporter
{
    protected static ?string $model = Payment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('fattura_id'),
            ExportColumn::make('paid_at'),
            ExportColumn::make('amount')
                ->state(fn (Payment $p) => MoneyInput::centsToMajor($p->amount_cents)),
            ExportColumn::make('currency'),
            ExportColumn::make('method')->state(fn (Payment $p) => $p->method->value),
            ExportColumn::make('reference'),
            ExportColumn::make('notes'),
            ExportColumn::make('financial_entry_id'),
            ExportColumn::make('created_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Exported '.number_format($export->successful_rows).' payment(s).';
    }
}
