<?php

use App\Domains\Calendar\Models\CalendarEvent;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.calcom.api_key', 'test-api-key');
    config()->set('services.calcom.base_url', 'https://api.cal.com/v2');
});

it('upserts bookings returned by the REST API', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.cal.com/v2/bookings*' => Http::response([
            'data' => [
                [
                    'uid' => 'cal_sync_1',
                    'title' => 'Synced demo',
                    'startTime' => '2026-05-15T09:00:00Z',
                    'endTime' => '2026-05-15T09:30:00Z',
                    'status' => 'ACCEPTED',
                    'attendees' => [['email' => 'x@y.it']],
                ],
            ],
        ], 200),
    ]);

    $this->artisan('calcom:sync')->assertExitCode(0);

    expect(CalendarEvent::count())->toBe(1);
    expect(CalendarEvent::first()->cal_event_id)->toBe('cal_sync_1');
});

it('is idempotent — re-running does not create duplicates', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.cal.com/v2/bookings*' => Http::response([
            'data' => [[
                'uid' => 'cal_sync_1',
                'title' => 'Demo',
                'startTime' => '2026-05-15T09:00:00Z',
                'endTime' => '2026-05-15T09:30:00Z',
                'status' => 'ACCEPTED',
                'attendees' => [['email' => 'x@y.it']],
            ]],
        ], 200),
    ]);

    $this->artisan('calcom:sync');
    $this->artisan('calcom:sync');

    expect(CalendarEvent::count())->toBe(1);
});

it('skips silently and exits 0 when the API key is not configured', function () {
    config()->set('services.calcom.api_key', null);

    $this->artisan('calcom:sync')->assertExitCode(0);

    expect(CalendarEvent::count())->toBe(0);
});

it('returns failure when the upstream call errors', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.cal.com/v2/bookings*' => Http::response('boom', 500),
    ]);

    $this->artisan('calcom:sync')->assertExitCode(1);
});
