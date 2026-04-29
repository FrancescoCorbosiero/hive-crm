<?php

use App\Domains\Contacts\DTOs\ContactDTO;
use App\Domains\Contacts\Enums\ContactRole;
use App\Domains\Contacts\Events\ContactFlaggedDoNotEmail;
use App\Domains\Contacts\Models\Contact;
use App\Domains\Contacts\Services\Public\ContactsService;
use Illuminate\Support\Facades\Event;

it('returns a DTO when finding an existing contact', function () {
    $contact = Contact::factory()->customer()->create();

    $dto = app(ContactsService::class)->find($contact->id);

    expect($dto)->toBeInstanceOf(ContactDTO::class);
    expect($dto->id)->toBe($contact->id);
    expect($dto->name)->toBe($contact->name);
    expect($dto->roles)->toBe([ContactRole::Customer->value]);
});

it('returns null for a missing contact', function () {
    expect(app(ContactsService::class)->find(99999))->toBeNull();
});

it('flags a contact do-not-email and dispatches the matching event', function () {
    Event::fake();

    $contact = Contact::factory()->create(['do_not_email' => false]);

    app(ContactsService::class)->flagDoNotEmail($contact->id, 'unsubscribe');

    expect($contact->fresh()->do_not_email)->toBeTrue();
    Event::assertDispatched(ContactFlaggedDoNotEmail::class, fn ($e) => $e->contactId === $contact->id && $e->reason === 'unsubscribe');
});

it('flagging an already do-not-email contact is a no-op event', function () {
    Event::fake();

    $contact = Contact::factory()->doNotEmail()->create();

    app(ContactsService::class)->flagDoNotEmail($contact->id);

    Event::assertNotDispatched(ContactFlaggedDoNotEmail::class);
});

it('looks up multiple contacts as DTOs in one call', function () {
    $a = Contact::factory()->create();
    $b = Contact::factory()->create();

    $dtos = app(ContactsService::class)->findMany([$a->id, $b->id]);

    expect($dtos)->toHaveCount(2);
    expect($dtos->first())->toBeInstanceOf(ContactDTO::class);
});

it('filters contacts by role through the public service', function () {
    Contact::factory()->customer()->count(3)->create();
    Contact::factory()->vendor()->count(2)->create();

    $customers = app(ContactsService::class)->withRole(ContactRole::Customer);

    expect($customers)->toHaveCount(3);
    expect($customers->first())->toBeInstanceOf(ContactDTO::class);
});

it('reports a DTO as not-mailable when the contact is do-not-email', function () {
    $contact = Contact::factory()->doNotEmail()->create();

    $dto = app(ContactsService::class)->find($contact->id);

    expect($dto->isMailable())->toBeFalse();
});

it('reports a DTO as not-mailable when the contact has no email', function () {
    $contact = Contact::factory()->create(['email' => null]);

    $dto = app(ContactsService::class)->find($contact->id);

    expect($dto->isMailable())->toBeFalse();
});
