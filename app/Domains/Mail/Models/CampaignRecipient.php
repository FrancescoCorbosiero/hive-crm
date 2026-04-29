<?php

declare(strict_types=1);

namespace App\Domains\Mail\Models;

use App\Domains\Mail\Database\Factories\CampaignRecipientFactory;
use App\Domains\Mail\Enums\RecipientStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignRecipient extends Model
{
    use HasFactory;

    protected $table = 'campaign_recipients';

    protected $fillable = [
        'campaign_id',
        'contact_id',
        'status',
        'sent_at',
        'ses_message_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => RecipientStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    protected static function newFactory(): CampaignRecipientFactory
    {
        return CampaignRecipientFactory::new();
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', RecipientStatus::Pending->value);
    }
}
