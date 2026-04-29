<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Mail\Enums\CampaignStatus;
use App\Domains\Mail\Enums\RecipientStatus;
use App\Domains\Mail\Mail\CampaignMail;
use App\Domains\Mail\Models\Campaign;
use App\Domains\Mail\Models\CampaignRecipient;
use App\Domains\Mail\Services\Public\MailService;
use Illuminate\Support\Facades\Mail;

it('dispatches an email per recipient for a 100-contact campaign', function () {
    Mail::fake();

    $campaign = Campaign::factory()->create();
    $contacts = Contact::factory()->count(100)->create([
        'do_not_email' => false,
    ]);

    app(MailService::class)->dispatchCampaign(
        $campaign->id,
        $contacts->pluck('id')->all(),
    );

    Mail::assertSentCount(100);
    Mail::assertSent(CampaignMail::class, 100);

    expect(CampaignRecipient::count())->toBe(100);
    expect($campaign->fresh()->recipients_count)->toBe(100);
    expect($campaign->fresh()->status)->toBe(CampaignStatus::Sent);
});

it('skips do_not_email contacts at dispatch time', function () {
    Mail::fake();

    $campaign = Campaign::factory()->create();
    $mailable = Contact::factory()->count(40)->create(['do_not_email' => false]);
    $optedOut = Contact::factory()->count(10)->create(['do_not_email' => true]);

    app(MailService::class)->dispatchCampaign(
        $campaign->id,
        $mailable->merge($optedOut)->pluck('id')->all(),
    );

    Mail::assertSentCount(40);
    expect(CampaignRecipient::count())->toBe(40);
});

it('skips contacts with no email at dispatch time', function () {
    Mail::fake();

    $campaign = Campaign::factory()->create();
    $hasEmail = Contact::factory()->count(3)->create(['do_not_email' => false, 'email' => 'x@y.it']);
    $noEmail = Contact::factory()->count(2)->create(['do_not_email' => false, 'email' => null]);

    app(MailService::class)->dispatchCampaign(
        $campaign->id,
        $hasEmail->merge($noEmail)->pluck('id')->all(),
    );

    Mail::assertSentCount(3);
});

it('enforces idempotency on (campaign_id, contact_id) — no duplicate recipient rows', function () {
    Mail::fake();

    $campaign = Campaign::factory()->create();
    $contacts = Contact::factory()->count(5)->create(['do_not_email' => false]);

    $ids = $contacts->pluck('id')->all();
    $idsWithDupes = array_merge($ids, $ids);

    app(MailService::class)->dispatchCampaign($campaign->id, $idsWithDupes);

    expect(CampaignRecipient::count())->toBe(5);
});

it('refuses to dispatch a campaign already in a final state', function () {
    $campaign = Campaign::factory()->create([
        'status' => CampaignStatus::Sent->value,
    ]);

    expect(fn () => app(MailService::class)->dispatchCampaign($campaign->id, []))
        ->toThrow(DomainException::class);
});

it('worker skips a recipient whose contact was flagged do_not_email after dispatch', function () {
    Mail::fake();

    $campaign = Campaign::factory()->create();
    $contact = Contact::factory()->create(['do_not_email' => false]);
    $recipient = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
    ]);

    // Race condition: bounce arrives between dispatch and worker.
    $contact->update(['do_not_email' => true]);

    \App\Domains\Mail\Jobs\SendCampaignRecipientJob::dispatchSync($recipient->id);

    Mail::assertNothingSent();
    expect($recipient->fresh()->status)->toBe(RecipientStatus::Skipped);
});
