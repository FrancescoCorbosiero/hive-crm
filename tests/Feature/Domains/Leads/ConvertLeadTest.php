<?php

use App\Domains\Contacts\Enums\ContactRole;
use App\Domains\Contacts\Models\Contact;
use App\Domains\Leads\Enums\LeadStatus;
use App\Domains\Leads\Events\LeadConverted;
use App\Domains\Leads\Models\Lead;
use App\Domains\Leads\Services\Public\LeadsService;
use App\Domains\Websites\Models\Website;
use Illuminate\Support\Facades\Event;

it('converts a lead into a Contact with the customer role', function () {
    Event::fake();

    $lead = Lead::factory()->create([
        'name' => 'Trattoria Da Mario',
        'email' => 'mario@damario.it',
        'phone' => '+39 02 1234',
    ]);

    $result = app(LeadsService::class)->convert($lead->id);

    expect($result['contact']->id)->not->toBeNull();
    expect($result['website'])->toBeNull();

    $contact = Contact::find($result['contact']->id);
    expect($contact->name)->toBe('Trattoria Da Mario');
    expect($contact->email)->toBe('mario@damario.it');
    expect($contact->hasRole(ContactRole::Customer))->toBeTrue();

    Event::assertDispatched(LeadConverted::class, fn (LeadConverted $e) => $e->leadId === $lead->id);
});

it('archives the lead after conversion (status=won + converted timestamps)', function () {
    $lead = Lead::factory()->create();

    app(LeadsService::class)->convert($lead->id);

    $fresh = $lead->fresh();
    expect($fresh->status)->toBe(LeadStatus::Won);
    expect($fresh->converted_contact_id)->not->toBeNull();
    expect($fresh->converted_at)->not->toBeNull();
});

it('also creates a Website when website attributes are passed', function () {
    Event::fake();

    $lead = Lead::factory()->create();

    $result = app(LeadsService::class)->convert($lead->id, [
        'name' => 'Sito di Mario',
        'url' => 'https://damario.it',
    ]);

    expect($result['website'])->not->toBeNull();

    $website = Website::find($result['website']->id);
    expect($website->getTranslation('name', config('app.locale')))->toBe('Sito di Mario');
    expect($website->url)->toBe('https://damario.it');
    expect($website->owner_contact_id)->toBe($result['contact']->id);

    Event::assertDispatched(LeadConverted::class, fn (LeadConverted $e) => $e->websiteId === $website->id);
});

it('refuses to convert an already-converted lead', function () {
    $lead = Lead::factory()->converted(123)->create();

    expect(fn () => app(LeadsService::class)->convert($lead->id))
        ->toThrow(DomainException::class);
});

it('rolls back the entire conversion if any step fails', function () {
    $lead = Lead::factory()->create();

    // Pass intentionally invalid website attributes to trigger a failure
    // after the Contact has already been created in the same transaction.
    expect(function () use ($lead) {
        app(LeadsService::class)->convert($lead->id, [
            // missing the required `url` field
            'name' => 'Bad website',
        ]);
    })->toThrow(\Exception::class);

    expect(Contact::count())->toBe(0);
    expect(Website::count())->toBe(0);
    expect($lead->fresh()->status)->toBe(LeadStatus::New);
});
