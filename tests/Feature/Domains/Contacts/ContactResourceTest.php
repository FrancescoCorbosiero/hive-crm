<?php

use App\Domains\Contacts\Filament\Resources\ContactResource;
use App\Domains\Contacts\Filament\Resources\ContactResource\Pages\CreateContact;
use App\Domains\Contacts\Filament\Resources\ContactResource\Pages\EditContact;
use App\Domains\Contacts\Filament\Resources\ContactResource\Pages\ListContacts;
use App\Domains\Contacts\Models\Contact;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the index page', function () {
    Contact::factory()->count(3)->create();

    Livewire::test(ListContacts::class)->assertSuccessful();
});

it('renders the create page', function () {
    Livewire::test(CreateContact::class)->assertSuccessful();
});

it('renders the edit page for an existing contact', function () {
    $contact = Contact::factory()->create();

    Livewire::test(EditContact::class, ['record' => $contact->id])->assertSuccessful();
});

it('exposes Contacts in the configured navigation group', function () {
    expect(ContactResource::getNavigationGroup())->not->toBeEmpty();
    expect(ContactResource::getModel())->toBe(Contact::class);
});
