<?php

declare(strict_types=1);

namespace App\Domains\Documents\Models;

use App\Domains\Documents\Database\Factories\DocumentFactory;
use App\Domains\Documents\Enums\DocumentCategory;
use App\Shared\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Document extends Model
{
    use BelongsToOwner;
    use HasFactory;
    use HasTranslations;

    protected $table = 'documents';

    /** @var array<int,string> */
    public array $translatable = ['title'];

    protected $fillable = [
        'title',
        'category',
        'file_path',
        'disk',
        'file_size',
        'mime',
        'related_type',
        'related_id',
        'issued_at',
        'metadata',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'metadata' => AsArrayObject::class,
            'issued_at' => 'date',
            'file_size' => 'integer',
        ];
    }

    protected static function newFactory(): DocumentFactory
    {
        return DocumentFactory::new();
    }
}
