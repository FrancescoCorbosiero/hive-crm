<?php

declare(strict_types=1);

namespace App\Domains\Finance\Models;

use App\Domains\Documents\Enums\RecurringFrequency;
use App\Shared\Concerns\BelongsToOwner;
use App\Shared\ValueObjects\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RecurringExpense extends Model
{
    use BelongsToOwner;

    protected $table = 'recurring_expenses';

    protected $fillable = [
        'name',
        'frequency',
        'amount_cents',
        'currency',
        'category',
        'vendor_contact_id',
        'started_at',
        'next_due_at',
        'last_logged_at',
        'is_active',
        'notes',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'frequency' => RecurringFrequency::class,
            'amount_cents' => 'integer',
            'started_at' => 'date',
            'next_due_at' => 'date',
            'last_logged_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function money(): Money
    {
        return new Money((int) $this->amount_cents, $this->currency ?: config('app.currency', 'EUR'));
    }

    public function isDelayed(?Carbon $now = null): bool
    {
        if (! $this->is_active || $this->next_due_at === null) {
            return false;
        }

        return $this->next_due_at->lt(($now ?? now())->copy()->startOfDay());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
