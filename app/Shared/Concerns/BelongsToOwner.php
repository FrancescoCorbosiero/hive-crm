<?php

declare(strict_types=1);

namespace App\Shared\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Provide a uniform `owner_user_id` relationship + auto-assignment hook
 * for every domain entity. v1 is single-user but this keeps multi-tenancy
 * cheap to add later — every row already has an owner column.
 */
trait BelongsToOwner
{
    public static function bootBelongsToOwner(): void
    {
        static::creating(function ($model) {
            if (! isset($model->owner_user_id)) {
                $model->owner_user_id = auth()->id() ?? User::query()->value('id');
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
