<?php

use App\Domains\Calendar\Enums\CalendarEventStatus;
use App\Domains\Calendar\Models\CalendarEvent;

beforeEach(function () {
    config()->set('services.calcom.webhook_secret', 'test-webhook-secret');
});

function calcomPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'triggerEvent' => 'BOOKING_CREATED',
        'createdAt' => '2026-04-29T10:00:00Z',
        'payload' => [
            'uid' => 'cal_uid_abc123',
            'title' => 'Discovery call — Acme S.r.l.',
            'startTime' => '2026-05-10T14:00:00Z',
            'endTime' => '2026-05-10T14:30:00Z',
            'status' => 'ACCEPTED',
            'attendees' => [
                ['email' => 'mario@acme.it', 'name' => 'Mario Rossi'],
            ],
        ],
    ], $overrides);
}

function signCalcom(array $payload): array
{
    $body = json_encode($payload);
    $sig = hash_hmac('sha256', $body, config('services.calcom.webhook_secret'));

    return [$body, $sig];
}

it('rejects an unsigned webhook with 403', function () {
    $body = json_encode(calcomPayload());

    $this->call('POST', '/webhooks/calcom', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertStatus(403);
});

it('rejects a webhook with an invalid signature', function () {
    $body = json_encode(calcomPayload());

    $this->call('POST', '/webhooks/calcom', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_CAL_SIGNATURE_256' => 'deadbeef',
    ], $body)->assertStatus(403);
});

it('accepts a webhook with a valid HMAC signature and creates a CalendarEvent', function () {
    [$body, $sig] = signCalcom(calcomPayload());

    $this->call('POST', '/webhooks/calcom', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_CAL_SIGNATURE_256' => $sig,
    ], $body)->assertStatus(204);

    expect(CalendarEvent::count())->toBe(1);
    $event = CalendarEvent::first();
    expect($event->cal_event_id)->toBe('cal_uid_abc123');
    expect($event->title)->toBe('Discovery call — Acme S.r.l.');
    expect($event->status)->toBe(CalendarEventStatus::Accepted);
    expect($event->attendee_email)->toBe('mario@acme.it');
});

it('also accepts the sha256=<hex> signature form', function () {
    [$body, $sig] = signCalcom(calcomPayload());

    $this->call('POST', '/webhooks/calcom', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_CAL_SIGNATURE_256' => 'sha256='.$sig,
    ], $body)->assertStatus(204);

    expect(CalendarEvent::count())->toBe(1);
});

it('is idempotent: the same event delivered twice produces one row', function () {
    [$body, $sig] = signCalcom(calcomPayload());
    $headers = [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_CAL_SIGNATURE_256' => $sig,
    ];

    $this->call('POST', '/webhooks/calcom', [], [], [], $headers, $body)->assertStatus(204);
    $this->call('POST', '/webhooks/calcom', [], [], [], $headers, $body)->assertStatus(204);

    expect(CalendarEvent::count())->toBe(1);
});

it('updates the existing row on a reschedule webhook for the same uid', function () {
    [$body1, $sig1] = signCalcom(calcomPayload());
    $this->call('POST', '/webhooks/calcom', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_CAL_SIGNATURE_256' => $sig1,
    ], $body1);

    $reschedule = calcomPayload([
        'triggerEvent' => 'BOOKING_RESCHEDULED',
        'payload' => [
            'startTime' => '2026-05-12T16:00:00Z',
            'endTime' => '2026-05-12T16:30:00Z',
        ],
    ]);
    [$body2, $sig2] = signCalcom($reschedule);
    $this->call('POST', '/webhooks/calcom', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_CAL_SIGNATURE_256' => $sig2,
    ], $body2);

    expect(CalendarEvent::count())->toBe(1);
    $event = CalendarEvent::first();
    expect($event->starts_at->equalTo(\Carbon\Carbon::parse('2026-05-12T16:00:00Z')))->toBeTrue();
});

it('marks the row as cancelled when a BOOKING_CANCELLED arrives', function () {
    [$body1, $sig1] = signCalcom(calcomPayload());
    $this->call('POST', '/webhooks/calcom', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_CAL_SIGNATURE_256' => $sig1,
    ], $body1);

    $cancelled = calcomPayload(['triggerEvent' => 'BOOKING_CANCELLED']);
    [$body2, $sig2] = signCalcom($cancelled);
    $this->call('POST', '/webhooks/calcom', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_CAL_SIGNATURE_256' => $sig2,
    ], $body2);

    expect(CalendarEvent::first()->status)->toBe(CalendarEventStatus::Cancelled);
});

it('rejects when no webhook secret is configured', function () {
    config()->set('services.calcom.webhook_secret', '');
    [$body, $sig] = signCalcom(calcomPayload());

    $this->call('POST', '/webhooks/calcom', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_CAL_SIGNATURE_256' => $sig,
    ], $body)->assertStatus(403);
});
