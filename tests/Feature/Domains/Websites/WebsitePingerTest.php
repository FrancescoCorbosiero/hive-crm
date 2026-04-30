<?php

use App\Domains\Websites\Events\WebsiteWentDown;
use App\Domains\Websites\Models\Website;
use App\Domains\Websites\Services\Internal\WebsitePinger;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

it('marks the website is_up=true on a 2xx HEAD response', function () {
    Http::preventStrayRequests();
    Http::fake(['https://example.com' => Http::response('', 200)]);

    $w = Website::factory()->create(['url' => 'https://example.com', 'is_up' => null]);

    app(WebsitePinger::class)->ping($w);

    $fresh = $w->fresh();
    expect($fresh->is_up)->toBeTrue();
    expect($fresh->last_status_code)->toBe(200);
    expect($fresh->last_pinged_at)->not->toBeNull();
});

it('treats a 3xx redirect as up', function () {
    Http::preventStrayRequests();
    Http::fake(['https://example.com' => Http::response('', 301)]);

    $w = Website::factory()->create(['url' => 'https://example.com']);
    app(WebsitePinger::class)->ping($w);

    expect($w->fresh()->is_up)->toBeTrue();
    expect($w->fresh()->last_status_code)->toBe(301);
});

it('falls back to GET when HEAD returns 4xx', function () {
    Http::preventStrayRequests();
    Http::fake([
        // Sequence of responses for any matched URL.
        'https://example.com' => Http::sequence()
            ->push('', 405)  // HEAD response
            ->push('', 200), // GET response
    ]);

    $w = Website::factory()->create(['url' => 'https://example.com']);
    app(WebsitePinger::class)->ping($w);

    expect($w->fresh()->is_up)->toBeTrue();
    expect($w->fresh()->last_status_code)->toBe(200);
});

it('marks down on a 5xx response', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://example.com' => Http::sequence()
            ->push('', 500)
            ->push('', 503),
    ]);

    $w = Website::factory()->create(['url' => 'https://example.com']);
    app(WebsitePinger::class)->ping($w);

    expect($w->fresh()->is_up)->toBeFalse();
});

it('marks down on a connection failure', function () {
    Http::preventStrayRequests();
    Http::fake(function () {
        throw new \Illuminate\Http\Client\ConnectionException('refused');
    });

    $w = Website::factory()->create(['url' => 'https://example.com']);
    app(WebsitePinger::class)->ping($w);

    expect($w->fresh()->is_up)->toBeFalse();
    expect($w->fresh()->last_status_code)->toBeNull();
});

it('dispatches WebsiteWentDown only on a true up→down transition', function () {
    Event::fake();
    Http::preventStrayRequests();
    Http::fake([
        'https://example.com' => Http::sequence()
            ->push('', 500)
            ->push('', 503),
    ]);

    $w = Website::factory()->create(['url' => 'https://example.com', 'is_up' => true]);

    app(WebsitePinger::class)->ping($w);

    Event::assertDispatched(WebsiteWentDown::class, fn ($e) => $e->websiteId === $w->id);
});

it('does NOT dispatch WebsiteWentDown on the very first ping (previous state unknown)', function () {
    Event::fake();
    Http::preventStrayRequests();
    Http::fake([
        'https://example.com' => Http::sequence()
            ->push('', 500)
            ->push('', 503),
    ]);

    $w = Website::factory()->create(['url' => 'https://example.com', 'is_up' => null]);
    app(WebsitePinger::class)->ping($w);

    Event::assertNotDispatched(WebsiteWentDown::class);
});

it('does NOT re-dispatch on a sustained-down site', function () {
    Event::fake();
    Http::preventStrayRequests();
    Http::fake([
        'https://example.com' => Http::sequence()
            ->push('', 500)
            ->push('', 503),
    ]);

    $w = Website::factory()->create(['url' => 'https://example.com', 'is_up' => false]);
    app(WebsitePinger::class)->ping($w);

    Event::assertNotDispatched(WebsiteWentDown::class);
});

it('skips websites with no url', function () {
    $w = Website::factory()->create(['url' => '']);
    app(WebsitePinger::class)->ping($w);

    expect($w->fresh()->is_up)->toBeNull();
});
