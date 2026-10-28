<?php

use App\Domains\Leads\Enums\LeadStatus;
use App\Domains\Leads\Filament\Resources\LeadResource\Pages\CreateLead;
use App\Domains\Leads\Filament\Resources\LeadResource\Pages\EditLead;
use App\Domains\Leads\Filament\Resources\LeadResource\Pages\ListLeads;
use App\Domains\Leads\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('renders the leads index page', function () {
    Lead::factory()->count(2)->create();
    Livewire::test(ListLeads::class)->assertSuccessful();
});

it('renders the leads create page', function () {
    Livewire::test(CreateLead::class)->assertSuccessful();
});

it('renders the leads edit page', function () {
    $lead = Lead::factory()->create();
    Livewire::test(EditLead::class, ['record' => $lead->id])->assertSuccessful();
});

it('counts every status tab badge from one grouped query', function () {
    Lead::factory()->count(3)->create(['status' => LeadStatus::New->value]);
    Lead::factory()->count(2)->create(['status' => LeadStatus::Proposal->value]);
    Lead::factory()->create(['status' => LeadStatus::Won->value]);

    // Was one COUNT per tab (8 here) on every search, sort and page turn.
    DB::enableQueryLog();
    $page = Livewire::test(ListLeads::class)->instance();
    $queries = count(DB::getQueryLog());

    $badges = collect($page->getTabs())->map(fn ($tab) => $tab->getBadge());
    expect($badges->only(['all', 'open', 'new', 'proposal', 'won', 'lost'])->all())
        ->toBe(['all' => 6, 'open' => 5, 'new' => 3, 'proposal' => 2, 'won' => 1, 'lost' => 0]);
    expect($queries)->toBeLessThanOrEqual(3); // table count + rows + tab counts
});
