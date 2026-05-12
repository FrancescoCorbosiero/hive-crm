<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Resources\FinancialEntryResource\Pages;

use App\Domains\Finance\Filament\Exports\FinancialEntryExporter;
use App\Domains\Finance\Filament\Imports\FinancialEntryImporter;
use App\Domains\Finance\Filament\Resources\FinancialEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFinancialEntries extends ListRecords
{
    protected static string $resource = FinancialEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ImportAction::make()->importer(FinancialEntryImporter::class),
            Actions\ExportAction::make()->exporter(FinancialEntryExporter::class),
            Actions\CreateAction::make(),
        ];
    }
}
