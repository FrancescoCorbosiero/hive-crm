<?php

declare(strict_types=1);

namespace App\Domains\Repositories\Filament\Resources\RepositoryResource\Pages;

use App\Domains\Repositories\Filament\Resources\RepositoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRepository extends CreateRecord
{
    protected static string $resource = RepositoryResource::class;
}
