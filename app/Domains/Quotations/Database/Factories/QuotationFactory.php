<?php

declare(strict_types=1);

namespace App\Domains\Quotations\Database\Factories;

use App\Domains\Quotations\Enums\QuotationStatus;
use App\Domains\Quotations\Models\Quotation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quotation>
 *
 * Bypasses sequential numbering for cheap test fixtures. Real quotations
 * come through QuotationsService::create which allocates atomically.
 */
class QuotationFactory extends Factory
{
    protected $model = Quotation::class;

    /**
     * Fill the derived totals at make time too: model events (and so the
     * DerivesTotalsFromLines hook) don't fire under a bare Event::fake().
     */
    public function configure(): static
    {
        return $this->afterMaking(fn (Quotation $quotation) => $quotation->fillTotalsFromLines());
    }

    public function definition(): array
    {
        $year = (int) now()->year;

        return [
            'year' => $year,
            'number' => fake()->unique()->numberBetween(1, 9999),
            'name' => fake()->randomElement(['Restyling sito', 'Nuovo e-commerce', 'Consulenza SEO']),
            'client_contact_id' => 1,
            'lead_id' => null,
            'issued_at' => now(),
            'valid_until' => now()->addDays(30),
            // Totals are derived from the lines (DerivesTotalsFromLines).
            'lines' => [
                ['description' => 'Servizio test', 'qty' => 1, 'unit_price_cents' => 100_000, 'vat_rate' => 22],
            ],
            'currency' => 'EUR',
            'status' => QuotationStatus::Draft->value,
            'document_id' => null,
            'fattura_id' => null,
            'notes' => null,
            'owner_user_id' => null,
        ];
    }
}
