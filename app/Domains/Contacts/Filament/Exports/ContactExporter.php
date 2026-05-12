<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Filament\Exports;

use App\Domains\Contacts\Models\Contact;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ContactExporter extends Exporter
{
    protected static ?string $model = Contact::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('name'),
            ExportColumn::make('ragione_sociale'),
            ExportColumn::make('email'),
            ExportColumn::make('phone'),
            ExportColumn::make('vat_number'),
            ExportColumn::make('tax_code'),
            ExportColumn::make('sdi_code'),
            ExportColumn::make('pec_email'),
            ExportColumn::make('roles')
                ->state(fn (Contact $c) => implode(',', $c->roles ?? [])),
            ExportColumn::make('address')
                ->state(fn (Contact $c) => $c->address
                    ? implode(' | ', [
                        $c->address['street'] ?? '',
                        $c->address['city'] ?? '',
                        $c->address['province'] ?? '',
                        $c->address['postal_code'] ?? '',
                        $c->address['country'] ?? '',
                    ])
                    : ''),
            ExportColumn::make('do_not_email'),
            ExportColumn::make('notes'),
            ExportColumn::make('created_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Exported '.number_format($export->successful_rows).' contact(s).';
    }
}
