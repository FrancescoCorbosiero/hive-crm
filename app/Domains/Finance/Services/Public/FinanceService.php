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
