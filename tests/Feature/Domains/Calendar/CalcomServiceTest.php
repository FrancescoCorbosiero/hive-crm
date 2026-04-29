<?php

use App\Domains\Calendar\Services\Public\CalcomService;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.calcom.api_key', 'test-api-key');
    config()->set('services.calcom.base_url', 'https://api.cal.com/v2');
});

it('sends an authenticated request to /bookings with the provided afterStart filter', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.cal.com/v2/bookings*' => Http::response([
            'data' => [
                ['uid' => 'cal_1', 'title' => 'Demo'],
                ['uid' => 'cal_2', 'title' => 'Discovery'],
            ],
        ], 200),
    ]);

    $service = new CalcomService(app(HttpFactory::class));
    $rows = $service->getBookingsSince(\Carbon\Carbon::parse('2026-04-29T00:00:00Z'));

    expect($rows)->toHaveCount(2);
    expect($rows[0]['uid'])->toBe('cal_1');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'afterStart=2026-04-29T00%3A00%3A00Z')
            && $request->hasHeader('Authorization', 'Bearer test-api-key');
    });
});

it('retries on 5xx responses and eventually returns the successful body', function () {
    Http::preventStrayRequests();
    Http::fakeSequence('https://api.cal.com/v2/bookings*')
        ->push('boom', 500)
        ->push('boom', 502)
        ->push(['data' => [['uid' => 'cal_recovered']]], 200);

    $service = new CalcomService(app(HttpFactory::class));
    $rows = $service->getBookingsSince(now());

    expect($rows)->toHaveCount(1);
    expect($rows[0]['uid'])->toBe('cal_recovered');

    // 1 original + 2 retries = 3 attempts before success.
    Http::assertSentCount(3);
});

it('returns null for a 404 booking lookup', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.cal.com/v2/bookings/missing' => Http::response('', 404),
    ]);

    $service = new CalcomService(app(HttpFactory::class));

    expect($service->getBooking('missing'))->toBeNull();
});

it('throws on a persistent 4xx (no retry on client errors)', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.cal.com/v2/bookings*' => Http::response('forbidden', 403),
    ]);

    $service = new CalcomService(app(HttpFactory::class));

    expect(fn () => $service->getBookingsSince(now()))
        ->toThrow(\Illuminate\Http\Client\RequestException::class);

    // No retries on 403.
    Http::assertSentCount(1);
});
