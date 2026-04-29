<?php

declare(strict_types=1);

namespace App\Domains\Mail\Filament\Resources\CampaignResource\Pages;

use App\Domains\Mail\Filament\Resources\CampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Concerns\Translatable;

class ListCampaigns extends ListRecords
{
    use Translatable;

    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }
}
