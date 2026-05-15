<?php

declare(strict_types=1);

namespace App\Domains\Repositories\Models;

use App\Domains\Repositories\Enums\RepositoryProvider;
use App\Shared\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;

class Repository extends Model
{
    use BelongsToOwner;

    protected $table = 'repositories';

    protected $fillable = [
        'name',
        'url',
        'provider',
        'owner_contact_id',
        'website_id',
        'notes',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'provider' => RepositoryProvider::class,
        ];
    }
}
