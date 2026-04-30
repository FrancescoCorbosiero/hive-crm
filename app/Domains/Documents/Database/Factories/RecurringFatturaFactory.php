<?php

declare(strict_types=1);

namespace App\Domains\Documents\Database\Factories;

use App\Domains\Documents\Enums\RecurringFrequency;
use App\Domains\Documents\Models\RecurringFattura;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringFattura>
 */
class RecurringFatturaFactory extends Factory
{
    protected $model = RecurringFattura::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Hosting mensile', 'Manutenzione trimestrale', 'Canone annuale']),
            'client_contact_id' => 1,
            'frequency' => RecurringFrequency::Monthly->value,
            'lines' => [
                ['description' => 'Servizio ricorrente', 'qty' => 1, 'unit_price_cents' => 8000, 'vat_rate' => 22],
            ],
            'currency' => 'EUR',
            'day_of_month' => 5,
            'next_issue_at' => now()->addMonth()->day(5),
            'is_active' => true,
            'last_issued_at' => null,
            'owner_user_id' => null,
        ];
    }

    public function dueToday(): self
    {
        return $this->state(fn () => ['next_issue_at' => now()->toDateString()]);
    }

    public function paused(): self
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
