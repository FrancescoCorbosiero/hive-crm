<?php

declare(strict_types=1);

namespace App\Domains\Leads\Filament\Resources\LeadResource\Pages;

use App\Domains\Leads\Filament\Exports\LeadExporter;
use App\Domains\Leads\Filament\Imports\LeadImporter;
use App\Domains\Leads\Filament\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ImportAction::make()->importer(LeadImporter::class),
            Actions\ExportAction::make()->exporter(LeadExporter::class),
            Actions\CreateAction::make(),
        ];
    }
}
