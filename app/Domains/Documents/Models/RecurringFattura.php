<?php

declare(strict_types=1);

namespace App\Domains\Documents\Models;

use App\Domains\Documents\Database\Factories\RecurringFatturaFactory;
use App\Domains\Documents\Enums\RecurringFrequency;
use App\Shared\Billing\HasLineItems;
use App\Shared\Concerns\BelongsToOwner;
use App\Shared\ValueObjects\Money;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecurringFattura extends Model
{
    use BelongsToOwner;
    use HasFactory;
    use HasLineItems;

    protected $table = 'recurring_fatture';

    protected $fillable = [
        'name',
        'client_contact_id',
        'frequency',
        'lines',
        'currency',
        'day_of_month',
        'next_issue_at',
        'is_active',
        'last_issued_at',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'frequency' => RecurringFrequency::class,
            'lines' => AsArrayObject::class,
            'day_of_month' => 'integer',
            'next_issue_at' => 'date',
            'is_active' => 'boolean',
            'last_issued_at' => 'date',
        ];
    }

    protected static function newFactory(): RecurringFatturaFactory
    {
        return RecurringFatturaFactory::new();
    }

    /**
     * What one issued cycle of this schedule totals (net + VAT).
     */
    public function cycleTotal(): Money
    {
        return $this->lineItems()->total();
    }

    /**
     * Issue date of the cycle after the one dated $date. Monthly
     * schedules with a day_of_month land on that day of the next month,
     * clamped to its length; everything else steps by the frequency.
     */
    public function issueDateAfter(CarbonInterface $date): CarbonImmutable
    {
        if ($this->frequency === RecurringFrequency::Monthly && $this->day_of_month) {
            $month = CarbonImmutable::instance($date)->startOfMonth()->addMonth();

            return $month->day(min($this->day_of_month, $month->daysInMonth));
        }

        return CarbonImmutable::instance($this->frequency->advance($date));
    }

    public function scopeDueOn(Builder $query, $date): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereDate('next_issue_at', '<=', $date);
    }
}
