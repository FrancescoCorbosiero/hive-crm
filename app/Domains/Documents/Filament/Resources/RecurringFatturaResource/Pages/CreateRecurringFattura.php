<?php

declare(strict_types=1);

namespace App\Domains\Documents\Filament\Resources\RecurringFatturaResource\Pages;

use App\Domains\Documents\Filament\Resources\RecurringFatturaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRecurringFattura extends CreateRecord
{
    protected static string $resource = RecurringFatturaResource::class;
}
