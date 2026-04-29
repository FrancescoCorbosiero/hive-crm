<?php

use App\Domains\Contacts\Models\Contact;
use App\Domains\Mail\Enums\RecipientStatus;
use App\Domains\Mail\Models\Campaign;
use App\Domains\Mail\Models\CampaignRecipient;
use App\Domains\Mail\Services\Internal\SesEventSync;
use App\Domains\Mail\Support\SnsMessageValidator;
use Aws\Sns\Message;

beforeEach(function () {
    // Swap the SNS validator for a fake that fills required fields and
    // bypasses signature checks. The dedicated rejection test below
    // restores the real validator to cover the negative path.
    $this->app->bind(SnsMessageValidator::class, function () {
        return new class extends SnsMessageValidator {
            public function validate(string $rawBody): Message
            {
                $payload = json_decode($rawBody, true) ?: [];
                $payload += [
                    'MessageId' => 'fake-'.bin2hex(random_bytes(4)),
                    'Timestamp' => now()->toIso8601ZuluString(),
                    'TopicArn' => 'arn:aws:sns:eu-south-1:0:t',
                    'Type' => 'Notification',
                    'Signature' => 'fake',
                    'SigningCertURL' => 'https://sns.eu-south-1.amazonaws.com/SimpleNotificationService.pem',
                    'SignatureVersion' => '1',
                ];

                return new Message($payload);
            }
        };
    });
});

function snsNotification(array $sesPayload): array
{
    return [
        'Type' => 'Notification',
        'MessageId' => 'sns-'.bin2hex(random_bytes(8)),
        'TopicArn' => 'arn:aws:sns:eu-south-1:000000000000:test',
        'Message' => json_encode($sesPayload),
    ];
}

it('flips do_not_email on the Contact when a Bounce arrives', function () {
    $contact = Contact::factory()->create(['email' => 'bouncey@example.com', 'do_not_email' => false]);
    $campaign = Campaign::factory()->create();
    $recipient = CampaignRecipient::factory()
        ->withMessageId('ses-msg-1')
        ->create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
        ]);

    $body = json_encode(snsNotification([
        'notificationType' => 'Bounce',
        'mail' => ['messageId' => 'ses-msg-1'],
        'bounce' => [
            'bouncedRecipients' => [['emailAddress' => 'bouncey@example.com']],
        ],
    ]));

    $this->call('POST', '/webhooks/ses', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertStatus(204);

    expect($contact->fresh()->do_not_email)->toBeTrue();
    expect($recipient->fresh()->status)->toBe(RecipientStatus::Bounced);
    expect($campaign->fresh()->bounced_count)->toBe(1);
});

it('flips do_not_email on the Contact when a Complaint arrives', function () {
    $contact = Contact::factory()->create(['email' => 'angry@example.com', 'do_not_email' => false]);
    $campaign = Campaign::factory()->create();
    CampaignRecipient::factory()
        ->withMessageId('ses-msg-c')
        ->create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
        ]);

    $body = json_encode(snsNotification([
        'notificationType' => 'Complaint',
        'mail' => ['messageId' => 'ses-msg-c'],
        'complaint' => [
            'complainedRecipients' => [['emailAddress' => 'angry@example.com']],
        ],
    ]));

    $this->call('POST', '/webhooks/ses', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertStatus(204);

    expect($contact->fresh()->do_not_email)->toBeTrue();
});

it('marks a recipient delivered without flipping do_not_email', function () {
    $contact = Contact::factory()->create(['email' => 'happy@example.com', 'do_not_email' => false]);
    $campaign = Campaign::factory()->create();
    $recipient = CampaignRecipient::factory()
        ->withMessageId('ses-msg-d')
        ->create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
        ]);

    $body = json_encode(snsNotification([
        'notificationType' => 'Delivery',
        'mail' => ['messageId' => 'ses-msg-d'],
    ]));

    $this->call('POST', '/webhooks/ses', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertStatus(204);

    expect($contact->fresh()->do_not_email)->toBeFalse();
    expect($recipient->fresh()->status)->toBe(RecipientStatus::Delivered);
    expect($campaign->fresh()->delivered_count)->toBe(1);
});

it('does not downgrade a Bounced recipient if a late Delivery arrives', function () {
    $contact = Contact::factory()->create();
    $campaign = Campaign::factory()->create();
    $recipient = CampaignRecipient::factory()
        ->withMessageId('ses-msg-late')
        ->create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
        ]);

    app(SesEventSync::class)->handle([
        'notificationType' => 'Bounce',
        'mail' => ['messageId' => 'ses-msg-late'],
        'bounce' => ['bouncedRecipients' => []],
    ]);
    app(SesEventSync::class)->handle([
        'notificationType' => 'Delivery',
        'mail' => ['messageId' => 'ses-msg-late'],
    ]);

    expect($recipient->fresh()->status)->toBe(RecipientStatus::Bounced);
});

it('ignores notifications whose messageId does not match any recipient', function () {
    $body = json_encode(snsNotification([
        'notificationType' => 'Delivery',
        'mail' => ['messageId' => 'unknown-msg'],
    ]));

    $this->call('POST', '/webhooks/ses', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertStatus(204);

    expect(CampaignRecipient::count())->toBe(0);
});

it('rejects a webhook whose SNS signature fails verification', function () {
    // Swap the fake binding with the real validator (which will fail
    // because the test body has no real signature).
    $this->app->bind(SnsMessageValidator::class, fn () => new SnsMessageValidator());

    $body = json_encode(snsNotification(['notificationType' => 'Delivery', 'mail' => ['messageId' => 'x']]));

    $this->call('POST', '/webhooks/ses', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertStatus(403);
});
