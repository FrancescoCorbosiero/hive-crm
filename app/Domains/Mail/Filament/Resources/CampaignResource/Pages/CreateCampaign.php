<?php

declare(strict_types=1);

namespace App\Domains\Mail\Filament\Resources\CampaignResource\Pages;

use App\Domains\Mail\Filament\Resources\CampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateCampaign extends CreateRecord
{
    use Translatable;

    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\LocaleSwitcher::make()];
    }
}
