<?php

declare(strict_types=1);

namespace App\Domains\Websites\Filament\Resources\WebsiteResource\Pages;

use App\Domains\Websites\Filament\Resources\WebsiteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\EditRecord\Concerns\Translatable;

class EditWebsite extends EditRecord
{
    use Translatable;

    protected static string $resource = WebsiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
