<?php

use App\Domains\Contacts\Enums\ContactRole;
use App\Domains\Contacts\Models\Contact;

it('creates a contact via the factory with at least one role', function () {
    $contact = Contact::factory()->create();

    expect($contact->id)->not->toBeNull();
    expect($contact->roles)->toBeArray();
    expect(count($contact->roles))->toBeGreaterThanOrEqual(1);
});

it('treats roles as a flag-set rather than a single value', function () {
    $contact = Contact::factory()->create([
        'roles' => [ContactRole::Customer->value, ContactRole::Vendor->value],
    ]);

    expect($contact->hasRole(ContactRole::Customer))->toBeTrue();
    expect($contact->hasRole(ContactRole::Vendor))->toBeTrue();
    expect($contact->hasRole(ContactRole::Collaborator))->toBeFalse();
});

it('assigns a role idempotently', function () {
    $contact = Contact::factory()->create(['roles' => []]);

    $contact->assignRole(ContactRole::Customer)->save();
    $contact->assignRole(ContactRole::Customer)->save();

    expect($contact->fresh()->roles)->toBe([ContactRole::Customer->value]);
});

it('removes a role without touching the others', function () {
    $contact = Contact::factory()->create([
        'roles' => [ContactRole::Customer->value, ContactRole::Vendor->value],
    ]);

    $contact->removeRole(ContactRole::Customer)->save();

    expect($contact->fresh()->roles)->toBe([ContactRole::Vendor->value]);
});

it('filters contacts by role with the withRole scope', function () {
    Contact::factory()->customer()->create();
    Contact::factory()->customer()->create();
    Contact::factory()->vendor()->create();

    expect(Contact::withRole(ContactRole::Customer)->count())->toBe(2);
    expect(Contact::withRole(ContactRole::Vendor)->count())->toBe(1);
});

it('skips do-not-email contacts in the mailable scope', function () {
    Contact::factory()->create(['do_not_email' => false]);
    Contact::factory()->doNotEmail()->create();

    expect(Contact::mailable()->count())->toBe(1);
});

it('excludes contacts with no email from the mailable scope', function () {
    Contact::factory()->create(['email' => 'has@example.com']);
    Contact::factory()->create(['email' => null]);

    expect(Contact::mailable()->count())->toBe(1);
});

it('auto-assigns the owner_user_id to the seeded admin when one exists', function () {
    \App\Models\User::factory()->create();

    $contact = Contact::factory()->create(['owner_user_id' => null]);

    expect($contact->owner_user_id)->not->toBeNull();
});

it('stores the address as an arrayobject and reads it back as keys', function () {
    $contact = Contact::factory()->create([
        'address' => ['street' => 'Via Roma 1', 'city' => 'Milano'],
    ]);

    expect($contact->fresh()->address['street'])->toBe('Via Roma 1');
    expect($contact->fresh()->address['city'])->toBe('Milano');
});
