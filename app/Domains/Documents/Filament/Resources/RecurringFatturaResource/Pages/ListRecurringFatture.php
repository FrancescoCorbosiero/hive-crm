<?php

declare(strict_types=1);

namespace App\Domains\Documents\Filament\Resources\RecurringFatturaResource\Pages;

use App\Domains\Documents\Filament\Resources\RecurringFatturaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecurringFatture extends ListRecords
{
    protected static string $resource = RecurringFatturaResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
