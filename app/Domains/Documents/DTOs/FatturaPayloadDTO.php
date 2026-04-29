<?php

declare(strict_types=1);

namespace App\Domains\Documents\DTOs;

use App\Domains\Contacts\DTOs\ContactDTO;
use App\Domains\Documents\Models\Fattura;
use App\Shared\ValueObjects\Money;
use Carbon\Carbon;

/**
 * Stable export shape for fatture, designed so a future SdI/Fattura
 * Elettronica XML exporter can plug in WITHOUT touching the Fattura
 * model or service.
 *
 * Future caller (in a separate package or domain):
 *   $payload = FatturaService::payload($id);
 *   $xml = SdiXmlExporter::render($payload);
 *
 * Anything renaming or restructuring goes here, not in the model.
 */
final readonly class FatturaPayloadDTO
{
    /**
     * @param  array<int, FatturaLineDTO>  $lines
     */
    public function __construct(
        public int $id,
        public int $year,
        public int $number,
        public string $displayNumber,
        public Carbon $issuedAt,
        public ?ContactDTO $client,
        public array $lines,
        public Money $subtotal,
        public Money $vat,
        public Money $total,
        public string $paymentStatus,
    ) {}

    public static function fromModel(Fattura $fattura, ?ContactDTO $client): self
    {
        $currency = $fattura->currency;

        $lines = collect((array) $fattura->lines)
            ->map(fn (array $row) => FatturaLineDTO::fromArray($row, $currency))
            ->all();

        return new self(
            id: $fattura->id,
            year: $fattura->year,
            number: $fattura->number,
            displayNumber: $fattura->displayNumber(),
            issuedAt: $fattura->issued_at,
            client: $client,
            lines: $lines,
            subtotal: $fattura->subtotal(),
            vat: $fattura->vat(),
            total: $fattura->total(),
            paymentStatus: $fattura->payment_status->value,
        );
    }
}
