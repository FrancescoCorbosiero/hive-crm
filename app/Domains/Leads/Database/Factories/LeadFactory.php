<?php

declare(strict_types=1);

namespace App\Domains\Leads\Database\Factories;

use App\Domains\Leads\Enums\LeadSource;
use App\Domains\Leads\Enums\LeadStatus;
use App\Domains\Leads\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        $faker = \Faker\Factory::create('it_IT');

        return [
            'name' => $faker->boolean(50) ? $faker->company() : $faker->name(),
            'company_name' => $faker->boolean(70) ? $faker->company() : null,
            'email' => $faker->safeEmail(),
            'phone' => $faker->phoneNumber(),
            'source' => $faker->randomElement(array_map(fn (LeadSource $s) => $s->value, LeadSource::cases())),
            'status' => LeadStatus::New->value,
            'estimated_value_cents' => $faker->numberBetween(50_000, 500_000),
            'estimated_value_currency' => 'EUR',
            'notes' => $faker->boolean(40) ? $faker->sentence() : null,
            'next_action_at' => $faker->dateTimeBetween('+1 day', '+3 weeks'),
            'last_contacted_at' => null,
            'lost_reason' => null,
            'converted_contact_id' => null,
            'converted_at' => null,
            'owner_user_id' => null,
        ];
    }

    public function open(): self
    {
        return $this->state(fn () => ['status' => LeadStatus::New->value]);
    }

    public function status(LeadStatus $status): self
    {
        return $this->state(fn () => ['status' => $status->value]);
    }

    public function converted(int $contactId): self
    {
        return $this->state(fn () => [
            'status' => LeadStatus::Won->value,
            'converted_contact_id' => $contactId,
            'converted_at' => now(),
        ]);
    }
}
