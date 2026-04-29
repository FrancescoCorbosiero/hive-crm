<?php

declare(strict_types=1);

namespace App\Domains\Mail\Jobs;

use App\Domains\Contacts\Services\Public\ContactsService;
use App\Domains\Mail\Enums\RecipientStatus;
use App\Domains\Mail\Mail\CampaignMail;
use App\Domains\Mail\Models\CampaignRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;

/**
 * Send one campaign email.
 *
 * Per-recipient because that's the smallest unit of failure: SES rate
 * limits, transient SMTP errors, an individual address bouncing locally
 * — none of those should stop the rest of the batch.
 *
 * RateLimited middleware uses the named "ses-send" limiter (configured
 * in MailServiceProvider) so we can throttle to whatever the SES sandbox
 * or production limit is.
 *
 * Hard rule (from the spec): contacts with do_not_email = true are
 * skipped here in the worker too, not just at dispatch time. Race
 * windows between dispatch and send happen — bounce arrives mid-batch,
 * SES webhook flips the flag, the in-flight worker for that contact
 * checks again and bails.
 */
class SendCampaignRecipientJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $recipientId) {}

    public function middleware(): array
    {
        return [new RateLimited('ses-send')];
    }

    public function handle(ContactsService $contacts): void
    {
        $recipient = CampaignRecipient::query()->find($this->recipientId);

        if (! $recipient) {
            return;
        }

        if ($recipient->status !== RecipientStatus::Pending) {
            return;
        }

        $contact = $contacts->find($recipient->contact_id);

        if (! $contact) {
            $this->markStatus($recipient, RecipientStatus::Skipped);

            return;
        }

        if (! $contact->isMailable()) {
            $this->markStatus($recipient, RecipientStatus::Skipped);

            return;
        }

        $campaign = $recipient->campaign;
        $locale = config('app.locale', 'it');

        $mailable = new CampaignMail(
            campaign: $campaign,
            recipient: $recipient,
            contactName: $contact->name,
            contactEmail: $contact->email,
            forLocale: $locale,
        );

        try {
            $sent = Mail::to($contact->email)->send($mailable);

            // Try to capture the SES message id so SNS notifications can
            // be matched back. Falls back to whatever the mail driver
            // exposes — works with the SES driver and the testing array
            // driver alike.
            $messageId = $this->extractMessageId($sent?->getSymfonySentMessage()?->getOriginalMessage())
                ?? null;

            $recipient->update([
                'status' => RecipientStatus::Sent->value,
                'sent_at' => now(),
                'ses_message_id' => $messageId,
            ]);

            $campaign->increment('sent_count');
        } catch (\Throwable $e) {
            Log::warning('mail.campaign.send_failed', [
                'recipient_id' => $recipient->id,
                'campaign_id' => $recipient->campaign_id,
                'exception' => $e->getMessage(),
            ]);

            $this->markStatus($recipient, RecipientStatus::Failed);
            throw $e; // Trigger queue retry; backoff above.
        }
    }

    private function markStatus(CampaignRecipient $recipient, RecipientStatus $status): void
    {
        $recipient->update(['status' => $status->value]);
    }

    private function extractMessageId(?Email $email): ?string
    {
        if (! $email) {
            return null;
        }

        $header = $email->getHeaders()->get('X-SES-MESSAGE-ID')
            ?? $email->getHeaders()->get('X-Amz-Message-Id')
            ?? $email->getHeaders()->get('Message-ID');

        return $header?->getBodyAsString();
    }
}
