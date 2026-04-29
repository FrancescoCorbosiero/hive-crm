<?php

use App\Domains\Websites\Filament\Resources\WebsiteResource\Pages\CreateWebsite;
use App\Domains\Websites\Filament\Resources\WebsiteResource\Pages\EditWebsite;
use App\Domains\Websites\Filament\Resources\WebsiteResource\Pages\ListWebsites;
use App\Domains\Websites\Models\Website;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('renders the websites index page', function () {
    Website::factory()->count(2)->create();
    Livewire::test(ListWebsites::class)->assertSuccessful();
});

it('renders the websites create page', function () {
    Livewire::test(CreateWebsite::class)->assertSuccessful();
});

it('renders the websites edit page', function () {
    $website = Website::factory()->create();
    Livewire::test(EditWebsite::class, ['record' => $website->id])->assertSuccessful();
});
