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

    public function definition(): array
    {
        $year = (int) now()->year;

        $lines = [
            [
                'description' => 'Sviluppo sito web',
                'qty' => 1,
                'unit_price_cents' => 250_000,
                'vat_rate' => 22,
            ],
        ];

        $subtotal = 250_000;
        $vat = (int) round($subtotal * 22 / 100);
        $total = $subtotal + $vat;

        return [
            'year' => $year,
            'number' => fake()->unique()->numberBetween(1, 9999),
            'client_contact_id' => 1,
            'issued_at' => now(),
            'lines' => $lines,
            'subtotal_cents' => $subtotal,
            'vat_cents' => $vat,
            'total_cents' => $total,
            'currency' => 'EUR',
            'payment_status' => PaymentStatus::Unpaid->value,
            'document_id' => null,
            'owner_user_id' => null,
        ];
    }
}
