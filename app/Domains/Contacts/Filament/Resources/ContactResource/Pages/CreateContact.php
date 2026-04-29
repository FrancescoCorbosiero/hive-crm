<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Filament\Resources\ContactResource\Pages;

use App\Domains\Contacts\Events\ContactCreated;
use App\Domains\Contacts\Filament\Resources\ContactResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContact extends CreateRecord
{
    protected static string $resource = ContactResource::class;

    protected function afterCreate(): void
    {
        ContactCreated::dispatch($this->record->id);
    }
}
