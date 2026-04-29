<?php

use App\Domains\Calendar\DTOs\CalendarEventDTO;
use App\Domains\Calendar\Enums\CalendarEventStatus;
use App\Domains\Calendar\Models\CalendarEvent;
use App\Domains\Calendar\Services\Public\CalendarReadService;

it('returns today\'s active events as DTOs', function () {
    CalendarEvent::factory()->today()->create();
    CalendarEvent::factory()->today()->cancelled()->create();
    CalendarEvent::factory()->create([
        'starts_at' => now()->addDays(2),
        'ends_at' => now()->addDays(2)->addMinutes(30),
    ]);

    $today = app(CalendarReadService::class)->today();

    expect($today)->toHaveCount(1);
    expect($today->first())->toBeInstanceOf(CalendarEventDTO::class);
});

it('returns null for a missing event', function () {
    expect(app(CalendarReadService::class)->find(99999))->toBeNull();
});

it('returns upcoming events ordered by start time', function () {
    CalendarEvent::factory()->create([
        'starts_at' => now()->addDays(3),
        'ends_at' => now()->addDays(3)->addMinutes(30),
    ]);
    CalendarEvent::factory()->create([
        'starts_at' => now()->addDays(1),
        'ends_at' => now()->addDays(1)->addMinutes(30),
    ]);
    CalendarEvent::factory()->create([
        'starts_at' => now()->subDays(1),
        'ends_at' => now()->subDays(1)->addMinutes(30),
    ]);

    $upcoming = app(CalendarReadService::class)->upcoming();

    expect($upcoming)->toHaveCount(2);
    expect($upcoming->first()->startsAt->lessThan($upcoming->last()->startsAt))->toBeTrue();
});

it('skips cancelled events from upcoming and today scopes', function () {
    CalendarEvent::factory()->today()->state(['status' => CalendarEventStatus::Cancelled->value])->create();

    expect(app(CalendarReadService::class)->today())->toHaveCount(0);
});
