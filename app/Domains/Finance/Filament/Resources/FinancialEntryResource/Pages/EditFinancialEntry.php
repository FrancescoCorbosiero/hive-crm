<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Resources\FinancialEntryResource\Pages;

use App\Domains\Finance\Filament\Resources\FinancialEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFinancialEntry extends EditRecord
{
    protected static string $resource = FinancialEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
