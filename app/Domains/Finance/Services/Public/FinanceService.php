<?php

declare(strict_types=1);

namespace App\Domains\Finance\Services\Public;

use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Services\Public\FatturaService;
use App\Domains\Finance\DTOs\FinancialEntryDTO;
use App\Domains\Finance\Enums\FinancialEntrySource;
use App\Domains\Finance\Enums\FinancialEntryType;
use App\Domains\Finance\Models\FinancialEntry;
use App\Shared\ValueObjects\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Public surface of the Finance domain.
 *
 * Operates on FinancialEntry — the domain-agnostic INCOME/LOSS row
 * the analytics layer aggregates. Other domains MUST go through this
 * service rather than reading/writing the table directly. Returns
 * DTOs and Money value objects, never Eloquent models.
 */
class FinanceService
{
    // ── Single-row lookups ─────────────────────────────────────────────

    public function find(int $id): ?FinancialEntryDTO
    {
        $entry = FinancialEntry::query()->find($id);

        return $entry ? FinancialEntryDTO::fromModel($entry) : null;
    }

    /**
     * Record an INCOME entry. The Documents listener uses this to
     * mirror Payments; ad-hoc callers can use it for any cash-in
     * that doesn't have a Fattura behind it.
     *
     * @param  array{
     *     amount_cents: int,
     *     currency?: string,
     *     occurred_at: \DateTimeInterface|string,
     *     description: string,
     *     category?: ?string,
     *     source_type?: ?string,
     *     source_id?: ?int,
     *     contact_id?: ?int,
     *     owner_user_id?: ?int,
     *     notes?: ?string,
     *  }  $attributes
     */
    public function recordIncome(array $attributes): int
    {
        return $this->record(FinancialEntryType::Income, $attributes);
    }

    /**
     * Record a LOSS entry. Same shape as recordIncome.
     *
     * @param  array{
     *     amount_cents: int,
     *     currency?: string,
     *     occurred_at: \DateTimeInterface|string,
     *     description: string,
     *     category?: ?string,
     *     source_type?: ?string,
     *     source_id?: ?int,
     *     contact_id?: ?int,
     *     owner_user_id?: ?int,
     *     notes?: ?string,
     *  }  $attributes
     */
    public function recordLoss(array $attributes): int
    {
        return $this->record(FinancialEntryType::Loss, $attributes);
    }

    /**
     * Generic record. Returns the new entry id so the caller can
     * store it for later cleanup (e.g. payment deletion).
     *
     * @param  array<string,mixed>  $attributes
     */
    public function record(FinancialEntryType $type, array $attributes): int
    {
        $entry = FinancialEntry::query()->create([
            'type' => $type->value,
            'amount_cents' => (int) $attributes['amount_cents'],
            'currency' => $attributes['currency'] ?? config('app.currency', 'EUR'),
            'occurred_at' => $attributes['occurred_at'],
            'description' => $attributes['description'],
            'category' => $attributes['category'] ?? null,
            'source_type' => $attributes['source_type'] ?? null,
            'source_id' => $attributes['source_id'] ?? null,
            'contact_id' => $attributes['contact_id'] ?? null,
            'notes' => $attributes['notes'] ?? null,
            'external_ref' => $attributes['external_ref'] ?? null,
            'owner_user_id' => $attributes['owner_user_id'] ?? null,
        ]);

        return $entry->id;
    }

    /**
     * Look up an entry id by its stable external reference, or null.
     * Callers use this to make "log this recurring thing" actions
     * idempotent (the column carries a partial-unique index).
     */
    public function findIdByExternalRef(string $externalRef): ?int
    {
        return FinancialEntry::query()
            ->where('external_ref', $externalRef)
            ->value('id');
    }

    public function delete(int $id): void
    {
        FinancialEntry::query()->where('id', $id)->delete();
    }

    /**
     * @return Collection<int, FinancialEntryDTO>
     */
    public function recent(int $limit = 10): Collection
    {
        return FinancialEntry::query()
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (FinancialEntry $entry) => FinancialEntryDTO::fromModel($entry));
    }

    // ── Fattura generation ─────────────────────────────────────────────

    /**
     * Generate a Fattura from an existing INCOME entry on demand.
     *
     * The entry is the source of truth for the amount/date/contact;
     * a single-line Fattura is created from those values and the
     * entry is linked back to the new Fattura via source_type/id.
     *
     * Only INCOME entries can produce a Fattura — losses don't map
     * onto an outgoing invoice. Returns the created Fattura.
     *
     * @param  array{
     *     vat_rate?: int|float,
     *     issued_at?: \DateTimeInterface|string|null,
     *     line_description?: ?string,
     *     client_contact_id?: ?int,
     *  }  $overrides
     */
    public function generateFattura(int $entryId, array $overrides = []): Fattura
    {
        $entry = FinancialEntry::query()->findOrFail($entryId);

        if ($entry->type !== FinancialEntryType::Income) {
            throw new \DomainException('Only INCOME entries can generate a Fattura.');
        }

        $clientContactId = $overrides['client_contact_id'] ?? $entry->contact_id;
        if (! $clientContactId) {
            throw new \DomainException('FinancialEntry has no contact_id and none was provided; cannot generate a Fattura.');
        }

        // VAT inclusion: the entry stores a net cash amount. We derive
        // unit_price_cents so that subtotal + vat ≈ entry amount. With
        // vat_rate = 0 the Fattura total equals the entry amount.
        $vatRate = (float) ($overrides['vat_rate'] ?? 0);
        $unitPriceCents = $vatRate > 0
            ? (int) round($entry->amount_cents / (1 + $vatRate / 100))
            : (int) $entry->amount_cents;

        $fattura = app(FatturaService::class)->create([
            'client_contact_id' => (int) $clientContactId,
            'issued_at' => $overrides['issued_at'] ?? $entry->occurred_at,
            'currency' => $entry->currency ?: config('app.currency', 'EUR'),
            'owner_user_id' => $entry->owner_user_id,
            'lines' => [[
                'description' => $overrides['line_description'] ?? $entry->description,
                'qty' => 1,
                'unit_price_cents' => $unitPriceCents,
                'vat_rate' => $vatRate,
            ]],
        ]);

        // Point the entry at the freshly-created Fattura so analytics
        // can see the linkage and we don't double-issue.
        $entry->update([
            'source_type' => FinancialEntrySource::Fattura->value,
            'source_id' => $fattura->id,
        ]);

        return $fattura;
    }

    // ── Aggregates ─────────────────────────────────────────────────────

    public function monthlyIncomeForWebsite(int $websiteId, CarbonInterface $month): Money
    {
        $start = Carbon::instance($month)->copy()->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return $this->sum(
            FinancialEntry::query()
                ->incomes()
                ->forSource(FinancialEntrySource::Website, $websiteId)
                ->occurredBetween($start, $end),
        );
    }

    public function ytdIncomeForWebsite(int $websiteId, ?CarbonInterface $asOf = null): Money
    {
        $asOf = $asOf ? Carbon::instance($asOf) : now();
        $start = $asOf->copy()->startOfYear();

        return $this->sum(
            FinancialEntry::query()
                ->incomes()
                ->forSource(FinancialEntrySource::Website, $websiteId)
                ->occurredBetween($start, $asOf),
        );
    }

    public function ytdTotal(FinancialEntryType $type, ?CarbonInterface $asOf = null): Money
    {
        $asOf = $asOf ? Carbon::instance($asOf) : now();
        $start = $asOf->copy()->startOfYear();

        return $this->sum(
            FinancialEntry::query()
                ->ofType($type)
                ->occurredBetween($start, $asOf),
        );
    }

    /**
     * Monthly income totals for the past N months ending at the given
     * date (defaults to today). Returned indexed by `YYYY-MM`.
     *
     * @return Collection<string, Money>
     */
    public function monthlyIncomeSeries(int $months = 12, ?CarbonInterface $endingAt = null): Collection
    {
        $end = $endingAt ? Carbon::instance($endingAt) : now();
        $cursor = $end->copy()->startOfMonth()->subMonths($months - 1);

        $series = collect();
        for ($i = 0; $i < $months; $i++) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();

            $total = $this->sum(
                FinancialEntry::query()
                    ->incomes()
                    ->occurredBetween($monthStart, $monthEnd),
            );

            $series->put($monthStart->format('Y-m'), $total);
            $cursor->addMonth();
        }

        return $series;
    }

    /**
     * Sum totals grouped by category for entries of the given type
     * within an inclusive date range. Returns category => Money sorted
     * descending by amount. Null categories collapse to '(other)'.
     *
     * @return Collection<string, Money>
     */
    public function breakdownByCategory(FinancialEntryType $type, CarbonInterface $start, CarbonInterface $end): Collection
    {
        $rows = FinancialEntry::query()
            ->ofType($type)
            ->occurredBetween(Carbon::instance($start), Carbon::instance($end))
            ->selectRaw('COALESCE(category, ?) AS category, SUM(amount_cents) AS total_cents', ['(other)'])
            ->groupBy('category')
            ->orderByDesc('total_cents')
            ->get();

        $currency = config('app.currency', 'EUR');

        return $rows->mapWithKeys(fn ($row) => [
            (string) $row->category => new Money((int) $row->total_cents, $currency),
        ]);
    }

    /**
     * Sum income entries grouped by source website within a date
     * range. Returns websiteId => Money, sorted descending. Callers
     * resolve website names via WebsitesService — Finance does not
     * import Websites models.
     *
     * @return Collection<int, Money>
     */
    public function incomeByWebsite(CarbonInterface $start, CarbonInterface $end): Collection
    {
        $rows = FinancialEntry::query()
            ->incomes()
            ->where('source_type', FinancialEntrySource::Website->value)
            ->whereNotNull('source_id')
            ->occurredBetween(Carbon::instance($start), Carbon::instance($end))
            ->selectRaw('source_id, SUM(amount_cents) AS total_cents')
            ->groupBy('source_id')
            ->orderByDesc('total_cents')
            ->get();

        $currency = config('app.currency', 'EUR');

        return $rows->mapWithKeys(fn ($row) => [
            (int) $row->source_id => new Money((int) $row->total_cents, $currency),
        ]);
    }

    /**
     * Sum a FinancialEntry query into a single Money. Uses the
     * configured default currency — mixed-currency rows would need
     * explicit FX handling, deliberately out of scope for v1.
     */
    private function sum($query): Money
    {
        $cents = (int) $query->sum('amount_cents');

        return new Money($cents, config('app.currency', 'EUR'));
    }
}
