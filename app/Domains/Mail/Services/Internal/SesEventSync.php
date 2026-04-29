<?php

declare(strict_types=1);

namespace App\Domains\Mail\Services\Internal;

use App\Domains\Contacts\Services\Public\ContactsService;
use App\Domains\Mail\Enums\RecipientStatus;
use App\Domains\Mail\Models\CampaignRecipient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Translate an SES → SNS notification into recipient + contact updates.
 *
 * Hard rule (from the spec): Bounces and Complaints auto-set
 * do_not_email = true on the contact. We go through ContactsService
 * for that — never touch Contact directly.
 *
 * SNS notification message body (parsed from JSON):
 *  {
 *    "notificationType": "Bounce|Complaint|Delivery",
 *    "mail": { "messageId": "...", ... },
 *    "bounce" | "complaint" | "delivery": { ... }
 *  }
 *
 * SES configuration-set events (Open / Click) use a slightly different
 * "eventType" envelope; both are handled.
 */
class SesEventSync
{
    public function __construct(private readonly ContactsService $contacts) {}

    public function handle(array $message): void
    {
        $type = (string) (
            Arr::get($message, 'eventType')
            ?? Arr::get($message, 'notificationType')
            ?? ''
        );
        $messageId = (string) Arr::get($message, 'mail.messageId', '');

        if ($type === '' || $messageId === '') {
            return;
        }

        $recipient = CampaignRecipient::query()
            ->where('ses_message_id', $messageId)
            ->first();

        if (! $recipient) {
            // Test sends or expired campaigns can land here legitimately.
            // Log and move on — never throw, SES will keep retrying.
            Log::info('mail.ses.unmatched_event', ['type' => $type, 'messageId' => $messageId]);

            return;
        }

        DB::transaction(function () use ($recipient, $type, $message) {
            switch (strtolower($type)) {
                case 'bounce':
                    $this->markRecipient($recipient, RecipientStatus::Bounced, 'bounced_count');
                    $this->flagBouncedAddresses(
                        Arr::get($message, 'bounce.bouncedRecipients', []),
                        'bounce',
                    );
                    break;

                case 'complaint':
                    $this->markRecipient($recipient, RecipientStatus::Complained, 'complained_count');
                    $this->flagBouncedAddresses(
                        Arr::get($message, 'complaint.complainedRecipients', []),
                        'complaint',
                    );
                    break;

                case 'delivery':
                    $this->markRecipient($recipient, RecipientStatus::Delivered, 'delivered_count');
                    break;

                case 'open':
                    $this->markRecipient($recipient, RecipientStatus::Opened, 'opened_count');
                    break;

                case 'click':
                    $this->markRecipient($recipient, RecipientStatus::Clicked, 'clicked_count');
                    break;

                default:
                    Log::info('mail.ses.unknown_event', ['type' => $type, 'messageId' => $recipient->ses_message_id]);
            }
        });
    }

    private function markRecipient(CampaignRecipient $recipient, RecipientStatus $status, string $counter): void
    {
        // Don't downgrade a Delivered recipient back to Sent if a
        // delayed Delivery event arrives after a Bounce. Status only
        // moves toward terminal/engagement states.
        $rank = [
            RecipientStatus::Pending->value => 0,
            RecipientStatus::Sent->value => 1,
            RecipientStatus::Delivered->value => 2,
            RecipientStatus::Bounced->value => 5,
            RecipientStatus::Complained->value => 5,
            RecipientStatus::Opened->value => 3,
            RecipientStatus::Clicked->value => 4,
            RecipientStatus::Unsubscribed->value => 5,
        ];

        $current = $rank[$recipient->status->value] ?? 0;
        $next = $rank[$status->value] ?? 0;

        if ($next >= $current) {
            $recipient->update(['status' => $status->value]);
        }

        $recipient->campaign->increment($counter);
    }

    /**
     * @param  array<int, array<string,mixed>>  $entries
     */
    private function flagBouncedAddresses(array $entries, string $reason): void
    {
        $emails = collect($entries)
            ->pluck('emailAddress')
            ->filter()
            ->values()
            ->all();

        $contactIds = $this->contacts->idsByEmails($emails);

        foreach ($contactIds as $id) {
            $this->contacts->flagDoNotEmail((int) $id, $reason);
        }
    }
}
