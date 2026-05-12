<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Filament\Resources\ScheduledTaskResource\Pages;

use App\Domains\Scheduling\Filament\Resources\ScheduledTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditScheduledTask extends EditRecord
{
    protected static string $resource = ScheduledTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => ! $this->getRecord()->is_system),
        ];
    }
}
