<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Models;

use App\Domains\Calendar\Database\Factories\CalendarEventFactory;
use App\Domains\Calendar\Enums\CalendarEventStatus;
use App\Shared\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    use BelongsToOwner;
    use HasFactory;

    protected $table = 'calendar_events';

    protected $fillable = [
        'cal_event_id',
        'title',
        'starts_at',
        'ends_at',
        'attendee_email',
        'status',
        'payload',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => CalendarEventStatus::class,
            'payload' => AsArrayObject::class,
        ];
    }

    protected static function newFactory(): CalendarEventFactory
    {
        return CalendarEventFactory::new();
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereBetween('starts_at', [
            now()->startOfDay(),
            now()->endOfDay(),
        ]);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            CalendarEventStatus::Accepted->value,
            CalendarEventStatus::Pending->value,
        ]);
    }
}
