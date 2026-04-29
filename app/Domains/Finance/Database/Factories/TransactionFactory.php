<?php

declare(strict_types=1);

namespace App\Domains\Finance\Database\Factories;

use App\Domains\Finance\Enums\TransactionType;
use App\Domains\Finance\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $faker = \Faker\Factory::create('it_IT');

        $isIncome = $faker->boolean(60);

        return [
            'type' => $isIncome ? TransactionType::Income->value : TransactionType::Expense->value,
            'amount_cents' => $isIncome
                ? $faker->numberBetween(2_000, 250_000)
                : $faker->numberBetween(500, 80_000),
            'currency' => 'EUR',
            'occurred_at' => $faker->dateTimeBetween('-1 year', 'now'),
            'description' => $isIncome ? 'Fattura cliente' : 'Spesa generica',
            'category' => $isIncome ? 'consulting' : 'tools',
            'source_type' => null,
            'source_id' => null,
            'contact_id' => null,
            'notes' => null,
            'owner_user_id' => null,
        ];
    }

    public function income(int $cents = 10_000): self
    {
        return $this->state(fn () => [
            'type' => TransactionType::Income->value,
            'amount_cents' => $cents,
        ]);
    }

    public function expense(int $cents = 1_000): self
    {
        return $this->state(fn () => [
            'type' => TransactionType::Expense->value,
            'amount_cents' => $cents,
        ]);
    }

    public function forWebsite(int $websiteId): self
    {
        return $this->state(fn () => [
            'source_type' => 'website',
            'source_id' => $websiteId,
        ]);
    }

    public function on(string|\DateTimeInterface $date): self
    {
        return $this->state(fn () => ['occurred_at' => $date]);
    }
}
