<?php

declare(strict_types=1);

namespace App\Domains\DomainNames\Filament\Resources\DomainNameResource\Pages;

use App\Domains\DomainNames\Filament\Resources\DomainNameResource;
use App\Domains\DomainNames\Services\Public\DomainNamesService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDomainName extends EditRecord
{
    protected static string $resource = DomainNameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return app(DomainNamesService::class)->autoLink($data);
    }
}
