<?php

use App\Domains\Websites\Enums\WebsiteStatus;
use App\Domains\Websites\Models\Website;
use Illuminate\Support\Facades\Http;

it('pings every active website and updates ping columns', function () {
    Http::preventStrayRequests();
    Http::fake(['*' => Http::response('', 200)]);

    Website::factory()->count(3)->active()->create();
    Website::factory()->state(['status' => WebsiteStatus::Archived->value])->create();

    $this->artisan('websites:ping')->assertExitCode(0);

    expect(Website::query()->whereNotNull('last_pinged_at')->count())->toBe(3);
});

it('skips archived sites in the command', function () {
    Http::preventStrayRequests();

    Website::factory()->state(['status' => WebsiteStatus::Archived->value])->create();

    $this->artisan('websites:ping')->assertExitCode(0);

    Http::assertNothingSent();
});
