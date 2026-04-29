<?php

declare(strict_types=1);

namespace App\Domains\Mail\Filament\Resources\CampaignResource\Pages;

use App\Domains\Mail\Filament\Resources\CampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\EditRecord\Concerns\Translatable;

class EditCampaign extends EditRecord
{
    use Translatable;

    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
