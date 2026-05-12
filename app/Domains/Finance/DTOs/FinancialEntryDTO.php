<?php

declare(strict_types=1);

namespace App\Domains\Finance\DTOs;

use App\Domains\Finance\Models\FinancialEntry;
use App\Shared\ValueObjects\Money;
use Carbon\Carbon;

final readonly class FinancialEntryDTO
{
    public function __construct(
        public int $id,
        public string $type,
        public Money $amount,
        public Carbon $occurredAt,
        public string $description,
        public ?string $category,
        public ?string $sourceType,
        public ?int $sourceId,
        public ?int $contactId,
        public ?string $notes,
    ) {}

    public static function fromModel(FinancialEntry $entry): self
    {
        return new self(
            id: $entry->id,
            type: $entry->type->value,
            amount: $entry->money,
            occurredAt: $entry->occurred_at,
            description: $entry->description,
            category: $entry->category,
            sourceType: $entry->source_type,
            sourceId: $entry->source_id,
            contactId: $entry->contact_id,
            notes: $entry->notes,
        );
    }
}
