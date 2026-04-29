<?php

declare(strict_types=1);

namespace App\Domains\Documents\DTOs;

use App\Domains\Documents\Models\Document;
use Carbon\Carbon;

final readonly class DocumentDTO
{
    public function __construct(
        public int $id,
        public array $title,
        public string $category,
        public string $filePath,
        public string $disk,
        public int $fileSize,
        public ?string $mime,
        public ?string $relatedType,
        public ?int $relatedId,
        public ?Carbon $issuedAt,
    ) {}

    public static function fromModel(Document $d): self
    {
        return new self(
            id: $d->id,
            title: $d->getTranslations('title'),
            category: $d->category->value,
            filePath: $d->file_path,
            disk: $d->disk,
            fileSize: $d->file_size,
            mime: $d->mime,
            relatedType: $d->related_type,
            relatedId: $d->related_id,
            issuedAt: $d->issued_at,
        );
    }
}
