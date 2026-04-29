<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Resources\TransactionResource\Pages;

use App\Domains\Finance\Filament\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
