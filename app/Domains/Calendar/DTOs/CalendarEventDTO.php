<?php

declare(strict_types=1);

namespace App\Domains\Calendar\DTOs;

use App\Domains\Calendar\Models\CalendarEvent;
use Carbon\Carbon;

final readonly class CalendarEventDTO
{
    public function __construct(
        public int $id,
        public string $calEventId,
        public string $title,
        public Carbon $startsAt,
        public Carbon $endsAt,
        public ?string $attendeeEmail,
        public string $status,
    ) {}

    public static function fromModel(CalendarEvent $e): self
    {
        return new self(
            id: $e->id,
            calEventId: $e->cal_event_id,
            title: $e->title,
            startsAt: $e->starts_at,
            endsAt: $e->ends_at,
            attendeeEmail: $e->attendee_email,
            status: $e->status->value,
        );
    }
}
