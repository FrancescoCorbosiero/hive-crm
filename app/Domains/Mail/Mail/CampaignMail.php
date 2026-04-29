<?php

declare(strict_types=1);

namespace App\Domains\Mail\Mail;

use App\Domains\Mail\Models\Campaign;
use App\Domains\Mail\Models\CampaignRecipient;
use App\Domains\Mail\Support\UnsubscribeToken;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class CampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Campaign $campaign,
        public CampaignRecipient $recipient,
        public string $contactName,
        public string $contactEmail,
        public string $forLocale,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->campaign->getTranslation('subject', $this->forLocale, useFallbackLocale: true);

        return new Envelope(
            subject: $subject,
            metadata: [
                'campaign_id' => (string) $this->campaign->id,
                'recipient_id' => (string) $this->recipient->id,
            ],
        );
    }

    public function content(): Content
    {
        $body = $this->campaign->getTranslation('body_html', $this->forLocale, useFallbackLocale: true);

        // Token-replacement happens here, never in the DB. Keep the merge
        // tag list small + obvious — easy to teach, easy to audit.
        $body = strtr($body, [
            '{{name}}' => e($this->contactName),
            '{{email}}' => e($this->contactEmail),
            '{{unsubscribe_url}}' => UnsubscribeToken::url($this->recipient->contact_id),
        ]);

        return new Content(
            view: 'mail.campaign',
            with: [
                'bodyHtml' => $body,
                'unsubscribeUrl' => UnsubscribeToken::url($this->recipient->contact_id),
            ],
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'List-Unsubscribe' => '<'.UnsubscribeToken::url($this->recipient->contact_id).'>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ],
        );
    }
}
