<?php

declare(strict_types=1);

namespace App\Domains\Mail\Models;

use App\Domains\Mail\Database\Factories\CampaignFactory;
use App\Domains\Mail\Enums\CampaignStatus;
use App\Shared\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Campaign extends Model
{
    use BelongsToOwner;
    use HasFactory;
    use HasTranslations;

    protected $table = 'campaigns';

    /** @var array<int,string> */
    public array $translatable = ['subject', 'body_html'];

    protected $fillable = [
        'name',
        'subject',
        'body_html',
        'status',
        'scheduled_at',
        'sent_at',
        'recipients_count',
        'sent_count',
        'delivered_count',
        'bounced_count',
        'complained_count',
        'opened_count',
        'clicked_count',
        'unsubscribed_count',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => CampaignStatus::class,
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    protected static function newFactory(): CampaignFactory
    {
        return CampaignFactory::new();
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function scopeInFlight(Builder $query): Builder
    {
        return $query->whereIn('status', [
            CampaignStatus::Scheduled->value,
            CampaignStatus::Sending->value,
        ]);
    }
}
