<?php

use App\Domains\Contacts\Models\Contact;
use Spatie\Activitylog\Models\Activity;

it('soft-deletes a contact instead of removing the row', function () {
    $contact = Contact::factory()->create();

    $contact->delete();

    expect(Contact::query()->count())->toBe(0);
    expect(Contact::withTrashed()->count())->toBe(1);
    expect($contact->fresh()->trashed())->toBeTrue();
});

it('restores a soft-deleted contact', function () {
    $contact = Contact::factory()->create();
    $contact->delete();

    Contact::withTrashed()->find($contact->id)->restore();

    expect(Contact::query()->count())->toBe(1);
});

it('hard-deletes via forceDelete', function () {
    $contact = Contact::factory()->create();

    $contact->forceDelete();

    expect(Contact::withTrashed()->count())->toBe(0);
});

it('logs an activity row on contact create', function () {
    $contact = Contact::factory()->create();

    $row = Activity::query()->where('subject_id', $contact->id)->latest()->first();
    expect($row)->not->toBeNull();
    expect($row->event)->toBe('created');
    expect($row->log_name)->toBe('contact');
});

it('logs an activity row on contact update with a diff payload', function () {
    $contact = Contact::factory()->create(['name' => 'Old Name']);
    $contact->update(['name' => 'New Name']);

    $row = Activity::query()->where('subject_id', $contact->id)->where('event', 'updated')->first();
    expect($row)->not->toBeNull();
    expect($row->properties['attributes']['name'])->toBe('New Name');
    expect($row->properties['old']['name'])->toBe('Old Name');
});

it('does not log an empty update (logOnlyDirty is on)', function () {
    $contact = Contact::factory()->create();
    $beforeCount = Activity::query()->count();

    // Save with no changes — logOnlyDirty + dontSubmitEmptyLogs
    // means no activity row should appear.
    $contact->save();

    expect(Activity::query()->count())->toBe($beforeCount);
});
