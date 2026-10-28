<?php

declare(strict_types=1);

namespace App\Domains\Mail\Jobs;

use App\Domains\Contacts\Services\Public\ContactsService;
use App\Domains\Mail\Enums\CampaignStatus;
use App\Domains\Mail\Enums\RecipientStatus;
use App\Domains\Mail\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Orchestrator: walks the campaign's recipients table, skipping any
 * contact that is do_not_email or has no email, and pushes a
 * SendCampaignRecipientJob for each remaining recipient.
 *
 * The campaign stays Sending while those jobs run; the last recipient
 * to be processed flips it to Sent (Campaign::completeIfDrained), so
 * the status and sent_at reflect actual delivery, not the enqueueing.
 *
 * Idempotent: re-running on a half-dispatched campaign only enqueues
 * the recipients still marked Pending.
 */
class DispatchCampaignJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $campaignId) {}

    public function handle(ContactsService $contacts): void
    {
        $campaign = Campaign::query()->find($this->campaignId);

        if (! $campaign || $campaign->status->isFinal()) {
            return;
        }

        $campaign->update([
            'status' => CampaignStatus::Sending->value,
        ]);

        $campaign->recipients()->pending()->chunkById(100, function ($recipients) use ($contacts) {
            $byId = $contacts->findMany($recipients->pluck('contact_id')->all())->keyBy('id');

            foreach ($recipients as $recipient) {
                $contact = $byId->get($recipient->contact_id);

                if (! $contact || ! $contact->isMailable()) {
                    $recipient->update(['status' => RecipientStatus::Skipped->value]);

                    continue;
                }

                SendCampaignRecipientJob::dispatch($recipient->id);
            }
        });

        // Nothing left to send (all skipped, or a sync queue already
        // processed everything): complete now.
        $campaign->completeIfDrained();
    }
}
