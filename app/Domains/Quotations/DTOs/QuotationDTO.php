<?php

declare(strict_types=1);

namespace App\Domains\Quotations\DTOs;

use App\Domains\Quotations\Models\Quotation;
use App\Shared\ValueObjects\Money;
use Carbon\Carbon;

final readonly class QuotationDTO
{
    public function __construct(
        public int $id,
        public int $year,
        public int $number,
        public string $displayNumber,
        public string $name,
        public int $clientContactId,
        public ?int $leadId,
        public Carbon $issuedAt,
        public ?Carbon $validUntil,
        public Money $subtotal,
        public Money $vat,
        public Money $total,
        public string $status,
        public ?int $fatturaId,
        public ?int $documentId,
    ) {}

    public static function fromModel(Quotation $q): self
    {
        return new self(
            id: $q->id,
            year: $q->year,
            number: $q->number,
            displayNumber: $q->displayNumber(),
            name: $q->name,
            clientContactId: $q->client_contact_id,
            leadId: $q->lead_id,
            issuedAt: $q->issued_at,
            validUntil: $q->valid_until,
            subtotal: $q->subtotal(),
            vat: $q->vat(),
            total: $q->total(),
            status: $q->status->value,
            fatturaId: $q->fattura_id,
            documentId: $q->document_id,
        );
    }
}
