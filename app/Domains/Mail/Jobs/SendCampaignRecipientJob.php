<?php

declare(strict_types=1);

namespace App\Domains\Mail\Jobs;

use App\Domains\Contacts\Services\Public\ContactsService;
use App\Domains\Mail\Enums\RecipientStatus;
use App\Domains\Mail\Mail\CampaignMail;
use App\Domains\Mail\Models\CampaignRecipient;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;
use Throwable;

/**
 * Send one campaign email.
 *
 * Per-recipient because that's the smallest unit of failure: SES rate
 * limits, transient SMTP errors, an individual address bouncing locally
 * — none of those should stop the rest of the batch.
 *
 * RateLimited middleware uses the named "ses-send" limiter (configured
 * in MailServiceProvider) so we can throttle to whatever the SES sandbox
 * or production limit is. A rate-limited job is released back onto the
 * queue and every release counts as an attempt, so the retry budget is
 * expressed as a deadline (retryUntil) plus a cap on real failures
 * (maxExceptions) rather than a number of tries.
 *
 * The recipient stays Pending across retries — a retry only re-sends a
 * Pending recipient — and is marked Failed in failed(), once the queue
 * has given up on it.
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

    public int $maxExceptions = 3;

    public int $backoff = 30;

    public function __construct(public int $recipientId) {}

    public function middleware(): array
    {
        return [new RateLimited('ses-send')];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addHours(12);
    }

    public function handle(ContactsService $contacts): void
    {
        $recipient = CampaignRecipient::query()->find($this->recipientId);

        if (! $recipient || $recipient->status !== RecipientStatus::Pending) {
            return;
        }

        $contact = $contacts->find($recipient->contact_id);

        if (! $contact || ! $contact->isMailable()) {
            $this->settle($recipient, RecipientStatus::Skipped);

            return;
        }

        $campaign = $recipient->campaign;

        $mailable = new CampaignMail(
            campaign: $campaign,
            recipient: $recipient,
            contactName: $contact->name,
            contactEmail: $contact->email,
            forLocale: config('app.locale', 'it'),
        );

        try {
            $sent = Mail::to($contact->email)->send($mailable);
        } catch (Throwable $e) {
            Log::warning('mail.campaign.send_failed', [
                'recipient_id' => $recipient->id,
                'campaign_id' => $recipient->campaign_id,
                'attempt' => $this->attempts(),
                'exception' => $e->getMessage(),
            ]);

            throw $e; // Let the queue retry; failed() runs once it gives up.
        }

        // Capture the SES message id so SNS notifications can be matched
        // back. Falls back to whatever the mail driver exposes — works
        // with the SES driver and the testing array driver alike.
        $recipient->update([
            'status' => RecipientStatus::Sent->value,
            'sent_at' => now(),
            'ses_message_id' => $this->extractMessageId($sent?->getSymfonySentMessage()?->getOriginalMessage()),
        ]);

        $campaign->increment('sent_count');
        $campaign->completeIfDrained();
    }

    /**
     * The queue has given up (exceptions or deadline exhausted).
     */
    public function failed(?Throwable $exception = null): void
    {
        $recipient = CampaignRecipient::query()->find($this->recipientId);

        if ($recipient && $recipient->status === RecipientStatus::Pending) {
            $this->settle($recipient, RecipientStatus::Failed);
        }
    }

    private function settle(CampaignRecipient $recipient, RecipientStatus $status): void
    {
        $recipient->update(['status' => $status->value]);
        $recipient->campaign?->completeIfDrained();
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
