<?php

declare(strict_types=1);

namespace App\Domains\Leads\Filament\Exports;

use App\Domains\Leads\Models\Lead;
use App\Shared\Filament\MoneyInput;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class LeadExporter extends Exporter
{
    protected static ?string $model = Lead::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('name'),
            ExportColumn::make('email'),
            ExportColumn::make('phone'),
            ExportColumn::make('source')->state(fn (Lead $l) => $l->source?->value),
            ExportColumn::make('status')->state(fn (Lead $l) => $l->status->value),
            // Cents → decimal so spreadsheet apps render it as money.
            ExportColumn::make('estimated_value')
                ->state(fn (Lead $l) => MoneyInput::centsToMajor($l->estimated_value_cents)),
            ExportColumn::make('next_action_at'),
            ExportColumn::make('converted_at'),
            ExportColumn::make('notes'),
            ExportColumn::make('created_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Exported '.number_format($export->successful_rows).' lead(s).';
    }
}
