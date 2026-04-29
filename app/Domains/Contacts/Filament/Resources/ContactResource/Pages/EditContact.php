<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Filament\Resources\ContactResource\Pages;

use App\Domains\Contacts\Filament\Resources\ContactResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContact extends EditRecord
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
