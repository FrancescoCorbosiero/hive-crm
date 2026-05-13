<?php

declare(strict_types=1);

namespace App\Domains\Finance\Models;

use App\Domains\Finance\Database\Factories\FinancialEntryFactory;
use App\Domains\Finance\Enums\FinancialEntrySource;
use App\Domains\Finance\Enums\FinancialEntryType;
use App\Shared\Concerns\BelongsToOwner;
use App\Shared\ValueObjects\Money;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Pure abstract finance record — INCOME or LOSS — that the analytics
 * layer consumes. Other domains never query this table directly: they
 * either record an entry through FinanceService or mirror their own
 * events into one via a Finance-owned listener.
 *
 * A Fattura is one possible artifact generated from an entry, not the
 * other way around. See FinanceService::generateFattura().
 */
class FinancialEntry extends Model
{
    use BelongsToOwner;
    use HasFactory;

    protected $table = 'financial_entries';

    protected $fillable = [
        'type',
        'amount_cents',
        'currency',
        'occurred_at',
        'description',
        'category',
        'source_type',
        'source_id',
        'contact_id',
        'notes',
        'external_ref',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => FinancialEntryType::class,
            'occurred_at' => 'date',
            'amount_cents' => 'integer',
        ];
    }

    protected static function newFactory(): FinancialEntryFactory
    {
        return FinancialEntryFactory::new();
    }

    public function getMoneyAttribute(): Money
    {
        return new Money(
            (int) $this->amount_cents,
            $this->currency ?: config('app.currency', 'EUR'),
        );
    }

    public function setMoney(Money $money): self
    {
        $this->amount_cents = $money->cents;
        $this->currency = $money->currency;

        return $this;
    }

    // ── Scopes ─────────────────────────────────────────────────────────

    public function scopeOfType(Builder $query, FinancialEntryType|string $type): Builder
    {
        return $query->where('type', $type instanceof FinancialEntryType ? $type->value : $type);
    }

    public function scopeIncomes(Builder $query): Builder
    {
        return $query->where('type', FinancialEntryType::Income->value);
    }

    public function scopeLosses(Builder $query): Builder
    {
        return $query->where('type', FinancialEntryType::Loss->value);
    }

    public function scopeForSource(Builder $query, FinancialEntrySource|string $alias, int $id): Builder
    {
        $value = $alias instanceof FinancialEntrySource ? $alias->value : $alias;

        return $query->where('source_type', $value)->where('source_id', $id);
    }

    public function scopeOccurredBetween(Builder $query, CarbonInterface $start, CarbonInterface $end): Builder
    {
        return $query->whereBetween('occurred_at', [
            $start->copy()->startOfDay(),
            $end->copy()->endOfDay(),
        ]);
    }
}
