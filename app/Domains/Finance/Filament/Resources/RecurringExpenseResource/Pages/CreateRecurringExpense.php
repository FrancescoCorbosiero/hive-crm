<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Resources\RecurringExpenseResource\Pages;

use App\Domains\Finance\Filament\Resources\RecurringExpenseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRecurringExpense extends CreateRecord
{
    protected static string $resource = RecurringExpenseResource::class;
}
