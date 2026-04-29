<?php

declare(strict_types=1);

namespace App\Domains\Finance\Models;

use App\Domains\Finance\Database\Factories\TransactionFactory;
use App\Domains\Finance\Enums\TransactionSource;
use App\Domains\Finance\Enums\TransactionType;
use App\Shared\Concerns\BelongsToOwner;
use App\Shared\ValueObjects\Money;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use BelongsToOwner;
    use HasFactory;

    protected $table = 'transactions';

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
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'occurred_at' => 'date',
            'amount_cents' => 'integer',
        ];
    }

    protected static function newFactory(): TransactionFactory
    {
        return TransactionFactory::new();
    }

    /**
     * Read accessor: hand back a Money instance composed from the two
     * underlying columns. Setting goes through the explicit setMoney()
     * helper to keep both columns in lockstep.
     */
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

    public function scopeOfType(Builder $query, TransactionType|string $type): Builder
    {
        return $query->where('type', $type instanceof TransactionType ? $type->value : $type);
    }

    public function scopeIncomes(Builder $query): Builder
    {
        return $query->where('type', TransactionType::Income->value);
    }

    public function scopeExpenses(Builder $query): Builder
    {
        return $query->where('type', TransactionType::Expense->value);
    }

    public function scopeForSource(Builder $query, TransactionSource|string $alias, int $id): Builder
    {
        $value = $alias instanceof TransactionSource ? $alias->value : $alias;

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
