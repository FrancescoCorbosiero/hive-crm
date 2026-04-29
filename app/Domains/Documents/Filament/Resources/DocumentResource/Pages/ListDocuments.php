<?php

declare(strict_types=1);

namespace App\Domains\Documents\Filament\Resources\DocumentResource\Pages;

use App\Domains\Documents\Filament\Resources\DocumentResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Concerns\Translatable;

class ListDocuments extends ListRecords
{
    use Translatable;

    protected static string $resource = DocumentResource::class;
}
