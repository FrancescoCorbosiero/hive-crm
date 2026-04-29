<?php

declare(strict_types=1);

namespace App\Domains\Mail\Database\Factories;

use App\Domains\Mail\Enums\RecipientStatus;
use App\Domains\Mail\Models\Campaign;
use App\Domains\Mail\Models\CampaignRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignRecipient>
 */
class CampaignRecipientFactory extends Factory
{
    protected $model = CampaignRecipient::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'contact_id' => 1,
            'status' => RecipientStatus::Pending->value,
            'sent_at' => null,
            'ses_message_id' => null,
        ];
    }

    public function withMessageId(string $id): self
    {
        return $this->state(fn () => ['ses_message_id' => $id, 'status' => RecipientStatus::Sent->value]);
    }
}
