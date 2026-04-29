<?php

declare(strict_types=1);

namespace App\Domains\Documents\Database\Factories;

use App\Domains\Documents\Enums\DocumentCategory;
use App\Domains\Documents\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'title' => ['it' => fake()->sentence(3), 'en' => fake()->sentence(3)],
            'category' => DocumentCategory::Other->value,
            'file_path' => 'documents/'.fake()->uuid().'.pdf',
            'disk' => 's3',
            'file_size' => fake()->numberBetween(10_000, 5_000_000),
            'mime' => 'application/pdf',
            'related_type' => null,
            'related_id' => null,
            'issued_at' => fake()->dateTimeThisYear(),
            'metadata' => null,
            'owner_user_id' => null,
        ];
    }

    public function fattura(int $fatturaId = 1): self
    {
        return $this->state(fn () => [
            'category' => DocumentCategory::Fattura->value,
            'related_type' => 'fattura',
            'related_id' => $fatturaId,
        ]);
    }
}
