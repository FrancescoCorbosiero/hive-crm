<?php

use App\Domains\Mail\Filament\Pages\MailTestPage;
use App\Domains\Mail\Filament\Resources\CampaignResource\Pages\CreateCampaign;
use App\Domains\Mail\Filament\Resources\CampaignResource\Pages\EditCampaign;
use App\Domains\Mail\Filament\Resources\CampaignResource\Pages\ListCampaigns;
use App\Domains\Mail\Models\Campaign;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('renders the campaigns index page', function () {
    Campaign::factory()->count(2)->create();
    Livewire::test(ListCampaigns::class)->assertSuccessful();
});

it('renders the campaign create page', function () {
    Livewire::test(CreateCampaign::class)->assertSuccessful();
});

it('renders the campaign edit page', function () {
    $campaign = Campaign::factory()->create();
    Livewire::test(EditCampaign::class, ['record' => $campaign->id])->assertSuccessful();
});

it('renders the MailTestPage', function () {
    Livewire::test(MailTestPage::class)->assertSuccessful();
});
