<?php

declare(strict_types=1);

namespace App\Domains\Documents\Models;

use App\Domains\Documents\Database\Factories\PaymentFactory;
use App\Domains\Documents\Enums\PaymentMethod;
use App\Shared\Concerns\BelongsToOwner;
use App\Shared\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{
    use BelongsToOwner;
    use HasFactory;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fattura_id', 'amount_cents', 'method', 'paid_at', 'reference', 'financial_entry_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('payment');
    }

    protected $table = 'payments';

    protected $fillable = [
        'fattura_id',
        'paid_at',
        'amount_cents',
        'currency',
        'method',
        'reference',
        'notes',
        'financial_entry_id',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'date',
            'amount_cents' => 'integer',
            'method' => PaymentMethod::class,
        ];
    }

    protected static function newFactory(): PaymentFactory
    {
        return PaymentFactory::new();
    }

    public function fattura(): BelongsTo
    {
        return $this->belongsTo(Fattura::class);
    }

    public function amount(): Money
    {
        return new Money((int) $this->amount_cents, $this->currency);
    }
}
