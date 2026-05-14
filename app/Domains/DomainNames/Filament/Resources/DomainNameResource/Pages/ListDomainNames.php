<?php

declare(strict_types=1);

namespace App\Domains\DomainNames\Filament\Resources\DomainNameResource\Pages;

use App\Domains\DomainNames\Filament\Resources\DomainNameResource;
use App\Domains\DomainNames\Filament\Widgets\DomainsOverviewWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDomainNames extends ListRecords
{
    protected static string $resource = DomainNameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DomainsOverviewWidget::class,
        ];
    }
}
