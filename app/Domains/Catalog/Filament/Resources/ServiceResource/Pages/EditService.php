<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Filament\Resources\ServiceResource\Pages;

use App\Domains\Catalog\Filament\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
