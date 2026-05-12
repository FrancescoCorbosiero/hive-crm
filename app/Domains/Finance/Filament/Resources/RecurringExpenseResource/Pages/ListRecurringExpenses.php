<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Resources\RecurringExpenseResource\Pages;

use App\Domains\Finance\Filament\Resources\RecurringExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecurringExpenses extends ListRecords
{
    protected static string $resource = RecurringExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
