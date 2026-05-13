<?php

declare(strict_types=1);

namespace App\Domains\Quotations\Services\Public;

use App\Domains\Documents\Services\Public\DocumentsService;
use App\Domains\Documents\Services\Public\FatturaService;
use App\Domains\Documents\Services\Public\RecurringFatturaService;
use App\Domains\Quotations\DTOs\QuotationDTO;
use App\Domains\Quotations\Enums\LineCadence;
use App\Domains\Quotations\Enums\QuotationStatus;
use App\Domains\Quotations\Models\Quotation;
use App\Domains\Quotations\Models\QuotationCounter;
use App\Domains\Quotations\Services\Internal\QuotationPdfRenderer;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QuotationsService
{
    public function __construct(
        private readonly DocumentsService $documents,
        private readonly FatturaService $fatture,
        private readonly RecurringFatturaService $recurring,
        private readonly QuotationPdfRenderer $renderer,
    ) {}

    /**
     * Allocate the next sequential number and create the Quotation.
     * Race-safe via the counter row's lockForUpdate, mirroring the
     * Fattura pattern from Phase 6.
     *
     * Totals computed from `lines` — caller doesn't pass them directly.
     *
     * @param  array{
     *     name: string,
     *     client_contact_id: int,
     *     lead_id?: ?int,
     *     issued_at?: \DateTimeInterface|string|null,
     *     valid_until?: \DateTimeInterface|string|null,
     *     lines: array<int, array{description: string, qty: int|float, unit_price_cents: int, vat_rate: int|float}>,
     *     currency?: string,
     *     year?: int,
     *     notes?: ?string,
     *     owner_user_id?: ?int,
     *  }  $attributes
     */
    public function create(array $attributes): Quotation
    {
        $issuedAt = isset($attributes['issued_at'])
            ? Carbon::parse($attributes['issued_at'])
            : Carbon::now();
        $year = (int) ($attributes['year'] ?? $issuedAt->year);
        $currency = $attributes['currency'] ?? config('app.currency', 'EUR');

        [$subtotal, $vat, $total] = $this->computeTotals($attributes['lines']);

        return DB::transaction(function () use ($attributes, $year, $issuedAt, $currency, $subtotal, $vat, $total) {
            $counter = QuotationCounter::query()->lockForUpdate()->find($year);
            if (! $counter) {
                QuotationCounter::query()->create(['year' => $year, 'last_number' => 0]);
                $counter = QuotationCounter::query()->lockForUpdate()->find($year);
            }
            $next = $counter->last_number + 1;
            $counter->last_number = $next;
            $counter->save();

            return Quotation::query()->create([
                'year' => $year,
                'number' => $next,
                'name' => $attributes['name'],
                'client_contact_id' => $attributes['client_contact_id'],
                'lead_id' => $attributes['lead_id'] ?? null,
                'issued_at' => $issuedAt,
                'valid_until' => isset($attributes['valid_until'])
                    ? Carbon::parse($attributes['valid_until'])
                    : $issuedAt->copy()->addDays(30),
                'lines' => $attributes['lines'],
                'subtotal_cents' => $subtotal,
                'vat_cents' => $vat,
                'total_cents' => $total,
                'currency' => $currency,
                'status' => QuotationStatus::Draft->value,
                'notes' => $attributes['notes'] ?? null,
                'owner_user_id' => $attributes['owner_user_id'] ?? null,
            ]);
        });
    }

    /**
     * Mark a Draft quotation as Sent. No-op if already sent or further along.
     */
    public function markSent(int $id): void
    {
        $q = Quotation::query()->findOrFail($id);

        if ($q->status === QuotationStatus::Draft) {
            $q->update(['status' => QuotationStatus::Sent->value]);
        }
    }

    /**
     * Accept a quotation and materialize:
     *
     *  1. An upfront Fattura containing ALL lines (una tantum + the
     *     first cycle of any recurring line). This is what the
     *     customer pays at signing.
     *  2. One RecurringFattura per recurring cadence group present on
     *     the quotation (monthly / quarterly / yearly). Each schedule
     *     gets `next_issue_at = issued_at + one period`, so the next
     *     bill arrives exactly one cycle after the upfront fattura.
     *
     * Backward compat: lines without a `cadence` key are treated as
     * `una_tantum` — older quotations behave exactly as before
     * (one Fattura, no recurring schedules).
     *
     * Idempotent in the strong sense: rejected/expired quotations
     * cannot be accepted; an already-accepted quotation throws.
     *
     * Returns the upfront Fattura id (also persisted to
     * `quotation.fattura_id` for the existing relation surface).
     */
    public function accept(int $id): int
    {
        return DB::transaction(function () use ($id) {
            $q = Quotation::query()->lockForUpdate()->findOrFail($id);

            if ($q->status->isFinal()) {
                throw new DomainException("Quotation {$id} is already in a final state.");
            }

            $issuedAt = Carbon::now();
            $allLines = (array) $q->lines;

            // Upfront fattura with EVERY line (una tantum + first cycle
            // of recurrings). Cadence is dropped — a fattura line is a
            // single billing event, not a schedule.
            $fattura = $this->fatture->create([
                'client_contact_id' => $q->client_contact_id,
                'issued_at' => $issuedAt,
                'lines' => $this->stripCadenceKey($allLines),
                'currency' => $q->currency,
                'owner_user_id' => $q->owner_user_id,
            ]);

            // Seed one RecurringFattura per recurring cadence group.
            foreach ($this->groupRecurringLines($allLines) as $cadenceValue => $lines) {
                $cadence = LineCadence::from($cadenceValue);
                $frequency = $cadence->toRecurringFrequency();
                if ($frequency === null) {
                    continue;
                }

                $this->recurring->create([
                    'name' => $q->name.' — '.$cadence->label(),
                    'client_contact_id' => $q->client_contact_id,
                    'frequency' => $frequency->value,
                    'lines' => $this->stripCadenceKey($lines),
                    'currency' => $q->currency,
                    'next_issue_at' => $frequency->advance($issuedAt),
                    'owner_user_id' => $q->owner_user_id,
                ]);
            }

            $q->update([
                'status' => QuotationStatus::Accepted->value,
                'fattura_id' => $fattura->id,
            ]);

            return $fattura->id;
        });
    }

    /**
     * @param  array<int, array<string,mixed>>  $lines
     * @return array<string, array<int, array<string,mixed>>>
     *         keyed by cadence value, only non-una_tantum groups
     */
    private function groupRecurringLines(array $lines): array
    {
        $groups = [];
        foreach ($lines as $line) {
            $cadence = $line['cadence'] ?? LineCadence::UnaTantum->value;
            if ($cadence === LineCadence::UnaTantum->value) {
                continue;
            }
            $groups[$cadence][] = $line;
        }

        return $groups;
    }

    /**
     * @param  array<int, array<string,mixed>>  $lines
     * @return array<int, array<string,mixed>>
     */
    private function stripCadenceKey(array $lines): array
    {
        return array_map(function (array $line): array {
            unset($line['cadence']);

            return $line;
        }, $lines);
    }

    public function reject(int $id): void
    {
        $q = Quotation::query()->findOrFail($id);

        if ($q->status->isFinal()) {
            throw new DomainException("Quotation {$id} is already in a final state.");
        }

        $q->update(['status' => QuotationStatus::Rejected->value]);
    }

    public function find(int $id): ?QuotationDTO
    {
        $q = Quotation::query()->find($id);

        return $q ? QuotationDTO::fromModel($q) : null;
    }

    /**
     * Render a PDF and link it back as a Document of category=other.
     * The PDF is secondary to the tracking workflow — uses a small
     * Blade template, not the full fattura layout.
     */
    public function render(int $id, ?string $disk = null): int
    {
        $q = Quotation::query()->findOrFail($id);
        $pdf = $this->renderer->render($q);

        $disk ??= config('filesystems.default');
        $path = sprintf(
            'quotations/%d/%s-%s.pdf',
            $q->year,
            str_pad((string) $q->number, 4, '0', STR_PAD_LEFT),
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
                'title' => $q->displayNumber().' — '.$q->name,
                'category' => \App\Domains\Documents\Enums\DocumentCategory::Other->value,
                'related_type' => 'quotation',
                'related_id' => $q->id,
                'issued_at' => $q->issued_at,
                'owner_user_id' => $q->owner_user_id,
            ],
        );

        $q->update(['document_id' => $document->id]);

        return $document->id;
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
