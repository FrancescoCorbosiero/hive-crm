<?php

declare(strict_types=1);

namespace App\Domains\DomainNames\Models;

use App\Domains\DomainNames\Enums\DomainStatus;
use App\Domains\DomainNames\Enums\Registrar;
use App\Shared\Concerns\BelongsToOwner;
use App\Shared\ValueObjects\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DomainName extends Model
{
    use BelongsToOwner;

    protected $table = 'domain_names';

    protected $fillable = [
        'name',
        'registrar',
        'status',
        'registered_at',
        'expires_at',
        'renewal_period_months',
        'auto_renew',
        'renewal_cost_cents',
        'currency',
        'owner_contact_id',
        'website_id',
        'notes',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'registrar' => Registrar::class,
            'status' => DomainStatus::class,
            'registered_at' => 'date',
            'expires_at' => 'date',
            'renewal_period_months' => 'integer',
            'auto_renew' => 'boolean',
            'renewal_cost_cents' => 'integer',
        ];
    }

    public function renewalCost(): ?Money
    {
        if ($this->renewal_cost_cents === null) {
            return null;
        }

        return new Money((int) $this->renewal_cost_cents, $this->currency ?: config('app.currency', 'EUR'));
    }

    public function daysUntilExpiry(?Carbon $now = null): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        $now = ($now ?? now())->copy()->startOfDay();

        return (int) round($now->diffInDays($this->expires_at->copy()->startOfDay(), false));
    }

    /**
     * Registrable host with scheme / "www." / path stripped — the form
     * the name should already be in, but normalised defensively so the
     * Website auto-link match is reliable.
     */
    public function host(): string
    {
        $value = trim(mb_strtolower((string) $this->name));
        $host = str_contains($value, '://')
            ? (string) parse_url($value, PHP_URL_HOST)
            : (string) (parse_url('//'.$value, PHP_URL_HOST) ?: $value);

        return preg_replace('/^www\./', '', $host) ?? $host;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', DomainStatus::Active->value);
    }

    public function scopeExpiringWithin(Builder $query, int $days, ?Carbon $now = null): Builder
    {
        $start = ($now ?? now())->copy()->startOfDay();
        $end = $start->copy()->addDays($days);

        return $query->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$start, $end]);
    }

    /**
     * Domains that need the operator's eyes: expiring within $days OR
     * already past expiry. `expiringWithin` deliberately excludes the
     * past — this scope deliberately includes it, because an already-
     * expired domain is the single most urgent row in the portfolio
     * and must never fall out of the highlight.
     */
    public function scopeNeedsAttention(Builder $query, int $days, ?Carbon $now = null): Builder
    {
        $cutoff = ($now ?? now())->copy()->startOfDay()->addDays($days);

        return $query->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', $cutoff);
    }
}
