<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Services\Public;

use App\Domains\Calendar\DTOs\CalendarEventDTO;
use App\Domains\Calendar\Models\CalendarEvent;
use Illuminate\Support\Collection;

/**
 * Read-side public surface for the Calendar domain. Returns DTOs.
 */
class CalendarReadService
{
    public function find(int $id): ?CalendarEventDTO
    {
        $event = CalendarEvent::query()->find($id);

        return $event ? CalendarEventDTO::fromModel($event) : null;
    }

    /**
     * @return Collection<int, CalendarEventDTO>
     */
    public function today(): Collection
    {
        return CalendarEvent::query()
            ->today()
            ->active()
            ->orderBy('starts_at')
            ->get()
            ->map(fn (CalendarEvent $e) => CalendarEventDTO::fromModel($e));
    }

    /**
     * @return Collection<int, CalendarEventDTO>
     */
    public function upcoming(int $limit = 20): Collection
    {
        return CalendarEvent::query()
            ->upcoming()
            ->active()
            ->orderBy('starts_at')
            ->limit($limit)
            ->get()
            ->map(fn (CalendarEvent $e) => CalendarEventDTO::fromModel($e));
    }
}
