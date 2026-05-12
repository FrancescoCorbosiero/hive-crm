<?php

use App\Domains\Contacts\Enums\ContactRole;
use App\Domains\Contacts\Listeners\TagCustomerOnFatturaPaid;
use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Events\FatturaPaid;

it('tags the contact as Customer when a fattura is paid', function () {
    $contact = Contact::factory()->create(['roles' => []]);

    (new TagCustomerOnFatturaPaid())->handle(
        new FatturaPaid(fatturaId: 1, clientContactId: $contact->id),
    );

    expect($contact->fresh()->hasRole(ContactRole::Customer))->toBeTrue();
});

it('is idempotent when the role is already present', function () {
    $contact = Contact::factory()->create([
        'roles' => [ContactRole::Customer->value],
    ]);
    $originalUpdatedAt = $contact->updated_at;

    (new TagCustomerOnFatturaPaid())->handle(
        new FatturaPaid(fatturaId: 1, clientContactId: $contact->id),
    );

    expect($contact->fresh()->roles)->toBe([ContactRole::Customer->value]);
    expect($contact->fresh()->updated_at->equalTo($originalUpdatedAt))->toBeTrue();
});

it('preserves existing non-Customer roles', function () {
    $contact = Contact::factory()->create([
        'roles' => [ContactRole::Vendor->value],
    ]);

    (new TagCustomerOnFatturaPaid())->handle(
        new FatturaPaid(fatturaId: 1, clientContactId: $contact->id),
    );

    $roles = $contact->fresh()->roles;
    expect($roles)->toContain(ContactRole::Vendor->value);
    expect($roles)->toContain(ContactRole::Customer->value);
});

it('silently ignores a missing contact', function () {
    (new TagCustomerOnFatturaPaid())->handle(
        new FatturaPaid(fatturaId: 1, clientContactId: 999_999),
    );

    expect(true)->toBeTrue(); // no exception
});
