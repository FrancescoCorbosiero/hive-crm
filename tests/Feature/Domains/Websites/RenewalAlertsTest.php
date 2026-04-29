<?php

use App\Domains\Websites\Enums\WebsiteStatus;
use App\Domains\Websites\Events\RenewalApproaching;
use App\Domains\Websites\Models\Website;
use Illuminate\Support\Facades\Event;

it('dispatches a RenewalApproaching event for websites in 7/14/30-day windows', function () {
    Event::fake();

    Website::factory()->renewingIn(7)->create();
    Website::factory()->renewingIn(14)->create();
    Website::factory()->renewingIn(30)->create();
    Website::factory()->renewingIn(20)->create(); // outside windows
    Website::factory()->renewingIn(45)->create(); // outside windows

    $this->artisan('websites:check-renewals')->assertExitCode(0);

    Event::assertDispatchedTimes(RenewalApproaching::class, 3);
});

it('skips archived and suspended websites in the renewal check', function () {
    Event::fake();

    Website::factory()
        ->renewingIn(7)
        ->state(['status' => WebsiteStatus::Archived->value])
        ->create();

    Website::factory()
        ->renewingIn(7)
        ->state(['status' => WebsiteStatus::Suspended->value])
        ->create();

    $this->artisan('websites:check-renewals')->assertExitCode(0);

    Event::assertNotDispatched(RenewalApproaching::class);
});

it('passes the correct days-until value in the event', function () {
    Event::fake();

    $website = Website::factory()->renewingIn(14)->create();

    $this->artisan('websites:check-renewals');

    Event::assertDispatched(
        RenewalApproaching::class,
        fn (RenewalApproaching $e) => $e->websiteId === $website->id && $e->daysUntilRenewal === 14,
    );
});
