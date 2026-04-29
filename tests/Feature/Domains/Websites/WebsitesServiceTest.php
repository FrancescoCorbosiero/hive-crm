<?php

use App\Domains\Websites\DTOs\WebsiteDTO;
use App\Domains\Websites\Models\Website;
use App\Domains\Websites\Services\Public\WebsitesService;

it('returns a DTO for an existing website', function () {
    $website = Website::factory()->create([
        'name' => ['it' => 'Sito IT', 'en' => 'Site EN'],
    ]);

    $dto = app(WebsitesService::class)->find($website->id);

    expect($dto)->toBeInstanceOf(WebsiteDTO::class);
    expect($dto->id)->toBe($website->id);
    expect($dto->nameForLocale('it'))->toBe('Sito IT');
    expect($dto->nameForLocale('en'))->toBe('Site EN');
});

it('returns null for a missing website', function () {
    expect(app(WebsitesService::class)->find(99999))->toBeNull();
});

it('lists websites owned by a contact (scalar FK lookup)', function () {
    Website::factory()->count(2)->create(['owner_contact_id' => 42]);
    Website::factory()->create(['owner_contact_id' => 99]);

    $dtos = app(WebsitesService::class)->forContact(42);

    expect($dtos)->toHaveCount(2);
});

it('returns websites renewing in a window as DTOs', function () {
    Website::factory()->renewingIn(5)->create();
    Website::factory()->renewingIn(8)->create();

    $dtos = app(WebsitesService::class)->renewingWithin(7);

    expect($dtos)->toHaveCount(1);
    expect($dtos->first())->toBeInstanceOf(WebsiteDTO::class);
});
