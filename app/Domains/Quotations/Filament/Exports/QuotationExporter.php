<?php

declare(strict_types=1);

namespace App\Domains\Quotations\Filament\Exports;

use App\Domains\Quotations\Models\Quotation;
use App\Shared\Filament\MoneyInput;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class QuotationExporter extends Exporter
{
    protected static ?string $model = Quotation::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('display_number')
                ->state(fn (Quotation $q) => $q->displayNumber()),
            ExportColumn::make('name'),
            ExportColumn::make('client_contact_id'),
            ExportColumn::make('issued_at'),
            ExportColumn::make('valid_until'),
            ExportColumn::make('subtotal')
                ->state(fn (Quotation $q) => MoneyInput::centsToMajor($q->subtotal_cents)),
            ExportColumn::make('total')
                ->state(fn (Quotation $q) => MoneyInput::centsToMajor($q->total_cents)),
            ExportColumn::make('currency'),
            ExportColumn::make('status')->state(fn (Quotation $q) => $q->status->value),
            ExportColumn::make('fattura_id'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Exported '.number_format($export->successful_rows).' quotation(s).';
    }
}
