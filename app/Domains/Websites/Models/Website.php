<?php

declare(strict_types=1);

namespace App\Domains\Websites\Models;

use App\Domains\Websites\Database\Factories\WebsiteFactory;
use App\Domains\Websites\Enums\WebsiteStatus;
use App\Shared\Concerns\BelongsToOwner;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Website extends Model
{
    use BelongsToOwner;
    use HasFactory;
    use HasTranslations;

    protected $table = 'websites';

    /** @var array<int,string> */
    public array $translatable = ['name', 'notes'];

    protected $fillable = [
        'name',
        'notes',
        'trello_board_url',
        'url',
        'status',
        'owner_contact_id',
        'tech_stack',
        'subscription_started_at',
        'next_renewal_at',
        'renewal_period_months',
        'is_up',
        'last_status_code',
        'last_pinged_at',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'tech_stack' => AsArrayObject::class,
            'status' => WebsiteStatus::class,
            'subscription_started_at' => 'date',
            'next_renewal_at' => 'date',
            'renewal_period_months' => 'integer',
            'is_up' => 'boolean',
            'last_status_code' => 'integer',
            'last_pinged_at' => 'datetime',
        ];
    }

    protected static function newFactory(): WebsiteFactory
    {
        return WebsiteFactory::new();
    }

    public function daysUntilRenewal(?Carbon $now = null): ?int
    {
        if (! $this->next_renewal_at) {
            return null;
        }

        $now = ($now ?? now())->copy()->startOfDay();

        return (int) round($now->diffInDays($this->next_renewal_at->copy()->startOfDay(), false));
    }

    public function scopeRenewingWithin($query, int $days, ?Carbon $now = null)
    {
        $start = ($now ?? now())->copy()->startOfDay();
        $end = $start->copy()->addDays($days);

        return $query->whereBetween('next_renewal_at', [$start, $end]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', WebsiteStatus::Active->value);
    }

    // ── Cross-domain read-only relation ────────────────────────────────
    // Domains pointing at this website. Kept as a plain hasMany (not a
    // hard belongsTo dependency) — reading is fine, writing stays with
    // the DomainNames domain. Mirrors the Contact model's pattern.

    public function domainNames(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Domains\DomainNames\Models\DomainName::class, 'website_id');
    }
}
