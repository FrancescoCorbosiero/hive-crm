<?php

declare(strict_types=1);

namespace App\Domains\Repositories\Filament\Resources\RepositoryResource\Pages;

use App\Domains\Repositories\Filament\Resources\RepositoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRepositories extends ListRecords
{
    protected static string $resource = RepositoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
