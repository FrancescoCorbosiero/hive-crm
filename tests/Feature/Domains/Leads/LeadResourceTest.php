<?php

use App\Domains\Leads\Filament\Resources\LeadResource\Pages\CreateLead;
use App\Domains\Leads\Filament\Resources\LeadResource\Pages\EditLead;
use App\Domains\Leads\Filament\Resources\LeadResource\Pages\ListLeads;
use App\Domains\Leads\Models\Lead;
use App\Models\User;
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
