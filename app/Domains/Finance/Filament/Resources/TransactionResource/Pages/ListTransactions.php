<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Resources\TransactionResource\Pages;

use App\Domains\Finance\Filament\Exports\TransactionExporter;
use App\Domains\Finance\Filament\Imports\TransactionImporter;
use App\Domains\Finance\Filament\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ImportAction::make()->importer(TransactionImporter::class),
            Actions\ExportAction::make()->exporter(TransactionExporter::class),
            Actions\CreateAction::make(),
        ];
    }
}
