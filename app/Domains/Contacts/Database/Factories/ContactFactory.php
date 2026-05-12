<?php

declare(strict_types=1);

namespace App\Domains\Contacts\Database\Factories;

use App\Domains\Contacts\Enums\ContactRole;
use App\Domains\Contacts\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        $faker = \Faker\Factory::create('it_IT');

        $isCompany = $faker->boolean(40);
        $companyName = $isCompany ? $faker->company() : null;

        return [
            'name' => $companyName ?? $faker->name(),
            'ragione_sociale' => $companyName,
            'email' => $faker->unique()->safeEmail(),
            'phone' => $faker->phoneNumber(),
            'vat_number' => $isCompany ? 'IT'.$faker->numerify('###########') : null,
            'tax_code' => $isCompany ? null : strtoupper($faker->bothify('??????##?##?###?')),
            'sdi_code' => $isCompany ? strtoupper($faker->bothify('???####')) : null,
            'pec_email' => $isCompany ? $faker->unique()->safeEmail() : null,
            'address' => [
                'street' => $faker->streetAddress(),
                'city' => $faker->city(),
                'province' => strtoupper($faker->lexify('??')),
                'postal_code' => $faker->postcode(),
                'country' => 'IT',
            ],
            'notes' => $faker->boolean(30) ? $faker->sentence() : null,
            'roles' => $faker->randomElements(
                array_map(fn (ContactRole $r) => $r->value, ContactRole::cases()),
                $faker->numberBetween(1, 2),
            ),
            'do_not_email' => false,
            'owner_user_id' => null,
        ];
    }

    public function customer(): self
    {
        return $this->state(fn () => ['roles' => [ContactRole::Customer->value]]);
    }

    public function vendor(): self
    {
        return $this->state(fn () => ['roles' => [ContactRole::Vendor->value]]);
    }

    public function doNotEmail(): self
    {
        return $this->state(fn () => ['do_not_email' => true]);
    }
}
