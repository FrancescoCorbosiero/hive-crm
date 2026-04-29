<?php

declare(strict_types=1);

namespace App\Domains\Documents\Services\Public;

use App\Domains\Contacts\Services\Public\ContactsService;
use App\Domains\Documents\DTOs\FatturaPayloadDTO;
use App\Domains\Documents\Enums\DocumentCategory;
use App\Domains\Documents\Enums\PaymentStatus;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Models\FatturaCounter;
use App\Domains\Documents\Services\Internal\FatturaPdfRenderer;
use App\Shared\ValueObjects\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FatturaService
{
    public function __construct(
        private readonly ContactsService $contacts,
        private readonly DocumentsService $documents,
        private readonly FatturaPdfRenderer $renderer,
    ) {}

    /**
     * Allocate the next sequential number for the given year and create
     * the Fattura. Race-safe: locks the per-year counter row inside the
     * transaction, so concurrent callers serialize on it. The
     * (year, number) UNIQUE constraint on the fatture table is the
     * second line of defence.
     *
     * Totals are computed from `lines` — callers don't pass them
     * directly. This keeps the math source-of-truth in one place.
     *
     * Does NOT render the PDF. Call render($id) separately so failed
     * PDF generation doesn't roll back the number allocation (the
     * fattura number is a tax artefact — losing it on a hiccup would
     * be very bad).
     *
     * @param  array{
     *     client_contact_id: int,
     *     issued_at?: \DateTimeInterface|string|null,
     *     lines: array<int, array{
     *         description: string,
     *         qty: int|float,
     *         unit_price_cents: int,
     *         vat_rate: int|float
     *     }>,
     *     currency?: string,
     *     payment_status?: string,
     *     year?: int,
     *     owner_user_id?: ?int,
     *  }  $attributes
     */
    public function create(array $attributes): Fattura
    {
        $issuedAt = isset($attributes['issued_at'])
            ? Carbon::parse($attributes['issued_at'])
            : Carbon::now();
        $year = (int) ($attributes['year'] ?? $issuedAt->year);
        $currency = $attributes['currency'] ?? config('app.currency', 'EUR');

        [$subtotalCents, $vatCents, $totalCents] = $this->computeTotals($attributes['lines']);

        return DB::transaction(function () use ($attributes, $year, $issuedAt, $currency, $subtotalCents, $vatCents, $totalCents) {
            // Lock the counter row for this year. firstOrCreate creates
            // it the first time we issue a fattura in a new fiscal year.
            $counter = FatturaCounter::query()
                ->lockForUpdate()
                ->find($year);

            if (! $counter) {
                $counter = FatturaCounter::query()->create([
                    'year' => $year,
                    'last_number' => 0,
                ]);
                // Re-fetch with lock so subsequent allocations serialize
                // on this row even if firstOrCreate took a fast path.
                $counter = FatturaCounter::query()->lockForUpdate()->find($year);
            }

            $next = $counter->last_number + 1;
            $counter->last_number = $next;
            $counter->save();

            return Fattura::query()->create([
                'year' => $year,
                'number' => $next,
                'client_contact_id' => $attributes['client_contact_id'],
                'issued_at' => $issuedAt,
                'lines' => $attributes['lines'],
                'subtotal_cents' => $subtotalCents,
                'vat_cents' => $vatCents,
                'total_cents' => $totalCents,
                'currency' => $currency,
                'payment_status' => $attributes['payment_status'] ?? PaymentStatus::Unpaid->value,
                'owner_user_id' => $attributes['owner_user_id'] ?? null,
            ]);
        });
    }

    /**
     * Render the fattura to PDF, upload to the configured disk, register
     * a Document row and link it back to the Fattura. Re-rendering is
     * a no-op-ish — produces a new file but updates document_id.
     */
    public function render(int $fatturaId, ?string $disk = null): int
    {
        $fattura = Fattura::query()->findOrFail($fatturaId);
        $payload = $this->payload($fatturaId);

        $pdf = $this->renderer->render($payload);

        $disk ??= config('filesystems.default');
        $path = sprintf(
            'fatture/%d/%s-%s.pdf',
            $fattura->year,
            str_pad((string) $fattura->number, 4, '0', STR_PAD_LEFT),
            Str::random(8),
        );

        Storage::disk($disk)->put($path, $pdf, [
            'visibility' => 'private',
            'ContentType' => 'application/pdf',
        ]);

        $document = $this->documents->register(
            path: $path,
            disk: $disk,
            size: strlen($pdf),
            mime: 'application/pdf',
            attributes: [
                'title' => 'Fattura '.$fattura->displayNumber(),
                'category' => DocumentCategory::Fattura->value,
                'related_type' => 'fattura',
                'related_id' => $fattura->id,
                'issued_at' => $fattura->issued_at,
                'owner_user_id' => $fattura->owner_user_id,
            ],
        );

        $fattura->update(['document_id' => $document->id]);

        return $document->id;
    }

    /**
     * Snapshot a fattura for downstream exporters (e.g. SdI XML).
     */
    public function payload(int $fatturaId): FatturaPayloadDTO
    {
        $fattura = Fattura::query()->findOrFail($fatturaId);
        $client = $this->contacts->find($fattura->client_contact_id);

        return FatturaPayloadDTO::fromModel($fattura, $client);
    }

    /**
     * @param  array<int, array<string,mixed>>  $lines
     * @return array{0:int,1:int,2:int}  [subtotal, vat, total] in cents
     */
    private function computeTotals(array $lines): array
    {
        $subtotal = 0;
        $vat = 0;

        foreach ($lines as $line) {
            $qty = (float) ($line['qty'] ?? 0);
            $unit = (int) ($line['unit_price_cents'] ?? 0);
            $rate = (float) ($line['vat_rate'] ?? 0);

            $lineSubtotal = (int) round($qty * $unit);
            $lineVat = (int) round($lineSubtotal * $rate / 100);

            $subtotal += $lineSubtotal;
            $vat += $lineVat;
        }

        return [$subtotal, $vat, $subtotal + $vat];
    }
}
