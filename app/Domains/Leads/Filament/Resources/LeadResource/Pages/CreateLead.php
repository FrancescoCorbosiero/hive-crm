<?php

declare(strict_types=1);

namespace App\Domains\Leads\Filament\Resources\LeadResource\Pages;

use App\Domains\Leads\Filament\Resources\LeadResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;
}
