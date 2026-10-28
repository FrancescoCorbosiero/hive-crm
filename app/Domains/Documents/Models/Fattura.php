<?php

declare(strict_types=1);

namespace App\Domains\Documents\Models;

use App\Domains\Documents\Database\Factories\FatturaFactory;
use App\Domains\Documents\Enums\PaymentStatus;
use App\Domains\Documents\Events\FatturaDeleted;
use App\Shared\Billing\DerivesTotalsFromLines;
use App\Shared\Concerns\BelongsToOwner;
use App\Shared\ValueObjects\Money;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Fattura extends Model
{
    use BelongsToOwner;
    use DerivesTotalsFromLines;
    use HasFactory;
    use LogsActivity;

    protected static function booted(): void
    {
        // The status is derived from paid vs. total vs. due date, so an
        // edit that moves the total or the due date re-derives it (runs
        // after DerivesTotalsFromLines has refreshed the totals).
        static::saving(function (Fattura $fattura): void {
            if ($fattura->exists && $fattura->isDirty(['total_cents', 'due_date'])) {
                $fattura->payment_status = $fattura->derivePaymentStatus();
            }
        });

        // Delete payments through Eloquent rather than relying on the FK
        // cascade, so each one fires PaymentDeleted and Finance can
        // retract the income it mirrored.
        static::deleting(function (Fattura $fattura): void {
            $fattura->payments()->get()->each->delete();
        });

        static::deleted(function (Fattura $fattura): void {
            FatturaDeleted::dispatch($fattura->id);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['payment_status', 'paid_amount_cents', 'total_cents', 'document_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('fattura');
    }

    protected $table = 'fatture';

    protected $fillable = [
        'year',
        'number',
        'client_contact_id',
        'issued_at',
        'due_date',
        'lines',
        'subtotal_cents',
        'vat_cents',
        'total_cents',
        'paid_amount_cents',
        'currency',
        'payment_status',
        'regime_fiscale',
        'natura',
        'document_id',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'number' => 'integer',
            'issued_at' => 'date',
            'due_date' => 'date',
            'lines' => AsArrayObject::class,
            'subtotal_cents' => 'integer',
            'vat_cents' => 'integer',
            'total_cents' => 'integer',
            'paid_amount_cents' => 'integer',
            'payment_status' => PaymentStatus::class,
        ];
    }

    /**
     * Payment status implied by the cached paid amount, the total and the
     * due date. Precedence: Cancelled > Paid > Overdue > PartiallyPaid >
     * Unpaid — a partially paid invoice past its due date is late money,
     * so it is Overdue. Cancelled is sticky — a voided fattura never
     * revives.
     */
    public function derivePaymentStatus(?CarbonInterface $today = null): PaymentStatus
    {
        $paid = (int) $this->paid_amount_cents;
        $total = (int) $this->total_cents;

        return match (true) {
            $this->payment_status === PaymentStatus::Cancelled => PaymentStatus::Cancelled,
            $total > 0 && $paid >= $total => PaymentStatus::Paid,
            $this->isPastDue($today) => PaymentStatus::Overdue,
            $paid > 0 => PaymentStatus::PartiallyPaid,
            default => PaymentStatus::Unpaid,
        };
    }

    /**
     * Past due from the day AFTER due_date — an invoice due today is not
     * late yet (same rule as the fatture:recompute-overdue sweep).
     */
    public function isPastDue(?CarbonInterface $today = null): bool
    {
        return $this->due_date !== null
            && $this->due_date->lt(($today ?? now())->copy()->startOfDay());
    }

    public function paidAmount(): Money
    {
        return new Money((int) $this->paid_amount_cents, $this->currency);
    }

    public function outstanding(): Money
    {
        $cents = max(0, (int) $this->total_cents - (int) $this->paid_amount_cents);

        return new Money($cents, $this->currency);
    }

    protected static function newFactory(): FatturaFactory
    {
        return FatturaFactory::new();
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Display number with the standard "0001/2026" format.
     */
    public function displayNumber(): string
    {
        return str_pad((string) $this->number, 4, '0', STR_PAD_LEFT).'/'.$this->year;
    }

    public function subtotal(): Money
    {
        return new Money((int) $this->subtotal_cents, $this->currency);
    }

    public function vat(): Money
    {
        return new Money((int) $this->vat_cents, $this->currency);
    }

    public function total(): Money
    {
        return new Money((int) $this->total_cents, $this->currency);
    }
}
