<?php

declare(strict_types=1);

namespace App\Domains\Documents\Database\Factories;

use App\Domains\Documents\Enums\PaymentMethod;
use App\Domains\Documents\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'fattura_id' => 1,
            'paid_at' => now(),
            'amount_cents' => 100_000,
            'currency' => 'EUR',
            'method' => PaymentMethod::BankTransfer->value,
            'reference' => null,
            'notes' => null,
            'financial_entry_id' => null,
            'owner_user_id' => null,
        ];
    }
}
