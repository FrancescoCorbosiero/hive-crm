<?php

declare(strict_types=1);

namespace App\Domains\Mail\Services\Public;

use App\Domains\Contacts\Services\Public\ContactsService;
use App\Domains\Mail\Enums\CampaignStatus;
use App\Domains\Mail\Enums\RecipientStatus;
use App\Domains\Mail\Jobs\DispatchCampaignJob;
use App\Domains\Mail\Mail\TestMail;
use App\Domains\Mail\Models\Campaign;
use App\Domains\Mail\Models\CampaignRecipient;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class MailService
{
    public function __construct(private readonly ContactsService $contacts) {}

    /**
     * Build the recipients table for a campaign + dispatch the worker.
     *
     * Pre-flights `do_not_email` here so we never even create
     * recipient rows for opted-out contacts. The job-level guard is
     * the second line of defence (race window: bounce arrives mid-batch).
     *
     * @param  array<int>  $contactIds
     */
    public function dispatchCampaign(int $campaignId, array $contactIds): void
    {
        DB::transaction(function () use ($campaignId, $contactIds) {
            $campaign = Campaign::query()->lockForUpdate()->findOrFail($campaignId);

            if ($campaign->status->isFinal()) {
                throw new DomainException("Campaign {$campaignId} is already in final state.");
            }

            $now = now();
            $rows = [];
            foreach (array_unique($contactIds) as $cid) {
                $contact = $this->contacts->find($cid);
                if (! $contact || ! $contact->isMailable()) {
                    continue;
                }

                $rows[] = [
                    'campaign_id' => $campaign->id,
                    'contact_id' => $cid,
                    'status' => RecipientStatus::Pending->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($rows) {
                // Honour the unique (campaign_id, contact_id) constraint.
                CampaignRecipient::query()->upsert(
                    $rows,
                    ['campaign_id', 'contact_id'],
                    ['updated_at'],
                );
            }

            $campaign->update([
                'status' => CampaignStatus::Scheduled->value,
                'recipients_count' => count($rows),
            ]);
        });

        DispatchCampaignJob::dispatch($campaignId);
    }

    public function sendTest(string $toEmail, string $subject, string $bodyHtml): void
    {
        Mail::to($toEmail)->send(new TestMail($subject, $bodyHtml));
    }
}
