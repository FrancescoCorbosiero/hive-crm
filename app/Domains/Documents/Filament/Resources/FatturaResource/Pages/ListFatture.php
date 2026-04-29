<?php

declare(strict_types=1);

namespace App\Domains\Documents\Filament\Resources\FatturaResource\Pages;

use App\Domains\Documents\Filament\Resources\FatturaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFatture extends ListRecords
{
    protected static string $resource = FatturaResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
