<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Filament\Resources\ContactResource\Pages;

use App\Domains\Contacts\Filament\Exports\ContactExporter;
use App\Domains\Contacts\Filament\Imports\ContactImporter;
use App\Domains\Contacts\Filament\Resources\ContactResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ImportAction::make()->importer(ContactImporter::class),
            Actions\ExportAction::make()->exporter(ContactExporter::class),
            Actions\CreateAction::make(),
        ];
    }
}
