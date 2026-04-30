<?php

declare(strict_types=1);

namespace App\Domains\Finance\Services\Public;

use App\Domains\Finance\DTOs\TransactionDTO;
use App\Domains\Finance\Enums\TransactionSource;
use App\Domains\Finance\Enums\TransactionType;
use App\Domains\Finance\Models\Transaction;
use App\Shared\ValueObjects\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Public surface of the Finance domain.
 *
 * Other domains MUST consume Finance through this service. Returns DTOs
 * and Money value objects, never Eloquent models.
 */
class FinanceService
{
    // ── Single-row lookups ─────────────────────────────────────────────

    public function find(int $id): ?TransactionDTO
    {
        $tx = Transaction::query()->find($id);

        return $tx ? TransactionDTO::fromModel($tx) : null;
    }

    /**
     * Cross-domain income recording: used by Documents' PaymentRecorded
     * listener to mirror a Payment as an Income Transaction. Returns
     * the new Transaction id so the caller can store it for later
     * cleanup if the underlying payment is deleted.
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
     *  }  $attributes
     */
    public function recordIncome(array $attributes): int
    {
        $tx = Transaction::query()->create([
            'type' => TransactionType::Income->value,
            'amount_cents' => (int) $attributes['amount_cents'],
            'currency' => $attributes['currency'] ?? config('app.currency', 'EUR'),
            'occurred_at' => $attributes['occurred_at'],
            'description' => $attributes['description'],
            'category' => $attributes['category'] ?? null,
            'source_type' => $attributes['source_type'] ?? null,
            'source_id' => $attributes['source_id'] ?? null,
            'contact_id' => $attributes['contact_id'] ?? null,
            'owner_user_id' => $attributes['owner_user_id'] ?? null,
        ]);

        return $tx->id;
    }

    public function deleteTransaction(int $id): void
    {
        Transaction::query()->where('id', $id)->delete();
    }

    /**
     * @return Collection<int, TransactionDTO>
     */
    public function recent(int $limit = 10): Collection
    {
        return Transaction::query()
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Transaction $tx) => TransactionDTO::fromModel($tx));
    }

    // ── Aggregates ─────────────────────────────────────────────────────

    public function monthlyIncomeForWebsite(int $websiteId, CarbonInterface $month): Money
    {
        $start = Carbon::instance($month)->copy()->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return $this->sum(
            Transaction::query()
                ->incomes()
                ->forSource(TransactionSource::Website, $websiteId)
                ->occurredBetween($start, $end),
        );
    }

    public function ytdIncomeForWebsite(int $websiteId, ?CarbonInterface $asOf = null): Money
    {
        $asOf = $asOf ? Carbon::instance($asOf) : now();
        $start = $asOf->copy()->startOfYear();

        return $this->sum(
            Transaction::query()
                ->incomes()
                ->forSource(TransactionSource::Website, $websiteId)
                ->occurredBetween($start, $asOf),
        );
    }

    public function ytdTotal(TransactionType $type, ?CarbonInterface $asOf = null): Money
    {
        $asOf = $asOf ? Carbon::instance($asOf) : now();
        $start = $asOf->copy()->startOfYear();

        return $this->sum(
            Transaction::query()
                ->ofType($type)
                ->occurredBetween($start, $asOf),
        );
    }

    /**
     * Monthly income totals for the past N months ending at the given date
     * (defaults to today). Returned indexed by `YYYY-MM`.
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
                Transaction::query()
                    ->incomes()
                    ->occurredBetween($monthStart, $monthEnd),
            );

            $series->put($monthStart->format('Y-m'), $total);
            $cursor->addMonth();
        }

        return $series;
    }

    /**
     * Sum totals grouped by category for transactions of the given type
     * within an inclusive date range. Returns category => Money sorted
     * descending by amount. Null categories collapse to '(other)'.
     *
     * @return Collection<string, Money>
     */
    public function breakdownByCategory(TransactionType $type, CarbonInterface $start, CarbonInterface $end): Collection
    {
        $rows = Transaction::query()
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
     * Sum income transactions grouped by source website within a date
     * range. Returns websiteId => Money, sorted descending. Callers
     * resolve website names via WebsitesService — Finance does not
     * import Websites models.
     *
     * @return Collection<int, Money>
     */
    public function incomeByWebsite(CarbonInterface $start, CarbonInterface $end): Collection
    {
        $rows = Transaction::query()
            ->incomes()
            ->where('source_type', TransactionSource::Website->value)
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
     * Sum a transactions query into a single Money. Uses the configured
     * default currency — mixed-currency rows would need explicit FX
     * handling, deliberately out of scope for v1.
     */
    private function sum($query): Money
    {
        $cents = (int) $query->sum('amount_cents');

        return new Money($cents, config('app.currency', 'EUR'));
    }
}
