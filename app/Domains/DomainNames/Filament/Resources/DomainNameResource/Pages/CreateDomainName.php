<?php

declare(strict_types=1);

namespace App\Domains\DomainNames\Filament\Resources\DomainNameResource\Pages;

use App\Domains\DomainNames\Filament\Resources\DomainNameResource;
use App\Domains\DomainNames\Services\Public\DomainNamesService;
use Filament\Resources\Pages\CreateRecord;

class CreateDomainName extends CreateRecord
{
    protected static string $resource = DomainNameResource::class;

    /**
     * Resolve the website / owner-contact links from the domain name
     * when the user left them blank.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return app(DomainNamesService::class)->autoLink($data);
    }
}
