<?php

declare(strict_types=1);

namespace App\Domains\Finance\Filament\Resources\TransactionResource\Pages;

use App\Domains\Finance\Filament\Resources\TransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;
}
