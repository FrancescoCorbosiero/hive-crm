<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Filament\Resources\ScheduledTaskResource\Pages;

use App\Domains\Scheduling\Filament\Resources\ScheduledTaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateScheduledTask extends CreateRecord
{
    protected static string $resource = ScheduledTaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // User-created rows are never system — only the seeder writes
        // is_system=true.
        $data['is_system'] = false;

        return $data;
    }
}
