<?php

declare(strict_types=1);

namespace App\Domains\Finance\DTOs;

use App\Domains\Finance\Models\Transaction;
use App\Shared\ValueObjects\Money;
use Carbon\Carbon;

final readonly class TransactionDTO
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

    public static function fromModel(Transaction $tx): self
    {
        return new self(
            id: $tx->id,
            type: $tx->type->value,
            amount: $tx->money,
            occurredAt: $tx->occurred_at,
            description: $tx->description,
            category: $tx->category,
            sourceType: $tx->source_type,
            sourceId: $tx->source_id,
            contactId: $tx->contact_id,
            notes: $tx->notes,
        );
    }
}
