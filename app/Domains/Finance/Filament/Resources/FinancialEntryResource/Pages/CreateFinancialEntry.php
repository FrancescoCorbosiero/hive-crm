<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Resources\FinancialEntryResource\Pages;

use App\Domains\Finance\Filament\Resources\FinancialEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFinancialEntry extends CreateRecord
{
    protected static string $resource = FinancialEntryResource::class;
}
