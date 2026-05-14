<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Filament\Resources\ServiceResource\Pages;

use App\Domains\Catalog\Filament\Resources\ServiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;
}
