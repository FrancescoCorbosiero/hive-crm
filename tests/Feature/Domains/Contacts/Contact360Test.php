<?php

declare(strict_types=1);

use App\Domains\Contacts\Filament\Resources\ContactResource;
use App\Domains\Contacts\Filament\Resources\ContactResource\Pages\EditContact;
use App\Domains\Contacts\Filament\Resources\ContactResource\Pages\ViewContact;
use App\Domains\Contacts\Filament\Resources\ContactResource\RelationManagers\DomainNamesRelationManager;
use App\Domains\Contacts\Filament\Resources\ContactResource\RelationManagers\WebsitesRelationManager;
use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Models\Fattura;
use App\Domains\DomainNames\Enums\DomainStatus;
use App\Domains\DomainNames\Enums\Registrar;
use App\Domains\DomainNames\Models\DomainName;
use App\Domains\Quotations\Models\Quotation;
use App\Domains\Websites\Models\Website;
use App\Models\User;
use Livewire\Livewire;

/**
 * Contact 360: the customer-view page should expose every related
 * cross-domain artefact in one place AND offer one-click "create
 * related" header actions that pre-fill the new record with this
 * contact.
 *
 * The Websites + DomainNames relation managers were missing prior to
 * this — a glaring gap since the whole cost-tracking story (Steps 1,
 * 4, 5) revolves around those. Quick-create header actions reuse the
 * same public services / URL pre-fill patterns already established.
 */
beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('exposes the Websites and DomainNames relation managers on the Contact view', function () {
    $relations = ContactResource::getRelations();

    expect($relations)->toContain(WebsitesRelationManager::class);
    expect($relations)->toContain(DomainNamesRelationManager::class);
});

it('lists websites owned by the contact via the hasMany relation', function () {
    $contact = Contact::factory()->create();
    Website::factory()->create(['owner_contact_id' => $contact->id]);
    Website::factory()->create(['owner_contact_id' => $contact->id]);
    Website::factory()->create(); // owned by someone else

    expect($contact->websites()->count())->toBe(2);
});

it('lists domains owned by the contact via the hasMany relation', function () {
    $contact = Contact::factory()->create();
    DomainName::query()->create([
        'name' => 'a.com',
        'registrar' => Registrar::Aruba->value,
        'status' => DomainStatus::Active->value,
        'renewal_period_months' => 12,
        'auto_renew' => true,
        'currency' => 'EUR',
        'owner_contact_id' => $contact->id,
    ]);
    DomainName::query()->create([
        'name' => 'b.com',
        'registrar' => Registrar::Other->value,
        'status' => DomainStatus::Active->value,
        'renewal_period_months' => 12,
        'auto_renew' => true,
        'currency' => 'EUR',
        'owner_contact_id' => $contact->id,
    ]);
    DomainName::query()->create([
        'name' => 'someone-else.com',
        'registrar' => Registrar::Other->value,
        'status' => DomainStatus::Active->value,
        'renewal_period_months' => 12,
        'auto_renew' => true,
        'currency' => 'EUR',
    ]);

    expect($contact->domainNames()->count())->toBe(2);
});

it('renders the View page with both new relation managers without errors', function () {
    $contact = Contact::factory()->create();
    Website::factory()->create(['owner_contact_id' => $contact->id]);

    Livewire::test(ViewContact::class, ['record' => $contact->id])
        ->assertSuccessful();
});

it('issues a draft Fattura tied to the contact via the quick-create action', function () {
    $contact = Contact::factory()->create();

    Livewire::test(EditContact::class, ['record' => $contact->id])
        ->callAction('issueFattura');

    $fattura = Fattura::query()->latest('id')->firstOrFail();
    expect($fattura->client_contact_id)->toBe($contact->id);
});

it('creates a draft Quotation tied to the contact via the quick-create action', function () {
    $contact = Contact::factory()->create(['name' => 'Studio Verdi']);

    Livewire::test(EditContact::class, ['record' => $contact->id])
        ->callAction('createQuotation');

    $quotation = Quotation::query()->latest('id')->firstOrFail();
    expect($quotation->client_contact_id)->toBe($contact->id);
    expect($quotation->name)->toBe('Studio Verdi');
});

it('exposes Website / Domain quick-create links pointing at the right URLs', function () {
    $contact = Contact::factory()->create();

    $actions = ContactResource::quickCreateHeaderActions($contact);
    $byName = collect($actions)->keyBy(fn ($a) => $a->getName());

    $websiteUrl = $byName->get('createWebsite')->getUrl();
    expect($websiteUrl)->toContain('owner_contact_id='.$contact->id);

    $domainUrl = $byName->get('registerDomain')->getUrl();
    expect($domainUrl)->toContain('owner_contact_id='.$contact->id);
});

// NOTE: The mount() hook that reads ?owner_contact_id and pre-fills
// the create form is a 5-line read-and-fill with no branching beyond
// a null check. Livewire's synthetic test request doesn't propagate
// query params into request()->query(), and HTTP-level rendering
// tests require a built Vite manifest the test env doesn't ship.
// Coverage strategy: the URL-generation test above proves the
// outgoing link is correctly formed; the receiving pre-fill is
// verified manually in the UI.
