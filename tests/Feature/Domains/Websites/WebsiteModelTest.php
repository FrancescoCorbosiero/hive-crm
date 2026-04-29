<?php

use App\Domains\Websites\Enums\WebsiteStatus;
use App\Domains\Websites\Models\Website;

it('round-trips translatable name through JSON storage', function () {
    $website = Website::factory()->create([
        'name' => ['it' => 'Sito di prova', 'en' => 'Test site'],
    ]);

    $fresh = $website->fresh();

    expect($fresh->getTranslation('name', 'it'))->toBe('Sito di prova');
    expect($fresh->getTranslation('name', 'en'))->toBe('Test site');
});

it('falls back to the configured fallback locale for missing translations', function () {
    $website = Website::factory()->create([
        'name' => ['en' => 'Only English'],
    ]);

    expect($website->getTranslation('name', 'it', useFallbackLocale: true))->toBe('Only English');
});

it('casts the status column to the WebsiteStatus enum', function () {
    $website = Website::factory()->create(['status' => WebsiteStatus::Maintenance->value]);

    expect($website->fresh()->status)->toBe(WebsiteStatus::Maintenance);
});

it('computes days until renewal correctly', function () {
    $website = Website::factory()->renewingIn(7)->create();

    expect($website->daysUntilRenewal())->toBe(7);
});

it('returns null for daysUntilRenewal when no renewal date is set', function () {
    $website = Website::factory()->create(['next_renewal_at' => null]);

    expect($website->daysUntilRenewal())->toBeNull();
});

it('finds websites renewing within a window', function () {
    Website::factory()->renewingIn(3)->create();
    Website::factory()->renewingIn(20)->create();
    Website::factory()->renewingIn(40)->create();

    expect(Website::renewingWithin(7)->count())->toBe(1);
    expect(Website::renewingWithin(30)->count())->toBe(2);
});

it('round-trips a tech_stack array through JSON storage', function () {
    $website = Website::factory()->create([
        'tech_stack' => ['Laravel', 'Tailwind'],
    ]);

    expect((array) $website->fresh()->tech_stack)->toBe(['Laravel', 'Tailwind']);
});
