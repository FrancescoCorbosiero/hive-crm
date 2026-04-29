<?php

declare(strict_types=1);

namespace App\Domains\Leads\Models;

use App\Domains\Leads\Database\Factories\LeadFactory;
use App\Domains\Leads\Enums\LeadSource;
use App\Domains\Leads\Enums\LeadStatus;
use App\Shared\Casts\MoneyCast;
use App\Shared\Concerns\BelongsToOwner;
use App\Shared\ValueObjects\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use BelongsToOwner;
    use HasFactory;

    protected $table = 'leads';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'source',
        'status',
        'estimated_value_cents',
        'estimated_value_currency',
        'notes',
        'next_action_at',
        'converted_contact_id',
        'converted_at',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'source' => LeadSource::class,
            'next_action_at' => 'datetime',
            'converted_at' => 'datetime',
            'estimated_value_cents' => 'integer',
            'estimated_value' => MoneyCast::class.':estimated_value_cents,estimated_value_currency',
        ];
    }

    protected static function newFactory(): LeadFactory
    {
        return LeadFactory::new();
    }

    // ── Helpers ────────────────────────────────────────────────────────

    public function isConverted(): bool
    {
        return $this->converted_contact_id !== null;
    }

    public function setEstimatedValue(?Money $money): self
    {
        if ($money === null) {
            $this->estimated_value_cents = null;
            $this->estimated_value_currency = config('app.currency', 'EUR');

            return $this;
        }

        $this->estimated_value_cents = $money->cents;
        $this->estimated_value_currency = $money->currency;

        return $this;
    }

    public function getEstimatedValueAttribute(): ?Money
    {
        if ($this->estimated_value_cents === null) {
            return null;
        }

        return new Money(
            (int) $this->estimated_value_cents,
            $this->estimated_value_currency ?: config('app.currency', 'EUR'),
        );
    }

    // ── Scopes ─────────────────────────────────────────────────────────

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [LeadStatus::Won->value, LeadStatus::Lost->value]);
    }

    public function scopeOfStatus(Builder $query, LeadStatus|string $status): Builder
    {
        return $query->where('status', $status instanceof LeadStatus ? $status->value : $status);
    }
}
