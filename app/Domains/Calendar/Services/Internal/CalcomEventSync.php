<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Services\Internal;

use App\Domains\Calendar\Enums\CalendarEventStatus;
use App\Domains\Calendar\Models\CalendarEvent;
use Carbon\Carbon;
use Illuminate\Support\Arr;

/**
 * Idempotent upsert from a Cal.com booking payload to a CalendarEvent row.
 *
 * Used by both the webhook controller (real-time path) and the hourly
 * sync command (fallback for missed deliveries). Webhook retries are
 * common — dedupe by `cal_event_id` (Cal.com's `uid`).
 *
 * Marked Internal/ deliberately: not part of the cross-domain public
 * surface. Other domains that need to read calendar data go through
 * CalendarReadService.
 */
class CalcomEventSync
{
    /**
     * Process a single Cal.com webhook payload or a single REST booking
     * row. Returns the upserted CalendarEvent.
     *
     * Webhook envelope: { triggerEvent: ..., payload: { uid, ... } }.
     * REST row: the booking row directly.
     */
    public function handlePayload(array $payload): ?CalendarEvent
    {
        $trigger = (string) Arr::get($payload, 'triggerEvent', '');
        $booking = Arr::get($payload, 'payload', $payload);

        if (! is_array($booking)) {
            return null;
        }

        $uid = (string) (Arr::get($booking, 'uid') ?? Arr::get($booking, 'id') ?? '');
        if ($uid === '') {
            return null;
        }

        $statusFromPayload = Arr::get($booking, 'status');
        $status = $trigger === 'BOOKING_CANCELLED'
            ? CalendarEventStatus::Cancelled
            : CalendarEventStatus::fromCalcom($statusFromPayload);

        $startsAt = $this->parseTime(Arr::get($booking, 'startTime') ?? Arr::get($booking, 'start_time'));
        $endsAt = $this->parseTime(Arr::get($booking, 'endTime') ?? Arr::get($booking, 'end_time'));

        if (! $startsAt || ! $endsAt) {
            return null;
        }

        $attendees = Arr::get($booking, 'attendees', []);
        $attendeeEmail = is_array($attendees) && isset($attendees[0]['email'])
            ? (string) $attendees[0]['email']
            : null;

        $title = (string) (Arr::get($booking, 'title') ?? Arr::get($booking, 'eventType.title') ?? 'Cal.com event');

        return CalendarEvent::query()->updateOrCreate(
            ['cal_event_id' => $uid],
            [
                'title' => $title,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'attendee_email' => $attendeeEmail,
                'status' => $status->value,
                'payload' => $payload,
            ],
        );
    }

    /**
     * Cal.com sends Zulu timestamps. We pin every stored datetime to the
     * app timezone so Eloquent's datetime cast round-trips cleanly:
     * Eloquent persists Carbon's wall-clock representation without an
     * offset and re-reads it in app tz, which would silently shift the
     * absolute time on each save/read cycle without this conversion.
     */
    private function parseTime(mixed $value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->setTimezone(config('app.timezone', 'UTC'));
        } catch (\Throwable) {
            return null;
        }
    }
}
