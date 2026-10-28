<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Mail\Enums\CampaignStatus;
use App\Domains\Mail\Enums\RecipientStatus;
use App\Domains\Mail\Jobs\DispatchCampaignJob;
use App\Domains\Mail\Jobs\SendCampaignRecipientJob;
use App\Domains\Mail\Models\Campaign;
use App\Domains\Mail\Models\CampaignRecipient;
use App\Domains\Mail\Services\Public\MailService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

function pendingRecipient(?Campaign $campaign = null): CampaignRecipient
{
    return CampaignRecipient::factory()->create([
        'campaign_id' => ($campaign ?? Campaign::factory()->create(['status' => CampaignStatus::Sending->value]))->id,
        'contact_id' => Contact::factory()->create(['do_not_email' => false])->id,
        'status' => RecipientStatus::Pending->value,
    ]);
}

it('actually retries a send that failed transiently', function () {
    $recipient = pendingRecipient();
    $job = new SendCampaignRecipientJob($recipient->id);

    Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('SES: Throttling'));
    expect(fn () => app()->call([$job, 'handle']))->toThrow(RuntimeException::class);

    // Still eligible for the queue's next attempt…
    expect($recipient->fresh()->status)->toBe(RecipientStatus::Pending);

    // …which delivers it.
    Mail::fake();
    app()->call([$job, 'handle']);
    expect($recipient->fresh()->status)->toBe(RecipientStatus::Sent);
    Mail::assertSentCount(1);
});

it('marks the recipient Failed only once the queue gives up on it', function () {
    $recipient = pendingRecipient();

    (new SendCampaignRecipientJob($recipient->id))->failed(new RuntimeException('SES: MessageRejected'));

    expect($recipient->fresh()->status)->toBe(RecipientStatus::Failed);
});

it('budgets retries on exceptions and time, so rate-limit releases cannot exhaust it', function () {
    // The RateLimited middleware releases the job back onto the queue, and
    // every release counts as an attempt: with `$tries = 3`, any campaign
    // bigger than three minutes' worth of the SES limit lost recipients.
    $job = new SendCampaignRecipientJob(1);

    expect($job->tries ?? null)->toBeNull();
    expect($job->maxExceptions)->toBe(3);
    expect($job->retryUntil()->isFuture())->toBeTrue();
});

it('keeps a campaign Sending until its last recipient is processed', function () {
    Bus::fake([SendCampaignRecipientJob::class]);
    $campaign = Campaign::factory()->create(['status' => CampaignStatus::Scheduled->value]);
    $first = pendingRecipient($campaign);
    $second = pendingRecipient($campaign);

    app()->call([new DispatchCampaignJob($campaign->id), 'handle']);

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Sending);
    expect($campaign->fresh()->sent_at)->toBeNull();
    Bus::assertDispatchedTimes(SendCampaignRecipientJob::class, 2);

    Mail::fake();
    app()->call([new SendCampaignRecipientJob($first->id), 'handle']);
    expect($campaign->fresh()->status)->toBe(CampaignStatus::Sending);

    app()->call([new SendCampaignRecipientJob($second->id), 'handle']);
    expect($campaign->fresh()->status)->toBe(CampaignStatus::Sent);
    expect($campaign->fresh()->sent_at)->not->toBeNull();
});

it('completes a campaign whose recipients were all skipped at dispatch', function () {
    $campaign = Campaign::factory()->create(['status' => CampaignStatus::Scheduled->value]);
    $recipient = pendingRecipient($campaign);
    Contact::query()->whereKey($recipient->contact_id)->update(['do_not_email' => true]);

    app()->call([new DispatchCampaignJob($campaign->id), 'handle']);

    expect($recipient->fresh()->status)->toBe(RecipientStatus::Skipped);
    expect($campaign->fresh()->status)->toBe(CampaignStatus::Sent);
});

it('builds the recipient list without one contact lookup per recipient', function () {
    Bus::fake();
    $campaign = Campaign::factory()->create();
    $ids = Contact::factory()->count(60)->create(['do_not_email' => false])->pluck('id')->all();

    DB::enableQueryLog();
    app(MailService::class)->dispatchCampaign($campaign->id, $ids);
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect(CampaignRecipient::count())->toBe(60);
    expect($queries)->toBeLessThan(10);
});
