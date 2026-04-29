<?php

declare(strict_types=1);

namespace App\Domains\Leads\DTOs;

use App\Domains\Leads\Models\Lead;
use App\Shared\ValueObjects\Money;
use Carbon\Carbon;

final readonly class LeadDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $source,
        public string $status,
        public ?Money $estimatedValue,
        public ?string $notes,
        public ?Carbon $nextActionAt,
        public ?int $convertedContactId,
        public ?Carbon $convertedAt,
    ) {}

    public static function fromModel(Lead $lead): self
    {
        return new self(
            id: $lead->id,
            name: $lead->name,
            email: $lead->email,
            phone: $lead->phone,
            source: $lead->source?->value,
            status: $lead->status->value,
            estimatedValue: $lead->estimated_value,
            notes: $lead->notes,
            nextActionAt: $lead->next_action_at,
            convertedContactId: $lead->converted_contact_id,
            convertedAt: $lead->converted_at,
        );
    }

    public function isConverted(): bool
    {
        return $this->convertedContactId !== null;
    }
}
