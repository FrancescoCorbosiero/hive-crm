<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Filament\Resources\ScheduledTaskResource\Pages;

use App\Domains\Scheduling\Filament\Resources\ScheduledTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListScheduledTasks extends ListRecords
{
    protected static string $resource = ScheduledTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync')
                ->label(__('scheduling/labels.sync_system'))
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    \Illuminate\Support\Facades\Artisan::call('scheduling:sync');
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title(__('scheduling/labels.sync_done'))
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
