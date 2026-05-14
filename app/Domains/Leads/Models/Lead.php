<?php

declare(strict_types=1);

namespace App\Domains\Leads\Models;

use App\Domains\Leads\Database\Factories\LeadFactory;
use App\Domains\Leads\Enums\BudgetTier;
use App\Domains\Leads\Enums\BusinessCategory;
use App\Domains\Leads\Enums\LeadSource;
use App\Domains\Leads\Enums\LeadStatus;
use App\Domains\Leads\Enums\LostReason;
use App\Domains\Leads\Enums\WebsiteType;
use App\Shared\Casts\MoneyCast;
use App\Shared\Concerns\BelongsToOwner;
use App\Shared\ValueObjects\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Lead extends Model
{
    use BelongsToOwner;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'company_name', 'email', 'status', 'estimated_value_cents', 'next_action_at', 'last_contacted_at', 'lost_reason', 'converted_contact_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('lead');
    }

    /** Free email providers we should NOT use to derive a company name. */
    private const FREE_EMAIL_DOMAINS = [
        'gmail.com', 'googlemail.com', 'yahoo.com', 'yahoo.it', 'hotmail.com',
        'hotmail.it', 'outlook.com', 'outlook.it', 'live.com', 'live.it',
        'icloud.com', 'me.com', 'aol.com', 'proton.me', 'protonmail.com',
        'libero.it', 'tin.it', 'virgilio.it', 'alice.it', 'tiscali.it',
    ];

    protected static function booted(): void
    {
        static::saving(function (Lead $lead): void {
            $lead->backfillCompanyNameFromEmail();
            $lead->stampLastContactedOnStatusAdvance();
        });
    }

    protected $table = 'leads';

    protected $fillable = [
        'name',
        'company_name',
        'social_url',
        'is_redesign',
        'budget_tier',
        'business_category',
        'website_type',
        'is_estero',
        'email',
        'phone',
        'source',
        'status',
        'estimated_value_cents',
        'estimated_value_currency',
        'notes',
        'next_action_at',
        'last_contacted_at',
        'lost_reason',
        'converted_contact_id',
        'converted_at',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'source' => LeadSource::class,
            'lost_reason' => LostReason::class,
            'budget_tier' => BudgetTier::class,
            'business_category' => BusinessCategory::class,
            'website_type' => WebsiteType::class,
            'is_redesign' => 'boolean',
            'is_estero' => 'boolean',
            'next_action_at' => 'datetime',
            'last_contacted_at' => 'datetime',
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

    /**
     * Open leads with no contact (or creation, for never-contacted) within $days.
     */
    public function scopeStale(Builder $query, int $days = 14): Builder
    {
        $cutoff = now()->subDays($days);

        return $query->open()->where(function (Builder $q) use ($cutoff) {
            $q->where('last_contacted_at', '<', $cutoff)
                ->orWhere(function (Builder $q2) use ($cutoff) {
                    $q2->whereNull('last_contacted_at')->where('created_at', '<', $cutoff);
                });
        });
    }

    // ── Lifecycle hooks ────────────────────────────────────────────────

    private function backfillCompanyNameFromEmail(): void
    {
        if (! empty($this->company_name) || empty($this->email)) {
            return;
        }

        $domain = strtolower((string) substr(strrchr($this->email, '@') ?: '', 1));

        if ($domain === '' || in_array($domain, self::FREE_EMAIL_DOMAINS, true)) {
            return;
        }

        // example.co.uk → "Example", studio-bianchi.it → "Studio Bianchi"
        $root = explode('.', $domain)[0];
        $this->company_name = str(str_replace(['-', '_'], ' ', $root))->title()->toString();
    }

    private function stampLastContactedOnStatusAdvance(): void
    {
        if (! $this->isDirty('status')) {
            return;
        }

        $new = $this->status instanceof LeadStatus ? $this->status : LeadStatus::tryFrom((string) $this->status);

        if ($new === null || $new === LeadStatus::New) {
            return;
        }

        if ($this->last_contacted_at === null && ! $this->isDirty('last_contacted_at')) {
            $this->last_contacted_at = now();
        }
    }
}
