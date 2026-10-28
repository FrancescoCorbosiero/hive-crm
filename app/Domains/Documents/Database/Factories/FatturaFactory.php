<?php

declare(strict_types=1);

namespace App\Domains\Documents\Database\Factories;

use App\Domains\Documents\Enums\PaymentStatus;
use App\Domains\Documents\Models\Fattura;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fattura>
 *
 * Note: this factory bypasses sequential numbering on purpose so tests
 * can build canonical fixtures cheaply. Real fatture come through
 * FatturaService::create which allocates the next number atomically.
 */
class FatturaFactory extends Factory
{
    protected $model = Fattura::class;

    /**
     * Fill the derived totals at make time too: model events (and so the
     * DerivesTotalsFromLines hook) don't fire under a bare Event::fake().
     */
    public function configure(): static
    {
        return $this->afterMaking(fn (Fattura $fattura) => $fattura->fillTotalsFromLines());
    }

    public function definition(): array
    {
        return [
            'year' => (int) now()->year,
            'number' => fake()->unique()->numberBetween(1, 9999),
            'client_contact_id' => 1,
            'issued_at' => now(),
            // Totals are derived from the lines (DerivesTotalsFromLines):
            // 2_500.00 + 22% VAT = 3_050.00.
            'lines' => [
                [
                    'description' => 'Sviluppo sito web',
                    'qty' => 1,
                    'unit_price_cents' => 250_000,
                    'vat_rate' => 22,
                ],
            ],
            'currency' => 'EUR',
            'payment_status' => PaymentStatus::Unpaid->value,
            'document_id' => null,
            'owner_user_id' => null,
        ];
    }

    /**
     * A single VAT-free line, so the fattura totals exactly $cents —
     * for tests that care about payment arithmetic, not tax.
     */
    public function totalling(int $cents): self
    {
        return $this->state(fn () => [
            'lines' => [[
                'description' => 'Servizio',
                'qty' => 1,
                'unit_price_cents' => $cents,
                'vat_rate' => 0,
            ]],
        ]);
    }
}
