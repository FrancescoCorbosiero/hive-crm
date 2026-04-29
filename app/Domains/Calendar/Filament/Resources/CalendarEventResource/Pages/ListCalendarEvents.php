<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Filament\Resources\CalendarEventResource\Pages;

use App\Domains\Calendar\Filament\Resources\CalendarEventResource;
use Filament\Resources\Pages\ListRecords;

class ListCalendarEvents extends ListRecords
{
    protected static string $resource = CalendarEventResource::class;
}
