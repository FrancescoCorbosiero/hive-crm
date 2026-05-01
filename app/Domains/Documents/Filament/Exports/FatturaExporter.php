<?php

declare(strict_types=1);

namespace App\Domains\Documents\Filament\Exports;

use App\Domains\Documents\Models\Fattura;
use App\Shared\Filament\MoneyInput;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * Export-only by design: Italian fatture are tax artifacts. Importing
 * them via CSV would either bypass the race-safe sequential numbering
 * (and risk gaps / duplicates) or duplicate the existing Filament
 * create-form path. Neither is desirable.
 */
class FatturaExporter extends Exporter
{
    protected static ?string $model = Fattura::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('display_number')
                ->state(fn (Fattura $f) => $f->displayNumber()),
            ExportColumn::make('year'),
            ExportColumn::make('number'),
            ExportColumn::make('issued_at'),
            ExportColumn::make('due_date'),
            ExportColumn::make('client_contact_id'),
            ExportColumn::make('subtotal')
                ->state(fn (Fattura $f) => MoneyInput::centsToMajor($f->subtotal_cents)),
            ExportColumn::make('vat')
                ->state(fn (Fattura $f) => MoneyInput::centsToMajor($f->vat_cents)),
            ExportColumn::make('total')
                ->state(fn (Fattura $f) => MoneyInput::centsToMajor($f->total_cents)),
            ExportColumn::make('paid_amount')
                ->state(fn (Fattura $f) => MoneyInput::centsToMajor($f->paid_amount_cents)),
            ExportColumn::make('outstanding')
                ->state(fn (Fattura $f) => MoneyInput::centsToMajor(
                    max(0, $f->total_cents - $f->paid_amount_cents),
                )),
            ExportColumn::make('currency'),
            ExportColumn::make('payment_status')
                ->state(fn (Fattura $f) => $f->payment_status->value),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Exported '.number_format($export->successful_rows).' fattura(e).';
    }
}
