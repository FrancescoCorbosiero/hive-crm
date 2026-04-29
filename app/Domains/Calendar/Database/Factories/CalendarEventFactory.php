<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Database\Factories;

use App\Domains\Calendar\Enums\CalendarEventStatus;
use App\Domains\Calendar\Models\CalendarEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    public function definition(): array
    {
        $start = now()->copy()->addDays(fake()->numberBetween(-3, 14))->setTime(fake()->numberBetween(9, 17), 0);
        $end = (clone $start)->addMinutes(fake()->randomElement([30, 45, 60]));

        return [
            'cal_event_id' => 'cal_'.Str::random(24),
            'title' => fake()->randomElement([
                'Discovery call',
                'Project kickoff',
                'Design review',
                'Demo cliente',
                'Stand-up settimanale',
            ]),
            'starts_at' => $start,
            'ends_at' => $end,
            'attendee_email' => fake()->safeEmail(),
            'status' => CalendarEventStatus::Accepted->value,
            'payload' => null,
            'owner_user_id' => null,
        ];
    }

    public function today(): self
    {
        return $this->state(fn () => [
            'starts_at' => now()->setTime(14, 0),
            'ends_at' => now()->setTime(14, 30),
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn () => ['status' => CalendarEventStatus::Cancelled->value]);
    }
}
