<?php

declare(strict_types=1);

namespace App\Domains\Websites\Database\Factories;

use App\Domains\Websites\Enums\WebsiteStatus;
use App\Domains\Websites\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Website>
 */
class WebsiteFactory extends Factory
{
    protected $model = Website::class;

    public function definition(): array
    {
        $faker = \Faker\Factory::create('it_IT');

        $started = $faker->dateTimeBetween('-3 years', '-3 months');
        $months = $faker->randomElement([12, 24]);
        $renewal = (clone $started)->modify('+'.$months.' months');

        // Roll forward to a future renewal date.
        while ($renewal < new \DateTimeImmutable('+30 days')) {
            $renewal = $renewal->modify('+'.$months.' months');
        }

        $name = $faker->company();

        return [
            'name' => ['it' => $name, 'en' => $name],
            'notes' => null,
            'url' => 'https://'.$faker->domainName(),
            'status' => WebsiteStatus::Active->value,
            'owner_contact_id' => null,
            'tech_stack' => $faker->randomElements(
                ['Laravel', 'WordPress', 'Astro', 'Next.js', 'Tailwind', 'Filament', 'Livewire', 'Stripe'],
                $faker->numberBetween(1, 4),
            ),
            'subscription_started_at' => $started,
            'next_renewal_at' => $renewal,
            'renewal_period_months' => $months,
            'owner_user_id' => null,
        ];
    }

    public function active(): self
    {
        return $this->state(['status' => WebsiteStatus::Active->value]);
    }

    public function archived(): self
    {
        return $this->state(['status' => WebsiteStatus::Archived->value]);
    }

    public function renewingIn(int $days): self
    {
        return $this->state(fn () => [
            'next_renewal_at' => now()->addDays($days)->startOfDay(),
        ]);
    }
}
