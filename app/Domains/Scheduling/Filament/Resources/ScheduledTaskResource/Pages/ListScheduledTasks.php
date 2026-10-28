<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Filament\Resources\ScheduledTaskResource\Pages;

use App\Domains\Scheduling\Filament\Resources\ScheduledTaskResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

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
                    Artisan::call('scheduling:sync');
                    Notification::make()
                        ->success()
                        ->title(__('scheduling/labels.sync_done'))
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
