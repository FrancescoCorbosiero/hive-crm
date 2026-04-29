<?php

declare(strict_types=1);

namespace App\Domains\Documents\Services\Public;

use App\Domains\Documents\DTOs\DocumentDTO;
use App\Domains\Documents\Enums\DocumentCategory;
use App\Domains\Documents\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentsService
{
    public function find(int $id): ?DocumentDTO
    {
        $doc = Document::query()->find($id);

        return $doc ? DocumentDTO::fromModel($doc) : null;
    }

    /**
     * Persist an uploaded file to the configured disk and create the
     * matching Document row. Disk defaults to the application default
     * (Contabo's `s3` in production, `local` in dev).
     *
     * @param  array{
     *     title: string|array<string,string>,
     *     category?: string,
     *     related_type?: ?string,
     *     related_id?: ?int,
     *     issued_at?: \DateTimeInterface|string|null,
     *     metadata?: ?array<string,mixed>,
     *     owner_user_id?: ?int,
     *  }  $attributes
     */
    public function store(UploadedFile $file, array $attributes, ?string $disk = null): DocumentDTO
    {
        $disk ??= config('filesystems.default');
        $path = 'documents/'.now()->format('Y/m').'/'.Str::uuid().'.'.$file->getClientOriginalExtension();

        Storage::disk($disk)->put($path, $file->get(), [
            'visibility' => 'private',
            'ContentType' => $file->getMimeType(),
        ]);

        return $this->register(
            path: $path,
            disk: $disk,
            size: $file->getSize() ?: 0,
            mime: $file->getMimeType(),
            attributes: $attributes,
        );
    }

    /**
     * Register a Document row for an already-uploaded file. Used by
     * FatturaService after PDF rendering.
     */
    public function register(
        string $path,
        string $disk,
        int $size,
        ?string $mime,
        array $attributes,
    ): DocumentDTO {
        $defaultLocale = config('app.locale', 'it');
        $title = $attributes['title'] ?? '';
        if (is_string($title)) {
            $title = [$defaultLocale => $title];
        }

        $doc = Document::query()->create([
            'title' => $title,
            'category' => $attributes['category'] ?? DocumentCategory::Other->value,
            'file_path' => $path,
            'disk' => $disk,
            'file_size' => $size,
            'mime' => $mime,
            'related_type' => $attributes['related_type'] ?? null,
            'related_id' => $attributes['related_id'] ?? null,
            'issued_at' => $attributes['issued_at'] ?? null,
            'metadata' => $attributes['metadata'] ?? null,
            'owner_user_id' => $attributes['owner_user_id'] ?? null,
        ]);

        return DocumentDTO::fromModel($doc);
    }

    public function temporaryUrl(int $documentId, int $minutes = 15): ?string
    {
        $doc = Document::query()->find($documentId);
        if (! $doc) {
            return null;
        }

        $disk = Storage::disk($doc->disk);

        // S3-style disks support temporaryUrl, the local driver doesn't —
        // fall back to a regular URL for dev convenience.
        if (method_exists($disk, 'temporaryUrl')) {
            try {
                return $disk->temporaryUrl($doc->file_path, now()->addMinutes($minutes));
            } catch (\Throwable) {
                // fall through
            }
        }

        return $disk->url($doc->file_path);
    }
}
