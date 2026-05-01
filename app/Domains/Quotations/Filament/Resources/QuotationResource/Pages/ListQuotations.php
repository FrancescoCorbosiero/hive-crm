<?php

declare(strict_types=1);

namespace App\Domains\Quotations\Filament\Resources\QuotationResource\Pages;

use App\Domains\Quotations\Filament\Exports\QuotationExporter;
use App\Domains\Quotations\Filament\Resources\QuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuotations extends ListRecords
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()->exporter(QuotationExporter::class),
            Actions\CreateAction::make(),
        ];
    }
}
